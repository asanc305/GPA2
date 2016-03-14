<?php
/**
 * Created by PhpStorm.
 * User: Lizette Mendoza
 * Date: 3/9/16
 * Time: 11:57 PM
 */
include_once'../common_files/dbconnector.php';

class SemesterForecastController {

    protected $session_name = 'sec_session_id';   // Set a custom session name
    protected $secure = false; // This stops JavaScript being able to access the session id.
    protected $httponly = true; // Forces sessions to only use cookies.
    protected $cookieParams;
    protected $userID;
    protected $username;

    public function __construct() {

        if (ini_set('session.use_only_cookies', 1) === FALSE) {
            header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
            exit();
        }
        // Gets current cookies params.
        $this->cookieParams = session_get_cookie_params();
        session_set_cookie_params($this->cookieParams["lifetime"],
            $this->cookieParams["path"],
            $this->cookieParams["domain"],
            $this->secure,
            $this->httponly);

        // Sets the session name to the one set above.
        session_name($this->session_name);
        session_start();

        $this->userID = $_SESSION['userID'];
        $this->username = $_SESSION['username'];
    }

    function GPAGoal() {
        $db = new DatabaseConnector();
        $return = [];

        $stmt = "SELECT gpaGoal FROM Users WHERE userID = ?";
        $params = array($this->userID);
        $output = $db->select($stmt, $params);

        if($output[0][0] == "") {
            //toLog(1, "ERROR", "GPAGoal", "GPA Goal is null");
        }
        else {
            for ($i = 0, $c = count($output); $i < $c; $i++) {
                $gpaGoal = $output[$i][0];
                array_push($return, array($gpaGoal));
            }

            echo json_encode($return);
        }

    }

    //Query DB for creditsTaken and creditsLeft
    function takenAndRemaining() {
        $db = new DatabaseConnector();
        $return = [];

        $stmt   = "Select  f.creditsTaken, e.RemainingCredits From ((Select sum(CourseInfo.credits) as RemainingCredits From (Select a.courseInfoID From (SELECT courseInfoID FROM `MajorBucketRequiredCourses` Where bucketID in (Select  bucketID From MajorBucket Where majorID in (Select majorID From StudentMajor Where userID = ?) AND allRequired = '1'))a inner join StudentCourse on a.courseInfoID = StudentCourse.courseInfoID AND  StudentCourse.userID = ? AND StudentCourse.grade = 'ND'

            UNION

            Select b.courseInfoID From (SELECT courseInfoID FROM `MajorBucketRequiredCourses` Where bucketID in (Select  bucketID From MajorBucket Where majorID  in (Select majorID From StudentMajor Where userID = ?) AND allRequired = '0'))b inner join StudentCourse on b.courseInfoID = StudentCourse.courseInfoID AND  StudentCourse.userID = ? AND StudentCourse.grade = 'ND' and StudentCourse.selected = '1'

              )c inner join CourseInfo on CourseInfo.courseInfoID = c.courseInfoID)e

            JOIN

                (Select Sum(CourseInfo.credits) as creditsTaken from CourseInfo inner join StudentCourse on StudentCourse.courseInfoID = CourseInfo.courseInfoID AND StudentCourse.grade not in (Select grade from StudentCourse where grade = 'ND') AND StudentCourse.userID = ?)f)";
        $params = array($this->userID, $this->userID, $this->userID, $this->userID, $this->userID);
        $output = $db->select($stmt, $params);

        if($output[0][0] == "") {
            //toLog(1, "ERROR", "takenAndRemaining", "creditsTaken is null");
        }
        else if($output[0][1] == "") {
            //toLog(1, "ERROR", "takenAndRemaining", "creditsLeft is null");
        }
        else {
            for ($i = 0, $c = count($output); $i < $c; $i++) {
                $creditsTaken = $output[$i][0];
                $creditsLeft = $output[$i][1];
                array_push($return, array($creditsTaken, $creditsLeft));
            }

            echo json_encode($return);
        }
    }

    //Query DB for grades and course credits
    function gradesAndCredits() {
        $db = new DatabaseConnector();
        $return = [];

        $stmt = "SELECT StudentCourse.grade, CourseInfo.credits FROM CourseInfo INNER JOIN StudentCourse
             ON StudentCourse.courseInfoID = CourseInfo.courseInfoID WHERE StudentCourse.userID = ?
             AND NOT StudentCourse.grade = 'IP' AND NOT StudentCourse.grade = 'ND' AND NOT StudentCourse.grade = 'DR'
             AND NOT StudentCourse.grade = 'TR'";
        $params = array($this->userID);
        $output = $db->select($stmt, $params);

        if($output[0][0] == "") {
            //toLog(1, "ERROR", "gradesAndCredits", "courseGrade is null");
        }
        else if($output[0][1] == "") {
            //toLog(1, "ERROR", "gradesAndCredits", "courseCredits is null");
        }
        else {
            for ($i = 0, $c = count($output); $i < $c; $i++) {
                $courseGrade = $output[$i][0];
                $courseCredits = $output[$i][1];
                array_push($return, array($courseGrade, $courseCredits));
            }
            echo json_encode($return);
        }
    }

    function currentCourses() {

        $db = new DatabaseConnector();
        $return = [];

        $stmt = "SELECT CourseInfo.courseID, CourseInfo.courseName, CourseInfo.credits, StudentCourse.weight, StudentCourse.relevance FROM StudentCourse INNER JOIN CourseInfo ON StudentCourse.courseInfoID = CourseInfo.courseInfoID WHERE grade = 'IP' AND userID = ? ";
        $params = array($this->userID);
        $output = $db->select($stmt, $params);

        if($output[0][0] == "") {
            //toLog(1, "ERROR", "currentCourses", "courseGrade is null");
        }
        else if($output[0][1] == "") {
            //toLog(1, "ERROR", "currentCourses", "courseCredits is null");
        }
        else if($output[0][2] == "") {
            //toLog(1, "ERROR", "currentCourses", "courseCredits is null");
        }
        else if($output[0][3] == "") {
            //toLog(3, "ERROR", "currentCourses", "courseCredits is null");
        }
        else if($output[0][4] == "") {
            //toLog(3, "ERROR", "currentCourses", "courseCredits is null");
        }
        else {
            for ($i = 0, $c = count($output); $i < $c; $i++) {
                $courseID = $output[$i][0];
                $courseName = $output[$i][1];
                $credits = $output[$i][2];
                $courseWeight = $output[$i][3];
                $courseRelevance = $output[$i][4];

                array_push($return, array($courseID, $courseName, $credits, $courseWeight, $courseRelevance));
            }
            echo json_encode($return);
        }
    }

    // in theory this should UPDATE weight and relevance in DB
    function modifyWeightAndRelevance() {
        $db = new DatabaseConnector();

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

        $stmt = "UPDATE StudentCourse SET relevance = ?, weight = ? WHERE courseInfoID = (SELECT courseInfoID FROM CourseInfo WHERE courseID = ?) AND userID = ?";
        $params = array($modifiedRelevance, $modifiedWeight, $courseID, $this->userID);
        $db->query($stmt, $params);

        /*if ($mysqli->query($sql) === TRUE) {

            $result = array(
                'success' => true
            );
        } else {
            $result = array(
                'success' => false,
                'message' => 'Weight and Relevance could not be updated'
            );
        }

        echo json_encode($result);*/

    }








} //end of semesterForecastController()
