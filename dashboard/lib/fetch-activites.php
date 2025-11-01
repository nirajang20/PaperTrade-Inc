<?php
require "Core.php";
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['active_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$user_id = (int) $_SESSION['active_id'];

$limit   = isset($_GET['limit'])  ? (int) $_GET['limit']  : 40;
$offset  = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$status  = (isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'all' && $_GET['status'] !== 'All')
    ? $_GET['status']
    : null;
$search  = isset($_GET['search']) ? trim($_GET['search']) : null;
$start   = isset($_GET['start'])  && $_GET['start'] !== '' ? $_GET['start'] . " 00:00:00" : null;
$end     = isset($_GET['end'])    && $_GET['end']   !== '' ? $_GET['end']   . " 23:59:59" : null;

$sql = "
    SELECT
        id,
        action_title,
        page_name,
        notes,
        status,
        device,
        ipv4,
        COALESCE(point_color, '#59a54e') AS point_color,
        created_at
    FROM activities
    WHERE user_id = ?
";
$params = [$user_id];
$types  = "i";

if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types   .= "s";
}

if ($search) {
    $sql .= " AND (action_title LIKE ? OR page_name LIKE ? OR device LIKE ?)";
    $s = "%{$search}%";
    array_push($params, $s, $s, $s);
    $types .= "sss";
}

if ($start) {
    $sql .= " AND created_at >= ?";
    $params[] = $start;
    $types   .= "s";
}

if ($end) {
    $sql .= " AND created_at <= ?";
    $params[] = $end;
    $types   .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types   .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}

echo json_encode($out);

