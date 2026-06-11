<?php
require 'config.php';
require_once 'log_helper.php';

$users = $conn->query("SELECT id, username, role FROM user WHERE active = 0 ORDER BY id");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>ผู้ใช้ที่ถูกปิดการใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-4" style="max-width: 900px;">
        <h3 class="mb-4">🚫 ผู้ใช้ที่ถูกปิดการใช้งาน</h3>

        <table class="table table-bordered table-striped bg-white">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= $u['id'] ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($u['username']) ?>
                        </td>
                        <td>
                            <?= $u['role'] ?>
                        </td>
                        <td>
                            <a href="admin.php?action=activate&id=<?= $u['id'] ?>" class="btn btn-sm btn-success">
                                ✔ เปิดการใช้งาน
                            </a>
                        </td>
                    </tr>
                <?php endwhile ?>
            </tbody>
        </table>

        <a href="admin.php" class="btn btn-secondary mt-4">⬅️ กลับหน้า Admin</a>
    </div>
</body>

</html>