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
    $username = htmlspecialchars($user['username']);
    $email = htmlspecialchars($user['email']);
    $trainer = empty($user['trainer']) ? "no trainer" : htmlspecialchars($user['trainer']);
    $role = isset($user['role']) ? strtolower(htmlspecialchars($user['role'])) : "member";
} else {
    session_destroy();
    header("Location: login.php");
    exit;
}

$api_key = getenv('API_NINJAS_KEY') ?: "Kxm+hl7Pe2gOJLjyj/nirQ==HgJw19KccE4cECTv"; 

$muscle_groups = [
    "abdominals", "biceps", "calves", "chest", "forearms",
    "glutes", "hamstrings", "lats", "lower_back", "middle_back",
    "neck", "quadriceps", "traps", "triceps"
];


$muscle = isset($_GET['muscle']) ? strtolower(trim($_GET['muscle'])) : "biceps";
$muscle = in_array($muscle, $muscle_groups) ? $muscle : "biceps";

$api_url = "https://api.api-ninjas.com/v1/exercises?muscle=" . urlencode($muscle);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Api-Key: $api_key"]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$workouts = [];
if ($http_code === 200 && $response) {
    $workouts = json_decode($response, true);
    if (!is_array($workouts)) {
        $workouts = [];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Trainings</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <nav>
        <h1>Fitness Hub</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <?php if ($trainer !== "no trainer" || $role=="admin") : ?>
                <li><a href="member_trainings.php">Trainings (Member)</a></li>
            <?php endif; ?>
            <?php if ($trainer === "no trainer") : ?>
                <li><a href="joinus.php">Join Us</a></li>
            <?php endif; ?>
            <?php if ($role!="admin"): ?>
             <li><a href="contact.php">Contact</a></li>
            <?php endif; ?>
            <?php if ($role === "trainer") : ?>
                <li><a href="trainerscontrolpanel.php"style="color:red">Trainings Panel</a></li>
            <?php endif; ?>
            <?php if ($role === "trainer") : ?>
                <li><a href="trainercpjoinus.php" style="color:red">Trainer Join CP</a></li>
            <?php endif; ?>

            <?php if ($role === "admin") : ?>
                <li><a href="admin.php" style="color:red">Admin Panel</a></li>
            <?php endif; ?>
            <?php if ($role === "admin") : ?>
                <li><a href="contactpanel.php"style="color:red">Contact Panel</a></li>
            <?php endif; ?>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Free Training Sessions</h2>
        <p>Select a muscle group to see related workouts.</p>

        <form method="GET" action="">
            <label for="muscle">Choose a muscle group:</label>
            <select name="muscle" id="muscle">
                <?php foreach ($muscle_groups as $group): ?>
                    <option value="<?= htmlspecialchars($group); ?>" <?= $muscle === $group ? 'selected' : ''; ?>>
                        <?= ucfirst(str_replace("_", " ", htmlspecialchars($group))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Get Workouts</button>
        </form>

        <!-- Display Exercises -->
        <?php if (!empty($workouts)): ?>
            <div class="workout-list">
                <?php foreach ($workouts as $workout): ?>
                    <div class="workout-card">
                        <h3><?= htmlspecialchars($workout['name'] ?? 'Unknown Workout'); ?></h3>
                        <p><strong>Type:</strong> <?= htmlspecialchars($workout['type'] ?? 'N/A'); ?></p>
                        <p><strong>Muscle Group:</strong> <?= ucfirst(str_replace("_", " ", htmlspecialchars($workout['muscle'] ?? 'N/A'))); ?></p>
                        <p><strong>Equipment:</strong> <?= htmlspecialchars($workout['equipment'] ?? 'None'); ?></p>
                        <p><strong>Instructions:</strong> <?= nl2br(htmlspecialchars($workout['instructions'] ?? 'No instructions available.')); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>⚠️ No workouts found for "<?= ucfirst(str_replace("_", " ", htmlspecialchars($muscle))); ?>". Try another muscle group.</p>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?= date("Y"); ?> Fitness Hub. All Rights Reserved.</p>
    </footer>

</body>
</html>
