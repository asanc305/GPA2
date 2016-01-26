<?php
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
if($action == "prepareTable") {
	if (isset($_SESSION['username'])) {
	
		$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
        $stmt = $mysqli->prepare("SELECT type
        FROM Users
        WHERE userID=?");
        $stmt->bind_param('s', $_SESSION['userID']);
        $stmt->execute();
        $stmt->bind_result($admin);
        $stmt->fetch();
        
		$output = array();
		array_push($output, array("Change Password", ""));
		array_push($output, array("Change Major", ""));
		array_push($output, array("Change Themes", ""));
		array_push($output, array("Export Data", '<button type="button" id="ExportButton">Export Data</button>'));
		array_push($output, array("Import Data", '<input type="file" id="ImportFile">'));
		array_push($output, array("Delete Data", '<button type="button" id="DeleteButton">Delete Data</button>'));
		array_push($output, array("Import GPA Audit (PDF)", '<form id="PDFimport" action="settings.php" enctype="multipart/form-data" method="post"><input type="file" name="file" id="Whatif"><input type="hidden" name="action" value="importWhatif"></form>'));
		if($admin == 1)
		{
			array_push($output, array("Import Requirments", '<form id="Reqimport" action="settings.php" enctype="multipart/form-data" method="post"><input type="file" name="file" id="ImportReqirments"><input type="hidden" name="action" value="importReq"></form>'));
		}
		
		echo json_encode($output);
	}
}
if($action == "exportData") {
	if (isset($_SESSION['username'])) {
		$user = $_SESSION['userID'];
		$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
        $stmt = $mysqli->prepare("SELECT studentCourseID
        FROM StudentCourse
        WHERE userID=?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $stmt->bind_result($studentCourse);
        $courseList = "(";
        while($stmt->fetch())
        {
        	$courseList = $courseList . $studentCourse . ", ";
        }
        $courseList = substr($courseList, 0, (strlen($courseList) - 2));
        $courseList = $courseList . ")";
		$dump1 = shell_exec('mysqldump --user=root --password=sqliscool --host=localhost --no-create-info --xml GPA_Tracker Users  StudentCourse StudentMajor --where="userID=' . $user . '"');
		$dump2 = shell_exec('mysqldump --user=root --password=sqliscool --host=localhost --no-create-info --xml GPA_Tracker AssessmentType Assessment --where="studentCourseID in ' . $courseList .'"');
		echo cutLastLine(cutLastLine(cutLastLine($dump1))) . "\n" . cutFirstLine(cutFirstLine(cutFirstLine($dump2)));
	}
}
if($action == "importData") {
	if(isset($_SESSION['username'])){
		$xml = simplexml_load_string($_POST['file']);
		if($xml === false)
		{
			echo "Failed loading XML: ";
    		foreach(libxml_get_errors() as $error) {
        		echo "<br>", $error->message;
			}
		}
		else
		{
			if($xml->database[0]['name'] == 'GPA_Tracker'){
				if(validate_and_insert_data($xml)) {
					echo "true";
				}
				else
				{
					echo "Error! Cannot insert for another student.";
				}
			}
			else {
				echo "Data can only be inserted into GPA_Tracker.";
			}
		}
	}
}

if($action == "deleteData") {
	if(isset($_SESSION['username'])){
		$user = $_SESSION['userID'];
		$mysqli = new mysqli("localhost","root","sqliscool","GPA_Tracker");
        $stmt = $mysqli->prepare("DELETE from StudentCourse WHERE userID = ?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
	}
}

if($action == "importWhatif") {
	if(isset($_SESSION['userID'])){
		$username = $_SESSION['username'];
		$user = $_SESSION['userID'];
		if(!file_exists($username))
		{
			mkdir($username, 0777);
			$loc = $_FILES['file']['tmp_name'];
			shell_exec('qpdf --password="" --decrypt ' . $loc . ' ' . $username . '/unencrypted.pdf');
			$courseInfo = shell_exec('python PDFWhatIfParser.py ' . $username . '/unencrypted.pdf');
			echo 'python PDFWhatIfParser.py ' . $username . '/unencrypted.pdf';
			$allData = explode("!!!!", $courseInfo);
			$majorData = explode("\n", $allData[0]);
			$courses = explode("\n", $allData[1]);
			$ipCourses = explode("\n", $allData[2]);
			$neededCourses = explode("\n", $allData[3]);
			$majorName;
			foreach ($majorData as $maj) {
				if($maj != "")
				{
					echo "5";
					$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
					$stmt = $mysqli->prepare("INSERT INTO StudentMajor (userID, majorID) VALUES (?, (SELECT majorID from Major WHERE majorName = ?))");
					$stmt->bind_param('ss', $user, $maj);
					$stmt->execute();
					
					$majorName = $maj;
    			}
			}
			
			foreach ($courses as $course) {
				if($course != ""){
					$courseDetails = explode("$$&&", $course);
					$bucket = $courseDetails[0];
					$semester = $courseDetails[1];
					$year = $courseDetails[2];
					$courseID = $courseDetails[3];
					$courseName = $courseDetails[4];
					$grade = $courseDetails[5];
					$credits = $courseDetails[6];
				
					$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
					$stmt = $mysqli->prepare("INSERT INTO CourseInfo (courseID, courseName, credits) VALUES (?, ?, ?)");
					if($stmt !== false){
						$stmt->bind_param('sss', $courseID, $courseName, $credits);
						$stmt->execute();
					}
					else {
						echo "Error on course CourseInfo Import\n";
					}
					
					if($bucket != "None"){
						$stmt = $mysqli->prepare("INSERT INTO MajorBucketRequiredCourses (bucketID, courseInfoID, minimumGrade) VALUES ((SELECT bucketID FROM MajorBucket WHERE description = ? AND majorID in (SELECT majorID FROM Major WHERE majorName = ?)), (SELECT courseInfoID FROM CourseInfo WHERE courseID = ? ), 'C')");
						if($stmt !== false){
							$stmt->bind_param('sss', $bucket, $majorName, $courseID);
							$stmt->execute();
						}
						else {
							echo "Error on course MajorRequiredBucketCourses Import\n";
						}
					}
					
					$stmt = $mysqli->prepare("INSERT INTO StudentCourse (grade, weight, relevance, semester, year, courseInfoID, selected, userID) VALUES (?, 0, 0, ?, ?, (SELECT CourseInfoID FROM CourseInfo WHERE courseID = ?), 0, ?)");
					if($stmt !== false)
					{
						$stmt->bind_param('sssss', $grade, $semester, $year, $courseID, $user);
						$stmt->execute();
					}
					else {
						echo "Error on StudentCourse Import\n";
					}	
    			}
			}
			
			foreach ($ipCourses as $ipCourse) {
				if($ipCourse != ""){
					$courseDetails = explode("$$&&", $ipCourse);
					$bucket = $courseDetails[0];
					$semester = $courseDetails[1];
					$year = $courseDetails[2];
					$courseID = $courseDetails[3];
					$courseName = $courseDetails[4];
					$grade = $courseDetails[5];
					$credits = $courseDetails[6];
				
					$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
					$stmt = $mysqli->prepare("INSERT INTO CourseInfo (courseID, courseName, credits) VALUES (?, ?, ?)");
					if($stmt !== false){
						$stmt->bind_param('sss', $courseID, $courseName, $credits);
						$stmt->execute();
					}
					else {
						echo "Error on course CourseInfo Import\n";
					}
					
					if($bucket != "None"){
						$stmt = $mysqli->prepare("INSERT INTO MajorBucketRequiredCourses (bucketID, courseInfoID, minimumGrade) VALUES ((SELECT bucketID FROM MajorBucket WHERE description = ? AND majorID in (SELECT majorID FROM Major WHERE majorName = ?)), (SELECT courseInfoID FROM CourseInfo WHERE courseID = ? ), 'C')");
						if($stmt !== false){
							$stmt->bind_param('sss', $bucket, $majorName, $courseID);
							$stmt->execute();
						}
						else {
							echo "Error on course MajorRequiredBucketCourses Import\n";
						}
					}
					
					$stmt = $mysqli->prepare("INSERT INTO StudentCourse (grade, weight, relevance, semester, year, courseInfoID, selected, userID) VALUES ('IP', 0, 0, ?, ?, (SELECT CourseInfoID FROM CourseInfo WHERE courseID = ?), 0, ?)");
					if($stmt !== false)
					{
						$stmt->bind_param('ssss', $semester, $year, $courseID, $user);
						$stmt->execute();
					}
					else {
						echo "Error on StudentCourse Import\n";
					}	
    			}
			}
			
			foreach ($neededCourses as $neededCourse) {
				if($neededCourse != ""){
					$stmt = $mysqli->prepare("INSERT INTO StudentCourse (grade, weight, relevance, semester, year, courseInfoID, selected, userID) VALUES ('ND', 0, 0, '', '', (SELECT CourseInfoID FROM CourseInfo WHERE courseID = ?), 0, ?)");
					if($stmt !== false)
					{
						$stmt->bind_param('ss', $neededCourse, $user);
						$stmt->execute();
					}
					else {
						echo "Error on StudentCourse Import\n";
					}	
    			}
			}
			shell_exec('rm -rf ' . $username);
			//echo "true";
		}
	}
}
if($action == "importReq") {
	$file = file_get_contents($_FILES['file']['tmp_name']);
	$adminData = simplexml_load_string($file);
	
	if($adminData == false)
	{
		echo "Error loading string. Check File.";
	}
	else
	{
		importDataAdmin($adminData);
	}
}

function importDataAdmin($adminData){
	$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
	foreach($adminData->database[0]->children() as $table_data)
	{
		foreach($table_data->children() as $rows)
		{
			if($table_data['name'] == 'MajorBucket')
			{
				if($rows->field[7] == "null")
				{
					$majorID = $rows->field[0];
					$stmt = $mysqli->prepare("INSERT INTO MajorBucket (majorID, dateStart, dateEnd, description, allRequired, quantityNeeded, quantification, parentID) VALUES (?, ?, ?, ?, ?, ?, ?, null)
                                          ON DUPLICATE KEY UPDATE dateStart=VALUES(dateStart), dateEnd=VALUES(dateEnd), allRequired=VALUES(allRequired), quantification=VALUES(quantification), parentID=VALUES(parentID)");
					$stmt->bind_param('sssssss', $rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3], $rows->field[4], $rows->field[5], $rows->field[6]);
					$stmt->execute();
				}
				else
				{
					$stmt = $mysqli->prepare("SELECT bucketID FROM MajorBucket WHERE majorID = ? and description = ?");
					$stmt->bind_param('ss', $rows->field[0], $rows->field[7]);
					$stmt->execute();
					$stmt->bind_result($parentID);
					$stmt->fetch();
					              
					$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
					$stmt = $mysqli->prepare("INSERT INTO MajorBucket (majorID, dateStart, dateEnd, description, allRequired, quantityNeeded, quantification, parentID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                          ON DUPLICATE KEY UPDATE dateStart=VALUES(dateStart), dateEnd=VALUES(dateEnd), allRequired=VALUES(allRequired), quantification=VALUES(quantification), parentID=VALUES(parentID)");
					if($stmt == false)
					{
						echo $stmt->error;
					}
					else
					{
						$stmt->bind_param('sssssssi', $rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3], $rows->field[4], $rows->field[5], $rows->field[6], $parentID);
						$stmt->execute();
					}
				}
			}
			else if($table_data['name'] == 'CourseInfo'){
				$stmt = $mysqli->prepare("INSERT INTO CourseInfo (courseID, courseName, credits) VALUES (?, ?, ?)
                                          ON DUPLICATE KEY UPDATE courseName=VALUES(courseName), credits=VALUES(credits)");
                $stmt->bind_param('sss', $rows->field[0], $rows->field[1], $rows->field[2]);
                $stmt->execute();
			}
			else if($table_data['name'] == 'MajorBucketRequiredCourses'){
				$stmt = $mysqli->prepare("INSERT INTO MajorBucketRequiredCourses (courseInfoID, bucketID, minimumGrade) VALUES ((SELECT courseInfoID FROM CourseInfo WHERE courseID = ?), (SELECT bucketID FROM MajorBucket WHERE majorID = ? and description = ?), ?)
                                          ON DUPLICATE KEY UPDATE courseInfoID=VALUES(courseInfoID), bucketID=VALUES(bucketID), minimumGrade=VALUES(minimumGrade)");
                $stmt->bind_param('ssss', $rows->field[0], $majorID, $rows->field[1], $rows->field[2]);
                $stmt->execute();
			}
		}
	}
}

/*if($action == "importWhatif") {
	if(isset($_SESSION['username'])){
		$filename = $_SESSION['username'] . 'Whatif';
		$wifile = fopen($filename, $_POST['file']);
		fwrite($wifile, $wifile);
		fclose($filename);
		$xml = simplexml_load_string(shell_exec('python3 WhatIfParser.py ' . $_SESSION['username'] . ' ' . $filename));
		if($xml === false)
		{
			echo "Failed loading XML: ";
    		foreach(libxml_get_errors() as $error) {
        		echo "<br>", $error->message;
			}
		}
		else
		{
			if($xml->database[0]['name'] == 'GPA_Tracker'){
				if(insert_student_data($xml)) {
					echo "true";
				}
				else
				{
					echo "Error!";
				}
			}
			else {
				echo "Data can only be inserted into GPA_Tracker.";
			}
		}
		shell_exec('rm ' . $filename)
	}
}*/
function cutLastLine($string)
{
	return substr($string, 0, strrpos($string, "\n"));
}
function cutFirstLine($string)
{
	return substr(strstr($string, "\n"), 1);
}
function isIn($course, $courseList)
{
	for($i =0; $i < count($courseList); $i++)
	{
		if($course == $courseList[$i])
		{
			return true;
		}
	}
	return false;
}
function validate_and_insert_data($xml)
{
	$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
	$user = $_SESSION['userID'];
	$coursesSet = false;
	foreach($xml->database[0]->children() as $table_data)
	{
		foreach($table_data->children() as $rows)
		{
			if($table_data['name'] == 'StudentData')
			{
				if($rows->field[1] != $user)
				{
					return false;
				}
			}
			else if($table_data['name'] == 'StudentCourse')
			{
				if($rows->field[8] != $user)
				{
					return false;
				}
				$stmt = $mysqli->prepare("INSERT INTO StudentCourse (grade, weight, relevance, studentCourseID, semester, year, courseInfoID, selected, userID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                                          ON DUPLICATE KEY UPDATE grade=VALUES(grade), weight=VALUES(weight), relevance=VALUES(relevance)");
				$stmt->bind_param('sssssssss', $rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3], $rows->field[4], $rows->field[5], $rows->field[6], $rows->field[7], $user);
    			$stmt->execute();
			}
			else if($table_data['name'] == 'StudentMajor')
			{
				if($rows->field[0] != $user)
				{
					return false;
				}
				$stmt = $mysqli->prepare("INSERT INTO StudentMajor (userID, majorID, declaredDate) VALUES (?, ?, ?)
                                          ON DUPLICATE KEY UPDATE declaredDate=VALUES(declaredDate)");
				$stmt->bind_param('ssss', $user, $rows->field[1], $rows->field[2]);
    			$stmt->execute();
			}
			else if($table_data['name'] == 'AssessmentType')
			{
				if(!$coursesSet)
				{
					$stmt = $mysqli->prepare("SELECT studentCourseID
					FROM StudentCourse
					WHERE userID=?");
					$stmt->bind_param('s', $user);
					$stmt->execute();
					$stmt->bind_result($studentCourse);
					$courseList = array();
					while($stmt->fetch())
					{
						array_push($courseList, $studentCourse);
					}
					$coursesSet = true;
				}
				if(!isIn($rows->field[2], $courseList))
				{
					return false;
				}
				$stmt = $mysqli->prepare("INSERT INTO AssessmentType (assessmentName, percentage, studentCourseID, assessmentTypeID) VALUES (?, ?, ?, ?)
                                          ON DUPLICATE KEY UPDATE assessmentName=VALUES(assessmentName), percentage=VALUES(percentage)");
				$stmt->bind_param('ssss', $rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3]);
    			$stmt->execute();
			}
			else if($table_data['name'] == 'Assessment')
			{
				if(!$coursesSet)
				{
				    $mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
					$stmt = $mysqli->prepare("SELECT studentCourseID
					FROM StudentCourse
					WHERE userID=?");
					$stmt->bind_param('s', $user);
					$stmt->execute();
					$stmt->bind_result($studentCourse);
					$courseList = array();
					while($stmt->fetch())
					{
						array_push($courseList, $studentCourse);
					}
					$coursesSet = true;
				}
				if(!isIn($rows->field[3], $courseList))
				{
					return false;
				}
				$stmt = $mysqli->prepare("INSERT INTO Assessment (assessmentTypeID, grade, assessmentID, studentCourseID) VALUES (?, ?, ?, ?)
                                          ON DUPLICATE KEY UPDATE assessmentTypeID=VALUES(assessmentTypeID), grade=VALUES(grade)");
				$stmt->bind_param('ssss', $rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3]);
    			$stmt->execute();
			}
		}
	}
	return true;
}
/*insert_student_data($xml) {
	$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
	$user = $_SESSION['username'];
	foreach($xml->database[0]->children() as $table_data)
	{
		foreach($table_data->children() as $rows)
		{
			$stmt = $mysqli->prepare("INSERT INTO student_course (username, courseID, grade, weight, relevance, student_course_id, semester, year) VALUES (?, ?, ?, ?, ?)
                                          ON DUPLICATE KEY UPDATE grade=VALUES(grade), weight=VALUES(weight), relevance=VALUES(relevance)");
				$stmt->bind_param('sssssss', $user, $rows->field[1], $rows->field[2], $rows->field[3], $rows->field[4], $rows->field[5], $rows->field[6]);
    			$stmt->execute();
		}
	}
}*/

