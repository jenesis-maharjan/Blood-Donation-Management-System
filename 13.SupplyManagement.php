<?php
include '8.AdminHeader.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sath";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* Display all matches with their current statuses */
function display_matches($conn) {
    $sql = "SELECT * FROM matches";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<div class='admin-table-container'>";
        echo "<h2 class='admin-title'>Admin Match Log</h2>";
        echo "<table class='admin-table'>";
        echo "<thead>
                <tr>
                    <th>Match ID</th>
                    <th>Blood Request ID</th>
                    <th>Donation Candidate ID</th>
                    <th>Blood Group</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
              </thead>
              <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['id']) . "</td>
                    <td>" . htmlspecialchars($row['blood_request_id']) . "</td>
                    <td>" . htmlspecialchars($row['donation_candidate_id']) . "</td>
                    <td>" . htmlspecialchars($row['blood_group']) . "</td>
                    <td>" . htmlspecialchars($row['status']) . "</td>
                    <td>
                        <form method='post' class='status-form'>
                            <input type='hidden' name='match_id' value='" . htmlspecialchars($row['id']) . "'>
                            <select name='new_status' class='status-select'>
                                <option value='Matched' " . ($row['status'] === 'Matched' ? 'selected' : '') . ">Matched</option>
                                <option value='Canceled' " . ($row['status'] === 'Canceled' ? 'selected' : '') . ">Canceled</option>
                            </select>
                            <input type='submit' name='update_match_status' value='Update' class='status-update-button'>
                        </form>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No matches found.</p>";
    }
}

/* Handle match status update form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_match_status'])) {
    $match_id = $_POST['match_id'];
    $new_status = $_POST['new_status'];

    // Update match status
    $update_status_sql = "UPDATE matches SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_status_sql);
    
    // Check if prepare() was successful
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("si", $new_status, $match_id);

    if ($stmt->execute()) {
        $message = "Match Status Updated Successfully";
        $messageType = 'success';
    } else {
        $message = "Error Updating Match Status: " . $stmt->error;
        $messageType = 'error';
    }

    // Update related tables
    update_status_in_related_tables($conn, $match_id, $new_status);
}

/* Update status in related tables (blood_requests and donation_candidates) */
function update_status_in_related_tables($conn, $match_id, $new_status) {
    $match_sql = "SELECT * FROM matches WHERE id = ?";
    $stmt = $conn->prepare($match_sql);
    
    // Check if prepare() was successful
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();

    if ($match) {
        $blood_request_id = $match['blood_request_id'];
        $donation_candidate_id = $match['donation_candidate_id'];

        if ($new_status === 'Matched') {
            // Update statuses to 'matched' and 'reserved'
            $update_blood_request = $conn->prepare("UPDATE blood_requests SET status = 'Matched' WHERE id = ?");
            $update_blood_request->bind_param("i", $blood_request_id);
            if (!$update_blood_request->execute()) {
                die("Error updating blood request status: " . $conn->error);
            }

            $update_candidate = $conn->prepare("UPDATE donation_candidates SET status = 'Reserved' WHERE id = ?");
            $update_candidate->bind_param("i", $donation_candidate_id);
            if (!$update_candidate->execute()) {
                die("Error updating donation candidate status: " . $conn->error);
            }
        } elseif ($new_status === 'Canceled') {
            // Revert statuses to 'Pending'
            $update_blood_request = $conn->prepare("UPDATE blood_requests SET status = 'Pending' WHERE id = ?");
            $update_blood_request->bind_param("i", $blood_request_id);
            if (!$update_blood_request->execute()) {
                die("Error reverting blood request status: " . $conn->error);
            }

            $update_candidate = $conn->prepare("UPDATE donation_candidates SET status = 'Pending' WHERE id = ?");
            $update_candidate->bind_param("i", $donation_candidate_id);
            if (!$update_candidate->execute()) {
                die("Error reverting donation candidate status: " . $conn->error);
            }
        }
    } else {
        die("No match found with ID: " . $match_id);
    }
}

/* Handle adding a new match */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_match'])) {
    $blood_request_id = $_POST['blood_request_id'];
    $donation_candidate_id = $_POST['donation_candidate_id'];
    $blood_group = $_POST['blood_group'];

    // Insert new match
    $insertMatch = "INSERT INTO matches (blood_request_id, donation_candidate_id, status, blood_group) VALUES (?, ?, 'Matched', ?)";
    $stmt = $conn->prepare($insertMatch);
    
    // Check if prepare() was successful
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("iis", $blood_request_id, $donation_candidate_id, $blood_group);
    $stmt->execute();

    // Update statuses
    $update_blood_request = $conn->prepare("UPDATE blood_requests SET status = 'Matched' WHERE id = ?");
    $update_blood_request->bind_param("i", $blood_request_id);
    $update_blood_request->execute();

    $update_candidate = $conn->prepare("UPDATE donation_candidates SET status = 'Reserved' WHERE id = ?");
    $update_candidate->bind_param("i", $donation_candidate_id);
    $update_candidate->execute();

    $message = "Match Added Successfully!";
    $messageType = 'success';
}

// Display all matches
display_matches($conn);
?>

<!-- Fetch Pending Blood Requests -->
<?php
$pendingRequests = $conn->query("SELECT id FROM blood_requests WHERE status = 'Pending'");
$pendingCandidates = $conn->query("SELECT id FROM donation_candidates WHERE status = 'Pending'");
?>

<h2 class="admin-title" style="margin-top: 50px;">Add New Match</h2>
<form method="post" class="match-form">
    <!-- Blood Request Dropdown -->
    <div class="input-group">
        <select id="blood_request_id" name="blood_request_id" required>
            <option value="" disabled selected>Select Blood Request ID</option>
            <?php while ($row = $pendingRequests->fetch_assoc()) : ?>
                <option value="<?= htmlspecialchars($row['id']); ?>"><?= htmlspecialchars($row['id']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Donation Candidate Dropdown -->
    <div class="input-group">
        <select id="donation_candidate_id" name="donation_candidate_id" required>
            <option value="" disabled selected>Select Donation Candidate ID</option>
            <?php while ($row = $pendingCandidates->fetch_assoc()) : ?>
                <option value="<?= htmlspecialchars($row['id']); ?>"><?= htmlspecialchars($row['id']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Blood Group Dropdown -->
    <div class="input-group">
        <select name="blood_group" required>
            <option value="" disabled selected>Blood Group</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
        </select>
    </div>

    <input type="submit" name="add_match" value="Add Match" class="btn-submit">
</form>


<?php
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