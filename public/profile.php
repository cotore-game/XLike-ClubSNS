<?php
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/config.php';

// ログインしていない場合はログインページへリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: signin');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$view_username = null; // ユーザー名で取得するように変更
$user_profile = null;
$user_posts = [];
$error_message = '';

// 表示するユーザーのユーザー名を取得
if (isset($_GET['username'])) {
    $view_username = $_GET['username'];
} else {
    // ユーザー名が指定されていない場合は、ログインユーザー自身のプロフィールを表示
    $view_username = $_SESSION['username']; // セッションから現在のユーザー名を取得
}

try {
    // ユーザープロフィール情報の取得 (user_idではなくusernameで検索)
    $stmt = $pdo->prepare("SELECT user_id, username, display_name, profile_text, profile_image_url, created_at FROM users WHERE username = :username");
    $stmt->bindParam(':username', $view_username, PDO::PARAM_STR); // PDO::PARAM_STR を使用
    $stmt->execute();
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_profile) {
        // ユーザーが見つからない場合
        $error_message = '指定されたユーザーは見つかりませんでした。';
    } else {
        // 表示対象ユーザーのuser_idを確定
        $view_user_id = $user_profile['user_id']; // プロフィール情報からuser_idを取得

        // そのユーザーの投稿を取得 (user_idは上記で取得したものを使用)
        $stmt_posts = $pdo->prepare("
            SELECT 
                post_id, 
                content, 
                image_url, 
                created_at 
            FROM 
                posts 
            WHERE 
                user_id = :user_id
            ORDER BY 
                created_at DESC
        ");
        $stmt_posts->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
        $stmt_posts->execute();
        $user_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_message = 'プロフィール情報の取得中にエラーが発生しました。';
    // エラー詳細をログに記録したい場合は $e->getMessage() を利用
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_profile['display_name'] ?? 'ユーザー'); ?>さんのプロフィール - MySNS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>MySNS</h1>
            <nav>
                <ul>
                    <li><a href="timeline">タイムライン</a></li>
                    <li><a href="users/<?php echo htmlspecialchars($_SESSION['username']); ?>">プロフィール (<?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?>)</a></li>
                    <li><a href="logout">ログアウト</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif ($user_profile): ?>
            <section class="user-profile-section">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($user_profile['profile_image_url'] ?? 'assets/default_profile.png'); ?>" alt="プロフィール画像" class="profile-image">
                    <h2><?php echo htmlspecialchars($user_profile['display_name']); ?></h2>
                    <p class="username">@<?php echo htmlspecialchars($user_profile['username']); ?></p>
                    <p class="joined-date">参加日: <?php echo htmlspecialchars(date('Y年m月d日', strtotime($user_profile['created_at']))); ?></p>
                </div>
                <div class="profile-body">
                    <?php if ($user_profile['profile_text']): ?>
                        <p class="profile-text"><?php echo nl2br(htmlspecialchars($user_profile['profile_text'])); ?></p>
                    <?php else: ?>
                        <p class="profile-text">プロフィールが設定されていません。</p>
                    <?php endif; ?>

                    <?php if ($user_profile['user_id'] === $current_user_id): // 表示中のユーザーがログインユーザー自身か確認 ?>
                        <div class="profile-actions">
                            <a href="edit_profile" class="button">プロフィールを編集</a>
                        </div>
                    <?php else: ?>
                        <div class="profile-actions">
                            </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="user-posts-section">
                <h3><?php echo htmlspecialchars($user_profile['display_name']); ?>さんの投稿</h3>
                <?php if (empty($user_posts)): ?>
                    <p>まだ投稿がありません。</p>
                <?php else: ?>
                    <?php foreach ($user_posts as $post): ?>
                        <div class="post">
                            <p class="post-meta">
                                <a href="users/<?php echo htmlspecialchars($user_profile['username']); ?>"> **<?php echo htmlspecialchars($user_profile['display_name']); ?>** @<?php echo htmlspecialchars($user_profile['username']); ?> 
                                </a>
                                - <?php echo htmlspecialchars(date('Y/m/d H:i', strtotime($post['created_at']))); ?>
                            </p>
                            <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <?php if ($post['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="投稿画像" class="post-image">
                            <?php endif; ?>
                            <div class="post-actions">
                                <a href="post_detail?post_id=<?php echo htmlspecialchars($post['post_id']); ?>">詳細・コメント</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> MySNS</p>
        </div>
    </footer>
</body>
</html>
