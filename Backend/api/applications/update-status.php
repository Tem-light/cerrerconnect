<?php
include("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
$stmt->bind_param("si", $data['status'], $data['applicationId']);
$stmt->execute();

echo json_encode(["message" => "Status updated"]);
?>