<?php
include('8.AdminHeader.php');

require_once 'db.php';

// Handle form submissions for adding/updating/deleting events
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_event'])) {
        $title = $conn->real_escape_string($_POST['title']);
        $image = $conn->real_escape_string($_POST['image']);
        $event_date = $conn->real_escape_string($_POST['event_date']);
        $location = $conn->real_escape_string($_POST['location']);
        $details = $conn->real_escape_string($_POST['details']);
        
        $sql = "INSERT INTO events (title, image, event_date, location, details) 
                VALUES ('$title', '$image', '$event_date', '$location', '$details')";
        if ($conn->query($sql)) {
            $message = "Event added successfully!";
            $messageType = "success";
            echo "<script>scrollToTopAndHideAddForm();</script>";
        } else {
            $message = "Error adding event: " . $conn->error;
            $messageType = "error";
        }
    } elseif (isset($_POST['edit_event'])) {
        $id = $conn->real_escape_string($_POST['event_id']);
        $title = $conn->real_escape_string($_POST['title']);
        $image = $conn->real_escape_string($_POST['image']);
        $event_date = $conn->real_escape_string($_POST['event_date']);
        $location = $conn->real_escape_string($_POST['location']);
        $details = $conn->real_escape_string($_POST['details']);
        
        $sql = "UPDATE events SET title='$title', image='$image', event_date='$event_date', location='$location', details='$details' WHERE id='$id'";
        if ($conn->query($sql)) {
            $message = "Event updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating event: " . $conn->error;
            $messageType = "error";
        }    
    } elseif (isset($_POST['delete_event'])) {
        $id = $conn->real_escape_string($_POST['event_id']);
        $sql = "DELETE FROM events WHERE id='$id'";
        if ($conn->query($sql)) {
            $message = "Event deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting event: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Fetch events from the database
$sql = "SELECT * FROM events ORDER BY event_date";
$result = $conn->query($sql);
?>

<!-- Message Display -->
<?php if (!empty($message)) : ?>
    <div class="status-message <?= $messageType; ?>" id="statusMessage">
        <p><?= $message; ?></p>
    </div>
<?php endif; ?>

<!-- JavaScript for scrolling and visibility -->
<script>
    function toggleAddEventForm() {
        const addEventForm = document.querySelector('.add-event-form-container');
        addEventForm.style.display = addEventForm.style.display === 'none' ? 'block' : 'none';
        if (addEventForm.style.display === 'block') {
            addEventForm.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function scrollToTopAndHideAddForm() {
        const addEventForm = document.querySelector('.add-event-form-container');
        addEventForm.style.display = 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    if (document.getElementById('statusMessage')) {
        setTimeout(function() {
            document.getElementById('statusMessage').classList.add('fade-out');
        }, 2000);
    }
</script>

<div class="event-main">
    <section class="events-section">
        <div class="title-container">
            <h2>Manage Blood Donation Events</h2><br>
            <button class="toggle-add-form-btn" onclick="toggleAddEventForm()">+ Add Event</button>
        </div>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="event">';
                echo '<form method="POST" action="" class="event-form">';
                echo '<input type="hidden" name="event_id" value="' . htmlspecialchars($row['id']) . '">';

                // Title
                echo '<label>Title:</label>';
                echo '<input type="text" name="title" value="' . htmlspecialchars($row['title']) . '" class="input-field">';

                // Image
                echo '<label>Image Filename:</label>';
                echo '<input type="text" name="image" value="' . htmlspecialchars($row['image']) . '" class="input-field">';

                // Date
                echo '<label>Date:</label>';
                echo '<input type="date" name="event_date" value="' . htmlspecialchars($row['event_date']) . '" class="input-field">';

                // Location
                echo '<label>Location:</label>';
                echo '<input type="text" name="location" value="' . htmlspecialchars($row['location']) . '" class="input-field">';

                // Details
                echo '<label>Details:</label>';
                echo '<textarea name="details" class="input-field">' . htmlspecialchars($row['details']) . '</textarea>';

                // Buttons
                echo '<div class="button-group">';
                echo '<button type="submit" name="edit_event" class="edit-btn">Edit Event</button>';
                echo '<button type="submit" name="delete_event" class="delete-btn">Delete Event</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
            }
        } else {
            echo '<p>No events available. Use the form below to add one.</p>';
        }
        ?>
    </section>
</div>

<!-- Hidden Add Event Form -->
<div class="event-main add-event-form-container" style="display: none;">
    <section class="events-section">
        <form method="POST" action="" class="add-event-form">
            <h3>Add New Event</h3>
            <div class="input-group">
                <input type="text" name="title" placeholder="Event Title" required class="input-field">
            </div>
            <div class="input-group">
                <input type="text" name="image" placeholder="Image Filename" required class="input-field">
            </div>
            <div class="input-group">
                <input type="date" name="event_date" min="<?= date('Y-m-d');?>" required class="input-field">
            </div>
            <div class="input-group">
                <input type="text" name="location" placeholder="Location" required class="input-field">
            </div>
            <div class="input-group">
                <textarea name="details" placeholder="Event Details" required class="input-field"></textarea>
            </div>
            <button type="submit" name="add_event" class="add-btn">Add Event</button>
        </form>
    </section>
</div>

<?php
$conn->close();
?>
