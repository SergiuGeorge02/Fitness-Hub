<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question = trim($_POST['question']);

    if (!empty($question)) {
        $insert_stmt = $conn->prepare("INSERT INTO questions (username, question) VALUES (?, ?)");
        $insert_stmt->bind_param('ss', $username, $question);

        if ($insert_stmt->execute()) {
            $success = "Your question has been submitted successfully!";
        } else {
            $error = "Failed to submit your question. Please try again!";
        }
    } else {
        $error = "The question field cannot be empty!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="styles/contact.css">
</head>
<body>

    <nav>
        <h1>Fitness Hub</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="contact-container">
        <h2>Contact Us</h2>
        <p>Have a question? Fill out the form below and we’ll get back to you.</p>

        <div class="message-container">
            <?php if (isset($error)) : ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if (isset($success)) : ?>
                <p class="success"><?= htmlspecialchars($success); ?></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Your Username:</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($username); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="question">Your Question:</label>
                <textarea name="question" id="question" rows="4" placeholder="Type your question here..." required></textarea>
            </div>

            <button type="submit" class="btn-primary">Submit</button>
        </form>
    </div>

    <footer>
        <p>&copy; <?= date("Y"); ?> Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
