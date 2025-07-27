<?php

define('ERROR_LOG_FILE', __DIR__ . '/app_error.log');

/**
 * カスタムエラーハンドラ関数
 * PHPエラーが発生した際に呼び出されます
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // 報告されないエラータイプ (error_reporting() の設定による) なら何もしない
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $log_message = '';
    $date_time = date('Y-m-d H:i:s');

    switch ($errno) {
        case E_USER_ERROR: // trigger_error() でE_USER_ERRORを指定した場合
            $log_message = "[$date_time] FATAL ERROR: [$errno] $errstr in $errfile on line $errline\n";
            break;
        case E_USER_WARNING: // trigger_error() でE_USER_WARNINGを指定した場合
            $log_message = "[$date_time] WARNING: [$errno] $errstr in $errfile on line $errline\n";
            break;
        case E_USER_NOTICE: // trigger_error() でE_USER_NOTICEを指定した場合
            $log_message = "[$date_time] NOTICE: [$errno] $errstr in $errfile on line $errline\n";
            break;
        case E_WARNING: // PHP標準の警告
            $log_message = "[$date_time] WARNING: [$errno] $errstr in $errfile on line $errline\n";
            break;
        case E_NOTICE: // PHP標準の通知
            $log_message = "[$date_time] NOTICE: [$errno] $errstr in $errfile on line $errline\n";
            break;
        case E_DEPRECATED: // 非推奨の関数使用
            $log_message = "[$date_time] DEPRECATED: [$errno] $errstr in $errfile on line $errline\n";
            break;
        default: // その他のエラー
            $log_message = "[$date_time] UNKNOWN ERROR: [$errno] $errstr in $errfile on line $errline\n";
            break;
    }

    // ファイルにログを追記
    error_log($log_message, 3, ERROR_LOG_FILE);

    // PHPの通常のエラーハンドラに制御を渡さない
    return true;
}

/**
 * 未処理の例外をキャッチするハンドラ関数
 */
function customExceptionHandler($exception) {
    $date_time = date('Y-m-d H:i:s');
    $log_message = "[$date_time] UNCAUGHT EXCEPTION: " . $exception->getMessage() . 
                   " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n" .
                   "Stack trace:\n" . $exception->getTraceAsString() . "\n";
    
    // ファイルにログを追記
    error_log($log_message, 3, ERROR_LOG_FILE);

    // ユーザーには一般的なエラーメッセージを表示
    http_response_code(500);
    die("An unexpected error occurred. Please try again later or contact the administrator.");
}

// PHPのエラーレポートレベルを設定
// E_ALL: 全てのエラー、警告、通知を報告 (開発時)
// E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED: 致命的なエラーのみ (本番時、ログには全て出力)
error_reporting(E_ALL);

// 画面にエラーを表示するかどうか
// 開発時は1、本番では0 (ログにのみ出力)
ini_set('display_errors', 0); // ここを0に設定し、エラーはログにのみ出力

// エラーログをファイルに出力する設定（error_log関数のデフォルト）
ini_set('log_errors', 1);

// PHPの組み込みのエラーハンドラをカスタムハンドラで上書き
set_error_handler("customErrorHandler");

// PHPの組み込みの例外ハンドラをカスタムハンドラで上書き
set_exception_handler("customExceptionHandler");

// PHPの終了時に呼び出される関数
register_shutdown_function(function() {
    $last_error = error_get_last();
    // 致命的なエラー（パースエラー、メモリーオーバーフローなど）をキャッチ
    if ($last_error && in_array($last_error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $date_time = date('Y-m-d H:i:s');
        $log_message = "[$date_time] FATAL ERROR (Shutdown): " . $last_error['message'] . 
                       " in " . $last_error['file'] . " on line " . $last_error['line'] . "\n";
        error_log($log_message, 3, ERROR_LOG_FILE);

        // ユーザーには一般的なメッセージを表示
        if (!headers_sent()) {
            http_response_code(500);
            die("A critical error occurred. Please contact the administrator.");
        }
    }
});
