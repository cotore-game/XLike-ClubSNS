<?php
// このファイルは、指定されたユーザーのプロフィール情報と、そのユーザーの投稿を表示します。

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

$view_username = null; // URLから取得、またはログインユーザーのユーザー名
$user_profile = null;  // 取得したユーザープロフィールデータ
$user_posts = [];      // 取得したユーザーの投稿データ
$error_message = '';   // エラーメッセージを格納する変数

// URLのGETパラメータから表示対象のユーザー名を取得します。
// ユーザー名が指定されていない場合は、ログインしている自分自身のプロフィールを表示します。
if (isset($_GET['username'])) {
    $view_username = $_GET['username'];
} else {
    $view_username = $current_username;
}

try {
    // データベースからユーザープロフィール情報を取得します。ユーザー名で検索します。
    $stmt = $pdo->prepare("SELECT user_id, username, display_name, profile_text, profile_image_url, created_at FROM users WHERE username = :username");
    $stmt->bindParam(':username', $view_username, PDO::PARAM_STR);
    $stmt->execute();
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC); // 結果を連想配列として取得

    if (!$user_profile) {
        // 指定されたユーザーが見つからなかった場合
        $error_message = '指定されたユーザーは見つかりませんでした。';
    } else {
        // プロフィールが見つかった場合、そのユーザーのIDを確定
        $view_user_id = $user_profile['user_id'];

        // そのユーザーの投稿をデータベースから取得します。
        // 最新の投稿が上に来るように、作成日時で降順にソートします。
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
        $user_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC); // 結果を連想配列として取得
    }

} catch (PDOException $e) {
    // データベース操作中にエラーが発生した場合
    $error_message = 'プロフィール情報の取得中にエラーが発生しました。';
}

// ページ固有のタイトルを設定し、共通ヘッダーに渡します。
$page_title = htmlspecialchars($user_profile['display_name'] ?? 'ユーザー') . 'さんのプロフィール - MySNS';
require_once __DIR__ . '/../includes/header.php'; // 共通ヘッダーを読み込み
?>

    <div class="container"> <?php if ($error_message): // エラーメッセージがある場合に表示 ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif ($user_profile): // プロフィール情報が正常に取得できた場合に表示 ?>
            <section class="user-profile-section">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($user_profile['profile_image_url'] ?? 'assets/default_profile.png'); ?>" alt="プロフィール画像" class="profile-image">
                    <h2><?php echo htmlspecialchars($user_profile['display_name']); ?></h2>
                    <p class="username">@<?php echo htmlspecialchars($user_profile['username']); ?></p>
                    <p class="joined-date">参加日: <?php echo htmlspecialchars(date('Y年m月d日', strtotime($user_profile['created_at']))); ?></p>
                </div>
                <div class="profile-body">
                    <?php if ($user_profile['profile_text']): // プロフィール本文がある場合に表示 ?>
                        <p class="profile-text"><?php echo nl2br(htmlspecialchars($user_profile['profile_text'])); ?></p>
                    <?php else: ?>
                        <p class="profile-text">プロフィールが設定されていません。</p>
                    <?php endif; ?>

                    <?php if ($user_profile['user_id'] === $current_user_id): // 表示中のユーザーがログインユーザー自身の場合 ?>
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
                <?php if (empty($user_posts)): // 投稿がまだ存在しない場合 ?>
                    <p>まだ投稿がありません。</p>
                <?php else: // 投稿がある場合、それぞれの投稿を表示 ?>
                    <?php foreach ($user_posts as $post): ?>
                        <div class="post">
                            <p class="post-meta">
                                <a href="users/<?php echo htmlspecialchars($user_profile['username']); ?>">
                                    <strong><?php echo htmlspecialchars($user_profile['display_name']); ?></strong> @<?php echo htmlspecialchars($user_profile['username']); ?> 
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
        <?php endif; ?>
    </div> <?php require_once __DIR__ . '/../includes/footer.php'; // 共通フッターを読み込み ?>
    