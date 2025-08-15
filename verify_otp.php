<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];
    $otp = $_POST['otp'];

    $sql = "SELECT * FROM users WHERE phone='$phone' AND otp='$otp'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $update = "UPDATE users SET otp=NULL WHERE phone='$phone'";
        $conn->query($update);
        echo "<h3>OTP Verified Successfully! Registration Complete ✅</h3>";
    } else {
        echo "<h3>Invalid OTP ❌</h3>";
    }
}
?>

<form method="POST">
    <input type="hidden" name="phone" value="<?php echo $_GET['phone'] ?? ''; ?>">
    <input type="text" name="otp" placeholder="Enter OTP" required><br>
    <button type="submit">Verify OTP</button>
</form>
