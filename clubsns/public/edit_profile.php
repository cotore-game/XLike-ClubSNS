<?php
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/config.php';

// ログインしていない場合はログインページへリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: signin');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // セッションからユーザー名を取得
$display_name = $_SESSION['display_name']; // セッションから表示名を取得

$user_profile = null;
$errors = [];
$success_message = '';

try {
    // 現在のユーザープロフィール情報を取得
    $stmt = $pdo->prepare("SELECT display_name, profile_text, profile_image_url FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_profile) {
        // 通常は発生しないが、念のため
        $errors[] = 'プロフィール情報の取得に失敗しました。';
    }

    // POSTリクエストの場合（フォーム送信時）
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_display_name = trim($_POST['display_name'] ?? '');
        $new_profile_text = trim($_POST['profile_text'] ?? '');

        // バリデーション
        if (empty($new_display_name)) {
            $errors[] = '表示名は必須です。';
        } elseif (mb_strlen($new_display_name) > 50) {
            $errors[] = '表示名は50文字以内で入力してください。';
        }

        if (mb_strlen($new_profile_text) > 500) {
            $errors[] = 'プロフィール文は500文字以内で入力してください。';
        }

        // プロフィール画像のアップロード処理
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_type = $_FILES['profile_image']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = '許可されていないファイル形式です。JPG, JPEG, PNG, GIFのみアップロード可能です。';
            }
            if ($file_size > $max_file_size) {
                $errors[] = 'ファイルサイズが大きすぎます。2MB以内にしてください。';
            }

            if (empty($errors)) {
                // アップロードディレクトリのパス
                $upload_dir = __DIR__ . '/uploads/profile_images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // ディレクトリが存在しない場合は作成
                }

                // ユニークなファイル名を生成
                $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    // データベースに保存するURLは public/ からの相対パス
                    $new_profile_image_url = 'uploads/profile_images/' . $new_file_name;
                } else {
                    $errors[] = 'ファイルのアップロードに失敗しました。';
                }
            }
        } else if (isset($_POST['remove_image']) && $_POST['remove_image'] === 'true') {
            // 画像削除がリクエストされた場合
            $new_profile_image_url = null; // DBのURLをNULLにする
            // 既存のファイルを削除する場合はここに追加
            if (!empty($user_profile['profile_image_url']) && file_exists(__DIR__ . '/' . $user_profile['profile_image_url'])) {
                 unlink(__DIR__ . '/' . $user_profile['profile_image_url']);
            }
        } else {
            // 新しい画像がアップロードされず、削除もリクエストされていない場合は既存のURLを保持
            $new_profile_image_url = $user_profile['profile_image_url'];
        }

        if (empty($errors)) {
            // データベースを更新
            $stmt_update = $pdo->prepare("UPDATE users SET display_name = :display_name, profile_text = :profile_text, profile_image_url = :profile_image_url WHERE user_id = :user_id");
            $stmt_update->bindParam(':display_name', $new_display_name);
            $stmt_update->bindParam(':profile_text', $new_profile_text);
            $stmt_update->bindParam(':profile_image_url', $new_profile_image_url);
            $stmt_update->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);

            if ($stmt_update->execute()) {
                $success_message = 'プロフィールが更新されました。';
                // セッションの表示名も更新
                $_SESSION['display_name'] = $new_display_name;
                // ページをリロードして最新のプロフィール情報を表示
                header('Location: users/' . htmlspecialchars($username) . '?updated=true');
                exit();
            } else {
                $errors[] = 'プロフィールの更新に失敗しました。';
            }
        }
    }

} catch (PDOException $e) {
    $errors[] = 'データベースエラーが発生しました。';
    // エラーはカスタムエラーハンドラで処理されるため、ここでは一般的なメッセージを表示
}

// フォームの初期値として現在のプロフィール情報を再取得
// (POSTでエラーがあった場合も最新の情報を表示するため)
try {
    $stmt = $pdo->prepare("SELECT display_name, profile_text, profile_image_url FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'プロフィール情報の再取得に失敗しました。';
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール編集 - MySNS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>MySNS</h1>
            <nav>
                <ul>
                    <li><a href="timeline">タイムライン</a></li>
                    <li><a href="users/<?php echo htmlspecialchars($username); ?>">プロフィール (<?php echo htmlspecialchars($display_name); ?>)</a></li>
                    <li><a href="logout">ログアウト</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <section>
            <h2>プロフィール編集</h2>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true'): ?>
                <p class="success-message">プロフィールが正常に更新されました。</p>
            <?php endif; ?>

            <form action="edit_profile" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="display_name">表示名</label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo htmlspecialchars($user_profile['display_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="profile_text">プロフィール文</label>
                    <textarea id="profile_text" name="profile_text" rows="5" maxlength="500"><?php echo htmlspecialchars($user_profile['profile_text'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>プロフィール画像</label>
                    <?php if (!empty($user_profile['profile_image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($user_profile['profile_image_url']); ?>" alt="現在のプロフィール画像" class="profile-image-preview">
                        <br>
                        <input type="checkbox" id="remove_image" name="remove_image" value="true">
                        <label for="remove_image">画像を削除する</label>
                        <br>
                    <?php endif; ?>
                    <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                    <p class="help-text">JPG, JPEG, PNG, GIF形式、2MBまで</p>
                </div>

                <button type="submit" class="button">更新する</button>
                <a href="users/<?php echo htmlspecialchars($username); ?>" class="button" style="background-color: #6c757d;">キャンセル</a>
            </form>
        </section>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> MySNS</p>
        </div>
    </footer>
</body>
</html>
