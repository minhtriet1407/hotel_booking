<?php
require_once __DIR__ . '/config/database.php';
$result = $conn->query("DESCRIBE rooms");
$output = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $output[] = $row;
    }
} else {
    $output = ["error" => $conn->error];
}
header('Content-Type: application/json');
echo json_encode($output);
?>
