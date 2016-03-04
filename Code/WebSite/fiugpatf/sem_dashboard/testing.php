<?php

{

    $allAssessments = [
        ['46', '40', '85', '2015-09-01', '2502'],
        ['49', '27.78', '93', '2015-09-01', '2503'],
        ['33', '20', '90', '2015-09-01', '2504'],
        ['33', '20', '79', '2015-09-16', '2504'],
        ['34', '30', '69', '2015-10-06', '2504'],
        ['50', '27.78', '88', '2015-10-21', '2503'],
        ['36', '25', '95', '2015-11-02', '2504'],
        ['33', '20', '88', '2015-11-26', '2504'],
        ['34', '30', '88', '2015-12-11', '2504'],
        ['33', '20', '45', '2015-12-11', '2504'],
        ['35', '25', '96', '2015-12-19', '2504'],
        ['51', '44.44', '73', '2015-12-19', '2503']
    ]; //array that will hold all of the assessment info fetched from DB
    //$allAssessments[i][0] -> $ID or assessmentTypeID
    //$allAssessments[i][1] -> $per or percent
    //$allAssessments[i][2] -> $grade
    //$allAssessments[i][3] -> $date or dateEntered
    //$allAssessments[i][4] -> $course or studentCourseID
    $trackCourse = ['2502', '2503', '2504'];

    $year = substr($allAssessments[0][3], 0, 4);
    $semester = term(substr($allAssessments[0][3], 5, 2)); //fall, spring or summer
    $currTimePeriod = timePeriod($semester, $allAssessments[0][3]); //temp time period
    $timePeriodSize = checkSize($semester); //check how many segments for x-axis
    $currTrackCourse = 0;
    $tempArray = [];
    $arrayCourse = [];

    //echo "Year: $year - Semester: $semester - Current Time Period: $currTimePeriod - Size of TP: $timePeriodSize\n";

    for($i = 0, $c = count($allAssessments); $i < $c; $i++) {
        $assessmentTimePeriod = timePeriod($semester, $allAssessments[$i][3]);
        $gradesReturned = [];
        $lastAssessment = count($allAssessments)-1;
        //echo "Assessment Time Period: $assessmentTimePeriod\n";

        if($assessmentTimePeriod == $currTimePeriod) {
            //store stuff
            //$arrayCourse stores array of courseID, assessmentTypeID, percent, grade
            array_push($arrayCourse, array($allAssessments[$i][4], $allAssessments[$i][0], $allAssessments[$i][1], $allAssessments[$i][2]));
            //echo "ArrayCourse stored [".$allAssessments[$i][4].",".$allAssessments[$i][0].",".$allAssessments[$i][1].",". $allAssessments[$i][2]."]\n";

            if($i == $lastAssessment) {
                //echo "Yes, $i == $lastAssessment\n";
                //gardesReturned gets the average for the grades thus far for each course
                //returns array = [[course1, grade], [course2, grade], [course3, grade]
                $gradesReturned = gradeUpTo($arrayCourse);

                for($j = 0, $d = count($trackCourse); $j < $d; $j++) { // traverse each gradeReturned and add to tempArray
                    $currTrackCourse = $trackCourse[$j];

                    foreach($gradesReturned as list($cx, $gx)) {
                        if($currTrackCourse == $cx) {
                            // tempArray = [[time period, course, grade], [time period, course, grade]]
                            array_push($tempArray, array($currTimePeriod, $cx, $gx));
                            break;
                        }
                    }
                }
                $currTimePeriod = $assessmentTimePeriod; //update to new time period
            }
        }
        else {
            //echo "No, $i == $lastAssessment\n";
            //gradesReturned gets the average for the grades thus far for each course
            //returns array [[course1, grade], [course2, grade], [course3, grade]]
            //echo "ArrayCourse stored [".$arrayCourse[0][0].",".$arrayCourse[0][1].",".$arrayCourse[0][2].",". $arrayCourse[0][3]."]\n";
            $gradesReturned = gradeUpTo($arrayCourse);


            for($j = 0, $d = count($trackCourse); $j < $d; $j++) { // traverse each gradeReturned and add to tempArray
                //echo "Size of Track Course: " . count($trackCourse) . "\n";
                //echo "Current Track Course: $currTrackCourse - $trackCourse[$j]\n";
                $currTrackCourse = $trackCourse[$j];

                foreach($gradesReturned as list($cx, $gx)) {
                    //echo "Entered gradesReturned foreach loop\n";

                    if($currTrackCourse == $cx) {
                        // tempArray = [[time period, course, grade], [time period, course, grade]]
                        array_push($tempArray, array($currTimePeriod, $cx, $gx));
                        break;
                    }
                }
            }
            $currTimePeriod = $assessmentTimePeriod; //update to new time period
        }
    }

    $allPoints = [];
    for($q = 0, $c = count($trackCourse); $q < $c; $q++) { // go through every course
        $plots = [];
        array_push($plots, [0,0]); //start array with [0,0]
        $currTrackCourse = $trackCourse[$q];
        $found = false;
        $y = 0;
        $currAverage = 0;

        //while - y <= checkSize()
        while($y <= $timePeriodSize) {
            echo "While: $y\n";

            foreach ($tempArray as list($tp, $ci, $ag)) { //tp - time period, ci - course id, ag - average grade
                //echo "Entered the foreach loop\n";

                if ($currTrackCourse == $ci && $y == $tp) {
                    //echo "Time to plot: [". dateOfTerm($semester, $tp, $year) . ", $ag]\n";
                    array_push($plots, array(dateOfTerm($semester, $tp, $year), $ag));
                    $currAverage = $ag;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                echo "FOUND IT!\n";
                $y++;
            }
            else {
                //echo "Didn't find it\n";
                array_push($plots, array(dateOfTerm($semester, $y, $year), $currAverage));
                $y++;
            }
        }
        echo "I plotted\n";
        //$allPlots of course
        array_push($allPoints, $plots);

    }

    //echo json_encode($allPoints);
        //echo $allPoints;
}


function dateOfTerm($term, $timePeriod, $year) {

    if($term == 'fall') {
        $fallStart = date('Y-m-d', strtotime('third monday of august' . $year));

        if($timePeriod == 0) {
            return date('m-d', strtotime('third monday of august' . $year));
        }
        else if($timePeriod == 1) {
            return date('m-d', strtotime($fallStart . '+ 3 weeks'));
        }
        else if($timePeriod == 2) {
            return date('m-d', strtotime($fallStart . '+ 6 weeks'));
        }
        else if($timePeriod == 3) {
            return date('m-d', strtotime($fallStart . '+ 9 weeks'));
        }
        else if($timePeriod == 4) {
            return date('m-d', strtotime($fallStart . '+ 12 weeks'));
        }
        else if($timePeriod == 5) {
            return date('m-d', strtotime($fallStart . '+ 15 weeks'));
        }
        else if($timePeriod == 6) {
            return date('m-d', strtotime($fallStart . '+ 18 weeks'));
        }
        else {
            return 'summer';
        }
    }
    else if($term == 'spring') {
        $springStart = date('Y-m-d', strtotime('second monday of january' . $year));

        if($timePeriod == 0) {
            return date('m-d', strtotime('second monday of january' . $year));
        }
        else if($timePeriod == 1) {
            return date('m-d', strtotime($springStart . '+ 3 weeks'));
        }
        else if($timePeriod == 2) {
            return date('m-d', strtotime($springStart . '+ 6 weeks'));
        }
        else if($timePeriod == 3) {
            return date('m-d', strtotime($springStart . '+ 9 weeks'));
        }
        else if($timePeriod == 4) {
            return date('m-d', strtotime($springStart . '+ 12 weeks'));
        }
        else if($timePeriod == 5) {
            return date('m-d', strtotime($springStart . '+ 15 weeks'));
        }
        else if($timePeriod == 6) {
            return date('m-d', strtotime($springStart . '+ 18 weeks'));
        }
        else {
            return 'summer';
        }
    }
    else {
        return '';
    }
}

function term($month) {
    if($month == '06' || $month == '07') {
        return 'summer';
    }
    else if($month == '08' || $month == '09' || $month == '10' || $month == '11' || $month == '12') {
        return 'fall';
    }
    else if($month == '01' || $month == '02' || $month == '03' || $month == '04' || $month == '05') {
        return 'spring';
    }
    else {
        return '';
    }
}

function timePeriod($t, $d) {
    $year = substr($d, 0, 4);

    if($t == 'fall') {
        $fallStart = date('Y-m-d', strtotime('third monday of august' . $year));
        $tp6 = date('Y-m-d', strtotime($fallStart . '+ 18 weeks'));
        $tp5 = date('Y-m-d', strtotime($fallStart . '+ 15 weeks'));
        $tp4 = date('Y-m-d', strtotime($fallStart . '+ 12 weeks'));
        $tp3 = date('Y-m-d', strtotime($fallStart . '+ 9 weeks'));
        $tp2 = date('Y-m-d', strtotime($fallStart . '+ 6 weeks'));
        $tp1 = date('Y-m-d', strtotime($fallStart . '+ 3 weeks'));

        if(($d >= $fallStart) && ($d <= $tp1)) {
            return 1;
        }
        else if(($d > $tp1) && ($d <= $tp2)) {
            return 2;
        }
        else if(($d > $tp2) && ($d <= $tp3)) {
            return 3;
        }
        else if(($d > $tp3) && ($d <= $tp4)) {
            return 4;
        }
        else if(($d > $tp4) && ($d <= $tp5)) {
            return 5;
        }
        else if(($d > $tp5) && ($d < $tp6)) {
            return 6;
        }
        else {
            return 'summer';
        }
    }
    else if($t == 'spring') {
        $springStart = date('Y-m-d', strtotime('second monday of january' . $year));
        $tp6 = date('Y-m-d', strtotime($springStart . '+ 18 weeks'));
        $tp5 = date('Y-m-d', strtotime($springStart . '+ 15 weeks'));
        $tp4 = date('Y-m-d', strtotime($springStart . '+ 12 weeks'));
        $tp3 = date('Y-m-d', strtotime($springStart . '+ 9 weeks'));
        $tp2 = date('Y-m-d', strtotime($springStart . '+ 6 weeks'));
        $tp1 = date('Y-m-d', strtotime($springStart . '+ 3 weeks'));

        if(($d >= $springStart) && ($d <= $tp1)) {
            return date('m-d', strtotime($springStart . '+ 3 weeks'));
        }
        else if(($d > $tp1) && ($d <= $tp2)) {
            return date('m-d', strtotime($springStart . '+ 6 weeks'));
        }
        else if(($d > $tp2) && ($d <= $tp3)) {
            return date('m-d', strtotime($springStart . '+ 9 weeks'));
        }
        else if(($d > $tp3) && ($d <= $tp4)) {
            return date('m-d', strtotime($springStart . '+ 12 weeks'));
        }
        else if(($d > $tp4) && ($d <= $tp5)) {
            return date('m-d', strtotime($springStart . '+ 15 weeks'));
        }
        else if(($d > $tp5) && ($d < $tp6)) {
            return date('m-d', strtotime($springStart . '+ 18 weeks'));
        }
        else {
            return 'summer';
        }
    }
    else {
        return '';
    }

}

function checkSize($term) {

    if($term == 'fall') {
        return 6;
    }
    else if($term == 'spring') {
        return 6;
    }
    else if($term == 'summera') {
        return 4;
    }
    else if($term == 'summerb') {
        return 4;
    }
    else { //summer C
        return 6;
    }

}

//$array = [[2504, 33, 30, 55], [2504, 33, 30, 65], [2504, 34, 50, 70], [2502, 36, 50, 80]];

//gradeUpTo($array);

function gradeUpTo($arrayCourse){
    //$arrayCourse stores array of courseID, assessmentTypeID, percent, grade
    //echo "Entered gradeUpTo\n";
    //echo "ArrayCourse stored [".$arrayCourse[0][4].",".$arrayCourse[0][0].",".$arrayCourse[0][1].",". $arrayCourse[0][2]."]\n";
    //echo "ArrayCourse stored [".$arrayCourse[1][4].",".$arrayCourse[1][0].",".$arrayCourse[1][1].",". $arrayCourse[1][2]."]\n";

    $listCourse = [];
    $gradeEachCourse = [];

    foreach($arrayCourse as list($course, $ID, $percent, $grade)) {
        //echo "Entered gradeUpTo foreach loop\n";
        if(!isset($arr[$course])) {
            $arr[$course] = 1; //arr[$ID] is set
            array_push($listCourse, $course);
        }
    }

    for($i = 0, $c = count($listCourse); $i < $c; $i++) { // go through each Course

        $currCourse = $listCourse[$i];
        $collectAssessments = [];

        foreach($arrayCourse as list($co, $a, $p, $g)) {
            if($currCourse == $co) { //look fot same course
                array_push($collectAssessments, array($a, $p, $g)); // store ID, percent, grade
            }
        }
        $average = findAvg($collectAssessments); // find the current average for course

        //echo "Current Course: $currCourse - Average Grade: $average\n";

        array_push($gradeEachCourse, array($currCourse, $average));
    }

    return $gradeEachCourse;
}

function findAvg($arrCourse) {

    $listID = [];
    $calculateScore = 0;
    $weightUsed = 0;

    foreach($arrCourse as list($ID, $percent, $grade)) { // collect unique IDs

        if(!isset($arr[$ID])) {
            $arr[$ID] = 1; //arr[$ID] is set
            array_push($listID, $ID);
        }

    }

    for($i = 0, $c = count($listID); $i < $c; $i++) { // go through each ID

        //echo "Assessment Type ID: $listID[$i] \n";

        $currID = $listID[$i];
        $gradeTotal = 0;
        $currPercent = 0;
        $x = 0;

        foreach($arrCourse as list($a, $p, $g)) {
            if($currID == $a) { //look fot same IDs
                $gradeTotal += $g; // add grade to total
                $currPercent = $p; //percentage for AssessmentTypeID
                $x++; // track how many grades with the same AssessmentTypeID
            }
        }

        //echo "Assessment Type ID: $listID[$i] - Grade Total: $gradeTotal - Percent: $currPercent - How Many: $x\n";

        $calculateScore += (($gradeTotal / $x) * ($currPercent/100));
        $weightUsed += $currPercent;

        //echo "$calculateScore - $weightUsed\n";
    }

    $finalScore = ($calculateScore / $weightUsed) * 100;
    //echo "$finalScore\n";
    return $finalScore;
}




?>