<?php
include("../../config/db.php");
header("Content-Type: application/json");

$recruiterId = $_GET['recruiterId'];

$sql = "
SELECT j.*, 
(SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicantsCount
FROM jobs j WHERE recruiter_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recruiterId);
$stmt->execute();

$result = $stmt->get_result();
$jobs = [];

while ($row = $result->fetch_assoc()) {
  $row['requirements'] = json_decode($row['requirements']);
  $jobs[] = $row;
}

echo json_encode($jobs);
