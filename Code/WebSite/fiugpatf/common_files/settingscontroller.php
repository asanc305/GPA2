<?php
include_once 'dbconnector.php';
include_once 'toLog.php';

class SettingsController
{
    protected $user;
    protected $userName;
    protected $log;

    public function __construct($user, $userName)
    {
        $this->user = $user;
        $this->userName = $userName;
        $this->log = new ErrorLog();
    }

    public function prepareTable()
    {
        $db = new DatabaseConnector();
        header('Content-type: application/json');

        $params = array($this->user);
        $stmt = $db->select("SELECT type FROM Users WHERE userID=?", $params);

        $output = array();
        array_push($output, array("Change Password", ""));
        array_push($output, array("Change Major", ""));
        array_push($output, array("Change Themes", ""));
        array_push($output, array("Export Data", '<button type="button" id="ExportButton">Export Data</button>'));
        array_push($output, array("Import Data", '<input type="file" id="ImportFile">'));
        array_push($output, array("Delete Data", '<button type="button" id="DeleteButton">Delete Data</button>'));
        array_push($output, array("Import GPA Audit (PDF)", '<form id="PDFimport" action="settingsrouter.php"
        enctype="multipart/form-data" method="post"><input type="file" name="file" id="Whatif"><input type="hidden"
        name="action" value="importAudit"></form>'));

        if ($stmt[0][0] == 1) {
            array_push($output, array("Import Requirments", '<form id="Reqimport" action="settingsrouter.php"
            enctype="multipart/form-data" method="post" datatype="json"><input type="file" name="file" id="ImportReqirments"><input
            type="hidden" name="action" value="importReq"></form>'));
            array_push($output, array("Delete Program", '<button type="button" id="DeleteProgramButton">Delete Program</button>'));
        }

        echo json_encode($output);
    }

    public function importAudit()
    {
        $username = $this->userName;

        if (!file_exists($username)) {
            mkdir($username, 0777);
            $loc = $_FILES['file']['tmp_name'];
            shell_exec('qpdf --password="" --decrypt ' . $loc . ' ' . $username . '/unencrypted.pdf');
            $courseInfo = shell_exec('python PDFWhatIfParser.py ' . $username . '/unencrypted.pdf');
            echo 'python PDFWhatIfParser.py ' . $username . '/unencrypted.pdf';

            $allData = explode("!!!!", $courseInfo);
            $majorData = explode("\n", $allData[0]);
            $courses = explode("\n", $allData[1]);
            $uccComplete = explode("\n", $allData[2]);

            if ($this->checkFirstTime()) {
                $this->insertMajor($majorData);
                $this->insertCourses($courses);
                $this->instantiateNeeded($courses, $uccComplete[1]);
            } else {
                $this->update($courses);
            }

            shell_exec('rm -rf ' . $username);

            $this->log->toLog(1, __METHOD__, "GPA Audit Imported");
        }
    }

    public function checkFirstTime()
    {
        $conn = new DatabaseConnector();

        $params = array($this->user);

        $output = $conn->select("SELECT * FROM StudentCourse WHERE StudentCourse.userID = ?", $params);

        if (count($output) > 0)
            return false;
        else
            return true;
    }

    private function insertMajor($majorData)
    {
        $dbc = new DatabaseConnector();
        foreach ($majorData as $maj) {
            if ($maj != "") {
                $stmt = "INSERT INTO StudentMajor (userID, majorID) VALUES (?, (SELECT majorID from Major WHERE majorName = ?))";
                $params = array($this->user, $maj);
                $dbc->query($stmt, $params);
            }
        }
    }

    private function insertCourses($courses)
    {
        $conn = new DatabaseConnector();

        foreach ($courses as $course) {
            if ($course == "")
                continue;

            $courseDetails = explode("$$&&", $course);
            $semester = $courseDetails[0];
            $year = $courseDetails[1];
            $courseID = $courseDetails[2];
            $courseName = $courseDetails[3];
            $grade = $courseDetails[4];
            $credits = $courseDetails[5];
            $ctype = $courseDetails[6];

            if ($ctype == 'TR' or $ctype == 'OT')
                continue;

            //check if course is in database
            $params = array($courseID);
            $courseInfoID = $conn->select("SELECT courseInfoID FROM CourseInfo WHERE courseID = ?", $params);

            // if course is not in database then insert
            if (count($courseInfoID) == 0) {
                $params = array($courseID, $courseName, $credits);
                $conn->query("INSERT INTO CourseInfo (courseID, courseName, credits) VALUES (?, ?, ?)", $params);
            }

            //insert course
            $params = array($grade, $semester, $year, $courseID, $this->user);
            $conn->query("INSERT INTO StudentCourse (grade, weight, relevance, semester, year,
           courseInfoID, selected, userID) VALUES (?, 0, 0, ?, ?, (SELECT CourseInfoID FROM CourseInfo
           WHERE courseID = ?), 0, ?)", $params);
            $this->log->toLog(0, __METHOD__, "Course: $courseID inserted for user: $this->user");
        }
    }

    public function instantiateNeeded($courses, $uccComplete)
    {
        $conn = new DatabaseConnector();

        $takenCourses = array();
        foreach ($courses as $course) {
            if ($course == "")
                continue;
            $courseDetails = explode("$$&&", $course);
            array_push($takenCourses, array($courseDetails[2], $courseDetails[4]));
        }

        $param = array($this->user);
        $buckets = $conn->select("SELECT MajorBucket.bucketID, MajorBucket.allRequired, MajorBucket.quantityNeeded,
                          MajorBucket.quantification, MajorBucket.description FROM MajorBucket WHERE MajorBucket.parentID IS NULL AND
                          MajorBucket.majorID IN (SELECT StudentMajor.majorID FROM StudentMajor
                          WHERE StudentMajor.userID = ?)", $param);

        foreach ($buckets as $bucket) {
            if ($bucket[4] == 'UCC' and $uccComplete)
                continue;

            $this->checkBucket($takenCourses, $bucket);
        }
    }

    public function checkBucket($takenCourses, $bucket)
    {
        $conn = new DatabaseConnector();

        $params = array($bucket[0]);
        $childBuckets = $conn->select("SELECT bucketID, allRequired, quantityNeeded, quantification, description
        FROM MajorBucket WHERE MajorBucket.parentID = ?", $params);

        if (count($childBuckets) > 0) {
            foreach ($childBuckets as $childBucket)
                $this->checkBucket($takenCourses, $childBucket, $this->user);
        } else {
            $bucketCourses = $conn->select("SELECT CourseInfo.courseID, CourseInfo.credits, CourseInfo.courseInfoID,
            MajorBucketRequiredCourses.minimumGrade FROM CourseInfo INNER JOIN MajorBucketRequiredCourses on
            CourseInfo.courseInfoID = MajorBucketRequiredCourses.courseInfoID
            WHERE MajorBucketRequiredCourses.bucketID = ?", $params);

            $counter = 0;
            $coursesNotTaken = array();
            $bucketCompleted = false;

            foreach ($bucketCourses as $bucketCourse) {
                $passed = false;

                $keys = $this->search($takenCourses, '0', $bucketCourse[0]);

                foreach ($keys as $key) {
                    $grade = $this->convertGrade($key[1]);
                    $minGrade = $this->convertGrade($bucketCourse[3]);

                    if ($minGrade > $grade)
                        continue;

                    if ($bucket[3] == "credits")
                        $counter += $bucketCourse[1];
                    else
                        $counter++;
                    $passed = true;
                    break;
                }

                if (!$passed)
                    array_push($coursesNotTaken, $bucketCourse[2]);

                if ($counter >= $bucket[2]) {
                    $bucketCompleted = true;
                    $this->log->toLog("0", __METHOD__, "Bucket: $bucket[0] Completed");
                    break;
                }
            }

            if (!$bucketCompleted) {
                $this->log->toLog("0", __METHOD__, "Bucket: $bucket[0] not completed");
                foreach ($coursesNotTaken as $courseNotTaken) {
                    $params = array($courseNotTaken, $this->user);
                    $conn->query("INSERT INTO StudentCourse (grade, weight, relevance, semester, year, courseInfoID,
                selected, userID) VALUES ('ND', 0, 0, '', '', ?, 0, ?)", $params);
                }
            }
        }
    }

    public function search($array, $key, $value)
    {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, $this->search($subarray, $key, $value));
            }
        }
        return $results;
    }

    private function convertGrade($grade)
    {
        switch ($grade) {
            case 'A':
                return 4.0;
                break;
            case 'A-':
                return 3.7;
                break;
            case 'B+':
                return 3.3;
                break;
            case 'B':
                return 3.0;
                break;
            case 'B-':
                return 2.7;
                break;
            case 'C+':
                return 2.3;
                break;
            case 'C':
                return 2.0;
                break;
            case 'C-':
                return 1.7;
                break;
            case 'D+':
                return 1.3;
                break;
            case 'D':
                return 1.0;
                break;
            case 'D-':
                return .7;
                break;
            case 'F':
                return 0;
                break;
            case 'IP':
                return 5;
                break;

        }
    }

    public function update($courses)
    {
        $conn = new DatabaseConnector();

        foreach ($courses as $course) {
            if ($course == "")
                continue;

            $courseDetails = explode("$$&&", $course);
            $semester = $courseDetails[0];
            $year = $courseDetails[1];
            $courseID = $courseDetails[2];
            $courseName = $courseDetails[3];
            $grade = $courseDetails[4];
            $credits = $courseDetails[5];
            $ctype = $courseDetails[6];

            if ($ctype == 'TR' or $ctype == 'OT')
                continue;

            //check if course was already taken
            $params = array($grade, $semester, $year, $this->user, $courseID);
            $out = $conn->select("SELECT * FROM StudentCourse WHERE grade = ? and semester = ? and year = ?
          and userID = ? and courseInfoID = (SELECT courseInfoID FROM CourseInfo Where courseID = ?)", $params);

            if (count($out) > 0)
                continue;

            //check if course is IP or ND
            $params = array($this->user, $courseID);
            $out = $conn->select("SELECT grade, weight, relevance, studentCourseID, semester, year, courseInfoID FROM
          StudentCourse WHERE userID = ? and (grade = 'ND' OR grade = 'IP') AND courseInfoID = (SELECT courseInfoID FROM CourseInfo
          Where courseID = ?)", $params);

            if (count($out) > 0) {
                $params = array($grade, $out[0][3]);
                $conn->query("UPDATE StudentCourse SET grade = ? WHERE studentCourseID = ?", $params);

                $params = array($courseID);
                $minGrade = $conn->select("SELECT minimumGrade FROM MajorBucketRequiredCourses
              WHERE courseInfoID = (SELECT courseInfoID FROM CourseInfo Where courseID = ?)", $params);

                if ($this->convertGrade($grade) < $this->convertGrade($minGrade[0])) {
                    $params = array($out[1], $out[2], $out[6], $this->user);
                    $conn->query("INSERT INTO StudentCourse (grade, weight, relevance, semester, year, courseInfoID,
                selected, userID) VALUES ('ND', ?, ?, '', '', ?, 0, ?)", $params);
                    $this->log->toLog(0, __METHOD__, "Updated course $courseID");
                    continue;
                }

                continue;
            }

            $params = array($courseID);
            $out = $conn->select("SELECT courseInfoID FROM CourseInfo WHERE courseID = ?", $params);
            $this->log->toLog(0, __METHOD__, "Insert course $courseID");

            if (count($out) == 0) {
                $params = array($courseID, $courseName, $credits);
                $conn->query("INSERT INTO CourseInfo (courseID, courseName, credits)	VALUES (?, ?, ?)", $params);
            }

            $params = array($courseID);
            $courseInfoID = $conn->select("SELECT courseInfoID FROM CourseInfo WHERE courseID = ?", $params);


            $params = array($grade, $semester, $year, $courseInfoID[0][0], $this->user);

            $conn->query("INSERT INTO StudentCourse (grade, weight, relevance, semester, year, courseInfoID,
                selected, userID) VALUES (?, 0, 0, ?, ?, ?, 0, ?)", $params);
        }
    }

    public function testStub($courses, $ucc)
    {
        if ($this->checkFirstTime()) {
            $this->insertCourses($courses);
            $this->instantiateNeeded($courses, $ucc);
        } else {
            $this->update($courses);
        }
    }

    public function importReq()
    {
        $file = file_get_contents($_FILES['file']['tmp_name']);
        //$file = file_get_contents('nursing.xml');
        libxml_use_internal_errors(true);
        $adminData = simplexml_load_string($file);

        if ($adminData === false) {
            $response_array[0] = 'error';
            echo json_encode($response_array);
            return;
        }

        $this->importRequirments($adminData);

        $response_array[0] = 'success';
        echo json_encode($response_array);
    }

    public function importRequirments($adminData) {
        $db = new DatabaseConnector();
        foreach ($adminData->database[0]->children() as $table_data) {
            foreach ($table_data->children() as $rows) {
                if ($table_data['name'] == 'MajorBucket') {
                    if ($rows->field[7] == "null") {
                        $majorID = $rows->field[0];

                        $params = array($rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3],
                            $rows->field[4], $rows->field[5], $rows->field[6]);
                        $db->query("INSERT INTO MajorBucket (majorID, dateStart, dateEnd, description, allRequired,
                          quantityNeeded, quantification, parentID) VALUES (?, ?, ?, ?, ?, ?, ?, null) ON DUPLICATE
                          KEY UPDATE dateStart=VALUES(dateStart), dateEnd=VALUES(dateEnd), allRequired = VALUES
                          (allRequired), quantification=VALUES(quantification), parentID=VALUES(parentID)", $params);

                        $this->log->toLog(0, __METHOD__, "Major Bucket imported: $params[0], $params[1], $params[2], $params[3], $params[4], $params[5], $params[6]");
                    }
                    else {
                        $params = array($rows->field[0], $rows->field[7]);
                        $stmt = $db->select("SELECT bucketID FROM MajorBucket WHERE majorID = ? and
                          description = ?", $params);

                        $parentID = $stmt[0][0];

                        $params = array($rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3],
                            $rows->field[4], $rows->field[5], $rows->field[6], $parentID);

                        $db->query("INSERT INTO MajorBucket (majorID, dateStart, dateEnd, description, allRequired,
                        quantityNeeded, quantification, parentID) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY
                        UPDATE dateStart=VALUES(dateStart), dateEnd=VALUES(dateEnd), allRequired=VALUES(allRequired),
                        quantification=VALUES(quantification), parentID=VALUES(parentID)", $params);

                        $this->log->toLog(0, __METHOD__, "Major Bucket imported: $params[0], $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $parentID");
                    }
                }
                else if ($table_data['name'] == 'CourseInfo') {
                    $params = array($rows->field[0], $rows->field[1], $rows->field[2]);
                    $db->query("INSERT INTO CourseInfo (courseID, courseName, credits) VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE courseName=VALUES(courseName), credits=VALUES(credits)", $params);

                    $this->log->toLog(0, __METHOD__, "Course Imported: $params[0], $params[1], $params[2],");
                }
                else if ($table_data['name'] == 'MajorBucketRequiredCourses') {
                    $params = array($rows->field[0], $majorID, $rows->field[1], $rows->field[2]);
                    $db->query("INSERT INTO MajorBucketRequiredCourses (courseInfoID, bucketID, minimumGrade) VALUES
                      ((SELECT courseInfoID FROM CourseInfo WHERE courseID = ?), (SELECT bucketID FROM MajorBucket
                      WHERE majorID = ? and description = ?), ?) ON DUPLICATE KEY UPDATE courseInfoID = VALUES
                      (courseInfoID), bucketID=VALUES(bucketID), minimumGrade=VALUES(minimumGrade)", $params);

                    $this->log->toLog(0, __METHOD__, "Major Bucket imported: $params[0], $params[1], $params[2]");
                }
            }
        }
    }

    public function importReqDriver($fileName) {
        $file = file_get_contents($fileName);
        $adminData = simplexml_load_string($file);
        $this->importRequirments($adminData);
    }

    public function deleteProgram() {
        $db = new DatabaseConnector();
        $this->log->toLog(0, __METHOD__, "in");
        $params = array(4);
        $courses = $db->select("SELECT CourseInfo.courseInfoID FROM CourseInfo INNER JOIN MajorBucketRequiredCourses
          on MajorBucketRequiredCourses.courseInfoID = CourseInfo.courseInfoID WHERE MajorBucketRequiredCourses.bucketID
          IN (SELECT MajorBucket.bucketID FROM MajorBucket WHERE MajorBucket.majorID = ?)", $params);

        foreach($courses as $course) {
            $this->log->toLog(0, __METHOD__, "courses in program: $course[0]");

            $params = array($course[0]);
            $stmt = $db->select("select * from CourseInfo INNER JOIN MajorBucketRequiredCourses on
              CourseInfo.courseInfoID = MajorBucketRequiredCourses.courseInfoID WHERE CourseInfo.courseInfoID = ?", $params);

            if (count($stmt) > 1)
            {
                continue;
            }

            $db->query("DELETE from CourseInfo WHERE courseInfoID = ?", $params);
            $this->log->toLog(0, __METHOD__, "DELETED: $course[0]");
        }

        $params = array(4);
        $db->query("DELETE FROM MajorBucketRequiredCourses WHERE bucketID in (SELECT bucketID FROM MajorBucket
          WHERE majorID = ?)", $params);

        $db->query("Delete FROM MajorBucket WHERE majorID = ?", $params);
    }

}
