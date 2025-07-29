<?php
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/config.php';

// セッション変数を全て削除
$_SESSION = array();

// クッキーに保存されているセッションIDを削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// セッションを破壊
session_destroy();

// 共通パスワードのセッションもクリア
unset($_SESSION['common_password_passed']);

// ログインページまたは共通パスワードページへリダイレクト
header('Location: index');
exit();
