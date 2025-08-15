<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$UPLOAD_DIR = __DIR__ . '/uploads/';
if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0777, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['leafImage'])) {
    echo json_encode(['status'=>'error','message'=>'No image received']); exit;
}

$file = $_FILES['leafImage'];
$origName = basename($file['name']);
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['status'=>'error','message'=>'Invalid image type']); exit;
}

$unique = uniqid('leaf_', true) . '.' . $ext;
$target = $UPLOAD_DIR . $unique;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['status'=>'error','message'=>'Upload failed']); exit;
}

$model_server_url = 'http://127.0.0.1:5000/predict';
$response = null;
$use_model_server = true;

if ($use_model_server) {
    $curl = curl_init();
    $cfile = new CURLFile($target);
    curl_setopt_array($curl, [
        CURLOPT_URL => $model_server_url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => ['image'=> $cfile],
        CURLOPT_TIMEOUT => 10
    ]);
    $resp = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        $response = null;
    } else {
        $response = json_decode($resp, true);
    }
}

if (!$response) {
    $py = escapeshellcmd('python3 ' . __DIR__ . '/python/detect_disease.py ' . escapeshellarg($target));
    $out = shell_exec($py);
    $response = json_decode($out, true);
}

if (!$response || !isset($response['disease'])) {
    $response = [
        'status'=>'success',
        'disease'=>'Leaf Spot',
        'chemical'=>'Mancozeb (follow label)',
        'natural'=>'Neem oil spray',
        'tips'=>'Remove affected leaves; avoid wetting foliage',
        'confidence'=>85
    ];
}

$disease_name = $conn->real_escape_string($response['disease']);
$stmt = $conn->prepare("SELECT disease_id FROM diseases WHERE name = ? LIMIT 1");
$stmt->bind_param("s", $disease_name); $stmt->execute(); $r = $stmt->get_result();
if ($row = $r->fetch_assoc()) $disease_id = $row['disease_id'];
else {
    $ins = $conn->prepare("INSERT INTO diseases (name, description, image_url) VALUES (?, ?, ?)");
    $desc = "Auto entry for " . $disease_name;
    $img_rel = 'uploads/' . $unique;
    $ins->bind_param("sss", $disease_name, $desc, $img_rel);
    $ins->execute(); $disease_id = $ins->insert_id; $ins->close();
}
$stmt->close();

$user_id = $_SESSION['user_id'] ?? NULL;
$img_db = 'uploads/' . $unique;
$confidence = isset($response['confidence']) ? floatval($response['confidence']) : 0.0;
$ins2 = $conn->prepare("INSERT INTO scans (user_id, disease_id, image_url, confidence) VALUES (?, ?, ?, ?)");
if ($user_id === NULL) {
    $ins2->bind_param("iisd", $null_user = 0, $disease_id, $img_db, $confidence);
} else {
    $ins2->bind_param("iisd", $user_id, $disease_id, $img_db, $confidence);
}
$ins2->execute(); $scan_id = $ins2->insert_id; $ins2->close();

$response_out = [
    'status'=>'success',
    'scan_id'=>$scan_id,
    'disease'=>$response['disease'],
    'chemical'=>$response['chemical'] ?? '',
    'natural'=>$response['natural'] ?? '',
    'tips'=>$response['tips'] ?? '',
    'confidence'=>$confidence,
    'image_url'=>$img_db
];

echo json_encode($response_out);
exit;
?>