<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

/*
|--------------------------------------------------------------------------
| AUTHORIZATION
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| FLASH HELPERS
|--------------------------------------------------------------------------
*/
function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash_message'], $_SESSION['flash_type'])) {
        return null;
    }

    $flash = [
        'message' => $_SESSION['flash_message'],
        'type' => $_SESSION['flash_type']
    ];

    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return $flash;
}

/*
|--------------------------------------------------------------------------
| AUDIT LOG HELPER
|--------------------------------------------------------------------------
*/
function writeAuditLog(
    PDO $pdo,
    int $userId,
    string $entityType,
    ?int $entityId,
    string $actionType,
    ?string $fieldChanged,
    ?string $oldValue,
    ?string $newValue
): void {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (
            user_id,
            entity_type,
            entity_id,
            action_type,
            field_changed,
            old_value,
            new_value,
            change_timestamp
        ) VALUES (
            :user_id,
            :entity_type,
            :entity_id,
            :action_type,
            :field_changed,
            :old_value,
            :new_value,
            NOW()
        )
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':action_type' => $actionType,
        ':field_changed' => $fieldChanged,
        ':old_value' => $oldValue,
        ':new_value' => $newValue
    ]);
}

/*
|--------------------------------------------------------------------------
| IMAGE HELPERS
|--------------------------------------------------------------------------
*/
function getUploadDirectory(): string
{
    return __DIR__ . '/assets/images/seasonal/';
}

function ensureUploadDirectoryExists(): void
{
    $dir = getUploadDirectory();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function sanitizeFileName(string $fileName): string
{
    $fileName = basename($fileName);
    $fileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);
    return $fileName ?: 'image';
}

function validateImageUpload(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return [false, 'Invalid upload.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return [true, ''];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'File upload failed.'];
    }

    $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif'];
    $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/gif'];

    $originalName = $file['name'] ?? '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        return [false, 'Only .png, .jpg, .jpeg, and .gif files are allowed.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return [false, 'Uploaded file is not a valid image.'];
    }

    return [true, ''];
}

function processUploadedImage(array $file, ?string $existingImagePath = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, $existingImagePath, null];
    }

    [$valid, $message] = validateImageUpload($file);
    if (!$valid) {
        return [false, $existingImagePath, $message];
    }

    ensureUploadDirectoryExists();

    $uploadDir = getUploadDirectory();
    $sanitizedName = sanitizeFileName($file['name']);
    $targetPath = $uploadDir . $sanitizedName;
    $relativePath = 'assets/images/seasonal/' . $sanitizedName;

    $existingBaseName = $existingImagePath ? basename($existingImagePath) : null;

    if (file_exists($targetPath)) {
        if ($existingBaseName !== null && $existingBaseName === $sanitizedName) {
            if (is_file(__DIR__ . '/' . $existingImagePath)) {
                @unlink(__DIR__ . '/' . $existingImagePath);
            }
        } else {
            return [false, $existingImagePath, 'An image with that file name already exists. Please rename the file and try again.'];
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [false, $existingImagePath, 'Failed to save uploaded image.'];
    }

    if ($existingImagePath !== null && $existingImagePath !== '' && $existingImagePath !== $relativePath) {
        $oldFullPath = __DIR__ . '/' . $existingImagePath;
        if (is_file($oldFullPath)) {
            @unlink($oldFullPath);
        }
    }

    return [true, $relativePath, null];
}

/*
|--------------------------------------------------------------------------
| HANDLE CREATE SEASONAL SPECIAL
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_seasonal_special') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request token.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }

    $headerText = trim($_POST['header_text'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($headerText === '' || $description === '') {
        setFlash('Header text and description are required.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }

    $imagePath = null;

    if (isset($_FILES['image_path'])) {
        [$success, $newImagePath, $error] = processUploadedImage($_FILES['image_path'], null);
        if (!$success) {
            setFlash($error ?? 'Image upload failed.', 'error');
            header('Location: manage_seasonal_menu.php');
            exit;
        }
        $imagePath = $newImagePath;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO seasonal_specials (
                header_text,
                description,
                image_path,
                created_at,
                updated_at
            ) VALUES (
                :header_text,
                :description,
                :image_path,
                NOW(),
                NOW()
            )
        ");

        $stmt->execute([
            ':header_text' => $headerText,
            ':description' => $description,
            ':image_path' => $imagePath
        ]);

        $seasonalSpecialId = (int)$pdo->lastInsertId();

        writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'CREATE', 'header_text', null, $headerText);
        writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'CREATE', 'description', null, $description);
        writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'CREATE', 'image_path', null, $imagePath);

        setFlash('Seasonal special created successfully.', 'success');
        header('Location: manage_seasonal_menu.php');
        exit;
    } catch (PDOException $e) {
        setFlash('Error creating seasonal special.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| HANDLE UPDATE SEASONAL SPECIAL
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_seasonal_special') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request token.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }

    $seasonalSpecialId = (int)($_POST['seasonal_special_id'] ?? 0);
    $headerText = trim($_POST['header_text'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $removeImage = isset($_POST['remove_image']) ? 1 : 0;

    if ($seasonalSpecialId <= 0) {
        setFlash('Invalid seasonal special.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }

    if ($headerText === '' || $description === '') {
        setFlash('Header text and description are required.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT seasonal_special_id, header_text, description, image_path
            FROM seasonal_specials
            WHERE seasonal_special_id = :seasonal_special_id
            LIMIT 1
        ");
        $stmt->execute([':seasonal_special_id' => $seasonalSpecialId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            setFlash('Seasonal special not found.', 'error');
            header('Location: manage_seasonal_menu.php');
            exit;
        }

        $newImagePath = $existing['image_path'];

        if ($removeImage && !empty($existing['image_path'])) {
            $oldFullPath = __DIR__ . '/' . $existing['image_path'];
            if (is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
            $newImagePath = null;
        }

        if (isset($_FILES['image_path']) && ($_FILES['image_path']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$success, $processedPath, $error] = processUploadedImage($_FILES['image_path'], $existing['image_path'] ?: null);
            if (!$success) {
                setFlash($error ?? 'Image upload failed.', 'error');
                header('Location: manage_seasonal_menu.php');
                exit;
            }
            $newImagePath = $processedPath;
        }

        $updateStmt = $pdo->prepare("
            UPDATE seasonal_specials
            SET
                header_text = :header_text,
                description = :description,
                image_path = :image_path,
                updated_at = NOW()
            WHERE seasonal_special_id = :seasonal_special_id
        ");

        $updateStmt->execute([
            ':header_text' => $headerText,
            ':description' => $description,
            ':image_path' => $newImagePath,
            ':seasonal_special_id' => $seasonalSpecialId
        ]);

        $changed = false;

        if ((string)$existing['header_text'] !== $headerText) {
            writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'UPDATE', 'header_text', (string)$existing['header_text'], $headerText);
            $changed = true;
        }

        if ((string)$existing['description'] !== $description) {
            writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'UPDATE', 'description', (string)$existing['description'], $description);
            $changed = true;
        }

        if ((string)($existing['image_path'] ?? '') !== (string)($newImagePath ?? '')) {
            writeAuditLog(
                $pdo,
                (int)$_SESSION['user_id'],
                'seasonal_special',
                $seasonalSpecialId,
                'UPDATE',
                'image_path',
                $existing['image_path'] ?: null,
                $newImagePath
            );
            $changed = true;
        }

        if ($changed) {
            setFlash('Seasonal special updated successfully.', 'success');
        } else {
            setFlash('No changes were detected.', 'success');
        }

        header('Location: manage_seasonal_menu.php');
        exit;
    } catch (PDOException $e) {
        setFlash('Error updating seasonal special.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| HANDLE DELETE SEASONAL SPECIAL
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_seasonal_special') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request token.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }

    $seasonalSpecialId = (int)($_POST['seasonal_special_id'] ?? 0);

    if ($seasonalSpecialId <= 0) {
        setFlash('Invalid seasonal special.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT seasonal_special_id, header_text, description, image_path
            FROM seasonal_specials
            WHERE seasonal_special_id = :seasonal_special_id
            LIMIT 1
        ");
        $stmt->execute([':seasonal_special_id' => $seasonalSpecialId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            setFlash('Seasonal special not found.', 'error');
            header('Location: manage_seasonal_menu.php');
            exit;
        }

        if (!empty($existing['image_path'])) {
            $oldFullPath = __DIR__ . '/' . $existing['image_path'];
            if (is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }

        $deleteStmt = $pdo->prepare("DELETE FROM seasonal_specials WHERE seasonal_special_id = :seasonal_special_id");
        $deleteStmt->execute([':seasonal_special_id' => $seasonalSpecialId]);

        writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'DELETE', 'header_text', (string)$existing['header_text'], null);
        writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'DELETE', 'description', (string)$existing['description'], null);
        writeAuditLog($pdo, (int)$_SESSION['user_id'], 'seasonal_special', $seasonalSpecialId, 'DELETE', 'image_path', $existing['image_path'] ?: null, null);

        setFlash('Seasonal special deleted successfully.', 'success');
        header('Location: manage_seasonal_menu.php');
        exit;
    } catch (PDOException $e) {
        setFlash('Error deleting seasonal special.', 'error');
        header('Location: manage_seasonal_menu.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| FETCH SEASONAL SPECIALS
|--------------------------------------------------------------------------
*/
$seasonalStmt = $pdo->query("
    SELECT seasonal_special_id, header_text, description, image_path, created_at, updated_at
    FROM seasonal_specials
    ORDER BY created_at DESC, seasonal_special_id DESC
");
$seasonalSpecials = $seasonalStmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Seasonal Inventory - Main Channel Brewing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1100px;
            margin: 50px auto;
            padding: 0 15px;
        }

        .section-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.10);
            margin-bottom: 25px;
        }

        h1, h2 {
            margin-top: 0;
        }

        .top-bar {
            margin-bottom: 20px;
        }

        .btn,
        button {
            display: inline-block;
            text-decoration: none;
            background: #222;
            color: #fff;
            padding: 12px 18px;
            border-radius: 8px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn:hover,
        button:hover {
            background: #444;
        }

        .delete-btn {
            background: #b00020;
            padding: 10px 14px;
            min-width: 46px;
        }

        .delete-btn:hover {
            background: #d32f2f;
        }

        .flash {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .flash.success {
            background: #e6f4ea;
            color: #2e7d32;
        }

        .flash.error {
            background: #fdecea;
            color: #b71c1c;
        }

        form.seasonal-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        textarea {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .seasonal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 18px;
        }

        .seasonal-item-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 18px;
        }

        .seasonal-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .seasonal-image-wrap {
            width: 120px;
            height: 120px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
            margin-bottom: 12px;
        }

        .seasonal-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-image {
            font-size: 12px;
            color: #777;
            text-align: center;
            padding: 10px;
        }

        .meta {
            font-size: 12px;
            color: #777;
            margin-top: 8px;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .checkbox-row input {
            width: auto;
            margin: 0;
        }

        @media (max-width: 900px) {
            .seasonal-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            form.seasonal-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <a class="btn" href="AdminDashboard.php">← Admin Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="section-card">
        <h1>Manage Seasonal Inventory</h1>
        <p>Add new seasonal specials, update existing specials, manage images, and remove items as needed.</p>
    </div>

    <div class="section-card">
        <h2>Add New Seasonal Special</h2>

        <form class="seasonal-form" method="POST" action="manage_seasonal_menu.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_seasonal_special">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group full-width">
                <label for="header_text">Header Text</label>
                <input type="text" id="header_text" name="header_text" maxlength="255" required>
            </div>

            <div class="form-group full-width">
                <label for="image_path">Image Upload</label>
                <input type="file" id="image_path" name="image_path" accept=".png,.jpg,.jpeg,.gif">
            </div>

            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit">Save Seasonal Special</button>
            </div>
        </form>
    </div>

    <div class="section-card">
        <h2>All Seasonal Specials</h2>

        <?php if (empty($seasonalSpecials)): ?>
            <p>No seasonal specials found.</p>
        <?php else: ?>
            <div class="seasonal-grid">
                <?php foreach ($seasonalSpecials as $item): ?>
                    <div class="seasonal-item-card">
                        <div class="seasonal-image-wrap">
                            <?php if (!empty($item['image_path']) && is_file(__DIR__ . '/' . $item['image_path'])): ?>
                                <img src="<?= htmlspecialchars('/' . ltrim($item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="Seasonal Special Image">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>

                        <form class="seasonal-form" method="POST" action="manage_seasonal_menu.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_seasonal_special">
                            <input type="hidden" name="seasonal_special_id" value="<?= (int)$item['seasonal_special_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                            <div class="form-group full-width">
                                <label>Header Text</label>
                                <input
                                    type="text"
                                    name="header_text"
                                    maxlength="255"
                                    value="<?= htmlspecialchars((string)$item['header_text'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-group full-width">
                                <label>Replace Image</label>
                                <input type="file" name="image_path" accept=".png,.jpg,.jpeg,.gif">
                                <div class="checkbox-row">
                                    <input type="checkbox" name="remove_image" id="remove_image_<?= (int)$item['seasonal_special_id'] ?>">
                                    <label for="remove_image_<?= (int)$item['seasonal_special_id'] ?>">Remove current image</label>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="description" required><?= htmlspecialchars((string)$item['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <div class="seasonal-actions">
                                    <button type="submit">Save Changes</button>
                                </div>
                                <div class="meta">
                                    ID: <?= (int)$item['seasonal_special_id'] ?> |
                                    Created: <?= htmlspecialchars((string)$item['created_at'], ENT_QUOTES, 'UTF-8') ?> |
                                    Updated: <?= htmlspecialchars((string)$item['updated_at'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="manage_seasonal_menu.php" onsubmit="return confirm('Are you sure you want to delete this seasonal special?');" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="delete_seasonal_special">
                            <input type="hidden" name="seasonal_special_id" value="<?= (int)$item['seasonal_special_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="delete-btn" title="Delete Seasonal Special" aria-label="Delete Seasonal Special">🗑</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
