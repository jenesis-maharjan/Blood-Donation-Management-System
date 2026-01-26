<?php 
// Include header
include '8.AdminHeader.php'; // Ensure 'header.php' exists in your project directory

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sath";

// Create a database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * Function to display all blood requests with their current statuses.
 * 
 * @param mysqli $conn The database connection object
 */
function display_blood_requests($conn) {
    $sql = "SELECT * FROM blood_requests ORDER BY FIELD(status, 'Pending', 'Matched')";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<div class='admin-table-container'>";
        echo "<h2 class='admin-title'>Blood Requests Log</h2>";
        echo "<table class='admin-table'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Blood Group</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Reason</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>";

        // Loop through each record and display in the table
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['id']) . "</td>
                    <td>" . htmlspecialchars($row['name']) . "</td>
                    <td>" . htmlspecialchars($row['blood_group']) . "</td>
                    <td>" . htmlspecialchars($row['phone']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                    <td>" . htmlspecialchars($row['reason']) . "</td>
                    <td>" . htmlspecialchars($row['request_date']) . "</td>
                    <td>" . htmlspecialchars($row['status']) . "</td>
                    <td>
                        <form method='post' class='status-form'>
                            <input type='hidden' name='request_id' value='" . htmlspecialchars($row['id']) . "'>
                            <select name='new_status' class='status-select'>
                                <option value='Pending' " . ($row['status'] === 'Pending' ? 'selected' : '') . ">Pending</option>
                                <option value='Matched' " . ($row['status'] === 'Matched' ? 'selected' : '') . ">Matched</option>
                            </select>
                            <input type='submit' name='update_status' value='Update' class='status-update-button'>
                        </form>
                    </td>
                  </tr>";
        }

        echo "</tbody></table></div>";
    } else {
        echo "<p>No blood requests found.</p>";
    }
}

/**
 * Handle the form submission to update the status of a blood request.
 * 
 * @param mysqli $conn The database connection object
 */
function handle_status_update($conn) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];

    // Fetch the current status of the request
    $check_status_sql = "SELECT status FROM blood_requests WHERE id = ?";
    $stmt = $conn->prepare($check_status_sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $current_status = $row['status'];
    } else {
        return ["Invalid request ID.", "error"];
    }

    // Message and message type initialization
    $message = '';
    $messageType = '';

    // If transitioning to "Matched"
    if ($new_status == 'Matched' && $current_status == 'Pending') {
        $message = "Status Cannot be Changed from Pending.";
        $messageType = 'error';
    }
    // If transitioning back to "Pending"
    elseif ($new_status == 'Pending' && $current_status == 'Matched') {
        revert_to_pending($conn, $request_id, $message, $messageType);
    } else {
        $message = "Invalid status transition.";
        $messageType = "error";
    }

    return [$message, $messageType];
}

/**
 * Revert the blood request status to 'pending' and update related data.
 * 
 * @param mysqli $conn The database connection object
 * @param int $request_id The ID of the request being reverted
 * @param string &$message The message to display to the user
 * @param string &$messageType The type of message ('success' or 'error')
 */
function revert_to_pending($conn, $request_id, &$message, &$messageType) {
    $update_blood_request_sql = "UPDATE blood_requests SET status = 'Pending' WHERE id = ?";
    $stmt = $conn->prepare($update_blood_request_sql);
    $stmt->bind_param("i", $request_id);

    if ($stmt->execute()) {
        // Cancel the match and update candidate status
        $update_matches_sql = "UPDATE matches SET status = 'Canceled' WHERE blood_request_id = ?";
        $stmt = $conn->prepare($update_matches_sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        // Ensure donation_candidates are set back to 'Pending'
        $update_candidate_sql = "UPDATE donation_candidates SET status = 'Pending' 
                                 WHERE id IN (SELECT donation_candidate_id FROM matches WHERE blood_request_id = ?)";
        $stmt = $conn->prepare($update_candidate_sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        $message = "Match Canceled and Statuses updated.";
        $messageType = 'success';
    } else {
        $message = "Error Reverting to Pending: " . $conn->error;
        $messageType = 'error';
    }
}



/**
 * Cancel the match and update donation candidates' statuses.
 * 
 * @param mysqli $conn The database connection object
 * @param int $request_id The ID of the blood request
 */
function cancel_match_and_update_candidates($conn, $request_id) {
    $update_matches_sql = "UPDATE matches SET status = 'Canceled' WHERE blood_request_id = ?";
    $stmt = $conn->prepare($update_matches_sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();

    $update_candidate_sql = "UPDATE donation_candidates SET status = 'Pending' WHERE id IN (SELECT donation_candidate_id FROM matches WHERE blood_request_id = ?)";
    $stmt = $conn->prepare($update_candidate_sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
}

// Handle status update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    list($message, $messageType) = handle_status_update($conn);
}

// Display blood requests
display_blood_requests($conn);

// Close the database connection
$conn->close();
?>

<!-- Message Display -->
<?php if (!empty($message)) : ?>
    <div class="status-message <?= $messageType; ?>" id="statusMessage">
        <p><?= $message; ?></p>
    </div>
<?php endif; ?>

<!-- JavaScript to hide message after 2 seconds -->
<script>
    if (document.getElementById('statusMessage')) {
        setTimeout(function() {
            document.getElementById('statusMessage').classList.add('fade-out');
        }, 2000);
    }
</script>
