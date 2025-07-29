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
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if (isset($page_css)):?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($page_css); ?>">
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="/" class="header-logo">MySNS</a></h1>
            <nav>
                <ul class="header-list">
                    <li><a href="/timeline" <?php if (strpos($_SERVER['REQUEST_URI'], '/timeline') !== false && strpos($_SERVER['REQUEST_URI'], '/timeline.php') === false) echo 'class="active"'; ?>>タイムライン</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                            $loggedInUsername = $_SESSION['username'] ?? '';
                        ?>
                    <li><a href="/users/<?php echo htmlspecialchars($loggedInUsername); ?>" <?php if (strpos($_SERVER['REQUEST_URI'], '/users/' . $loggedInUsername) !== false) echo 'class="active"'; ?>>プロフィール</a></li>
                    <li><a href="/logout">ログアウト</a></li>
                    <?php else: ?>
                    <li><a href="/signin" <?php if (strpos($_SERVER['REQUEST_URI'], '/signin') !== false) echo 'class="active"'; ?>>ログイン</a></li>
                    <li><a href="/signup" <?php if (strpos($_SERVER['REQUEST_URI'], '/signup') !== false) echo 'class="active"'; ?>>新規登録</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
