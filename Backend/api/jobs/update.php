<?php
include("../../config/db.php");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$jobId = $_GET['id'];

$sql = "UPDATE jobs SET
title=?, company=?, location=?, type=?, category=?,
salary_min=?, salary_max=?, openings=?,
application_start=?, application_end=?,
contact_email=?, contact_website=?, description=?, requirements=?
WHERE id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "sssssiiissssssi",
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
  json_encode($data['requirements']),
  $jobId
);

$stmt->execute();
echo json_encode(["message" => "Job updated"]);
