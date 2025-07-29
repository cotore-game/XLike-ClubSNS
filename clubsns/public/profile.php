<?php
session_start();
require_once __DIR__ . '/../includes/db.php'; // DB接続ファイルをインクルード
require_once __DIR__ . '/../includes/header.php'; // ヘッダーをインクルード

// ユーザーがログインしていない場合は、共通パスワード入力ページにリダイレクト
if (!isset($_SESSION['department_access_granted']) || !$_SESSION['department_access_granted']) {
    header('Location: /'); // index.php (共通パスワード入力ページ) へリダイレクト
    exit;
}

$page_title = 'プロフィール';
$username_to_show = null;
$is_own_profile = false;

// URLからユーザー名を取得 (mod_rewrite 経由)
if (isset($_GET['username']) && !empty($_GET['username'])) {
    $username_to_show = $_GET['username'];
} elseif (isset($_SESSION['user_id'])) {
    // ログインしているがユーザー名がURLにない場合（自分のプロフィールページ）
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $loggedInUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($loggedInUser) {
            $username_to_show = $loggedInUser['username'];
            $is_own_profile = true;
        } else {
            // ユーザーが見つからない場合のエラー
            $_SESSION['error_message'] = "ログインユーザーのプロフィールが見つかりませんでした。";
            header('Location: /timeline'); // タイムラインへリダイレクト
            exit;
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch logged in user username: " . $e->getMessage());
        $_SESSION['error_message'] = "プロフィール情報の取得に失敗しました。";
        header('Location: /timeline');
        exit;
    }
} else {
    // ログインしていない状態でユーザー名も指定されていない場合はログインページへ
    header('Location: /signin');
    exit;
}

// 表示するユーザーの情報をデータベースから取得
$user_profile = null;
$user_posts = [];
if ($username_to_show) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, display_name, bio, profile_image_path, created_at FROM users WHERE username = ?");
        $stmt->execute([$username_to_show]);
        $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_profile) {
            // ユーザーが見つからない場合
            $_SESSION['error_message'] = "指定されたユーザーは見つかりませんでした。";
            header('Location: /timeline'); // タイムラインへリダイレクト
            exit;
        }

        // 表示するユーザーの投稿を取得
        $stmt_posts = $pdo->prepare("SELECT id AS post_id, content, image_path, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt_posts->execute([$user_profile['id']]);
        $user_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

        // 自分のプロフィールかどうかを再確認
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_profile['id']) {
            $is_own_profile = true;
        } else {
            $is_own_profile = false; // 他のユーザーのプロフィールを見ている場合
        }

        $page_title = htmlspecialchars($user_profile['display_name'] ?? $user_profile['username']) . 'のプロフィール';

    } catch (PDOException $e) {
        error_log("Failed to fetch user profile or posts: " . $e->getMessage());
        $_SESSION['error_message'] = "プロフィールまたは投稿の取得に失敗しました。";
        header('Location: /timeline');
        exit;
    }
}
?>

<section class="user-profile-section">
    <?php if (isset($_SESSION['error_message'])): ?>
        <p class="error-message"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <p class="success-message"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>

    <?php if ($user_profile): ?>
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user_profile['profile_image_path'] ?? '/images/default_profile.png'); ?>" alt="プロフィール画像" class="profile-image">
            <h2><?php echo htmlspecialchars($user_profile['display_name'] ?? $user_profile['username']); ?></h2>
            <p class="username">@<?php echo htmlspecialchars($user_profile['username']); ?></p>
            <p class="joined-date"><i class="far fa-calendar-alt"></i> 参加日: <?php echo htmlspecialchars(date('Y年m月d日', strtotime($user_profile['created_at']))); ?></p>
            <?php if (!empty($user_profile['bio'])): ?>
                <p class="profile-text"><?php echo nl2br(htmlspecialchars($user_profile['bio'])); ?></p>
            <?php endif; ?>
            <?php if ($is_own_profile): ?>
                <div class="profile-actions">
                    <a href="/edit_profile" class="button edit-profile-button">プロフィールを編集</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>プロフィール情報が見つかりませんでした。</p>
    <?php endif; ?>
</section>

<section class="user-posts-section">
    <h3><?php echo htmlspecialchars($user_profile['display_name'] ?? $user_profile['username']); ?>の投稿</h3>
    <?php if (empty($user_posts)): ?>
        <p>このユーザーはまだ投稿していません。</p>
    <?php else: ?>
        <?php foreach ($user_posts as $post): ?>
            <div class="post">
                <div class="post-meta">
                    <a href="/users/<?php echo htmlspecialchars($user_profile['username']); ?>" class="profile-link">
                         <strong><?php echo htmlspecialchars($user_profile['display_name'] ?? $user_profile['username']); ?></strong>
                         <span class="username">@<?php echo htmlspecialchars($user_profile['username']); ?></span>
                    </a>
                    <span class="timestamp"><?php echo htmlspecialchars(date('Y/m/d H:i', strtotime($post['created_at']))); ?></span>
                </div>
                <div class="post-content">
                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    <?php if (!empty($post['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="投稿画像" class="post-image">
                    <?php endif; ?>
                </div>
                <div class="post-actions">
                    <a href="/posts/<?php echo htmlspecialchars($post['post_id']); ?>"><i class="far fa-comment"></i> コメント</a>
                    </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
