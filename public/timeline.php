<?php
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$current_display_name = $_SESSION['display_name'] ?? $current_username;
$post_error = '';

// 投稿処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);

    if (empty($content)) {
        $post_error = '投稿内容を入力してください。';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (:user_id, :content)");
            $stmt->bindParam(':user_id', $current_user_id);
            $stmt->bindParam(':content', $content);
            if ($stmt->execute()) {
                header('Location: timeline');
                exit();
            } else {
                $post_error = '投稿に失敗しました。';
            }
        } catch (PDOException $e) {
            $post_error = '投稿処理中にエラーが発生しました。';
        }
    }
}

// タイムラインの投稿を取得
$posts = [];
try {
    // postsテーブルとusersテーブルを結合して、投稿者のdisplay_nameとusernameも取得
    $stmt = $pdo->prepare("
        SELECT 
            p.post_id, 
            p.user_id, 
            p.content, 
            p.image_url, 
            p.created_at, 
            u.username, 
            u.display_name 
        FROM 
            posts p 
        JOIN 
            users u ON p.user_id = u.user_id 
        ORDER BY 
            p.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // エラー処理（例: $post_error にメッセージを設定）
    $post_error = 'タイムラインの読み込み中にエラーが発生しました。';
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイムライン - MySNS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>MySNS</h1>
            <nav>
                <ul>
                    <li><a href="timeline">タイムライン</a></li>
                    <li><a href="users/<?php echo htmlspecialchars($current_username); ?>">プロフィール (<?php echo htmlspecialchars($current_display_name); ?>)</a></li>
                    <li><a href="logout">ログアウト</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <section class="post-form-section">
            <h2>新規投稿</h2>
            <?php if ($post_error): ?>
                <p class="error-message"><?php echo htmlspecialchars($post_error); ?></p>
            <?php endif; ?>
            <form action="timeline" method="post">
                <div class="form-group">
                    <textarea name="post_content" placeholder="今、何してる？" rows="4" required></textarea>
                </div>
                <button type="submit">投稿</button>
            </form>
        </section>

        <section class="timeline">
            <h2>タイムライン</h2>
            <?php if (empty($posts)): ?>
                <p>まだ投稿がありません。</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <p class="post-meta">
                            <a href="users/<?php echo htmlspecialchars($post['username']); ?>">
                                **<?php echo htmlspecialchars($post['display_name']); ?>** @<?php echo htmlspecialchars($post['username']); ?> 
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
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> MySNS</p>
        </div>
    </footer>
</body>
</html>
