<?php

// ログファイルのパスを定義
define('ERROR_LOG_FILE', __DIR__ . '/app_error.log');

// PHPのエラーレポートレベルを設定
error_reporting(E_ALL);

// 画面にエラーを表示しない
ini_set('display_errors', 0);

// エラーログをファイルに出力する設定
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_FILE);

/**
 * カスタムエラーハンドラ関数
 * PHPエラーが発生した際に呼び出されます
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // 報告対象外のエラーなら何もしない
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $date_time = date('Y-m-d H:i:s');
    $log_type = 'UNKNOWN';

    switch ($errno) {
        case E_ERROR: case E_PARSE: case E_CORE_ERROR: case E_COMPILE_ERROR:
            $log_type = 'FATAL ERROR'; break;
        case E_WARNING: case E_USER_WARNING:
            $log_type = 'WARNING'; break;
        case E_NOTICE: case E_USER_NOTICE:
            $log_type = 'NOTICE'; break;
        case E_DEPRECATED: case E_USER_DEPRECATED:
            $log_type = 'DEPRECATED'; break;
        default:
            $log_type = 'ERROR'; break;
    }

    $log_message = sprintf("[%s] %s: [%d] %s in %s on line %d\n",
                           $date_time, $log_type, $errno, $errstr, $errfile, $errline);

    error_log($log_message);

    // PHPの通常のエラーハンドラに制御を渡さない
    return true;
}

/**
 * 未処理の例外をキャッチするハンドラ関数
 */
function customExceptionHandler($exception) {
    $date_time = date('Y-m-d H:i:s');
    $log_message = sprintf("[%s] UNCAUGHT EXCEPTION: %s in %s on line %d\nStack trace:\n%s\n",
                           $date_time, $exception->getMessage(), $exception->getFile(), 
                           $exception->getLine(), $exception->getTraceAsString());
    
    error_log($log_message);

    // ユーザーには一般的なエラーメッセージを表示
    http_response_code(500);
    die("An unexpected error occurred. Please try again later or contact the administrator.");
}

// PHPの組み込みのエラーハンドラと例外ハンドラをカスタムハンドラで上書き
set_error_handler("customErrorHandler");
set_exception_handler("customExceptionHandler");

// PHPの終了時に呼び出される関数 (致命的なエラーをキャッチするため)
register_shutdown_function(function() {
    $last_error = error_get_last();
    // 致命的なエラー（E_ERROR, E_PARSEなど）をキャッチ
    if ($last_error && in_array($last_error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $date_time = date('Y-m-d H:i:s');
        $log_message = sprintf("[%s] FATAL ERROR (Shutdown): %s in %s on line %d\n",
                               $date_time, $last_error['message'], $last_error['file'], $last_error['line']);
        error_log($log_message);

        // ユーザーには一般的なメッセージを表示
        if (!headers_sent()) {
            http_response_code(500);
            die("A critical error occurred. Please contact the administrator.");
        }
    }
});
