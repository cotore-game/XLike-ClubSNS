<?php

require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['common_password_passed']) || $_SESSION['common_password_passed'] !== true) {
    header('Location: index.php');
    exit();
}

$errors = [];
$username = '';
$display_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $display_name = trim($_POST['display_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 入力値のバリデーション
    if (empty($username)) { $errors[] = 'ユーザー名を入力してください。'; }
    // usernameは一意である必要があるので、ここでチェック
    if (empty($display_name)) { $errors[] = 'ディスプレイ名を入力してください。'; }
    if (empty($password)) { $errors[] = 'パスワードを入力してください。'; }
    elseif (strlen($password) < 6) { $errors[] = 'パスワードは6文字以上で入力してください。'; }
    if ($password !== $confirm_password) { $errors[] = 'パスワードと確認用パスワードが一致しません。'; }

    if (empty($errors)) {
        try {
            // ユーザー名の重複チェック
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'このユーザー名は既に使われています。';
            }
        } catch (PDOException $e) {
            error_log("Username duplication check error: " . $e->getMessage());
            $errors[] = '登録処理中にエラーが発生しました。';
        }
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // ユーザー情報をデータベースに挿入
            $stmt = $pdo->prepare("INSERT INTO users (username, display_name, password_hash) VALUES (:username, :display_name, :password_hash)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':display_name', $display_name); // バインドパラメータ追加
            $stmt->bindParam(':password_hash', $password_hash);

            if ($stmt->execute()) {
                header('Location: signin.php?registered=true');
                exit();
            } else {
                $errors[] = 'ユーザー登録に失敗しました。';
            }
        } catch (PDOException $e) {
            error_log("User registration error: " . $e->getMessage());
            $errors[] = '登録処理中にエラーが発生しました。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規ユーザー登録</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>新規ユーザー登録</h1>
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="signup.php" method="post">
            <div class="form-group">
                <label for="username">ユーザー名:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label for="display_name">ディスプレイ名:</label> <input type="text" id="display_name" name="display_name" value="<?php echo htmlspecialchars($display_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">パスワード（確認用）:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">登録</button>
        </form>
        <p>既にアカウントをお持ちですか？ <a href="signin.php">ログインはこちら</a></p>
    </div>
</body>
</html>
