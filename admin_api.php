<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status'=>'error','message'=>'POST only']); exit; }
$dname = trim($_POST['dname'] ?? '');
$desc = trim($_POST['desc'] ?? '');
$rtype = $_POST['rtype'] ?? 'chemical';
$rname = trim($_POST['rname'] ?? '');
$rinstr = trim($_POST['rinstr'] ?? '');
if (!$dname) { echo json_encode(['status'=>'error','message'=>'Disease name required']); exit; }
$stmt = $conn->prepare("SELECT disease_id FROM diseases WHERE name = ? LIMIT 1");
$stmt->bind_param("s", $dname); $stmt->execute(); $r = $stmt->get_result();
if ($row = $r->fetch_assoc()) $disease_id = $row['disease_id'];
else {
    $ins = $conn->prepare("INSERT INTO diseases (name, description) VALUES (?, ?)");
    $ins->bind_param("ss", $dname, $desc); $ins->execute(); $disease_id = $ins->insert_id; $ins->close();
}
$stmt->close();
$ins2 = $conn->prepare("INSERT INTO remedies (disease_id, type, name, instructions) VALUES (?, ?, ?, ?)");
$ins2->bind_param("isss", $disease_id, $rtype, $rname, $rinstr);
if ($ins2->execute()) echo json_encode(['status'=>'success','message'=>'Added']);
else echo json_encode(['status'=>'error','message'=>'DB error']);
$ins2->close();
?>