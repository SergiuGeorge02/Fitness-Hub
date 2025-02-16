<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT username, email, trainer, `role` FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $username = $user['username'];
    $email = $user['email'];
    $trainer = (!isset($user['trainer']) || empty($user['trainer'])) ? "no trainer" : $user['trainer'];

    $role = isset($user['role']) ? strtolower($user['role']) : "member"; 
} else {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Hub</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>

    <nav>
        <h1>Fitness Hub</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="free_trainings.php">Trainings (Free)</a></li>
            <?php if ($trainer !== "no trainer" || $role=="admin") : ?>
                <li><a href="member_trainings.php">Trainings (Member)</a></li>
            <?php endif; ?>
            <?php if ($trainer === "no trainer" && $role=="member") : ?>
                <li><a href="joinus.php">Join Us</a></li>
            <?php endif; ?>
            <?php if ($role!="admin"): ?>
             <li><a href="contact.php">Contact</a></li>
            <?php endif; ?>

            <?php if ($role === "admin") : ?>
                <li><a href="admin.php" style="color:red">Admin Panel</a></li>
            <?php endif; ?>
            <?php if ($role === "trainer") : ?>
                <li><a href="trainerscontrolpanel.php"style="color:red">Trainings Panel</a></li>
            <?php endif; ?>
            <?php if ($role === "trainer") : ?>
                <li><a href="trainercpjoinus.php" style="color:red">Trainer Join CP</a></li>
            <?php endif; ?>

            <?php if ($role === "admin") : ?>
                <li><a href="contactpanel.php"style="color:red">Contact Panel</a></li>
            <?php endif; ?>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <section class="hero">
        <h2>Transform Your Body, Elevate Your Life</h2>
        <p>Join the best fitness community and take control of your health with expert-designed workouts.</p>
        <a href="free_trainings.php" class="btn">Explore Free Workouts</a>
    </section>

    <div class="container">
        <h2>About Fitness Hub</h2>
        <p>Stay fit and healthy with our specially curated fitness training programs. Whether you're a beginner or an advanced athlete, we have something for you!</p>

        <h3>Watch and Follow Along</h3>
        <div class="video-container">
            <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" allowfullscreen></iframe>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
