<?php

require_once 'employee_shift.php';

class ShiftWorkParser {
    private $employeeShifts = [];

    public function loadShiftsFromInputHandler($fileName) {
        $filePath = __DIR__ . '/input_handler/' . $fileName;

        if (!file_exists($filePath)) {
            throw new Exception('The intended file not found: ' . $fileName);
        }

        $shiftsDataArray = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error on decoding JSON file: ' . json_last_error_msg());
        }

        foreach ($shiftsDataArray as $shiftData) {
            $shift = new EmployeeShift(
                $shiftData['ShiftID'],
                $shiftData['EmployeeID'],
                $shiftData['StartTime'],
                $shiftData['EndTime']
            );

            if (!isset($this->employeeShifts[$shift->getEmployeeID()])) {
                $this->employeeShifts[$shift->getEmployeeID()] = [];
            }

            $this->employeeShifts[$shift->getEmployeeID()][] = $shift;
        }
    }


    public function processWeeklyShifts() {
        $processedWeeklyShifts = [];
    
        foreach ($this->employeeShifts as $employeeID => $shifts) {
            $this->sortShifts($shifts);
    
            $weeklyShiftHours = [];
            $invalidShifts = [];
    
            // invalid shifts 
            foreach ($shifts as $shift) {
                foreach ($shifts as $otherShift) {
                    if ($shift !== $otherShift && $shift->checkTimeOverlapping($otherShift)) {
                        $invalidShifts[$shift->getShiftID()] = true;
                        $invalidShifts[$otherShift->getShiftID()] = true;
                    }
                }
            }
    
           
            foreach ($shifts as $shift) {
                if (isset($invalidShifts[$shift->getShiftID()])) {
                    continue; // Skip invalid shifts
                }
    
                $startTime = $shift->getStartTime();
                $endTime = $shift->getEndTime();
    
                // Central Time
                $startTime->setTimezone(new DateTimeZone('America/Chicago'));
                $endTime->setTimezone(new DateTimeZone('America/Chicago'));
    
                
                $currentStartOfWeek = $this->fetchStartOfWeek($startTime);
                
                // Check if the shift crosses Sunday midnight
                if ($endTime > $this->fetchNextSundayMidnight($startTime)) {
                    
                    // current week
                    $hoursFirstPart = ($this->fetchNextSundayMidnight($startTime)->getTimestamp() - $startTime->getTimestamp()) / 3600;
                    $week1 = $currentStartOfWeek->format('Y-m-d');
    
                 
                    $hoursFirstPart = $this->dayLightSavingHours($startTime, $hoursFirstPart);
    
                    $this->updateHoursOfTheWeek($weeklyShiftHours, $week1, $hoursFirstPart);
    
                    // from sunday midnight: next week
                    $hoursSecondPart = ($endTime->getTimestamp() - $this->fetchNextSundayMidnight($startTime)->getTimestamp()) / 3600;
                    $week2 = $this->fetchStartOfWeek($endTime)->format('Y-m-d');
    
                   
                    $hoursSecondPart = $this->dayLightSavingHours($this->fetchNextSundayMidnight($startTime), $hoursSecondPart);
    
                    $this->updateHoursOfTheWeek($weeklyShiftHours, $week2, $hoursSecondPart);
                } else {
                    $weekStart = $currentStartOfWeek->format('Y-m-d');
                    $hours = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 3600;
    
                    
                    $hours = $this->dayLightSavingHours($startTime, $hours);
    
                    $this->updateHoursOfTheWeek($weeklyShiftHours, $weekStart, $hours);
                }
            }
    
            //output 
            foreach ($weeklyShiftHours as $weekStart => $hours) {
                $invalidShiftsForWeek = [];
                foreach ($shifts as $shift) {
                    // Check  overlaps 
                    if ($this->fetchStartOfWeek($shift->getStartTime())->format('Y-m-d') === $weekStart && isset($invalidShifts[$shift->getShiftID()])) {
                        $invalidShiftsForWeek[] = $shift->getShiftID();
                    }
                }
                $invalidShiftsArray = array_unique($invalidShiftsForWeek);
                $regularHours = round($hours['RegularHours'], 2);
                $OvertimeHours = round($hours['OvertimeHours'], 2);

                $processedWeeklyShifts[] = [
                    'EmployeeID' => $employeeID,
                    'StartOfWeek' => $weekStart,
                    'RegularHours' => $regularHours,
                    'OvertimeHours' => $OvertimeHours,
                    'InvalidShifts' => $invalidShiftsArray,
                ];
            }
        }
    
        return $processedWeeklyShifts;
    }
    

    private function sortShifts(array &$shifts) {
        usort($shifts, function ($a, $b) {
            return $a->getStartTime() <=> $b->getStartTime();
        });
    }

    private function fetchStartOfWeek($dateTime) {
        $dateTime = $dateTime->setTimezone(new DateTimeZone('America/Chicago'));
        $startOfWeek = clone $dateTime; // clone of the object
        $startOfWeek->modify('last sunday midnight');
        return $startOfWeek;
    }
    
    private function fetchNextSundayMidnight($dateTime) {
        $nextSunday = clone $dateTime;
        $nextSunday->modify('next sunday midnight');
        return $nextSunday;
    }

    private function dayLightSavingHours($startTime,$hours) {
        //checking second sunday in March or first sunday in November
        $marchCST = $startTime->format('m') === '03' && $startTime->format('w') === '0' && $startTime->format('j') >= 8 && $startTime->format('j') <= 14;
        $novemberCDT = $startTime->format('m') === '11' && $startTime->format('w') === '0' && $startTime->format('j') <= 7;

        if ($marchCST) {
            return $hours - 1;
        } else if ($novemberCDT) {
            return $hours + 1;
        }

        return $hours;
    }
    

    private function updateHoursOfTheWeek(&$weeklyShiftHours, $weekStart, $hours) {
        if (!isset($weeklyShiftHours[$weekStart])) {
            $weeklyShiftHours[$weekStart] = [
                'RegularHours' => 0,
                'OvertimeHours' => 0,
            ];
        }
    
        if ($hours <= (40 - $weeklyShiftHours[$weekStart]['RegularHours'])) {
            $regularHours = $hours;
        } else {
            $regularHours = 40 - $weeklyShiftHours[$weekStart]['RegularHours'];
        }

        if ($hours > $regularHours) {
            $overtimeHours = $hours - $regularHours;
        } else {
            $overtimeHours = 0;
        }

    
        $weeklyShiftHours[$weekStart]['RegularHours'] = $weeklyShiftHours[$weekStart]['RegularHours'] + $regularHours;
        $weeklyShiftHours[$weekStart]['OvertimeHours'] = $weeklyShiftHours[$weekStart]['OvertimeHours'] + $overtimeHours;
        
    }


   
}

?>