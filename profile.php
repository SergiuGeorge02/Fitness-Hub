<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, email, role, trainer FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$user = $result->fetch_assoc();
$username = $user['username'];
$email = $user['email'];
$role = ucfirst($user['role']);
$trainer = !empty($user['trainer']) ? $user['trainer'] : "No trainer assigned";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);

    if (!empty($current_password) && !empty($new_password)) {
        $password_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $password_stmt->bind_param('i', $user_id);
        $password_stmt->execute();
        $password_result = $password_stmt->get_result();
        $password_data = $password_result->fetch_assoc();

        if (password_verify($current_password, $password_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param('si', $hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to update password.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } else {
        $error = "All password fields are required!";
    }
}

$response_stmt = $conn->prepare("SELECT idresponse, response FROM response WHERE user = ?");
$response_stmt->bind_param('s', $username);
$response_stmt->execute();
$responses = $response_stmt->get_result();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_response'])) {
    $response_id = intval($_POST['response_id']);
    $action = $_POST['action'];
    
    if ($action === "thank_you") {
        $delete_stmt = $conn->prepare("DELETE FROM response WHERE idresponse = ?");
        $delete_stmt->bind_param('i', $response_id);
        if ($delete_stmt->execute()) {
            $success = "Thank you sent!";
        } else {
            $error = "Failed to process.";
        }
    } elseif ($action === "reply") {
        $reply_message = trim($_POST['reply_message']);
        if (!empty($reply_message)) {
            $insert_stmt = $conn->prepare("INSERT INTO questions (username, question) VALUES (?, ?)");
            $insert_stmt->bind_param('ss', $username, $reply_message);
            if ($insert_stmt->execute()) {
                $delete_stmt = $conn->prepare("DELETE FROM response WHERE idresponse = ?");
                $delete_stmt->bind_param('i', $response_id);
                $delete_stmt->execute();
                $success = "Response sent back to the admin.";
            } else {
                $error = "Failed to send response.";
            }
        } else {
            $error = "Reply cannot be empty.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="styles/profile.css">
</head>
<body>
    <nav>
        <h1>Profile</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="profile-container">
        <h2>My Profile</h2>
        
        <div class="message-container">
            <?php if (isset($error)) : ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if (isset($success)) : ?>
                <p class="success"><?= htmlspecialchars($success); ?></p>
            <?php endif; ?>
        </div>

        <p><strong>Username:</strong> <?= htmlspecialchars($username); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($email); ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($role); ?></p>
        <p><strong>Trainer:</strong> <?= htmlspecialchars($trainer); ?></p>

        <h3>Change Password</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <button type="submit" name="change_password" class="btn-primary">Change Password</button>
        </form>

        <h3>Admin Responses</h3>
        <?php if ($responses->num_rows > 0): ?>
            <div class="response-list">
                <?php while ($response = $responses->fetch_assoc()): ?>
                    <div class="response-card">
                        <p><?= nl2br(htmlspecialchars($response['response'])); ?></p>
                        <form method="POST" action="">
                            <input type="hidden" name="response_id" value="<?= $response['idresponse']; ?>">
                            <button type="submit" name="handle_response" value="thank_you" class="btn-primary">Thank You</button>
                            <textarea name="reply_message" placeholder="Reply to admin..." required></textarea>
                            <button type="submit" name="handle_response" value="reply" class="btn-secondary">Reply</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No responses from the admin yet.</p>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?= date("Y"); ?> Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
