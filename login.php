<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status"=>"error","message"=>"POST only"]); exit;
}

$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';

if (!$email || !$pass) {
    echo json_encode(["status"=>"error","message"=>"Provide credentials"]);
    exit;
}

$stmt = $conn->prepare("SELECT user_id, name, password FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email); $stmt->execute(); $res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (password_verify($pass, $row['password'])) {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_name'] = $row['name'];
        echo json_encode(["status"=>"success","message"=>"Login successful","user"=>["id"=>$row['user_id'],"name"=>$row['name']]]); exit;
    } else {
        echo json_encode(["status"=>"error","message"=>"Wrong password"]); exit;
    }
}
echo json_encode(["status"=>"error","message"=>"User not found"]);
?>