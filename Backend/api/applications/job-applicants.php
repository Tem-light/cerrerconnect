<?php
include("../../config/db.php");
header("Content-Type: application/json");

$jobId = $_GET['jobId'];

$sql = "
SELECT a.*, u.name, u.email 
FROM applications a
JOIN users u ON a.student_id = u.id
WHERE a.job_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jobId);
$stmt->execute();

$result = $stmt->get_result();
$applicants = [];

while ($row = $result->fetch_assoc()) {
  $applicants[] = $row;
}

echo json_encode($applicants);
?>