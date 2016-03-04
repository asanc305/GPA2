<?php
include_once '../common_files/db_connect.php';
include_once '../common_files/functions.php';
sec_session_start();

if (isset($_POST['action'])) {
    //echo json_encode('inside isset');
    $action = $_POST['action'];
} else {
    var_dump($_POST);
    echo 'inside else';
    $action = "";
}

if ($action == "getGradProgram") {
    $user  = $_SESSION['username'];
    $stmt5 = $mysqli->prepare("SELECT graduateProgram, requiredGPA FROM GraduatePrograms ");
    $stmt5->execute();
    $stmt5->bind_result($prg, $gpa);
    $output4 = array();
    while ($stmt5->fetch()) {
        array_push($output4, array(
            $prg,
            $gpa
        ));
    }
    echo json_encode($output4);

}

if ($action == "GPAGoal") {
    $user  = $_SESSION['username'];
    $userID = $_SESSION['userID'];
    $stmt5 = $mysqli->prepare("SELECT gpaGoal FROM Users WHERE userID = ?");
    $stmt5->bind_param('s', $userID);
    $stmt5->execute();
    $stmt5->bind_result($gpaGoal);
    $output4 = array();
    while ($stmt5->fetch()) {
        array_push($output4, array(
            $gpaGoal
        ));
    }
    echo json_encode($output4);

}

//Query DB for creditsTaken and creditsLeft
if ($action == "TakenAndRemaining") {
    $user   = $_SESSION['username'];
    $userID = $_SESSION['userID'];
    $stmt   = $mysqli->prepare("Select  f.creditsTaken, e.RemainingCredits From ((Select sum(CourseInfo.credits) as RemainingCredits From (Select a.courseInfoID From (SELECT courseInfoID FROM `MajorBucketRequiredCourses` Where bucketID in (Select  bucketID From MajorBucket Where majorID in (Select majorID From StudentMajor Where userID = '" . $userID . "') AND allRequired = '1'))a inner join StudentCourse on a.courseInfoID = StudentCourse.courseInfoID AND  StudentCourse.userID = '" . $userID . "' AND StudentCourse.grade = 'ND'

UNION

Select b.courseInfoID From (SELECT courseInfoID FROM `MajorBucketRequiredCourses` Where bucketID in (Select  bucketID From MajorBucket Where majorID  in (Select majorID From StudentMajor Where userID = '" . $userID . "') AND allRequired = '0'))b inner join StudentCourse on b.courseInfoID = StudentCourse.courseInfoID AND  StudentCourse.userID = '" . $userID . "' AND StudentCourse.grade = 'ND' and StudentCourse.selected = '1'

)c inner join CourseInfo on CourseInfo.courseInfoID = c.courseInfoID)e
JOIN
(Select Sum(CourseInfo.credits) as creditsTaken from CourseInfo inner join StudentCourse on StudentCourse.courseInfoID = CourseInfo.courseInfoID AND StudentCourse.grade not in (Select grade from StudentCourse where grade = 'ND') AND StudentCourse.userID ='" . $userID . "')f)");
    f.
    $stmt->execute();
    $stmt->bind_result($creditsTaken, $creditsLeft);
    $output = array();
    while ($stmt->fetch()) {
        array_push($output, array(
            $creditsTaken,
            $creditsLeft
        ));
    }
    echo json_encode($output);
}

//Query DB for grades and course credits
if ($action == "GradesAndCredits"){
    $user = $_SESSION['user'];
    $userID = $_SESSION['userID'];

    $log = fopen("phplog.txt", "w");

    if ($stmt = $mysqli->prepare("SELECT StudentCourse.grade, CourseInfo.credits FROM CourseInfo INNER JOIN StudentCourse
             ON StudentCourse.courseInfoID = CourseInfo.courseInfoID WHERE StudentCourse.userID = ?
             AND NOT StudentCourse.grade = 'IP' AND NOT StudentCourse.grade = 'ND' AND NOT StudentCourse.grade = 'DR'
             AND NOT StudentCourse.grade = 'TR'")){
        $stmt->bind_param('s', $userID);
        $stmt->execute();
        $stmt->bind_result($courseGrade, $courseCredits);
        $gradesAndCredits = array();

        while ($stmt->fetch()){
            fwrite($log, "$courseGrade : $courseCredits\n");
            array_push($gradesAndCredits, array(
                $courseGrade,
                $courseCredits
            ));
        }
    }
    else{
        fwrite($log, "S:$stmt->error \n M:$mysqli->error");
    }

    fwrite($log, "completed successfully\n");
    echo json_encode($gradesAndCredits);
}

//Query for Course Names and IDs and Credits In Progress
if ($action == "CurrentCourses") {
    $user = $_SESSION['user'];
    $userID = $_SESSION['userID'];

    $log = fopen("phplog.txt", "a");

    if ($stmt = $mysqli->prepare("SELECT CourseInfo.courseID, CourseInfo.courseName, CourseInfo.credits, StudentCourse.weight,StudentCourse.relevance FROM StudentCourse INNER JOIN CourseInfo ON StudentCourse.courseInfoID = CourseInfo.courseInfoID WHERE grade = 'IP' AND userID = ? ")) {
        $stmt->bind_param('s', $userID);
        $stmt->execute();
        $stmt->bind_result($courseID, $courseName, $credits, $courseWeight, $courseRelevance);
        $courseInfo = array();

        while ($stmt->fetch()){
            fwrite($log, "$courseID $courseName\n");
            array_push($courseInfo, array(
                $courseID,
                $courseName,
                $credits,
                $courseWeight,
                $courseRelevance
            ));
        }
    }
    else{
        fwrite($log, "S:$stmt->error \n M:$mysqli->error");
    }

    fwrite($log, "completed successfully\n");
    echo json_encode($courseInfo);
}

// in theory this should UPDATE weight and relevance in DB
if ($action == "ModifyWeightAndRelevance") {
    $user = $_SESSION['user'];
    $userID = $_SESSION['userID'];

    if (isset($_POST['courseID'])) {
        $courseID = $_POST['courseID'];
    } else {
        $courseID = "";
    }

    if (isset($_POST['modifiedRelevance'])) {
        $modifiedRelevance = $_POST['modifiedRelevance'];
    } else {
        $modifiedRelevance = "";
    }

    if (isset($_POST['modifiedWeight'])) {
        $modifiedWeight = $_POST['modifiedWeight'];
    } else {
        $modifiedWeight = "";
    }

    if ($stmt = $mysqli->prepare("UPDATE StudentCourse SET relevance = ?, weight = ? WHERE courseInfoID = (SELECT courseInfoID FROM CourseInfo WHERE courseID = ?) AND userID = ?")){
        $stmt->bind_param('ssss', $modifiedRelevance, $modifiedWeight, $courseID, $userID);
        $stmt->execute();
    }

    if ($mysqli->query($sql) === TRUE) {

        $result = array(
            'success' => true
        );
    } else {
        $result = array(
            'success' => false,
            'message' => 'Weight and Relevance could not be updated'
        );
    }

    echo json_encode($result);
}

if ($action == "getCurrentProgram") {
    $user   = $_SESSION['username'];
    $userID = $_SESSION['userID'];
    $stmt5  = $mysqli->prepare("SELECT majorName FROM Major Where majorID in (Select majorID From StudentMajor Where userID ='" . $userID . "') ");
    $stmt5->execute();
    $stmt5->bind_result($currentProg);
    $output4 = array();
    while ($stmt5->fetch()) {
        array_push($output4, array(
            $currentProg
        ));
    }
    echo json_encode($output4);

}

?>

