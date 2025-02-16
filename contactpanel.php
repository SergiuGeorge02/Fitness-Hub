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

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$user = $result->fetch_assoc();
$role = strtolower($user['role']);

if ($role !== "admin") {
    header("Location: index.php");
    exit;
}
$questions_stmt = $conn->prepare("SELECT id, username, question FROM questions ORDER BY id ASC");
$questions_stmt->execute();
$questions = $questions_stmt->get_result();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_response'])) {
    $question_id = intval($_POST['question_id']);
    $username = trim($_POST['username']);
    $response = trim($_POST['response']);

    if (!empty($response)) {
        $insert_stmt = $conn->prepare("INSERT INTO response (user, response) VALUES (?, ?)");
        $insert_stmt->bind_param('ss', $username, $response);

        if ($insert_stmt->execute()) {
            $delete_stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
            $delete_stmt->bind_param('i', $question_id);
            $delete_stmt->execute();

            $success = "Response sent successfully!";
        } else {
            $error = "Failed to send response. Please try again!";
        }
    } else {
        $error = "Response cannot be empty!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Contact Panel</title>
    <link rel="stylesheet" href="styles/contactpanel.css">
</head>
<body>

    <nav>
        <h1>Admin Contact Panel</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="admin-container">
        <h2>Manage User Questions</h2>

        <div class="message-container">
            <?php if (isset($error)) : ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if (isset($success)) : ?>
                <p class="success"><?= htmlspecialchars($success); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($questions->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Question</th>
                            <th>Response</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($question = $questions->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($question['username']); ?></td>
                                <td><?= nl2br(htmlspecialchars($question['question'])); ?></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="question_id" value="<?= $question['id']; ?>">
                                        <input type="hidden" name="username" value="<?= htmlspecialchars($question['username']); ?>">
                                        <textarea name="response" rows="3" required></textarea>
                                </td>
                                <td>
                                        <button type="submit" name="send_response" class="btn-primary">Send</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="info">No pending questions.</p>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?= date("Y"); ?> Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
