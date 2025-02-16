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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_training'])) {
    $title = trim($_POST['title']);
    $type = trim($_POST['type']);
    $muscle_group = trim($_POST['muscle_group']);
    $equipment = trim($_POST['equipment']);
    $instructions = trim($_POST['instructions']);
    $video_link = trim($_POST['video_link']);

    if (!empty($title) && !empty($type) && !empty($muscle_group) && !empty($equipment) && !empty($instructions)) {
        $insert_stmt = $conn->prepare("INSERT INTO trainings (trainer, title, type, muscle_group, equipment, instructions, video_link) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param('sssssss', $trainer, $title, $type, $muscle_group, $equipment, $instructions, $video_link);

        if ($insert_stmt->execute()) {
            $success = "Training added successfully!";
        } else {
            $error = "Failed to add training. Please try again!";
        }
    } else {
        $error = "All fields except video link are required!";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_training'])) {
    $training_id = intval($_POST['training_id']);
    $title = trim($_POST['title']);
    $type = trim($_POST['type']);
    $muscle_group = trim($_POST['muscle_group']);
    $equipment = trim($_POST['equipment']);
    $instructions = trim($_POST['instructions']);
    $video_link = trim($_POST['video_link']);

    if (!empty($title) && !empty($type) && !empty($muscle_group) && !empty($equipment) && !empty($instructions)) {
        $update_stmt = $conn->prepare("UPDATE trainings SET title = ?, type = ?, muscle_group = ?, equipment = ?, instructions = ?, video_link = ? WHERE id = ? AND trainer = ?");
        $update_stmt->bind_param('ssssssis', $title, $type, $muscle_group, $equipment, $instructions, $video_link, $training_id, $trainer);

        if ($update_stmt->execute()) {
            $success = "Training updated successfully!";
        } else {
            $error = "Failed to update training.";
        }
    } else {
        $error = "All fields except video link are required!";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_training'])) {
    $training_id = intval($_POST['training_id']);

    $delete_stmt = $conn->prepare("DELETE FROM trainings WHERE id = ? AND trainer = ?");
    $delete_stmt->bind_param('is', $training_id, $trainer);

    if ($delete_stmt->execute()) {
        $success = "Training deleted successfully!";
    } else {
        $error = "Failed to delete training.";
    }
}

$trainings_stmt = $conn->prepare("SELECT id, title, type, muscle_group, equipment, instructions, video_link FROM trainings WHERE trainer = ?");
$trainings_stmt->bind_param('s', $trainer);
$trainings_stmt->execute();
$trainings = $trainings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Training Control Panel</title>
    <link rel="stylesheet" href="styles/trainerscontrolpanelstyle.css">
</head>
<body>

    <nav>
        <h1>Trainer Training Panel</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="trainer-container">
        <h2>Manage Trainings</h2>

        <div class="message-container">
            <?php if (isset($error)) : ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if (isset($success)) : ?>
                <p class="success"><?= htmlspecialchars($success); ?></p>
            <?php endif; ?>
        </div>
        <h3 class="section-title">Add Training</h3>
        <div class="add-training">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Training Title:</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="form-group">
                    <label for="type">Training Type:</label>
                    <input type="text" name="type" id="type" required>
                </div>
                <div class="form-group">
                    <label for="muscle_group">Muscle Group:</label>
                    <input type="text" name="muscle_group" id="muscle_group" required>
                </div>
                <div class="form-group">
                    <label for="equipment">Equipment:</label>
                    <input type="text" name="equipment" id="equipment" required>
                </div>
                <div class="form-group">
                    <label for="instructions">Instructions:</label>
                    <textarea name="instructions" id="instructions" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="video_link">YouTube Video Link (Optional):</label>
                    <input type="text" name="video_link" id="video_link" placeholder="https://www.youtube.com/watch?v=example">
                </div>
                <button type="submit" name="add_training" class="btn-primary">Add Training</button>
            </form>
        </div>

   
        <h3 class="section-title">Your Trainings</h3>
        <div class="trainings-container">
            <?php if ($trainings->num_rows > 0): ?>
                <div class="training-list">
                    <?php while ($training = $trainings->fetch_assoc()): ?>
                        <div class="training-card">
                            <form method="POST" action="">
                                <input type="hidden" name="training_id" value="<?= $training['id']; ?>">
                                <input type="text" name="title" value="<?= htmlspecialchars($training['title']); ?>" required>
                                <input type="text" name="type" value="<?= htmlspecialchars($training['type']); ?>" required>
                                <input type="text" name="muscle_group" value="<?= htmlspecialchars($training['muscle_group']); ?>" required>
                                <input type="text" name="equipment" value="<?= htmlspecialchars($training['equipment']); ?>" required>
                                <textarea name="instructions" rows="3" required><?= htmlspecialchars($training['instructions']); ?></textarea>
                                <input type="text" name="video_link" value="<?= htmlspecialchars($training['video_link']); ?>" placeholder="YouTube Link">
                                <button type="submit" name="update_training" class="btn-primary">Update</button>
                                <button type="submit" name="delete_training" class="btn-danger">Delete</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No trainings added yet.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
