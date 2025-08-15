<?php
include 'db.php';
include 'config_twilio.php';
require __DIR__ . '/vendor/autoload.php'; // Twilio SDK

use Twilio\Rest\Client;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone = $_POST['phone'];

    $otp = rand(100000, 999999);
    $sql = "INSERT INTO users (username, password, phone, otp) VALUES ('$username', '$password', '$phone', '$otp')";

    if ($conn->query($sql) === TRUE) {
        $client = new Client($TWILIO_SID, $TWILIO_AUTH_TOKEN);
        try {
            $client->messages->create(
                $phone,
                ["from" => $TWILIO_PHONE, "body" => "Your OTP is: $otp"]
            );
            header("Location: verify_otp.php?phone=" . urlencode($phone));
            exit();
        } catch (Exception $e) {
            echo "Error sending OTP: " . $e->getMessage();
        }
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="text" name="phone" placeholder="Phone (+91XXXXXXXXXX)" required><br>
    <button type="submit">Register & Send OTP</button>
</form>
