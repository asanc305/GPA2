<?php
/**
 * Created by PhpStorm.
 * User: Lizette Mendoza
 * Date: 3/11/16
 * Time: 3:54 PM
 */

include_once'../common_files/dbconnector.php';

class SemesterForecastControllerTest extends PHPUnit_Framework_TestCase {

    function testGPAGoal001() {

        $db = new DatabaseConnector();

        $stmt = "SELECT gpaGoal FROM Users WHERE userID = ?";
        $params = array(27);
        $output = $db->select($stmt, $params);

        for($i = 0, $c = count($output); $i < $c; $i++) {
            $gpa = $output[$i][0];
            array_push($return, array($gpa));
        }

        $expected = [[3.2]];

        $this->assertEquals($return, $expected);
    }

    function testTakenAndRemaining002() {

        $db = new DatabaseConnector();
        $return = [];

        $stmt   = "Select  f.creditsTaken, e.RemainingCredits From ((Select sum(CourseInfo.credits) as RemainingCredits From (Select a.courseInfoID From (SELECT courseInfoID FROM `MajorBucketRequiredCourses` Where bucketID in (Select  bucketID From MajorBucket Where majorID in (Select majorID From StudentMajor Where userID = ?) AND allRequired = '1'))a inner join StudentCourse on a.courseInfoID = StudentCourse.courseInfoID AND  StudentCourse.userID = ? AND StudentCourse.grade = 'ND'

            UNION

            Select b.courseInfoID From (SELECT courseInfoID FROM `MajorBucketRequiredCourses` Where bucketID in (Select  bucketID From MajorBucket Where majorID  in (Select majorID From StudentMajor Where userID = ?) AND allRequired = '0'))b inner join StudentCourse on b.courseInfoID = StudentCourse.courseInfoID AND  StudentCourse.userID = ? AND StudentCourse.grade = 'ND' and StudentCourse.selected = '1'

              )c inner join CourseInfo on CourseInfo.courseInfoID = c.courseInfoID)e

            JOIN

            (Select Sum(CourseInfo.credits) as creditsTaken from CourseInfo inner join StudentCourse on StudentCourse.courseInfoID = CourseInfo.courseInfoID AND StudentCourse.grade not in (Select grade from StudentCourse where grade = 'ND') AND StudentCourse.userID = ?)f)";
        $params = array(12, 12, 12, 12, 12);
        $output = $db->select($stmt, $params);

        for($i = 0, $c = count($output); $i < $c; $i++) {
            $expectedTaken = $output[$i][0];
            $expectedLeft = $output[$i][1];
            array_push($return, array($expectedTaken, $expectedLeft));
        }

        //expected output for unit test
        $expected = [[151, 12]];

        $this->assertEquals($return, $expected);
    }


}// end of SemesterForecastControllerTest()