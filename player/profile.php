<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_player();

$pdo = db();
$user = current_user();

$stmt = $pdo->prepare('SELECT id, name, email, position, bio, profile_image FROM players WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $user['email']]);
$player = $stmt->fetch();

if ($player === false) {
    http_response_code(404);
    exit('Player profile not found. Ask admin to add your player record with the same email as your account.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $uploadError = null;
    $newProfileImagePath = null;
    $currentProfileImage = trim((string) ($player['profile_image'] ?? ''));

    if ($name === '' || $position === '') {
        set_flash('error', 'Name and position are required.');
    } else {
        if (isset($_FILES['profile_image']) && (int) $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int) $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadError = 'Could not upload the profile image.';
            } elseif ((int) $_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                $uploadError = 'Profile image must be 2 MB or smaller.';
            } else {
                $imageInfo = @getimagesize($_FILES['profile_image']['tmp_name']);
                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                ];

                if ($imageInfo === false || !isset($allowedMimeTypes[$imageInfo['mime'] ?? ''])) {
                    $uploadError = 'Please upload a JPG, PNG, WebP, or GIF image.';
                } else {
                    $uploadDirectory = __DIR__ . '/../storage/player-images';
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                        $uploadError = 'Could not prepare the image storage folder.';
                    } else {
                        $extension = $allowedMimeTypes[$imageInfo['mime']];
                        $fileName = sprintf('player-%d-%s.%s', (int) $player['id'], date('YmdHis'), $extension);
                        $targetPath = $uploadDirectory . '/' . $fileName;

                        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                            $uploadError = 'Could not save the uploaded image.';
                        } else {
                            $newProfileImagePath = 'storage/player-images/' . $fileName;
                        }
                    }
                }
            }
        }

        if ($uploadError !== null) {
            set_flash('error', $uploadError);
        } else {
            try {
                $update = $pdo->prepare('UPDATE players SET name = :name, position = :position, bio = :bio, profile_image = :profile_image WHERE id = :id');
                $profileImageValue = $newProfileImagePath !== null
                    ? $newProfileImagePath
                    : ($currentProfileImage !== '' ? $currentProfileImage : null);
                $update->execute([
                    'name' => $name,
                    'position' => $position,
                    'bio' => $bio === '' ? null : $bio,
                    'profile_image' => $profileImageValue,
                    'id' => $player['id'],
                ]);

                if ($newProfileImagePath !== null && $currentProfileImage !== '' && $currentProfileImage !== $newProfileImagePath) {
                    $oldImagePath = __DIR__ . '/../' . ltrim($currentProfileImage, '/');
                    if (is_file($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }

                set_flash('success', 'Profile updated.');
                header('Location: ' . app_url('/player/profile.php'));
                exit;
            } catch (PDOException $e) {
                if ($newProfileImagePath !== null) {
                    $rolledBackImage = __DIR__ . '/../' . $newProfileImagePath;
                    if (is_file($rolledBackImage)) {
                        @unlink($rolledBackImage);
                    }
                }

                set_flash('error', 'Could not update profile.');
            }
        }
    }

    $player['name'] = $name;
    $player['position'] = $position;
    $player['bio'] = $bio;
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Update Personal Profile</h1>

<?php
$profileImagePath = trim((string) ($player['profile_image'] ?? ''));
$profileInitial = strtoupper(substr((string) $player['name'], 0, 1));
?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <div class="player-avatar player-avatar-lg">
                <?php if ($profileImagePath !== ''): ?>
                    <img src="<?= e(storage_url($profileImagePath)) ?>" alt="<?= e($player['name']) ?> profile image">
                <?php else: ?>
                    <span><?= e($profileInitial) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="h5 mb-1"><?= e($player['name']) ?></h2>
                <p class="text-muted mb-0">Upload a profile image and keep your public profile up to date.</p>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="name">Name</label>
                <input class="form-control" id="name" name="name" value="<?= e($player['name']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" value="<?= e($player['email']) ?>" disabled>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="position">Position</label>
                <input class="form-control" id="position" name="position" value="<?= e($player['position']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="profile_image">Profile Image</label>
                <input class="form-control" id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="form-text">JPG, PNG, WebP, or GIF up to 2 MB.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="bio">Bio</label>
                <textarea class="form-control" id="bio" name="bio" rows="4"><?= e((string) ($player['bio'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
