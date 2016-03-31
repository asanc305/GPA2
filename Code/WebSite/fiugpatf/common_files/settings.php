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
		array_push($output, array("Import GPA Audit (PDF)", '<form id="PDFimport" action="router.php" enctype="multipart/form-data" method="post"><input type="file" name="file" id="Whatif"><input type="hidden" name="action" value="importAudit"></form>'));
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
		$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
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
			$neededCourses = explode("\n", $allData[2]);

			foreach ($majorData as $maj) {
				if($maj != "")
				{
					$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");
					$stmt = $mysqli->prepare("INSERT INTO StudentMajor (userID, majorID) VALUES (?, (SELECT majorID from Major WHERE majorName = ?))");
					$stmt->bind_param('ss', $user, $maj);
					$stmt->execute();
    			}
			}

			InsertCourses($courses, $user);
			InsertNeeded($neededCourses, $user);

			shell_exec('rm -rf ' . $username);

         toLog(1, "Info", "settings.php/gpaImport", "GPA Audit Imported");
		}
	}
}
if($action == "importReq") {
	$file = file_get_contents($_FILES['file']['tmp_name']);
	$adminData = simplexml_load_string($file);
	
	if($adminData === false)
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
	echo "here0\n";
	foreach($adminData->database[0]->children() as $table_data)
	{
		echo "here\n";
		foreach($table_data->children() as $rows)
		{
			echo "here1\n";
			if($table_data['name'] == 'MajorBucket')
			{
				echo "here2\n";
				if($rows->field[7] == "null")
				{
					echo "here3\n";
					$majorID = $rows->field[0];
					$stmt = $mysqli->prepare("INSERT INTO MajorBucket (majorID, dateStart, dateEnd, description, allRequired, quantityNeeded, quantification, parentID) VALUES (?, ?, ?, ?, ?, ?, ?, null)
                                          ON DUPLICATE KEY UPDATE dateStart=VALUES(dateStart), dateEnd=VALUES(dateEnd), allRequired=VALUES(allRequired), quantification=VALUES(quantification), parentID=VALUES(parentID)");
					$stmt->bind_param('sssssss', $rows->field[0], $rows->field[1], $rows->field[2], $rows->field[3], $rows->field[4], $rows->field[5], $rows->field[6]);
					$stmt->execute();
					echo "here4\n";
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

function InsertCourses($courses, $user){
	$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");

	foreach ($courses as $course) {
		if($course != ""){
			$courseDetails = explode("$$&&", $course);
			$semester = $courseDetails[0];
			$year = $courseDetails[1];
			$courseID = $courseDetails[2];
			$courseName = $courseDetails[3];
			$grade = $courseDetails[4];
			$credits = $courseDetails[5];
			$inserted = false;

			//check if course is in database
			if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM CourseInfo WHERE courseID = ?")){
				$stmt->bind_param("s", $courseID);
				$stmt->execute();
				$stmt->bind_result($count);
				$stmt->fetch();
				$stmt->close();

			}
			else {
				echo("Error on checking database for course\n");
			}

			// if course is not in database then insert
			if ((int)$count == 0) {
				if ($stmt = $mysqli->prepare("INSERT INTO CourseInfo (courseID, courseName, credits)
								VALUES (?, ?, ?)")){
					$stmt->bind_param('sss', $courseID, $courseName, $credits);
					$stmt->execute();
					$stmt->close();
				}
				else {
					echo("Error inserting course into database\n");
				}
			}

			//check if course is in student course and update
			if ($stmt = $mysqli->prepare("SELECT grade, semester, year FROM StudentCourse WHERE userID = ? AND
							courseInfoID = (SELECT courseInfoID FROM CourseInfo Where courseID = ?)")) {
				$stmt->bind_param('ss', $user, $courseID);
				$stmt->execute();
				$stmt->bind_result($eGrade, $eSemester, $eYear);

				while($stmt->fetch()){
					if ($semester == $eSemester && $year == $eYear){
						//update grade
						$mysqli2 = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");

						if ($stmt2 = $mysqli2->prepare("UPDATE StudentCourse SET grade = ? WHERE userID = ? AND
							semester = ? AND year = ? AND
							courseInfoID = (SELECT courseInfoID FROM CourseInfo Where courseID = ?)")){
							$stmt2->bind_param('sssss', $grade, $user, $semester, $year, $courseID);
							$stmt2->execute();
							$stmt2->close();
						}
						else {
							echo("Error updating grade $stmt->errno, $mysqli->error\n");
						}

						$stmt2->close();
						$mysqli2->close();

						$inserted = true;
						break;
					}
					elseif ($eGrade == 'ND'){
						//case where course was needed and now in progress
						$mysqli2 = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");

						if ($stmt2 = $mysqli2->prepare("UPDATE StudentCourse SET grade = ?, semester = ?, year = ?
							WHERE userID = ? AND courseInfoID = (SELECT courseInfoID FROM CourseInfo Where courseID = ?)
							AND grade = 'ND' ")){
							$stmt2->bind_param('sssss', $grade, $semester, $year, $user, $courseID);
							$stmt2->execute();
							$stmt2->close();
						}
						else {
							echo("Error updating grade from needed $stmt->errno, $mysqli->error\n");
						}

						$stmt2->close();
						$mysqli2->close();

						$inserted = true;

						break;
					}
				}
				$stmt->close();
			}
			else {
				echo("Error checking for studentCourse\n");
			}

			//insert
			if (!$inserted){
				if ($stmt = $mysqli->prepare("INSERT INTO StudentCourse (grade, weight, relevance, semester, year,
					courseInfoID, selected, userID) VALUES (?, 0, 0, ?, ?, (SELECT CourseInfoID FROM CourseInfo
					WHERE courseID = ?), 0, ?)")){
					$stmt->bind_param('sssss', $grade, $semester, $year, $courseID, $user);
					$stmt->execute();
					$stmt->close();
					continue;
				} else {
					echo("Error inserting course\n");
				}
			}
		}
	}
	$mysqli->close();
}

function InsertNeeded($neededCourses, $user){
	$mysqli = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");

	foreach ($neededCourses as $neededCourse) {
		$inserted = false;

		if($neededCourse != ""){
			if ($stmt = $mysqli->prepare("SELECT grade, weight, relevance FROM StudentCourse WHERE userID = ?
			 		AND courseInfoID = (SELECT courseInfoID FROM CourseInfo WHERE courseID = ?)")){
				$stmt->bind_param('ss', $user, $neededCourse);
				$stmt->execute();
				$stmt->bind_result($eGrade, $eWeight, $eRelevance);

				while($stmt->fetch()){
					if ($eGrade == 'ND')
					{
						$inserted = true;
						break;
					}
					else{
						$mysqli2 = new mysqli("localhost","sec_user","Uzg82t=u%#bNgPJw","GPA_Tracker");

						if ($stmt2 = $mysqli2->prepare("INSERT INTO StudentCourse (grade, weight, relevance, semester, year,
								courseInfoID, selected, userID) VALUES ('ND', ?, ?, '', '', (SELECT CourseInfoID
								FROM CourseInfo WHERE courseID = ?), 0, ?)")){
							$stmt2->bind_param('ssss', $eWeight, $eRelevance, $neededCourse, $user);
							$stmt2->execute();
							$stmt2->close();
							$mysqli2->close();
							$inserted = true;
							break;
						}
						else{
							echo("error insert with w/r");
						}
					}
				}
				$stmt->close();
			}
			else {
				echo("Error checking\n e1: $stmt->error e2: $mysqli->error\n");
			}

			if (!$inserted) {
				$stmt = $mysqli->prepare("INSERT INTO StudentCourse (grade, weight, relevance, semester, year, courseInfoID,
							selected, userID) VALUES ('ND', 0, 0, '', '', (SELECT CourseInfoID FROM CourseInfo
							WHERE courseID = ?), 0, ?)");

				if ($stmt !== false) {
					$stmt->bind_param('ss', $neededCourse, $user);
					$stmt->execute();
					$stmt->close();
				} else {
					echo("Error on StudentCourse Import\n");
				}
			}
		}
	}
	$mysqli->close();
}


