<?php
require_once 'config.php';

$search = $_GET['search'] ?? '';
$where = [];
$params = [];
$search_sql = '';
if (!empty($search)) {
    $search_sql = "AND (
        a.code_id LIKE ? OR a.name LIKE ? OR a.serialno LIKE ? OR a.model LIKE ? OR a.compname LIKE ? OR
        a.startdate LIKE ? OR a.expdate LIKE ? OR a.status LIKE ? OR a.remark LIKE ? OR
        l.name LIKE ? OR t.name LIKE ? OR p.name LIKE ?
    )";
    $search_param = "%$search%";
    for ($i = 0; $i < 12; $i++) $params[] = $search_param;
}
if (!empty($_GET['filter_location'])) {
    $where[] = "a.location_id = ?";
    $params[] = $_GET['filter_location'];
}
if (!empty($_GET['filter_type'])) {
    $where[] = "a.type_id = ?";
    $params[] = $_GET['filter_type'];
}
if (!empty($_GET['filter_position'])) {
    $where[] = "a.position_id = ?";
    $params[] = $_GET['filter_position'];
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
if ($search_sql) {
    $where_sql .= ($where_sql ? " AND " : "WHERE ") . substr($search_sql, 4);
}

$limit = 50;
$page = max((int)($_GET['page'] ?? 1), 1);
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as c 
              FROM assets a 
              LEFT JOIN locations l ON a.location_id = l.id 
              LEFT JOIN asset_types t ON a.type_id = t.id 
              LEFT JOIN positions p ON a.position_id = p.id 
              $where_sql";
$stmt = $conn->prepare($count_sql);
if ($params) {
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc()['c'];
$stmt->close();

$data_sql = "SELECT a.*, l.name as location_name, t.name as type_name, p.name as position_name 
             FROM assets a 
             LEFT JOIN locations l ON a.location_id = l.id 
             LEFT JOIN asset_types t ON a.type_id = t.id 
             LEFT JOIN positions p ON a.position_id = p.id 
             $where_sql 
             ORDER BY a.id ASC 
             LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($data_sql);
if ($params) {
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$assets = $stmt->get_result();
?>