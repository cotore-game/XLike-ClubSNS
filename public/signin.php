<?php

require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['common_password_passed']) || $_SESSION['common_password_passed'] !== true) {
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['user_id'])) {
    header('Location: timeline.php');
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success_message = 'ユーザー登録が完了しました。ログインしてください。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = 'ユーザー名とパスワードを入力してください。';
    } else {
        try {
            // ユーザー名でユーザー情報を取得
            $stmt = $pdo->prepare("SELECT user_id, username, display_name, password_hash FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // ログイン成功
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['display_name'] = $user['display_name']; // display_nameもセッションに保存すると便利
                header('Location: timeline.php');
                exit();
            } else {
                $error_message = 'ユーザー名またはパスワードが間違っています。';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = 'ログイン処理中にエラーが発生しました。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>ログイン</h1>
        <?php if ($success_message): ?>
            <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form action="signin.php" method="post">
            <div class="form-group">
                <label for="username">ユーザー名:</label> <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">ログイン</button>
        </form>
        <p>アカウントをお持ちでないですか？ <a href="signup.php">新規登録はこちら</a></p>
    </div>
</body>
</html>
