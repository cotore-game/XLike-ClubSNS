<?php
$page_title = $page_title ?? 'MySNS'; // ページタイトル
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <?php if (isset($page_css)): // 各ページ固有のCSSを読み込む ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($page_css); ?>">
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="/">MySNS</a></h1>
            <nav>
                <ul>
                    <li><a href="/timeline.php" <?php if (strpos($_SERVER['REQUEST_URI'], '/timeline.php') !== false) echo 'class="active"'; ?>>タイムライン</a></li>
                    <li><a href="/profile.php" <?php if (strpos($_SERVER['REQUEST_URI'], '/profile.php') !== false && !isset($_GET['user_id'])) echo 'class="active"'; ?>>プロフィール</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/logout.php">ログアウト</a></li>
                    <?php else: ?>
                    <li><a href="/signin.php" <?php if (strpos($_SERVER['REQUEST_URI'], '/signin.php') !== false) echo 'class="active"'; ?>>ログイン</a></li>
                    <li><a href="/signup.php" <?php if (strpos($_SERVER['REQUEST_URI'], '/signup.php') !== false) echo 'class="active"'; ?>>新規登録</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
