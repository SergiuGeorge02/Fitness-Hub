<?php
session_start();
require 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the user's assigned trainer
$stmt = $conn->prepare("SELECT trainer FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: login.php");
    exit;
}

$user = $result->fetch_assoc();
$trainer = $user['trainer'];

// Check if the user has an assigned trainer
if (empty($trainer) || $trainer == "no trainer") {
    $no_trainer = "You are not assigned to a trainer yet.";
} else {
    // Fetch trainings from the trainer
    $trainings_stmt = $conn->prepare("SELECT title, type, muscle_group, equipment, instructions, video_link FROM trainings WHERE trainer = ?");
    $trainings_stmt->bind_param('s', $trainer);
    $trainings_stmt->execute();
    $trainings = $trainings_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Trainings</title>
    <link rel="stylesheet" href="styles/membertrainingsstyle.css">
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

    <div class="container">
        <h2>Member-Only Training Sessions</h2>
        <p>Access exclusive workouts and personalized training plans.</p>

        <?php if (isset($no_trainer)): ?>
            <p class="warning"><?= htmlspecialchars($no_trainer); ?></p>
        <?php elseif ($trainings->num_rows > 0): ?>
            <div class="training-list">
                <?php while ($training = $trainings->fetch_assoc()): ?>
                    <div class="training-card">
                        <h3><?= htmlspecialchars($training['title']); ?></h3>
                        <p><strong>Type:</strong> <?= htmlspecialchars($training['type']); ?></p>
                        <p><strong>Muscle Group:</strong> <?= htmlspecialchars($training['muscle_group']); ?></p>
                        <p><strong>Equipment:</strong> <?= htmlspecialchars($training['equipment']); ?></p>
                        <p><strong>Instructions:</strong> <?= nl2br(htmlspecialchars($training['instructions'])); ?></p>

                        <?php if (!empty($training['video_link'])): ?>
                            <div class="video-container">
                                <iframe width="100%" height="315" src="<?= str_replace("watch?v=", "embed/", htmlspecialchars($training['video_link'])); ?>" frameborder="0" allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
