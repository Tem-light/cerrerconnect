<?php
include("../../config/db.php");

$jobId = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM jobs WHERE id=?");
$stmt->bind_param("i", $jobId);
$stmt->execute();

echo json_encode(["message" => "Job deleted"]);
