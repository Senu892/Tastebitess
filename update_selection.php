<?php
// update_selection.php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['selected_snacks'])) {
    $_SESSION['selected_snacks'] = $input['selected_snacks'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
}
?>