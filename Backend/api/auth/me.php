<?php
require_once "../middleware/auth.php";
require_once "../config/database.php";
require_once "../helpers/response.php";

$stmt = $pdo->prepare("SELECT id,name,email,role FROM users WHERE id=?");
$stmt->execute([$_REQUEST["user"]["id"]]);

jsonResponse($stmt->fetch());
