<?php
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/config.php';

// ユーザーがログインしているか確認します。
// ログインしていない場合は、ログインページにリダイレクトします。
if (!isset($_SESSION['user_id'])) {
    header('Location: signin');
    exit();
}

// 現在のログインユーザーのID、ユーザー名、表示名を取得します。
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$current_display_name = $_SESSION['display_name'] ?? $current_username;

$post_error = ''; // 投稿処理中に発生したエラーメッセージを格納する変数

// HTTPリクエストがPOSTメソッドで、かつ投稿内容が送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']); // 投稿内容の前後にある空白を除去

    // 投稿内容が空でないか検証
    if (empty($content)) {
        $post_error = '投稿内容を入力してください。';
    } else {
        try {
            // データベースに新しい投稿を挿入します。
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (:user_id, :content)");
            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            if ($stmt->execute()) {
                // 投稿成功後、タイムラインページを再読み込みしてフォームをクリアします。
                header('Location: timeline');
                exit();
            } else {
                $post_error = '投稿に失敗しました。';
            }
        } catch (PDOException $e) {
            // データベース操作中にエラーが発生した場合
            $post_error = '投稿処理中にエラーが発生しました。';
        }
    }
}

// タイムラインに表示する投稿データをデータベースから取得します。
$posts = []; // 投稿データを格納する配列を初期化
try {
    // 最新の投稿が上に来るように、作成日時で降順にソートします。
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
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC); // 結果を連想配列として取得
} catch (PDOException $e) {
    // タイムラインデータの読み込み中にエラーが発生した場合
    $post_error = 'タイムラインの読み込み中にエラーが発生しました。';
}

// ページ固有のタイトルを設定し、共通ヘッダーに渡します。
$page_title = 'タイムライン - MySNS';
require_once __DIR__ . '/../includes/header.php'; // 共通ヘッダーを読み込み
?>

    <div class="container"> <section class="post-form-section">
            <h2>新規投稿</h2>
            <?php if ($post_error): // 投稿エラーメッセージがある場合に表示 ?>
                <p class="error-message"><?php echo htmlspecialchars($post_error); ?></p>
            <?php endif; ?>
            <form action="timeline" method="post">
                <div class="form-group">
                    <textarea name="post_content" placeholder="今、何してる？" rows="4" required></textarea>
                </div>
                <button type="submit" class="button">投稿</button>
            </form>
        </section>

        <section class="timeline">
            <h2>タイムライン</h2>
            <?php if (empty($posts)): // 投稿がまだ存在しない場合 ?>
                <p>まだ投稿がありません。</p>
            <?php else: // 投稿がある場合、それぞれの投稿を表示 ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <p class="post-meta">
                            <a href="users/<?php echo htmlspecialchars($post['username']); ?>">
                                <strong><?php echo htmlspecialchars($post['display_name']); ?></strong> @<?php echo htmlspecialchars($post['username']); ?> 
                            </a>
                            - <?php echo htmlspecialchars(date('Y/m/d H:i', strtotime($post['created_at']))); ?>
                        </p>
                        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php if ($post['image_url']): // 投稿に画像がある場合に表示 ?>
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="投稿画像" class="post-image">
                        <?php endif; ?>
                        <div class="post-actions">
                            <a href="post_detail?post_id=<?php echo htmlspecialchars($post['post_id']); ?>">詳細・コメント</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div> <?php require_once __DIR__ . '/../includes/footer.php'; // 共通フッターを読み込み ?>