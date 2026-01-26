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

// Function to check if a request has been matched
function checkRequestStatus($conn, $requestId, &$statusMessage) {
    $sql = "SELECT status FROM blood_requests WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        if ($request['status'] === 'Matched') {
            $statusMessage = "The request with ID $requestId has been successfully matched.";
            return true;
        } else {
            $statusMessage = "The request with ID $requestId is still pending.";
        }
    } else {
        $statusMessage = "No request found with ID $requestId.";
    }
    return false;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $requestId = intval($_POST['request_id']);

    if ($requestId > 0) {
        $isMatched = checkRequestStatus($conn, $requestId, $message);
        $messageType = $isMatched ? "success" : "error";
    } else {
        $message = "Invalid Request ID.";
        $messageType = "error";
    }
}

$conn->close();
?>

<div class="allrequest">
    <form class="modern-form" method="POST" action="">
        <h2>Check Request Status</h2>
        <div class="input-group">
            <input type="number" name="request_id" placeholder="Enter Request ID" required>
        </div>
        <button type="submit" class="submit-btn">Check Status</button>
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
