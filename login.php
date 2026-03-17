<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: AdminDashboard.php');
    exit;
}

require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('
            SELECT user_id, email, password_hash, first_name, last_name, role
            FROM users
            WHERE email = :email
            LIMIT 1
        ');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'] ?? '';
            $_SESSION['last_name'] = $user['last_name'] ?? '';
            $_SESSION['role'] = $user['role'];

            // Initialize CSRF token for authenticated actions
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Optional: write login event to audit_log
            try {
                $auditStmt = $pdo->prepare("
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

                $auditStmt->bindValue(':user_id', (int)$user['user_id'], PDO::PARAM_INT);
                $auditStmt->bindValue(':inventory_id', null, PDO::PARAM_NULL);
                $auditStmt->bindValue(':action_type', 'LOGIN', PDO::PARAM_STR);
                $auditStmt->bindValue(':field_changed', 'users', PDO::PARAM_STR);
                $auditStmt->bindValue(':old_value', null, PDO::PARAM_NULL);
                $auditStmt->bindValue(':new_value', 'Successful login for ' . $user['email'], PDO::PARAM_STR);
                $auditStmt->execute();
            } catch (PDOException $e) {
                // Do not block login if audit logging fails
            }

            header('Location: AdminDashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
        }

        .login-container {
            max-width: 400px;
            margin: 80px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        h2 {
            margin-top: 0;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            cursor: pointer;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Admin Login</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
