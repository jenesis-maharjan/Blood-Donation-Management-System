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

$name = $phone = $email = $dob = $gender = $blood_group = $address = "";

function autoMatchBloodRequests($conn, $donateId) {
    // Retrieve details of the newly added donation candidate
    $sqlRequest = "SELECT * FROM donation_candidates WHERE id = ? AND status = 'pending'";
    $stmtRequest = $conn->prepare($sqlRequest);
    $stmtRequest->bind_param("i", $donateId);
    $stmtRequest->execute();
    $resultRequest = $stmtRequest->get_result();

    if ($resultRequest->num_rows > 0) {
        $donationCandidate = $resultRequest->fetch_assoc();
        $bloodGroup = $donationCandidate['blood_group'];

        // Find a matching blood request
        $sqlMatch = "SELECT * FROM blood_requests WHERE blood_group = ? AND status = 'pending' LIMIT 1";
        $stmtMatch = $conn->prepare($sqlMatch);
        $stmtMatch->bind_param("s", $bloodGroup);
        $stmtMatch->execute();
        $resultMatch = $stmtMatch->get_result();

        if ($resultMatch->num_rows > 0) {
            $bloodRequester = $resultMatch->fetch_assoc();
            $requestId = $bloodRequester['id'];

            // Create a new match entry
            $insertMatch = "INSERT INTO matches (blood_request_id, donation_candidate_id, status, blood_group) VALUES (?, ?, 'matched', ?)";
            $stmtInsertMatch = $conn->prepare($insertMatch);
            $stmtInsertMatch->bind_param("iis", $requestId, $donateId, $bloodGroup);
            $stmtInsertMatch->execute();

            // Update statuses
            $updateRequest = "UPDATE donation_candidates SET status = 'reserved' WHERE id = ?";
            $stmtUpdateRequest = $conn->prepare($updateRequest);
            $stmtUpdateRequest->bind_param("i", $donateId);
            $stmtUpdateRequest->execute();

            $updateDonation = "UPDATE blood_requests SET status = 'matched' WHERE id = ?";
            $stmtUpdateDonation = $conn->prepare($updateDonation);
            $stmtUpdateDonation->bind_param("i", $requestId);
            $stmtUpdateDonation->execute();
        }
    }
}
// Handle form submission
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $name = $conn->real_escape_string(trim($_POST['name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $dob = $conn->real_escape_string(trim($_POST['dob']));
    $gender = $conn->real_escape_string($_POST['gender']);
    $blood_group = $conn->real_escape_string($_POST['blood_group']);
    $address = $conn->real_escape_string(trim($_POST['address']));

    // Validation flags
    $isValid = true;
    $errors = [];

    // Validate name (no numbers)
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $isValid = false;
        $errors[] = "Name should only contain letters and spaces.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $isValid = false;
        $errors[] = "Invalid email address.";
    }

    // Validate phone number (assuming a specific format, e.g., 10-digit number)
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $isValid = false;
        $errors[] = "Phone number must be a 10-digit number.";
    }

    // Validate age (between 18 and 65 years)
    $birthDate = new DateTime($dob);
    $currentDate = new DateTime();
    $age = $currentDate->diff($birthDate)->y;

    if ($age < 18 || $age > 65) {
        $isValid = false;
        $errors[] = "Age must be between 18 and 65 years.";
    }

    // Insert data if valid
    if ($isValid) {
        $sql = "INSERT INTO donation_candidates (name, phone, email, dob, gender, blood_group, address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $phone, $email, $dob, $gender, $blood_group, $address);
        
        if ($stmt->execute()) {
            $donateId = $conn->insert_id;

            // Try to auto-match the new request
            autoMatchBloodRequests($conn, $donateId);

            // Clear the form fields by setting them to empty
            $name = $phone = $email = $dob = $gender = $blood_group = $address = "";

            $message = "Your request has been successfully placed,<br>Thank You So Much!!<br>For Donation Visit Sath Office, Jawalakhel";
            $messageType = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}


$conn->close();
?>

<div class="alldonate">
    <div class="donate-events">
        <a href="5.Events.php" class="donate-events-button">Find Upcoming Donation Events Here</a>
    </div>
    <form class="modern-form" method="POST" action="">
    <h2>Please send us your details</h2>
    <div class="input-group">
        <input type="text" name="name" placeholder="Name" value="<?= htmlspecialchars($name); ?>" required>
    </div>
    <div class="input-group">
        <input type="text" name="phone" placeholder="Phone" value="<?= htmlspecialchars($phone); ?>" required>
    </div>
    <div class="input-group">
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email); ?>" required>
    </div>
    <div class="input-group">
        <input type="date" name="dob" placeholder="Date of Birth" value="<?= htmlspecialchars($dob); ?>" required>
    </div>
    <div class="input-group">
        <select name="gender" required>
            <option value="" disabled <?= empty($gender) ? 'selected' : ''; ?>>Gender</option>
            <option value="Male" <?= ($gender == "Male") ? "selected" : ""; ?>>Male</option>
            <option value="Female" <?= ($gender == "Female") ? "selected" : ""; ?>>Female</option>
            <option value="Other" <?= ($gender == "Other") ? "selected" : ""; ?>>Other</option>
        </select>
    </div>
    <div class="input-group">
        <select name="blood_group" required>
            <option value="" disabled <?= empty($blood_group) ? 'selected' : ''; ?>>Blood Group</option>
            <option value="A+" <?= ($blood_group == "A+") ? "selected" : ""; ?>>A+</option>
            <option value="A-" <?= ($blood_group == "A-") ? "selected" : ""; ?>>A-</option>
            <option value="B+" <?= ($blood_group == "B+") ? "selected" : ""; ?>>B+</option>
            <option value="B-" <?= ($blood_group == "B-") ? "selected" : ""; ?>>B-</option>
            <option value="AB+" <?= ($blood_group == "AB+") ? "selected" : ""; ?>>AB+</option>
            <option value="AB-" <?= ($blood_group == "AB-") ? "selected" : ""; ?>>AB-</option>
            <option value="O+" <?= ($blood_group == "O+") ? "selected" : ""; ?>>O+</option>
            <option value="O-" <?= ($blood_group == "O-") ? "selected" : ""; ?>>O-</option>
        </select>
    </div>
    <div class="input-group">
        <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($address); ?>" required>
    </div>

    <button type="submit" class="submit-btn">Submit</button>
</form>

</div>

<!-- Popup Message -->
<?php if (!empty($message)) : ?>
<div class="popup <?= $messageType; ?>" id="popupMessage">
    <div class="popup-content">
        <span class="close-btn" onclick="document.getElementById('popupMessage').style.display='none'">&times;</span>
        <?= $message; ?>
    </div>
</div>
<?php endif; ?>

<?php
include('7.Footer.php');
?>
