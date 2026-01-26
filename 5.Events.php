<?php
include('1.Header.php');

$servername = "localhost";
$username = "root";
$password = "";
$database = "sath";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch events from the database
$sql = "SELECT * FROM events ORDER BY event_date";
$result = $conn->query($sql);
?>

<div class="event-main">
    <section class="events-section">
        <h2>Upcoming Blood Donation Events</h2>
        <?php
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                echo '<div class="event">';
                echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                echo '<div class="event-pics">';
                echo '<img src="Pics/' . htmlspecialchars($row['image']) . '" alt="Event Picture">';
                echo '</div>';
                echo '<p>Date: ' . htmlspecialchars($row['event_date']) . '</p>';
                echo '<p>Location: ' . htmlspecialchars($row['location']) . '</p>';
                echo '<p>Details: ' . htmlspecialchars($row['details']) . '</p>';
                echo '</div>';
            }
        } else {
            echo '<p>No upcoming events.</p>';
        }
        ?>
    </section>
</div>

<?php
$conn->close();
include '7.Footer.php';
?>
