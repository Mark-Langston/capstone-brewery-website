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

function setFlash(string $message, string $type = 'success'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlash(): ?array {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request token.', 'error');
        header('Location: manage_map.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $beersSold = trim($_POST['beers_sold'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');

        if ($name === '' || $address === '') {
            setFlash('Name and address are required.', 'error');
            header('Location: manage_map.php');
            exit;
        }

        if ($latitude !== '' && !is_numeric($latitude)) {
            setFlash('Latitude must be numeric.', 'error');
            header('Location: manage_map.php');
            exit;
        }

        if ($longitude !== '' && !is_numeric($longitude)) {
            setFlash('Longitude must be numeric.', 'error');
            header('Location: manage_map.php');
            exit;
        }

        $latValue = $latitude === '' ? null : $latitude;
        $lngValue = $longitude === '' ? null : $longitude;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO map_locations (
                    name,
                    address,
                    beers_sold,
                    latitude,
                    longitude,
                    created_at,
                    updated_at
                ) VALUES (
                    :name,
                    :address,
                    :beers_sold,
                    :latitude,
                    :longitude,
                    NOW(),
                    NOW()
                )
            ");

            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':address', $address, PDO::PARAM_STR);
            $stmt->bindValue(':beers_sold', $beersSold !== '' ? $beersSold : null, $beersSold !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':latitude', $latValue, $latValue !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':longitude', $lngValue, $lngValue !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();

            $id = (int) $pdo->lastInsertId();

            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'CREATE', 'name', null, $name);
            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'CREATE', 'address', null, $address);
            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'CREATE', 'beers_sold', null, $beersSold !== '' ? $beersSold : null);
            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'CREATE', 'latitude', null, $latValue);
            writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'CREATE', 'longitude', null, $lngValue);

            setFlash('Map location created successfully.', 'success');
        } catch (Throwable $e) {
            error_log('manage_map create failed: ' . $e->getMessage());
            setFlash('Error creating map location.', 'error');
        }

        header('Location: manage_map.php');
        exit;
    }

    if ($action === 'bulk_update') {
        $ids = $_POST['id'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            setFlash('No map locations were submitted.', 'error');
            header('Location: manage_map.php');
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
                $address = trim($_POST['address'][$id] ?? '');
                $beersSold = trim($_POST['beers_sold'][$id] ?? '');
                $latitude = trim($_POST['latitude'][$id] ?? '');
                $longitude = trim($_POST['longitude'][$id] ?? '');

                if ($name === '' || $address === '') {
                    throw new RuntimeException('Name and address are required for every map location.');
                }

                if ($latitude !== '' && !is_numeric($latitude)) {
                    throw new RuntimeException('Latitude must be numeric for every map location.');
                }

                if ($longitude !== '' && !is_numeric($longitude)) {
                    throw new RuntimeException('Longitude must be numeric for every map location.');
                }

                $latValue = $latitude === '' ? null : $latitude;
                $lngValue = $longitude === '' ? null : $longitude;
                $beersSoldValue = $beersSold !== '' ? $beersSold : null;

                $oldStmt = $pdo->prepare("
                    SELECT *
                    FROM map_locations
                    WHERE map_location_id = :id
                    LIMIT 1
                ");
                $oldStmt->execute([':id' => $id]);
                $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if (!$old) {
                    throw new RuntimeException('One or more map locations could not be found.');
                }

                $updateStmt = $pdo->prepare("
                    UPDATE map_locations
                    SET
                        name = :name,
                        address = :address,
                        beers_sold = :beers_sold,
                        latitude = :latitude,
                        longitude = :longitude,
                        updated_at = NOW()
                    WHERE map_location_id = :id
                ");

                $updateStmt->bindValue(':name', $name, PDO::PARAM_STR);
                $updateStmt->bindValue(':address', $address, PDO::PARAM_STR);
                $updateStmt->bindValue(':beers_sold', $beersSoldValue, $beersSoldValue !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateStmt->bindValue(':latitude', $latValue, $latValue !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateStmt->bindValue(':longitude', $lngValue, $lngValue !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $updateStmt->bindValue(':id', $id, PDO::PARAM_INT);
                $updateStmt->execute();

                $itemChanged = false;

                if ((string) $old['name'] !== $name) {
                    writeAuditLog($pdo, $actingUserId, 'map_location', $id, 'UPDATE', 'name', (string) $old['name'], $name);
                    $itemChanged = true;
                    $changedFields++;
                }

                if ((string) $old['address'] !== $address) {
                    writeAuditLog($pdo, $actingUserId, 'map_location', $id, 'UPDATE', 'address', (string) $old['address'], $address);
                    $itemChanged = true;
                    $changedFields++;
                }

                if ((string) ($old['beers_sold'] ?? '') !== (string) ($beersSoldValue ?? '')) {
                    writeAuditLog($pdo, $actingUserId, 'map_location', $id, 'UPDATE', 'beers_sold', $old['beers_sold'] ?: null, $beersSoldValue);
                    $itemChanged = true;
                    $changedFields++;
                }

                if ((string) ($old['latitude'] ?? '') !== (string) ($latValue ?? '')) {
                    writeAuditLog($pdo, $actingUserId, 'map_location', $id, 'UPDATE', 'latitude', $old['latitude'] !== null ? (string) $old['latitude'] : null, $latValue);
                    $itemChanged = true;
                    $changedFields++;
                }

                if ((string) ($old['longitude'] ?? '') !== (string) ($lngValue ?? '')) {
                    writeAuditLog($pdo, $actingUserId, 'map_location', $id, 'UPDATE', 'longitude', $old['longitude'] !== null ? (string) $old['longitude'] : null, $lngValue);
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
                setFlash("Saved {$changedFields} {$fieldLabel} across {$updatedItems} map {$itemLabel}.", 'success');
            } else {
                setFlash('No changes were detected.', 'success');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('manage_map bulk update failed: ' . $e->getMessage());
            setFlash($e instanceof RuntimeException ? $e->getMessage() : 'Error updating map locations.', 'error');
        }

        header('Location: manage_map.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            setFlash('Invalid map location.', 'error');
            header('Location: manage_map.php');
            exit;
        }

        try {
            $oldStmt = $pdo->prepare("SELECT * FROM map_locations WHERE map_location_id = ?");
            $oldStmt->execute([$id]);
            $old = $oldStmt->fetch();

            if ($old) {
                $pdo->prepare("DELETE FROM map_locations WHERE map_location_id = ?")->execute([$id]);

                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'DELETE', 'name', (string) $old['name'], null);
                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'DELETE', 'address', (string) $old['address'], null);
                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'DELETE', 'beers_sold', $old['beers_sold'] ?: null, null);
                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'DELETE', 'latitude', $old['latitude'] !== null ? (string) $old['latitude'] : null, null);
                writeAuditLog($pdo, (int) $_SESSION['user_id'], 'map_location', $id, 'DELETE', 'longitude', $old['longitude'] !== null ? (string) $old['longitude'] : null, null);
            }

            setFlash('Map location deleted successfully.', 'success');
        } catch (Throwable $e) {
            error_log('manage_map delete failed: ' . $e->getMessage());
            setFlash('Error deleting map location.', 'error');
        }

        header('Location: manage_map.php');
        exit;
    }
}

$items = $pdo->query("
    SELECT *
    FROM map_locations
    ORDER BY created_at DESC, map_location_id DESC
")->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Map</title>
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

        form.map-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .map-form {
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

        .map-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 18px;
        }

        .map-item-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 18px;
        }

        .map-preview {
            width: 100%;
            min-height: 110px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
            margin-bottom: 12px;
            padding: 12px;
            box-sizing: border-box;
        }

        .preview-title {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .preview-row {
            font-size: 13px;
            color: #555;
            margin-bottom: 5px;
            word-break: break-word;
        }

        .meta {
            font-size: 12px;
            color: #777;
            margin-top: 8px;
        }

        .map-actions {
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

        @media (max-width: 900px) {
            .map-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            form.map-form,
            .map-form {
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
        <a class="btn btn-primary" href="AdminDashboard.php">← Admin Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="section-card">
        <h1>Manage Map</h1>
        <p>Add locations that carry Main Channel Brewing beers. Latitude and longitude are optional now, but ready for Leaflet integration later.</p>
    </div>

    <div class="section-card">
        <h2>Add New Map Location</h2>

        <form class="map-form" method="POST" action="manage_map.php">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="name">Location Name</label>
                <input type="text" id="name" name="name" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" maxlength="500" required>
            </div>

            <div class="form-group full-width">
                <label for="beers_sold">Beers Sold at This Location</label>
                <textarea id="beers_sold" name="beers_sold" placeholder="Example: Dockside Lager, Harbor IPA, Sunset Wheat"></textarea>
            </div>

            <div class="form-group">
                <label for="latitude">Latitude</label>
                <input type="text" id="latitude" name="latitude" maxlength="50" placeholder="Example: 38.581572">
            </div>

            <div class="form-group">
                <label for="longitude">Longitude</label>
                <input type="text" id="longitude" name="longitude" maxlength="50" placeholder="Example: -121.494400">
            </div>

            <div class="form-group full-width">
                <button type="submit">Save Map Location</button>
            </div>
        </form>
    </div>

    <div class="section-card">
        <h2>All Map Locations</h2>

        <?php if (empty($items)): ?>
            <p>No map locations found.</p>
        <?php else: ?>
            <form id="bulk-update-form" method="POST" action="manage_map.php" style="display:none;">
                <input type="hidden" name="action" value="bulk_update">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            </form>

            <div class="map-grid">
                <?php foreach ($items as $item): ?>
                    <div class="map-item-card">
                        <div class="map-preview">
                            <div class="preview-title"><?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="preview-row"><strong>Address:</strong> <?= htmlspecialchars((string) $item['address'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="preview-row"><strong>Beers Sold:</strong> <?= htmlspecialchars((string) ($item['beers_sold'] ?? 'None listed'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="preview-row"><strong>Latitude:</strong> <?= htmlspecialchars((string) ($item['latitude'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="preview-row"><strong>Longitude:</strong> <?= htmlspecialchars((string) ($item['longitude'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="map-form">
                            <input type="hidden" form="bulk-update-form" name="id[]" value="<?= (int) $item['map_location_id'] ?>">

                            <div class="form-group">
                                <label>Location Name</label>
                                <input
                                    type="text"
                                    form="bulk-update-form"
                                    name="name[<?= (int) $item['map_location_id'] ?>]"
                                    maxlength="255"
                                    value="<?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <input
                                    type="text"
                                    form="bulk-update-form"
                                    name="address[<?= (int) $item['map_location_id'] ?>]"
                                    maxlength="500"
                                    value="<?= htmlspecialchars((string) $item['address'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="form-group full-width">
                                <label>Beers Sold at This Location</label>
                                <textarea
                                    form="bulk-update-form"
                                    name="beers_sold[<?= (int) $item['map_location_id'] ?>]"
                                ><?= htmlspecialchars((string) ($item['beers_sold'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Latitude</label>
                                <input
                                    type="text"
                                    form="bulk-update-form"
                                    name="latitude[<?= (int) $item['map_location_id'] ?>]"
                                    maxlength="50"
                                    value="<?= htmlspecialchars((string) ($item['latitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label>Longitude</label>
                                <input
                                    type="text"
                                    form="bulk-update-form"
                                    name="longitude[<?= (int) $item['map_location_id'] ?>]"
                                    maxlength="50"
                                    value="<?= htmlspecialchars((string) ($item['longitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>

                            <div class="form-group full-width">
                                <div class="map-actions">
                                    <button type="submit" form="bulk-update-form">Save Changes</button>
                                </div>
                                <div class="meta">
                                    ID: <?= (int) $item['map_location_id'] ?> |
                                    Created: <?= htmlspecialchars((string) $item['created_at'], ENT_QUOTES, 'UTF-8') ?> |
                                    Updated: <?= htmlspecialchars((string) $item['updated_at'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="manage_map.php" onsubmit="return confirm('Are you sure you want to delete this map location?');" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $item['map_location_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="delete-btn" title="Delete Map Location" aria-label="Delete Map Location">🗑</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bulk-save-bar">
                <button type="submit" form="bulk-update-form">Save All Map Changes</button>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
