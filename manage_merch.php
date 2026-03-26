<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $flash = [
        'message' => $_SESSION['flash_message'],
        'type' => $_SESSION['flash_type']
    ];

    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return $flash;
}

function writeAuditLog(
    PDO $pdo,
    int $userId,
    string $entityType,
    ?int $entityId,
    string $actionType,
    ?string $field,
    ?string $old,
    ?string $new
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
            :u,
            :e,
            :id,
            :a,
            :f,
            :o,
            :n,
            NOW()
        )
    ");

    $stmt->execute([
        'u' => $userId,
        'e' => $entityType,
        'id' => $entityId,
        'a' => $actionType,
        'f' => $field,
        'o' => $old,
        'n' => $new
    ]);
}

function uploadDir(): string
{
    return __DIR__ . '/assets/images/merch/';
}

function relativeImageDir(): string
{
    return 'assets/images/merch/';
}

function ensureUploadDirExists(): void
{
    $dir = uploadDir();
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

function processImage(array $file, ?string $existing = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, $existing, null];
    }

    [$valid, $message] = validateImageUpload($file);
    if (!$valid) {
        return [false, $existing, $message];
    }

    ensureUploadDirExists();

    $dir = uploadDir();
    $relativeDir = relativeImageDir();
    $name = sanitizeFileName($file['name']);
    $target = $dir . $name;
    $relativePath = $relativeDir . $name;

    $existingBaseName = $existing ? basename($existing) : null;

    if (file_exists($target)) {
        if ($existingBaseName !== null && $existingBaseName === $name) {
            $existingFullPath = __DIR__ . '/' . ltrim($existing, '/');
            if (is_file($existingFullPath)) {
                @unlink($existingFullPath);
            }
        } else {
            return [false, $existing, 'An image with that file name already exists. Please rename the file and try again.'];
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return [false, $existing, 'Upload failed'];
    }

    if ($existing && $existing !== $relativePath) {
        $old = __DIR__ . '/' . ltrim($existing, '/');
        if (is_file($old)) {
            @unlink($old);
        }
    }

    return [true, $relativePath, null];
}

function getBulkUploadedFileForId(array $files, int $merchId): array
{
    return [
        'name' => $files['name'][$merchId] ?? '',
        'type' => $files['type'][$merchId] ?? '',
        'tmp_name' => $files['tmp_name'][$merchId] ?? '',
        'error' => $files['error'][$merchId] ?? UPLOAD_ERR_NO_FILE,
        'size' => $files['size'][$merchId] ?? 0,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request', 'error');
        header('Location: manage_merch.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');

        if ($name === '' || $price === '') {
            setFlash('Required fields missing', 'error');
            header('Location: manage_merch.php');
            exit;
        }

        [$ok, $img, $err] = processImage($_FILES['image_path'] ?? []);
        if (!$ok) {
            setFlash($err ?? 'Image upload failed.', 'error');
            header('Location: manage_merch.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            $pdo->prepare("
                INSERT INTO merch (
                    name,
                    price,
                    image_path,
                    created_at,
                    updated_at
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    NOW(),
                    NOW()
                )
            ")->execute([$name, $price, $img]);

            $id = (int) $pdo->lastInsertId();

            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'merch', $id, 'CREATE', 'name', null, $name);
            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'merch', $id, 'CREATE', 'price', null, $price);
            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'merch', $id, 'CREATE', 'image_path', null, $img);

            $pdo->commit();
            setFlash('Created', 'success');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('Error creating merch item.', 'error');
        }

        header('Location: manage_merch.php');
        exit;
    }

    if ($action === 'bulk_update') {
        $ids = $_POST['id'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            setFlash('No merch items were submitted.', 'error');
            header('Location: manage_merch.php');
            exit;
        }

        $actingUserId = (int) $_SESSION['user_id'];
        $updatedItems = 0;
        $changedFields = 0;

        try {
            $pdo->beginTransaction();

            foreach ($ids as $rawId) {
                $id = (int) $rawId;

                if ($id <= 0) {
                    continue;
                }

                $name = trim($_POST['name'][$id] ?? '');
                $price = trim($_POST['price'][$id] ?? '');
                $removeImage = isset($_POST['remove_image'][$id]);

                if ($name === '' || $price === '') {
                    throw new RuntimeException('Name and price are required for every merch item.');
                }

                $oldStmt = $pdo->prepare("SELECT * FROM merch WHERE merch_id = ? LIMIT 1");
                $oldStmt->execute([$id]);
                $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$old) {
                    throw new RuntimeException('One or more merch items could not be found.');
                }

                $newImagePath = $old['image_path'] !== null ? (string) $old['image_path'] : null;

                if ($removeImage && $newImagePath !== null && $newImagePath !== '') {
                    $oldFile = __DIR__ . '/' . ltrim($newImagePath, '/');
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                    $newImagePath = null;
                }

                if (isset($_FILES['image_path']) && is_array($_FILES['image_path']['name'] ?? null)) {
                    $uploadedFile = getBulkUploadedFileForId($_FILES['image_path'], $id);
                    if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        [$ok, $processedImage, $err] = processImage($uploadedFile, $old['image_path'] ?: null);
                        if (!$ok) {
                            throw new RuntimeException($err ?? 'Image upload failed.');
                        }
                        $newImagePath = $processedImage;
                    }
                }

                $pdo->prepare("
                    UPDATE merch
                    SET
                        name = ?,
                        price = ?,
                        image_path = ?,
                        updated_at = NOW()
                    WHERE merch_id = ?
                ")->execute([$name, $price, $newImagePath, $id]);

                $itemChanged = false;

                if ((string) $old['name'] !== $name) {
                    writeAuditLog($pdo, $actingUserId, 'merch', $id, 'UPDATE', 'name', (string) $old['name'], $name);
                    $itemChanged = true;
                    $changedFields++;
                }

                if ((string) $old['price'] !== $price) {
                    writeAuditLog($pdo, $actingUserId, 'merch', $id, 'UPDATE', 'price', (string) $old['price'], $price);
                    $itemChanged = true;
                    $changedFields++;
                }

                if ((string) ($old['image_path'] ?? '') !== (string) ($newImagePath ?? '')) {
                    writeAuditLog($pdo, $actingUserId, 'merch', $id, 'UPDATE', 'image_path', $old['image_path'] ?: null, $newImagePath);
                    $itemChanged = true;
                    $changedFields++;
                }

                if ($itemChanged) {
                    $updatedItems++;
                }
            }

            $pdo->commit();

            if ($updatedItems > 0) {
                $itemLabel = $updatedItems === 1 ? 'item' : 'items';
                $fieldLabel = $changedFields === 1 ? 'change' : 'changes';
                setFlash("Saved {$changedFields} {$fieldLabel} across {$updatedItems} merch {$itemLabel}.", 'success');
            } else {
                setFlash('No changes were detected.', 'success');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash($e instanceof RuntimeException ? $e->getMessage() : 'Error updating merch items.', 'error');
        }

        header('Location: manage_merch.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            setFlash('Invalid merch item.', 'error');
            header('Location: manage_merch.php');
            exit;
        }

        try {
            $old = $pdo->prepare("SELECT * FROM merch WHERE merch_id = ?");
            $old->execute([$id]);
            $old = $old->fetch(PDO::FETCH_ASSOC);

            if ($old) {
                if (!empty($old['image_path'])) {
                    $f = __DIR__ . '/' . ltrim((string) $old['image_path'], '/');
                    if (is_file($f)) {
                        @unlink($f);
                    }
                }

                $pdo->beginTransaction();

                $pdo->prepare("DELETE FROM merch WHERE merch_id = ?")->execute([$id]);

                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'merch', $id, 'DELETE', 'name', (string) $old['name'], null);
                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'merch', $id, 'DELETE', 'price', (string) $old['price'], null);
                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'merch', $id, 'DELETE', 'image_path', $old['image_path'] ?: null, null);

                $pdo->commit();
            }

            setFlash('Deleted', 'success');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('Error deleting merch item.', 'error');
        }

        header('Location: manage_merch.php');
        exit;
    }
}

$items = $pdo->query("SELECT * FROM merch ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Merch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 0 15px;
        }

        .section-card,
        .card {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.10);
        }

        .top-bar {
            margin-bottom: 20px;
        }

        .btn,
        button {
            display: inline-block;
            background: #222;
            color: #fff;
            padding: 10px 14px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .btn:hover,
        button:hover {
            background: #444;
        }

        .delete-btn {
            background: #b00020;
            margin-top: 12px;
        }

        .delete-btn:hover {
            background: #d32f2f;
        }

        .flash.success {
            color: #2e7d32;
            background: #e6f4ea;
        }

        .flash.error {
            color: #b71c1c;
            background: #fdecea;
        }

        .flash {
            font-weight: bold;
        }

        form.merch-form,
        .merch-form {
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

        input {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 15px;
        }

        .merch-image-wrap {
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

        .merch-image-wrap img {
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

        .meta {
            font-size: 12px;
            color: #777;
            margin-top: 10px;
        }

        .merch-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .bulk-save-bar {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 700px) {
            form.merch-form,
            .merch-form {
                grid-template-columns: 1fr;
            }

            .bulk-save-bar {
                justify-content: stretch;
            }

            .bulk-save-bar button {
                width: 100%;
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
        <div class="card flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="section-card">
        <h1>Manage Merch</h1>
        <p>Add merch items, update existing merch, manage images, and remove items as needed.</p>
    </div>

    <div class="section-card">
        <h2>Add Merch</h2>
        <form class="merch-form" method="POST" action="manage_merch.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="name">Name</label>
                <input id="name" name="name" placeholder="Name" required>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input id="price" name="price" placeholder="Price" required>
            </div>

            <div class="form-group full-width">
                <label for="image_path">Image Upload</label>
                <input type="file" id="image_path" name="image_path" accept=".png,.jpg,.jpeg,.gif">
            </div>

            <div class="form-group full-width">
                <button type="submit">Save Merch Item</button>
            </div>
        </form>
    </div>

    <div class="section-card">
        <h2>All Merch</h2>

        <?php if (empty($items)): ?>
            <p>No merch items found.</p>
        <?php else: ?>
            <form id="bulk-update-form" method="POST" action="manage_merch.php" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="action" value="bulk_update">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            </form>

            <div class="grid">
                <?php foreach ($items as $i): ?>
                    <div class="card">
                        <div class="merch-image-wrap">
                            <?php if (!empty($i['image_path']) && is_file(__DIR__ . '/' . ltrim((string) $i['image_path'], '/'))): ?>
                                <img src="<?= htmlspecialchars('/' . ltrim((string) $i['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="Merch image">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>

                        <div class="merch-form">
                            <input type="hidden" form="bulk-update-form" name="id[]" value="<?= (int) $i['merch_id'] ?>">

                            <div class="form-group">
                                <label>Name</label>
                                <input
                                    form="bulk-update-form"
                                    name="name[<?= (int) $i['merch_id'] ?>]"
                                    value="<?= htmlspecialchars((string) $i['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label>Price</label>
                                <input
                                    form="bulk-update-form"
                                    name="price[<?= (int) $i['merch_id'] ?>]"
                                    value="<?= htmlspecialchars((string) $i['price'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-group full-width">
                                <label>Replace Image</label>
                                <input
                                    type="file"
                                    form="bulk-update-form"
                                    name="image_path[<?= (int) $i['merch_id'] ?>]"
                                    accept=".png,.jpg,.jpeg,.gif"
                                >
                                <div class="checkbox-row">
                                    <input
                                        type="checkbox"
                                        form="bulk-update-form"
                                        name="remove_image[<?= (int) $i['merch_id'] ?>]"
                                        id="remove_image_<?= (int) $i['merch_id'] ?>"
                                    >
                                    <label for="remove_image_<?= (int) $i['merch_id'] ?>">Remove current image</label>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <div class="merch-actions">
                                    <button type="submit" form="bulk-update-form">Save Changes</button>
                                </div>
                                <div class="meta">
                                    ID: <?= (int) $i['merch_id'] ?> |
                                    Created: <?= htmlspecialchars((string) $i['created_at'], ENT_QUOTES, 'UTF-8') ?> |
                                    Updated: <?= htmlspecialchars((string) $i['updated_at'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="manage_merch.php" onsubmit="return confirm('Are you sure you want to delete this merch item?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $i['merch_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bulk-save-bar">
                <button type="submit" form="bulk-update-form">Save All Merch Changes</button>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
