<?php
include("../../config/db.php");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$sql = "INSERT INTO jobs 
(recruiter_id,title,company,location,type,category,salary_min,salary_max,openings,
application_start,application_end,contact_email,contact_website,description,requirements)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "isssssiiissssss",
  $data['recruiterId'],
  $data['title'],
  $data['company'],
  $data['location'],
  $data['type'],
  $data['category'],
  $data['salaryMin'],
  $data['salaryMax'],
  $data['openings'],
  $data['applicationStart'],
  $data['applicationEnd'],
  $data['contactEmail'],
  $data['contactWebsite'],
  $data['description'],
  json_encode($data['requirements'])
);

if ($stmt->execute()) {
  echo json_encode(["message" => "Job posted successfully"]);
} else {
  http_response_code(500);
  echo json_encode(["message" => "Failed to post job"]);
}
