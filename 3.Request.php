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

$message = "";
$messageType = "";

// Function to auto-match new blood requests
function autoMatchBloodRequests($conn, $requestId, &$matchMessage) {
    // Retrieve details of the newly added request
    $sqlRequest = "SELECT * FROM blood_requests WHERE id = ? AND status = 'pending'";
    $stmtRequest = $conn->prepare($sqlRequest);
    $stmtRequest->bind_param("i", $requestId);
    $stmtRequest->execute();
    $resultRequest = $stmtRequest->get_result();

    if ($resultRequest->num_rows > 0) {
        $request = $resultRequest->fetch_assoc();
        $bloodGroup = $request['blood_group'];

        // Find a matching donation candidate
        $sqlMatch = "SELECT * FROM donation_candidates WHERE blood_group = ? AND status = 'pending' LIMIT 1";
        $stmtMatch = $conn->prepare($sqlMatch);
        $stmtMatch->bind_param("s", $bloodGroup);
        $stmtMatch->execute();
        $resultMatch = $stmtMatch->get_result();

        if ($resultMatch->num_rows > 0) {
            $donationCandidate = $resultMatch->fetch_assoc();
            $donationId = $donationCandidate['id'];

            // Create a new match entry
            $insertMatch = "INSERT INTO matches (blood_request_id, donation_candidate_id, status, blood_group) VALUES (?, ?, 'matched', ?)";
            $stmtInsertMatch = $conn->prepare($insertMatch);
            $stmtInsertMatch->bind_param("iis", $requestId, $donationId, $bloodGroup);
            $stmtInsertMatch->execute();

            // Update statuses
            $updateRequest = "UPDATE blood_requests SET status = 'matched' WHERE id = ?";
            $stmtUpdateRequest = $conn->prepare($updateRequest);
            $stmtUpdateRequest->bind_param("i", $requestId);
            $stmtUpdateRequest->execute();

            $updateDonation = "UPDATE donation_candidates SET status = 'reserved' WHERE id = ?";
            $stmtUpdateDonation = $conn->prepare($updateDonation);
            $stmtUpdateDonation->bind_param("i", $donationId);
            $stmtUpdateDonation->execute();

            // Prepare a notification message
            $matchMessage = "Reservation has been made succesfully!! <br> Please Collect it at Sath Office, Jawalakhel";
            return true;
        }
    }
    return false;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $name = $conn->real_escape_string(trim($_POST['name']));
    $blood_group = $conn->real_escape_string($_POST['blood_group']);
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $reason = $conn->real_escape_string(trim($_POST['reason']));

    // Validation flags
    $isValid = true;
    $errors = [];

    // Validate name (no numbers allowed)
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $isValid = false;
        $errors[] = "Name should only contain letters and spaces.";
    }

    // Validate phone (assuming 10-digit number format)
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $isValid = false;
        $errors[] = "Phone number must be a 10-digit number.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $isValid = false;
        $errors[] = "Invalid email address.";
    }

    // Insert data if valid
    if ($isValid) {
        $sql = "INSERT INTO blood_requests (name, blood_group, phone, email, reason, status) 
                VALUES ('$name', '$blood_group', '$phone', '$email', '$reason', 'pending')";

        if ($conn->query($sql) === TRUE) {
            $requestId = $conn->insert_id;

            // Try to auto-match the new request
            $matchMessage = "";
            $isMatched = autoMatchBloodRequests($conn, $requestId, $matchMessage);

            if ($isMatched) {
                $message = $matchMessage . "<br>Tracking ID: " . $requestId;
            } else {
                $message = "Your request has been successfully placed,<br> but no match was found at this time.<br>Tracking ID: " . $requestId . "<br>Please regularly check it in the tracking section.";
            }
            $messageType = "success";
            $_POST = [];

        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

$conn->close();
?>

<div class="allrequest">
    <form class="modern-form" method="POST" action="">
        <h2>Request Blood</h2>
        <div class="input-group">
            <input type="text" name="name" placeholder="Name" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES); ?>" required>
        </div>
        <div class="input-group">
            <select name="blood_group" required>
                <option value="" disabled <?= !isset($_POST['blood_group']) ? 'selected' : ''; ?>>Blood Group</option>
                <option value="A+" <?= ($_POST['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                <option value="A-" <?= ($_POST['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                <option value="B+" <?= ($_POST['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                <option value="B-" <?= ($_POST['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                <option value="AB+" <?= ($_POST['blood_group'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                <option value="AB-" <?= ($_POST['blood_group'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                <option value="O+" <?= ($_POST['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                <option value="O-" <?= ($_POST['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
            </select>
        </div>
        <div class="input-group">
            <input type="text" name="phone" placeholder="Phone" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES); ?>" required>
        </div>
        <div class="input-group">
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required>
        </div>
        <div class="input-group">
            <input type="text" name="reason" placeholder="Reason For Request" value="<?= htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES); ?>" required>
        </div>
        <button type="submit" class="submit-btn">Submit</button>
    </form>
</div>

<!-- Popup Message -->
<?php if (!empty($message)) : ?>
<div class="popup <?= $messageType; ?>" id="popupMessage">
    <div class="popup-content">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <p><?= $message; ?></p>
    </div>
</div>
<?php endif; ?>

<?php include('7.Footer.php'); ?>
