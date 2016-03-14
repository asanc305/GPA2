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

