<?php
//include_once 'db_connect.php';
//include_once 'functions.php';

//sec_session_start();

$session_name = 'sec_session_id';   // Set a custom session name
$secure = FALSE;
// This stops JavaScript being able to access the session id.
$httponly = true;
// Forces sessions to only use cookies.
if (ini_set('session.use_only_cookies', 1) === FALSE) {
    header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
    exit();
}
// Gets current cookies params.

$cookieParams = session_get_cookie_params();
session_set_cookie_params($cookieParams["lifetime"],
    $cookieParams["path"],
    $cookieParams["domain"],
    $secure,
    $httponly);
// Sets the session name to the one set above.
session_name($session_name);
session_start();

if (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $action = "";
}

if($action == "CurrentAssessments") {
    if (isset($_SESSION['userID'])) {
        $mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
        $user = $_SESSION['userID'];
        $stmt = $mysqli->prepare("SELECT CourseInfo.courseID, CourseInfo.courseName, CourseInfo.credits FROM StudentCourse INNER JOIN CourseInfo ON StudentCourse.courseInfoID = CourseInfo.courseInfoID WHERE grade = 'IP' AND userID = ? ");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $stmt->bind_result($courseID, $courseName, $credit);
        $output = array();

        while ($stmt->fetch()) {
			$grade = getGrade($user, $courseID);
			if($grade != 'No Grades') {
				array_push($output, array($courseID, $courseName, $credit, round($grade, 2)));
			}
			else
			{
				array_push($output, array($courseID, $courseName, $credit, $grade));
			}
        }

        echo json_encode($output);

    } else {
        echo "log in";
      }
}

if($action == 'remove')
{
    if(isset($_SESSION['userID']) AND isset($_POST['id']))
    {
        $mysqli = new mysqli("localhost","root","sqliscool","GPA_Tracker");
        $user = $_SESSION['userID'];
        $stmt = $mysqli->prepare("Delete 
        FROM StudentCourse 
        WHERE userID = ? 
        AND 
        grade = 'IP'
        AND courseInfoID in (SELECT C.courseInfoID
        	FROM CourseInfo C
        	Where courseID = ?)");
        $stmt->bind_param('ss', $user, $_POST['id']);
        $stmt->execute();
        echo "true";
    }
}

if($action == 'GetGraphData') {
    if (isset($_SESSION['userID'])) {
        $mysqli = new mysqli("localhost", "sec_user", "Uzg82t=u%#bNgPJw", "GPA_Tracker");
        $user = $_SESSION['userID'];
        $stmt = $mysqli->prepare("SELECT b.assessmentTypeID, b.percentage, a.grade, a.dateEntered, a.studentCourseID FROM Assessment as a,
          AssessmentType as b WHERE  a.studentCourseID in (SELECT studentCourseID FROM StudentCourse WHERE grade = 'IP' and userID = ?)
          AND b.assessmentTypeID = a.assessmentTypeID ORDER BY dateEntered");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $stmt->bind_result($ID, $per, $grade, $date, $course);

        $allAssessments = []; //array that will hold all of the assessment info fetched from DB
        //$allAssessments[i][0] -> $ID or assessmentTypeID
        //$allAssessments[i][1] -> $per or percent
        //$allAssessments[i][2] -> $grade
        //$allAssessments[i][3] -> $date or dateEntered
        //$allAssessments[i][4] -> $course or studentCourseID
        $trackCourse = [];

        while ($stmt->fetch()) {
            array_push($allAssessments, array($ID, $per, $grade, $date, $course));
            if (!isset($arr[$course])) {
                $arr[$course] = 1;
                array_push($trackCourse, $course);
            }
        }

        $year = substr($allAssessments[0][3], 0, 4);
        $semester = term(substr($allAssessments[0][3], 5, 2)); //fall, spring or summer
        $currTimePeriod = timePeriod($semester, $allAssessments[0][3]); //temp time period
        $timePeriodSize = checkSize($semester); //check how many segments for x-axis
        $currTrackCourse = 0;
        $tempArray = [];
        $arrayCourse = [];

        //echo "Year: $year - Semester: $semester - Current Time Period: $currTimePeriod - Size of TP: $timePeriodSize\n";

        for ($i = 0, $c = count($allAssessments); $i < $c; $i++) {
            $assessmentTimePeriod = timePeriod($semester, $allAssessments[$i][3]);
            $gradesReturned = [];
            $lastAssessment = count($allAssessments) - 1;
            //echo "Assessment Time Period: $assessmentTimePeriod\n";

            if ($assessmentTimePeriod == $currTimePeriod) {
                //store stuff
                //$arrayCourse stores array of courseID, assessmentTypeID, percent, grade
                array_push($arrayCourse, array($allAssessments[$i][4], $allAssessments[$i][0], $allAssessments[$i][1], $allAssessments[$i][2]));
                //echo "ArrayCourse stored [".$allAssessments[$i][4].",".$allAssessments[$i][0].",".$allAssessments[$i][1].",". $allAssessments[$i][2]."]\n";

                if ($i == $lastAssessment) {
                    //echo "Yes, $i == $lastAssessment\n";
                    //gardesReturned gets the average for the grades thus far for each course
                    //returns array = [[course1, grade], [course2, grade], [course3, grade]
                    $gradesReturned = gradeUpTo($arrayCourse);

                    for ($j = 0, $d = count($trackCourse); $j < $d; $j++) { // traverse each gradeReturned and add to tempArray
                        $currTrackCourse = $trackCourse[$j];

                        foreach ($gradesReturned as list($cx, $gx)) {
                            if ($currTrackCourse == $cx) {
                                // tempArray = [[time period, course, grade], [time period, course, grade]]
                                array_push($tempArray, array($currTimePeriod, $cx, $gx));
                                break;
                            }
                        }
                    }
                    $currTimePeriod = $assessmentTimePeriod; //update to new time period
                }
            } else {
                //echo "No, $i == $lastAssessment\n";
                //gradesReturned gets the average for the grades thus far for each course
                //returns array [[course1, grade], [course2, grade], [course3, grade]]
                //echo "ArrayCourse stored [".$arrayCourse[0][0].",".$arrayCourse[0][1].",".$arrayCourse[0][2].",". $arrayCourse[0][3]."]\n";
                $gradesReturned = gradeUpTo($arrayCourse);


                for ($j = 0, $d = count($trackCourse); $j < $d; $j++) { // traverse each gradeReturned and add to tempArray
                    //echo "Size of Track Course: " . count($trackCourse) . "\n";
                    //echo "Current Track Course: $currTrackCourse - $trackCourse[$j]\n";
                    $currTrackCourse = $trackCourse[$j];

                    foreach ($gradesReturned as list($cx, $gx)) {
                        //echo "Entered gradesReturned foreach loop\n";

                        if ($currTrackCourse == $cx) {
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
        for ($q = 0, $c = count($trackCourse); $q < $c; $q++) { // go through every ID
            $plots = [];
            array_push($plots, [0, 0]); //start array with [0,0]
            $currTrackCourse = $trackCourse[$q];
            $found = false;
            $y = 0;
            $currAverage = 0;

            //while - y <= checkSize()
            while ($y <= $timePeriodSize) {

                foreach ($tempArray as list($tp, $ci, $ag)) { //tp - time period, ci - course id, ag - average grade
                    //echo "Entered the foreach loop\n";

                    if ($currTrackCourse == $ci && $y == $tp) {
                        //echo "Time to plot: [". dateOfTerm($semester, $tp, $year) . ", $ag]\n";

                        //array_push($plots, array(dateOfTerm($semester, $tp, $year), $ag));
                        array_push($plots, array($tp, $ag));
                        $currAverage = $ag;
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    //echo "FOUND IT!\n";
                    $y++;
                } else {
                    //echo "Didn't find it\n";
                    //array_push($plots, array(dateOfTerm($semester, $y, $year), $currAverage));
                    array_push($plots, array($y, $currAverage));
                    $y++;
                }
            }
            //$allPlots of course
            array_push($allPoints, $plots);

        }

        echo json_encode($allPoints);
    }
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
        $springStart = date('m-d', strtotime('second monday of january' . $year));

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

function getGrade($user, $course)
{
	$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
    $stmt = $mysqli->prepare("SELECT assessmentName, percentage FROM AssessmentType WHERE studentCourseID in (SELECT studentCourseID
        FROM StudentCourse WHERE grade = 'IP' AND userID = ? AND courseInfoID in (select courseInfoID FROM CourseInfo WHERE courseID = ?))");
    $stmt->bind_param('ss', $user, $course);
    $stmt->execute();
    $stmt->bind_result($aName, $per);

    $average = 0;
    $grade;
    $totalPer = 0;
    while($stmt->fetch())
    {
        $grade = averageAssess($aName, $user, $course);
        if($grade != " ")
        {
            $average += $grade * $per;
            $totalPer += $per;
        }
    }

    if($totalPer == 0)
    {
        return "No Grades";
    }
    else{
        return $average/$totalPer;
    }
}

function averageAssess($category, $user, $course)
{
    $conn = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
    $stmt = $conn->prepare("SELECT grade
        FROM   Assessment
        WHERE  assessmentTypeID in (select assessmentTypeID 
        	FROM AssessmentType 
        	WHERE AssessmentName = ?) 
        AND 
        studentCourseID in (SELECT studentCourseID
        	FROM StudentCourse
			WHERE grade = 'IP' and userID = ? 
			AND courseInfoID in (select courseInfoID 
				FROM CourseInfo 
				WHERE courseID = ?))");
    $stmt->bind_param('sss', $category, $user, $course);
    $stmt->execute();
    $stmt->bind_result($Assessgrade);
    $runAvg = 0;
    $count = 0;
    while($stmt->fetch())
    {
        $runAvg += $Assessgrade;
        $count++;
    }
    if($count != 0)
    {
        return round($runAvg / $count, 2);
    }
    else{
        return " ";
    }
}
?>
