<?php
include('8.AdminHeader.php');

$servername = "localhost";
$username = "root";
$password = ""; 
$database = "sath";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle status update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $candidate_id = $_POST['candidate_id'];
    $new_status = $_POST['new_status'];

    // Fetch the current status of the candidate
    $stmt = $conn->prepare("SELECT status FROM donation_candidates WHERE id = ?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_status = $result->fetch_assoc()['status'];

    // Validate status change
    if ($current_status === 'Pending') {
        $message = "Status Cannot be Changed from Pending.";
        $messageType = 'error';
    } else {
        // Handle specific status transitions
        if ($current_status === 'Reserved' && $new_status === 'Pending') {
            // Update associated blood request and match statuses
            $stmt = $conn->prepare("UPDATE blood_requests SET status = 'Pending' WHERE id IN (SELECT blood_request_id FROM matches WHERE donation_candidate_id = ?)");
            $stmt->bind_param("i", $candidate_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE matches SET status = 'Canceled' WHERE donation_candidate_id = ?");
            $stmt->bind_param("i", $candidate_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE donation_candidates SET status = 'Pending' WHERE id = ?");
            $stmt->bind_param("i", $candidate_id);
            $stmt->execute();

            $message = "Status updated to Pending. Match canceled.";
            $messageType = 'success';
        } else {
            // General status update
            $stmt = $conn->prepare("UPDATE donation_candidates SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $candidate_id);

            if ($stmt->execute()) {
                $message = "Status Updated Successfully.";
                $messageType = 'success';
            } else {
                $message = "Error Updating Status: " . $conn->error;
                $messageType = 'error';
            }
        }
    }
}

// Fetch all donation candidates
$result = $conn->query("SELECT id, name, phone, email, dob, gender, blood_group, address, status 
                        FROM donation_candidates 
                        ORDER BY FIELD(status, 'Pending', 'Reserved')");
?>

<!-- Admin Table Container -->
<div class="admin-table-container">
    <h2 class="admin-title">Donors Log</h2>

    <?php if ($result->num_rows > 0) : ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Blood Group</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['phone']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['dob']); ?></td>
                        <td><?= htmlspecialchars($row['gender']); ?></td>
                        <td><?= htmlspecialchars($row['blood_group']); ?></td>
                        <td><?= htmlspecialchars($row['address']); ?></td>
                        <td><?= htmlspecialchars($row['status']); ?></td>
                        <td>
                            <!-- Form to update status -->
                            <form method="POST" class="status-form">
                                <input type="hidden" name="candidate_id" value="<?= $row['id']; ?>">
                                <select name="new_status" class="status-select">
                                    <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Reserved" <?= $row['status'] === 'Reserved' ? 'selected' : ''; ?>>Reserved</option>
                                </select>
                                <input type="submit" name="update_status" value="Update" class="status-update-button">
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No donation candidates found.</p>
    <?php endif; ?>
</div>

<!-- Status Message Display -->
<?php if (!empty($message)) : ?>
    <div class="status-message <?= $messageType; ?>" id="statusMessage">
        <p><?= $message; ?></p>
    </div>
<?php endif; ?>

<!-- Fade-Out Script for Status Message -->
<script>
    if (document.getElementById('statusMessage')) {
        setTimeout(() => {
            document.getElementById('statusMessage').classList.add('fade-out');
        }, 2000);
    }
</script>

<?php
$conn->close();
?>
