<?php
require 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['type']) || !isset($_GET['value'])) {
    echo json_encode(['available' => false]);
    exit;
}

$type = $_GET['type'];
$value = $_GET['value'];

if ($type === 'username') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
} elseif ($type === 'email') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
} else {
    echo json_encode(['available' => false]);
    exit;
}

$stmt->execute([$value]);
$count = $stmt->fetchColumn();

echo json_encode(['available' => $count === 0]);