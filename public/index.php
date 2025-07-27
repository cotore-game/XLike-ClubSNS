<?php
require_once __DIR__ . '/../includes/config.php';

$common_pass_error = '';

// 共通パスワード認証済みかチェック
if (isset($_SESSION['common_password_passed']) && $_SESSION['common_password_passed'] === true) {
    if (isset($_SESSION['user_id'])) {
        // ログイン済みならタイムラインへ
        header('Location: timeline.php');
    } else {
        // 未ログインならログインページへ
        header('Location: signin.php');
    }
    exit();
}

// フォームからパスワードが送信された場合
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['common_password'])) {
    $input_common_password = $_POST['common_password'];

    try {
        // DBから共通パスワードのハッシュ値を取得
        $stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'common_password_hash'");
        $stmt->execute();
        $setting = $stmt->fetch();

        // パスワードを検証
        if ($setting && password_verify($input_common_password, $setting['setting_value'])) {
            $_SESSION['common_password_passed'] = true; // セッションに認証情報を保存
            header('Location: signin.php'); // ログイン/サインアップページへリダイレクト
            exit();
        } else {
            $common_pass_error = '共通パスワードが間違っています。';
        }
    } catch (PDOException $e) {
        error_log("Common password authentication error: " . $e->getMessage());
        $common_pass_error = '認証処理中にエラーが発生しました。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>共通パスワード認証</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>共通パスワード認証</h1>
        <form action="index.php" method="post">
            <div class="form-group">
                <label for="common_password">共通パスワード:</label>
                <input type="password" id="common_password" name="common_password" required>
            </div>
            <?php if ($common_pass_error): ?>
                <p class="error-message"><?php echo htmlspecialchars($common_pass_error); ?></p>
            <?php endif; ?>
            <button type="submit">認証</button>
        </form>
    </div>
</body>
</html>
