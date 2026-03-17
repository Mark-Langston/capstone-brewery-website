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
        'type' => $_SESSION['flash_type'],
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

    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':entity_type', $entityType, PDO::PARAM_STR);

    if ($entityId === null) {
        $stmt->bindValue(':entity_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
    }

    if ($fieldChanged === null) {
        $stmt->bindValue(':field_changed', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':field_changed', $fieldChanged, PDO::PARAM_STR);
    }

    if ($oldValue === null) {
        $stmt->bindValue(':old_value', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':old_value', $oldValue, PDO::PARAM_STR);
    }

    if ($newValue === null) {
        $stmt->bindValue(':new_value', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':new_value', $newValue, PDO::PARAM_STR);
    }

    $stmt->bindValue(':action_type', $actionType, PDO::PARAM_STR);
    $stmt->execute();
}

/*
|--------------------------------------------------------------------------
| IMAGE HELPERS
|--------------------------------------------------------------------------
*/
function getUploadDirectory(): string
{
    return __DIR__ . '/assets/images/inventory/';
}

function getRelativeImageDirectory(): string
{
    return 'assets/images/inventory/';
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
    $relativeDir = getRelativeImageDirectory();
    $sanitizedName = sanitizeFileName($file['name']);
    $targetPath = $uploadDir . $sanitizedName;
    $relativePath = $relativeDir . $sanitizedName;

    if (file_exists($targetPath)) {
        return [false, $existingImagePath, 'An image with that file name already exists. Please rename the file and try again.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [false, $existingImagePath, 'Failed to save uploaded image.'];
    }

    if ($existingImagePath !== null && $existingImagePath !== '' && $existingImagePath !== $relativePath) {
        $oldFullPath = __DIR__ . '/' . ltrim($existingImagePath, '/');
        if (is_file($oldFullPath)) {
            @unlink($oldFullPath);
        }
    }

    return [true, $relativePath, null];
}

/*
|--------------------------------------------------------------------------
| REQUEST HANDLERS
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request token.', 'error');
        header('Location: manage_inventory.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_inventory') {
        $itemName = trim($_POST['item_name'] ?? '');
        $abv = trim($_POST['abv'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($itemName === '' || $abv === '' || $price === '' || $description === '') {
            setFlash('All fields except image are required.', 'error');
            header('Location: manage_inventory.php');
            exit;
        }

        if (!is_numeric($abv) || !is_numeric($price)) {
            setFlash('ABV and Price must be numeric values.', 'error');
            header('Location: manage_inventory.php');
            exit;
        }

        $imagePath = null;
        if (isset($_FILES['image_path'])) {
            [$success, $newImagePath, $error] = processUploadedImage($_FILES['image_path'], null);
            if (!$success) {
                setFlash($error ?? 'Image upload failed.', 'error');
                header('Location: manage_inventory.php');
                exit;
            }
            $imagePath = $newImagePath;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO inventory (
                    item_name,
                    abv,
                    price,
                    description,
                    image_path,
                    created_at,
                    updated_at
                ) VALUES (
                    :item_name,
                    :abv,
                    :price,
                    :description,
                    :image_path,
                    NOW(),
                    NOW()
                )
            ");

            $stmt->execute([
                ':item_name' => $itemName,
                ':abv' => $abv,
                ':price' => $price,
                ':description' => $description,
                ':image_path' => $imagePath,
            ]);

            $inventoryId = (int) $pdo->lastInsertId();
            $actingUserId = (int) $_SESSION['user_id'];

            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'CREATE', 'item_name', null, $itemName);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'CREATE', 'abv', null, $abv);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'CREATE', 'price', null, $price);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'CREATE', 'description', null, $description);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'CREATE', 'image_path', null, $imagePath);

            setFlash('Inventory item created successfully.', 'success');
        } catch (PDOException $e) {
            if ($imagePath !== null) {
                $newFullPath = __DIR__ . '/' . ltrim($imagePath, '/');
                if (is_file($newFullPath)) {
                    @unlink($newFullPath);
                }
            }
            setFlash('Error creating inventory item.', 'error');
        }

        header('Location: manage_inventory.php');
        exit;
    }

    if ($action === 'update_inventory') {
        $inventoryId = (int) ($_POST['inventory_id'] ?? 0);
        $itemName = trim($_POST['item_name'] ?? '');
        $abv = trim($_POST['abv'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $removeImage = isset($_POST['remove_image']);

        if ($inventoryId <= 0) {
            setFlash('Invalid inventory item.', 'error');
            header('Location: manage_inventory.php');
            exit;
        }

        if ($itemName === '' || $abv === '' || $price === '' || $description === '') {
            setFlash('All fields except image are required.', 'error');
            header('Location: manage_inventory.php');
            exit;
        }

        if (!is_numeric($abv) || !is_numeric($price)) {
            setFlash('ABV and Price must be numeric values.', 'error');
            header('Location: manage_inventory.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT inventory_id, item_name, abv, price, description, image_path
                FROM inventory
                WHERE inventory_id = :inventory_id
                LIMIT 1
            ");
            $stmt->execute([':inventory_id' => $inventoryId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                setFlash('Inventory item not found.', 'error');
                header('Location: manage_inventory.php');
                exit;
            }

            $newImagePath = $existing['image_path'] !== null ? (string) $existing['image_path'] : null;

            if ($removeImage && $newImagePath !== null && $newImagePath !== '') {
                $oldFullPath = __DIR__ . '/' . ltrim($newImagePath, '/');
                if (is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
                $newImagePath = null;
            }

            if (isset($_FILES['image_path']) && ($_FILES['image_path']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                [$success, $processedPath, $error] = processUploadedImage($_FILES['image_path'], $existing['image_path'] ?: null);
                if (!$success) {
                    setFlash($error ?? 'Image upload failed.', 'error');
                    header('Location: manage_inventory.php');
                    exit;
                }
                $newImagePath = $processedPath;
            }

            $updateStmt = $pdo->prepare("
                UPDATE inventory
                SET
                    item_name = :item_name,
                    abv = :abv,
                    price = :price,
                    description = :description,
                    image_path = :image_path,
                    updated_at = NOW()
                WHERE inventory_id = :inventory_id
            ");

            $updateStmt->execute([
                ':item_name' => $itemName,
                ':abv' => $abv,
                ':price' => $price,
                ':description' => $description,
                ':image_path' => $newImagePath,
                ':inventory_id' => $inventoryId,
            ]);

            $actingUserId = (int) $_SESSION['user_id'];
            $changed = false;

            if ((string) $existing['item_name'] !== $itemName) {
                writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'UPDATE', 'item_name', (string) $existing['item_name'], $itemName);
                $changed = true;
            }

            if ((string) $existing['abv'] !== $abv) {
                writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'UPDATE', 'abv', (string) $existing['abv'], $abv);
                $changed = true;
            }

            if ((string) $existing['price'] !== $price) {
                writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'UPDATE', 'price', (string) $existing['price'], $price);
                $changed = true;
            }

            if ((string) $existing['description'] !== $description) {
                writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'UPDATE', 'description', (string) $existing['description'], $description);
                $changed = true;
            }

            if ((string) ($existing['image_path'] ?? '') !== (string) ($newImagePath ?? '')) {
                writeAuditLog(
                    $pdo,
                    $actingUserId,
                    'inventory',
                    $inventoryId,
                    'UPDATE',
                    'image_path',
                    $existing['image_path'] ?: null,
                    $newImagePath
                );
                $changed = true;
            }

            if ($changed) {
                setFlash('Inventory item updated successfully.', 'success');
            } else {
                setFlash('No changes were detected.', 'success');
            }
        } catch (PDOException $e) {
            setFlash('Error updating inventory item.', 'error');
        }

        header('Location: manage_inventory.php');
        exit;
    }

    if ($action === 'delete_inventory') {
        $inventoryId = (int) ($_POST['inventory_id'] ?? 0);

        if ($inventoryId <= 0) {
            setFlash('Invalid inventory item.', 'error');
            header('Location: manage_inventory.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT inventory_id, item_name, abv, price, description, image_path
                FROM inventory
                WHERE inventory_id = :inventory_id
                LIMIT 1
            ");
            $stmt->execute([':inventory_id' => $inventoryId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                setFlash('Inventory item not found.', 'error');
                header('Location: manage_inventory.php');
                exit;
            }

            if (!empty($existing['image_path'])) {
                $oldFullPath = __DIR__ . '/' . ltrim((string) $existing['image_path'], '/');
                if (is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
            }

            $deleteStmt = $pdo->prepare('DELETE FROM inventory WHERE inventory_id = :inventory_id');
            $deleteStmt->execute([':inventory_id' => $inventoryId]);

            $actingUserId = (int) $_SESSION['user_id'];
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'DELETE', 'item_name', (string) $existing['item_name'], null);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'DELETE', 'abv', (string) $existing['abv'], null);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'DELETE', 'price', (string) $existing['price'], null);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'DELETE', 'description', (string) $existing['description'], null);
            writeAuditLog($pdo, $actingUserId, 'inventory', $inventoryId, 'DELETE', 'image_path', $existing['image_path'] ?: null, null);

            setFlash('Inventory item deleted successfully.', 'success');
        } catch (PDOException $e) {
            setFlash('Error deleting inventory item.', 'error');
        }

        header('Location: manage_inventory.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| FETCH INVENTORY
|--------------------------------------------------------------------------
*/
$inventoryStmt = $pdo->query("
    SELECT inventory_id, item_name, abv, price, description, image_path, created_at, updated_at
    FROM inventory
    ORDER BY created_at DESC, inventory_id DESC
");
$inventoryItems = $inventoryStmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - Main Channel Brewing</title>
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

        form.inventory-form {
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

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 18px;
        }

        .inventory-item-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 18px;
        }

        .inventory-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .inventory-image-wrap {
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

        .inventory-image-wrap img {
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
            .inventory-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            form.inventory-form {
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
        <h1>Manage Inventory</h1>
        <p>Add new inventory items, update existing inventory, manage images, and remove items as needed.</p>
    </div>

    <div class="section-card">
        <h2>Add New Inventory</h2>

        <form class="inventory-form" method="POST" action="manage_inventory.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_inventory">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="abv">ABV</label>
                <input type="text" id="abv" name="abv" maxlength="50" placeholder="Example: 6.5" required>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="text" id="price" name="price" maxlength="50" placeholder="Example: 7.00" required>
            </div>

            <div class="form-group">
                <label for="image_path">Image Upload</label>
                <input type="file" id="image_path" name="image_path" accept=".png,.jpg,.jpeg,.gif">
            </div>

            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group full-width">
                <button type="submit">Save Inventory Item</button>
            </div>
        </form>
    </div>

    <div class="section-card">
        <h2>All Inventory</h2>

        <?php if (empty($inventoryItems)): ?>
            <p>No inventory items found.</p>
        <?php else: ?>
            <div class="inventory-grid">
                <?php foreach ($inventoryItems as $item): ?>
                    <div class="inventory-item-card">
                        <div class="inventory-image-wrap">
                            <?php if (!empty($item['image_path']) && is_file(__DIR__ . '/' . ltrim((string) $item['image_path'], '/'))): ?>
                                <img src="<?= htmlspecialchars('/' . ltrim((string) $item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="Inventory image">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>

                        <form class="inventory-form" method="POST" action="manage_inventory.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_inventory">
                            <input type="hidden" name="inventory_id" value="<?= (int) $item['inventory_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                            <div class="form-group">
                                <label>Item Name</label>
                                <input type="text" name="item_name" maxlength="255" value="<?= htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-group">
                                <label>ABV</label>
                                <input type="text" name="abv" maxlength="50" value="<?= htmlspecialchars((string) $item['abv'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Price</label>
                                <input type="text" name="price" maxlength="50" value="<?= htmlspecialchars((string) $item['price'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Replace Image</label>
                                <input type="file" name="image_path" accept=".png,.jpg,.jpeg,.gif">
                                <div class="checkbox-row">
                                    <input type="checkbox" name="remove_image" id="remove_image_<?= (int) $item['inventory_id'] ?>">
                                    <label for="remove_image_<?= (int) $item['inventory_id'] ?>">Remove current image</label>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="description" required><?= htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <div class="inventory-actions">
                                    <button type="submit">Save Changes</button>
                                </div>
                                <div class="meta">
                                    ID: <?= (int) $item['inventory_id'] ?> |
                                    Created: <?= htmlspecialchars((string) $item['created_at'], ENT_QUOTES, 'UTF-8') ?> |
                                    Updated: <?= htmlspecialchars((string) $item['updated_at'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="manage_inventory.php" onsubmit="return confirm('Are you sure you want to delete this inventory item?');" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="delete_inventory">
                            <input type="hidden" name="inventory_id" value="<?= (int) $item['inventory_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="delete-btn" title="Delete Inventory Item" aria-label="Delete Inventory Item">🗑</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
