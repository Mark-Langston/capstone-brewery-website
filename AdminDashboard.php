<?php
declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Role Validation
|--------------------------------------------------------------------------
*/
$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

/*
|--------------------------------------------------------------------------
| User Display Name
|--------------------------------------------------------------------------
*/
$firstName = $_SESSION['first_name'] ?? '';
$email = $_SESSION['email'] ?? '';

/*
|--------------------------------------------------------------------------
| Navigation by Role
|--------------------------------------------------------------------------
*/
$commonLinks = [
    [
        'href' => 'manage_inventory.php',
        'label' => 'Manage Inventory'
    ],
    [
        'href' => 'manage_seasonal_menu.php',
        'label' => 'Manage Seasonal Inventory'
    ],
    [
        'href' => 'manage_map.php',
        'label' => 'Manage Map'
    ],
    [
        'href' => 'manage_merch.php',
        'label' => 'Manage Merch'
    ]
];

$superAdminOnlyLinks = [
    [
        'href' => 'audit_log.php',
        'label' => 'Audit Log'
    ],
    [
        'href' => 'manage_users.php',
        'label' => 'Manage Users'
    ]
];

$visibleLinks = $commonLinks;

if ($role === 'superadmin') {
    $visibleLinks = array_merge($superAdminOnlyLinks, $commonLinks);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Main Channel Brewing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.10);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .user-info {
            margin-bottom: 30px;
            color: #555;
        }

        .role-badge {
            display: inline-block;
            margin-left: 8px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #222;
            color: #fff;
            font-size: 12px;
            vertical-align: middle;
            text-transform: capitalize;
        }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
        }

        .dashboard-link {
            display: block;
            text-decoration: none;
            background: #222;
            color: #fff;
            padding: 18px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .dashboard-link:hover {
            background: #444;
        }

        .logout {
            margin-top: 30px;
        }

        .logout a {
            color: #b00020;
            text-decoration: none;
            font-weight: bold;
        }

        .logout a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>
        Admin Dashboard
        <span class="role-badge"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
    </h1>

    <div class="user-info">
        Welcome,
        <strong><?= htmlspecialchars($firstName !== '' ? $firstName : $email, ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if ($email !== ''): ?>
            (<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>)
        <?php endif; ?>
    </div>

    <div class="links-grid">
        <?php foreach ($visibleLinks as $link): ?>
            <a class="dashboard-link" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="logout">
        <a href="/logout.php">Log Out</a>
    </div>
</div>

</body>
</html>
