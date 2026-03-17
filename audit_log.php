<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

/*
|--------------------------------------------------------------------------
| AUTHORIZATION (SUPERADMIN ONLY)
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    http_response_code(403);
    exit('Access denied.');
}

/*
|--------------------------------------------------------------------------
| FETCH AUDIT LOG
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT 
        a.audit_id,
        a.user_id,
        u.email,
        a.entity_type,
        a.entity_id,
        a.action_type,
        a.field_changed,
        a.old_value,
        a.new_value,
        a.change_timestamp
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.user_id
    ORDER BY a.change_timestamp DESC
");

$auditLogs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Main Channel Brewing</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
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

        .btn {
            display: inline-block;
            text-decoration: none;
            background: #222;
            color: #fff;
            padding: 12px 18px;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        th {
            background: #fafafa;
            font-weight: bold;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .small-text {
            font-size: 12px;
            color: #666;
        }

        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #222;
            color: #fff;
            font-size: 11px;
        }

        .value-box {
            max-width: 250px;
            word-wrap: break-word;
        }

        @media (max-width: 900px) {
            table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="top-bar">
        <a class="btn" href="AdminDashboard.php">← Admin Dashboard</a>
    </div>

    <div class="section-card">
        <h1>Audit Log</h1>
        <p>View all system activity including user actions, inventory changes, and system events.</p>
    </div>

    <div class="section-card">
        <h2>All Activity</h2>

        <?php if (empty($auditLogs)): ?>
            <p>No audit records found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Entity</th>
                        <th>Action</th>
                        <th>Field</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($log['change_timestamp']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($log['email'] ?? 'Unknown') ?>
                                <div class="small-text">ID: <?= (int)$log['user_id'] ?></div>
                            </td>

                            <td>
                                <span class="pill">
                                    <?= htmlspecialchars($log['entity_type']) ?>
                                </span>
                                <div class="small-text">ID: <?= (int)$log['entity_id'] ?></div>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($log['action_type']) ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($log['field_changed'] ?? '-') ?>
                            </td>

                            <td class="value-box">
                                <?= htmlspecialchars($log['old_value'] ?? '-') ?>
                            </td>

                            <td class="value-box">
                                <?= htmlspecialchars($log['new_value'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

</div>

</body>
</html>
