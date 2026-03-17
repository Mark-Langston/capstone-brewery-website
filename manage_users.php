<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

/*
|--------------------------------------------------------------------------
| AUTHORIZATION
|--------------------------------------------------------------------------
| $_SESSION['user_id']
| $_SESSION['role']
*/
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #111827;
                color: #f9fafb;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }

            .denied-card {
                background: #1f2937;
                border: 1px solid #374151;
                border-radius: 14px;
                padding: 32px;
                max-width: 480px;
                width: 90%;
                text-align: center;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            }

            .btn {
                display: inline-block;
                margin-top: 18px;
                background: #2563eb;
                color: #fff;
                text-decoration: none;
                padding: 12px 18px;
                border-radius: 10px;
                font-weight: 600;
            }

            .btn:hover {
                background: #1d4ed8;
            }
        </style>
    </head>
    <body>
        <div class="denied-card">
            <h1>Access Denied</h1>
            <p>You must be logged in as a superadmin to view this page.</p>
            <a class="btn" href="AdminDashboard.php">Admin Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit;
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
| FLASH MESSAGE HELPERS
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
| inventory_id is nullable here because user-management actions are not tied
| to inventory records.
*/
function writeAuditLog(
    PDO $pdo,
    int $actingUserId,
    string $actionType,
    string $fieldChanged,
    ?string $oldValue,
    ?string $newValue,
    ?int $inventoryId = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (
            user_id,
            inventory_id,
            action_type,
            field_changed,
            old_value,
            new_value,
            change_timestamp
        ) VALUES (
            :user_id,
            :inventory_id,
            :action_type,
            :field_changed,
            :old_value,
            :new_value,
            NOW()
        )
    ");

    $stmt->bindValue(':user_id', $actingUserId, PDO::PARAM_INT);

    if ($inventoryId === null) {
        $stmt->bindValue(':inventory_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':inventory_id', $inventoryId, PDO::PARAM_INT);
    }

    $stmt->bindValue(':action_type', $actionType, PDO::PARAM_STR);
    $stmt->bindValue(':field_changed', $fieldChanged, PDO::PARAM_STR);

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
    $role = trim($_POST['role'] ?? '');

    $allowedRoles = ['superadmin', 'admin'];

    if ($email === '' || $password === '' || $firstName === '' || $lastName === '' || $role === '') {
        setFlash('All fields are required.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('Please enter a valid email address.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    if (!in_array($role, $allowedRoles, true)) {
        setFlash('Invalid role selected.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    try {
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);

        if ($checkStmt->fetch()) {
            setFlash('A user with that email already exists.', 'error');
            header('Location: manage_users.php');
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

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
            ':role' => $role
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        writeAuditLog(
            $pdo,
            (int)$_SESSION['user_id'],
            'CREATE_USER',
            'users',
            null,
            "Created user_id={$newUserId}, email={$email}, first_name={$firstName}, last_name={$lastName}, role={$role}"
        );

        setFlash('User created successfully.', 'success');
        header('Location: manage_users.php');
        exit;
    } catch (PDOException $e) {
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

    $deleteUserId = isset($_POST['delete_user_id']) ? (int)$_POST['delete_user_id'] : 0;

    if ($deleteUserId <= 0) {
        setFlash('Invalid user selected.', 'error');
        header('Location: manage_users.php');
        exit;
    }

    if ($deleteUserId === (int)$_SESSION['user_id']) {
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
        $userToDelete = $userStmt->fetch();

        if (!$userToDelete) {
            setFlash('User not found.', 'error');
            header('Location: manage_users.php');
            exit;
        }

        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
        $deleteStmt->execute([':user_id' => $deleteUserId]);

        writeAuditLog(
            $pdo,
            (int)$_SESSION['user_id'],
            'DELETE_USER',
            'users',
            "Deleted user_id={$userToDelete['user_id']}, email={$userToDelete['email']}, first_name={$userToDelete['first_name']}, last_name={$userToDelete['last_name']}, role={$userToDelete['role']}",
            null
        );

        setFlash('User deleted successfully.', 'success');
        header('Location: manage_users.php');
        exit;
    } catch (PDOException $e) {
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
$users = $usersStmt->fetchAll();

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
            margin: 0;
            font-family: Arial, sans-serif;
            background: #111827;
            color: #f9fafb;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        h1, h2, h3, p {
            margin-top: 0;
        }

        .top-bar {
            margin-bottom: 24px;
        }

        .btn,
        button,
        .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s ease, transform 0.1s ease;
        }

        .btn:hover,
        button:hover,
        .delete-btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-success {
            background: #059669;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #047857;
        }

        .delete-btn {
            background: #dc2626;
            color: #ffffff;
            width: 44px;
            height: 44px;
            padding: 0;
            font-size: 18px;
            border-radius: 12px;
        }

        .delete-btn:hover {
            background: #b91c1c;
        }

        .section-card {
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
        }

        .section-title {
            margin-bottom: 18px;
            font-size: 24px;
        }

        .flash {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .flash.success {
            background: rgba(5, 150, 105, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #d1fae5;
        }

        .flash.error {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(248, 113, 113, 0.4);
            color: #fee2e2;
        }

        form.user-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #e5e7eb;
        }

        input,
        select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #4b5563;
            background: #111827;
            color: #f9fafb;
            font-size: 14px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.18);
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }

        .user-card {
            background: #111827;
            border: 1px solid #374151;
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .user-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .user-email {
            font-size: 17px;
            font-weight: 700;
            word-break: break-word;
            color: #ffffff;
        }

        .user-detail {
            color: #d1d5db;
            font-size: 14px;
            line-height: 1.5;
        }

        .user-role {
            display: inline-block;
            margin-top: 6px;
            background: #1e3a8a;
            color: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .empty-state {
            color: #d1d5db;
            font-size: 15px;
        }

        .meta {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 4px;
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
            <a class="btn btn-primary" href="AdminDashboard.php">← Admin Dashboard</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

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
                        <option value="superadmin">superadmin</option>
                        <option value="admin">admin</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <button type="submit" class="btn btn-success">Save User</button>
                </div>
            </form>
        </div>

        <div class="section-card">
            <h2 class="section-title">All Users</h2>

            <?php if (empty($users)): ?>
                <p class="empty-state">No users found.</p>
            <?php else: ?>
                <div class="users-grid">
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <div class="user-card-header">
                                <div>
                                    <div class="user-email">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                    <div class="meta">
                                        User ID: <?php echo (int)$user['user_id']; ?>
                                    </div>
                                </div>

                                <?php if ((int)$user['user_id'] !== (int)$_SESSION['user_id']): ?>
                                    <form method="POST" action="manage_users.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="delete_user_id" value="<?php echo (int)$user['user_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="delete-btn" title="Delete User" aria-label="Delete User">🗑</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="user-detail">
                                <strong>First Name:</strong>
                                <?php echo htmlspecialchars($user['first_name']); ?>
                            </div>

                            <div class="user-detail">
                                <strong>Last Name:</strong>
                                <?php echo htmlspecialchars($user['last_name']); ?>
                            </div>

                            <div class="user-detail">
                                <strong>Role:</strong><br>
                                <span class="user-role"><?php echo htmlspecialchars($user['role']); ?></span>
                            </div>

                            <div class="user-detail">
                                <strong>Created:</strong>
                                <?php echo htmlspecialchars((string)$user['created_at']); ?>
                            </div>

                            <div class="user-detail">
                                <strong>Updated:</strong>
                                <?php echo htmlspecialchars((string)$user['updated_at']); ?>
                            </div>

                            <?php if ((int)$user['user_id'] === (int)$_SESSION['user_id']): ?>
                                <div class="meta">You are currently logged in as this user.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
