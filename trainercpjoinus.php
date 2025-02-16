<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND role = 'trainer'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$trainer = $result->fetch_assoc()['username'];

$requests_stmt = $conn->prepare("SELECT idjoinus, username, message FROM joinus WHERE trainer = ?");
$requests_stmt->bind_param('s', $trainer);
$requests_stmt->execute();
$requests = $requests_stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = intval($_POST['idjoinus']);
    $requested_user = trim($_POST['username']);
    $action = $_POST['action']; 

    if ($action === "accept") {
        $update_user_stmt = $conn->prepare("UPDATE users SET trainer = ? WHERE username = ?");
        $update_user_stmt->bind_param('ss', $trainer, $requested_user);

        if ($update_user_stmt->execute()) {
            $delete_stmt = $conn->prepare("DELETE FROM joinus WHERE idjoinus = ?");
            $delete_stmt->bind_param('i', $request_id);
            $delete_stmt->execute();
            header("Location: trainercpjoinus.php?success=accepted");
            exit;
        }
    } elseif ($action === "reject") {
        $delete_stmt = $conn->prepare("DELETE FROM joinus WHERE idjoinus = ?");
        $delete_stmt->bind_param('i', $request_id);

        if ($delete_stmt->execute()) {
            header("Location: trainercpjoinus.php?success=rejected");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Control Panel - Join Requests</title>
    <link rel="stylesheet" href="styles/trainerjoinuscpstyle.css">
</head>
<body>
    <nav>
        <h1>Trainer Panel</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="trainer-container">
        <h2>Join Requests</h2>

        <?php if (isset($_GET['success'])): ?>
            <p class="success">
                <?= ($_GET['success'] == "accepted") ? "✅ User successfully assigned!" : "❌ Request rejected!" ?>
            </p>
        <?php endif; ?>

        <?php if ($requests->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Message</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($request = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['username']); ?></td>
                                <td><?= nl2br(htmlspecialchars($request['message'])); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="idjoinus" value="<?= $request['idjoinus']; ?>">
                                        <input type="hidden" name="username" value="<?= htmlspecialchars($request['username']); ?>">
                                        <button type="submit" name="action" value="accept" class="btn-primary">Accept</button>
                                        <button type="submit" name="action" value="reject" class="btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No join requests available.</p>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?= date("Y"); ?> Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
