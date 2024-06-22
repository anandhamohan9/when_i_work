<?php

require_once 'employee_shift.php';
require_once 'shift_work_parser.php';

try {
   
    $shiftManager = new ShiftWorkParser();
    $shiftManager->loadShiftsFromInputHandler('dataset.json');

    $results = $shiftManager->processWeeklyShifts();

    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
