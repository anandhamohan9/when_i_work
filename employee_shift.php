<?php

class EmployeeShift {
    public $shiftID;
    public $employeeID;
    public $startTime;
    public $endTime;

    public function __construct($shiftID, $employeeID, $startTime, $endTime) {
        $this->shiftID = $shiftID;
        $this->employeeID = $employeeID;
        
         // Validate start time
         try {
            $this->startTime = new DateTime($startTime);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid start time : ' . $e->getMessage());
        }

        // Validate end time
        try {
            $this->endTime = new DateTime($endTime);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid end time : ' . $e->getMessage());
        }

        // check end time is greater than start time
        if ($this->startTime >= $this->endTime) {
            throw new InvalidArgumentException('End time must be greater than start time!!');
        }
    }

    public function getShiftID() {
        return $this->shiftID;
    }

    public function getEmployeeID() {
        return $this->employeeID;
    }

    public function getStartTime() {
        return $this->startTime;
    }

    public function getEndTime() {
        return $this->endTime;
    }


    public function checkTimeOverlapping(employeeShift $otherShift) {
        // Check if there is any overlap between two shifts
        return $this->startTime < $otherShift->getEndTime() && $this->endTime > $otherShift->getStartTime();
    }

}
?>
