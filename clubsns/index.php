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
