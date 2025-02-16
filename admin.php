<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = strtolower($user['role']);
    
    if ($role !== 'admin') {
        header("Location: index.php");
        exit;
    }
} else {
    session_destroy();
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, username, email, role, trainer FROM users ORDER BY id ASC");
$stmt->execute();
$users = $stmt->get_result();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $edit_id = intval($_POST['id']);
    $edit_username = trim($_POST['username']);
    $edit_email = trim($_POST['email']);
    $edit_role = trim($_POST['role']);
    $edit_trainer = trim($_POST['trainer']);

    if (!empty($edit_username) && !empty($edit_email) && !empty($edit_role)) {
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, trainer = ? WHERE id = ?");
        $update_stmt->bind_param('ssssi', $edit_username, $edit_email, $edit_role, $edit_trainer, $edit_id);
        
        if ($update_stmt->execute()) {
            header("Location: admin.php?success=1");
            exit;
        } else {
            $error_message = "Update failed!";
        }
    } else {
        $error_message = "All fields are required!";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $delete_id = intval($_POST['id']);

    if ($delete_id == $user_id) {
        $error_message = "You cannot delete yourself!";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param('i', $delete_id);

        if ($delete_stmt->execute()) {
            header("Location: admin.php?deleted=1");
            exit;
        } else {
            $error_message = "Failed to delete user!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles/admin.css">
    <script>
        function confirmEdit() {
            return confirm("Are you sure you want to update this user?");
        }

        function confirmDelete() {
            return confirm("⚠️ Are you sure you want to delete this user? This action cannot be undone!");
        }
    </script>
</head>
<body>

    <nav>
        <h1>Admin Panel</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="admincontainer">
        <h2>Manage Users</h2>

        <?php if (isset($_GET['success'])): ?>
            <p class="success">✅ User updated successfully!</p>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <p class="error">❌ User deleted successfully!</p>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Trainer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <form method="POST" action="" onsubmit="return confirmEdit();">
                            <input type="hidden" name="id" value="<?= $user['id']; ?>">
                            <td><?= $user['id']; ?></td>
                            <td><input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required></td>
                            <td><input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required></td>
                            <td>
                                <select name="role">
                                    <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="member" <?= ($user['role'] === 'member') ? 'selected' : ''; ?>>Member</option>
                                    <option value="trainer" <?= ($user['role'] === 'trainer') ? 'selected' : ''; ?>>Trainer</option>
                                </select>
                            </td>
                            <td><input type="text" name="trainer" value="<?= htmlspecialchars($user['trainer']); ?>"></td>
                            <td>
                                <div class="button-group">
                                    <button type="submit" name="update_user" class="btn-primary">Update</button>
                        </form>
                                    <?php if ($user['id'] != $user_id): ?>
                                        <form method="POST" action="" onsubmit="return confirmDelete();">
                                            <input type="hidden" name="id" value="<?= $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <button disabled class="btn-disabled">Can't Delete</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date("Y"); ?> Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
