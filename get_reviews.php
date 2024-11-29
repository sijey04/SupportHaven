<?php
require_once 'connection.php';

if (!isset($_GET['technician_id'])) {
    echo json_encode([]);
    exit;
}

$database = new Connection();
$db = $database->getConnection();

$query = "SELECT tr.*, u.firstname, u.lastname 
          FROM technician_reviews tr
          JOIN users u ON tr.user_id = u.id
          WHERE tr.technician_id = :technician_id
          ORDER BY tr.created_at DESC
          LIMIT 3";

$stmt = $db->prepare($query);
$stmt->bindParam(':technician_id', $_GET['technician_id']);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($reviews); 