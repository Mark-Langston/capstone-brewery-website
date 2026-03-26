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

if ($role !== 'superadmin') {
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

    $stmt->bindValue(':action_type', $actionType, PDO::PARAM_STR);

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

    $stmt->execute();
}

/*
|--------------------------------------------------------------------------
| HANDLE CREATE USER
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request token.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $selectedRole = trim($_POST['role'] ?? '');

    $allowedRoles = ['superadmin', 'admin'];

    if ($email === '' || $password === '' || $firstName === '' || $lastName === '' || $selectedRole === '') {
        setFlash('All fields are required.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('Please enter a valid email address.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    if (!in_array($selectedRole, $allowedRoles, true)) {
        setFlash('Invalid role selected.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    if (strlen($password) < 8) {
        setFlash('Password must be at least 8 characters long.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    try {
        $checkStmt = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $checkStmt->execute([':email' => $email]);

        if ($checkStmt->fetch()) {
            setFlash('A user with that email already exists.', 'error');
            header('Location: manage_users.php');
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare("
            INSERT INTO users (
                email,
                password_hash,
                first_name,
                last_name,
                role,
                created_at,
                updated_at
            ) VALUES (
                :email,
                :password_hash,
                :first_name,
                :last_name,
                :role,
                NOW(),
                NOW()
            )
        ");

        $insertStmt->execute([
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':role' => $selectedRole,
        ]);

        $newUserId = (int) $pdo->lastInsertId();
        $actingUserId = (int) $_SESSION['user_id'];

        writeAuditLog($pdo, $actingUserId, 'users', $newUserId, 'CREATE', 'email', null, $email);
        writeAuditLog($pdo, $actingUserId, 'users', $newUserId, 'CREATE', 'first_name', null, $firstName);
        writeAuditLog($pdo, $actingUserId, 'users', $newUserId, 'CREATE', 'last_name', null, $lastName);
        writeAuditLog($pdo, $actingUserId, 'users', $newUserId, 'CREATE', 'role', null, $selectedRole);

        $pdo->commit();

        setFlash('User created successfully.', 'success');
        header('Location: manage_users.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        setFlash('Error creating user.', 'error');
        header('Location: manage_users.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| HANDLE DELETE USER
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request token.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    $deleteUserId = isset($_POST['delete_user_id']) ? (int) $_POST['delete_user_id'] : 0;

    if ($deleteUserId <= 0) {
        setFlash('Invalid user selected.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    if ($deleteUserId === (int) $_SESSION['user_id']) {
        setFlash('You cannot delete your own currently logged-in account.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    try {
        $userStmt = $pdo->prepare("
            SELECT user_id, email, first_name, last_name, role
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $userStmt->execute([':user_id' => $deleteUserId]);
        $userToDelete = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$userToDelete) {
            setFlash('User not found.', 'error');
            header('Location: manage_users.php');
            exit;
        }

        $pdo->beginTransaction();

        $deleteStmt = $pdo->prepare("
            DELETE FROM users
            WHERE user_id = :user_id
        ");
        $deleteStmt->execute([':user_id' => $deleteUserId]);

        $actingUserId = (int) $_SESSION['user_id'];

        writeAuditLog($pdo, $actingUserId, 'users', $deleteUserId, 'DELETE', 'email', (string) $userToDelete['email'], null);
        writeAuditLog($pdo, $actingUserId, 'users', $deleteUserId, 'DELETE', 'first_name', (string) $userToDelete['first_name'], null);
        writeAuditLog($pdo, $actingUserId, 'users', $deleteUserId, 'DELETE', 'last_name', (string) $userToDelete['last_name'], null);
        writeAuditLog($pdo, $actingUserId, 'users', $deleteUserId, 'DELETE', 'role', (string) $userToDelete['role'], null);

        $pdo->commit();

        setFlash('User deleted successfully.', 'success');
        header('Location: manage_users.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        setFlash('Error deleting user.', 'error');
        header('Location: manage_users.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/
$usersStmt = $pdo->query("
    SELECT user_id, email, first_name, last_name, role, created_at, updated_at
    FROM users
    ORDER BY created_at DESC, user_id DESC
");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <style>
        * {
            box-sizing: border-box;
        }

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

        .section-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.10);
            margin-bottom: 25px;
        }

        h1,
        h2 {
            margin-top: 0;
        }

        .section-title {
            margin-bottom: 12px;
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
            width: 100%;
            margin-top: 12px;
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

        form.user-form {
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
        select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .user-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
        }

        .user-email {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .user-role {
            display: inline-block;
            margin-top: 8px;
            margin-bottom: 12px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #222;
            color: #fff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .user-detail {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .meta {
            font-size: 12px;
            color: #777;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            form.user-form {
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
            <h1 class="section-title">Manage Users</h1>
            <p>Create new users and remove existing users. Only superadmins can access this page.</p>
        </div>

        <div class="section-card">
            <h2 class="section-title">Add New User</h2>

            <form class="user-form" method="POST" action="manage_users.php" autocomplete="off">
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group full-width">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        maxlength="255"
                        placeholder="user@example.com"
                    >
                </div>

                <div class="form-group full-width">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        placeholder="Enter password"
                    >
                </div>

                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        required
                        maxlength="100"
                        placeholder="First name"
                    >
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        required
                        maxlength="100"
                        placeholder="Last name"
                    >
                </div>

                <div class="form-group full-width">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select a role</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <button type="submit">Save User</button>
                </div>
            </form>
        </div>

        <div class="section-card">
            <h2 class="section-title">All Users</h2>

            <?php if (empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <div class="users-grid">
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <div class="user-email">
                                <?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <div class="user-detail">
                                <?= htmlspecialchars((string) $user['first_name'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) $user['last_name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <div class="user-role">
                                <?= htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <div class="meta">
                                ID: <?= (int) $user['user_id'] ?><br>
                                Created: <?= htmlspecialchars((string) $user['created_at'], ENT_QUOTES, 'UTF-8') ?><br>
                                Updated: <?= htmlspecialchars((string) $user['updated_at'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <?php if ((int) $user['user_id'] !== (int) $_SESSION['user_id']): ?>
                                <form method="POST" action="manage_users.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="delete_user_id" value="<?= (int) $user['user_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="delete-btn">Delete User</button>
                                </form>
                            <?php else: ?>
                                <div class="meta">Current logged-in account</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
