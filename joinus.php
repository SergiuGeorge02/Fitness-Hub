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

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $username = htmlspecialchars($user['username']);
} else {
    session_destroy();
    header("Location: login.php");
    exit;
}

$trainers_stmt = $conn->prepare("SELECT username FROM users WHERE role = 'trainer'");
$trainers_stmt->execute();
$trainers_result = $trainers_stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_trainer = trim($_POST['trainer']);
    $message = trim($_POST['message']);

    if (empty($selected_trainer) || empty($message)) {
        $error = "All fields are required!";
    } else {
        $check_stmt = $conn->prepare("SELECT * FROM joinus WHERE username = ? AND trainer = ?");
        $check_stmt->bind_param('ss', $username, $selected_trainer);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "You have already sent a request to this trainer!";
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO joinus (username, trainer, message) VALUES (?, ?, ?)");
            $insert_stmt->bind_param('sss', $username, $selected_trainer, $message);
            
            if ($insert_stmt->execute()) {
                $success = "Request sent successfully to $selected_trainer!";
            } else {
                $error = "Failed to send request. Please try again!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us - Fitness Hub</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>

    <nav>
        <h1>Fitness Hub</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="free_trainings.php">Trainings (Free)</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="joinusform">
    <h2>Join a Trainer</h2>
    <p>Select a trainer and send a request message to start training!</p>

    <?php if (isset($error)) : ?>
        <p class="error"><?= htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (isset($success)) : ?>
        <p class="success"><?= htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="trainer">Select a Trainer:</label>
            <select name="trainer" id="trainer" required>
                <option value="">-- Choose a Trainer --</option>
                <?php while ($trainer = $trainers_result->fetch_assoc()) : ?>
                    <option value="<?= htmlspecialchars($trainer['username']); ?>">
                        <?= htmlspecialchars($trainer['username']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="message">Your Message:</label>
            <textarea name="message" id="message" rows="4" placeholder="Write a message to your trainer..." required></textarea>
        </div>

        <div class="form-group">
            <button type="submit" class="btn-primary">Send Request</button>
        </div>
    </form>
</div>


    <footer>
        <p>&copy; <?= date("Y"); ?> Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
