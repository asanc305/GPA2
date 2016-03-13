<?php
class DBTest extends PHPUnit_Framework_TestCase
{

   public function test1()
   {
      // Arrange
      $db = new DatabaseConnector();

      // Act
      $stmt = "SELECT StudentCourse.grade from StudentCourse WHERE StudentCourse.studentCourseID = ? and StudentCourse.userID = ?";
      $params = array("1330", "1");
      $out = $db->select($stmt, $params);

      // Assert
      $this->assertEquals('B', $out[0][0]);
   }

   public function test2()
   {
      // Arrange
      $db = new DatabaseConnector();

      // Act
      $stmt = "SELECT StudentCourse.semester, StudentCourse.userID from StudentCourse WHERE StudentCourse.courseInfoID = ? and StudentCourse.grade = ? ";
      $params = array("3", "B");
      $out = $db->select($stmt, $params);

      // Assert
      $expected = [
         ["Fall", "1"],
         ["Fall", "2"],
         ["Fall", "9"],
         ["Fall", "13"],
         ["Fall", "14"],
         ["Fall", "15"],
         ["Fall", "17"],
         ["Fall", "19"],
         ["Fall", "23"]
      ];
      $this->assertEquals($expected, $out);
   }

   // no COP3530, COP4338, 2Electives
   public function test3()
   {
      $sc = new SettingsController('29', 'g');

      $taken = array();
      array_push($taken, "Fall$$&&2013$$&&COP2210$$&&Programming I$$&&A-$$&&4.00$$&&EN");
      array_push($taken, "Fall$$&&2013$$&&ENC3249$$&&Prof Tech Writing Comp$$&&B-$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2013$$&&MAD2104$$&&Discrete Mathematics$$&&D-$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2013$$&&STA3033$$&&Prob & Stat For Cs$$&&C$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2013$$&&SYG2000$$&&Intro Sociology$$&&C$$&&3.00$$&&EN");
      array_push($taken, "Spring$$&&2014$$&&CDA3103$$&&Fund Computer System$$&&B+$$&&3.00$$&&EN");
      array_push($taken, "Spring$$&&2014$$&&CGS3095$$&&Technology in the Global Arena$$&&C$$&&3.00$$&&EN");
      array_push($taken, "Spring$$&&2014$$&&COP3337$$&&Programming II$$&&C+$$&&3.00$$&&EN");
      array_push($taken, "Spring$$&&2014$$&&MAD2104$$&&Discrete Mathematics$$&&C$$&&3.00$$&&EN");
      array_push($taken, "Summer$$&&2014$$&&CDA4101$$&&Structure Comp Org$$&&DR$$&&3.00$$&&EN");
      array_push($taken, "Summer$$&&2014$$&&COP4710$$&&Database Management$$&&F$$&&3.00$$&&EN");
      array_push($taken, "Summer$$&&2014$$&&COP3530$$&&Data Structures$$&&F$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2014$$&&CDA4101$$&&Structure Comp Org$$&&B$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2014$$&&COP3530$$&&Data Structures$$&&D$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2014$$&&COP4710$$&&Database Management$$&&B+$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2014$$&&COT3541$$&&Logic For Comp Sci$$&&B$$&&3.00$$&&EN");
      array_push($taken, "Summer$$&&2015$$&&COP4555$$&&Prin Of Prog Lang$$&&C+$$&&3.00$$&&EN");
      array_push($taken, "Summer$$&&2015$$&&MAD3512$$&&Theory Algorithms$$&&B$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2015$$&&CEN4010$$&&Software Eng I$$&&A$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2015$$&&CNT4713$$&&Net-centric Computing$$&&C+$$&&3.00$$&&EN");
      array_push($taken, "Fall$$&&2015$$&&COP4610$$&&Operating Syst Princ$$&&B+$$&&3.00$$&&EN");
      array_push($taken, "Spring$$&&2016$$&&CIS4911$$&&Senior Project$$&&IP$$&&3.00$$&&EN");
      array_push($taken, "Spring$$&&2016$$&&COP4520$$&&Parallel Computing$$&&IP$$&&3.00$$&&EN");
      array_push($taken, "Spring$$&&2016$$&&MAD3305$$&&Graph Theory$$&&IP$$&&3.00$$&&EN");

      $sc->testStub($taken, true);

      $db = new DatabaseConnector();
      $stmt = "SELECT courseInfoId, grade from StudentCourse WHERE userId = ?";
      $params = array('29');
      $out = $db->select($stmt, $params);

      $expected = [
         ["1703", "A-"],
         ["56", "B-"],
         ["26", "D-"],
         ["1596", "C"],
         ["1701", "C"],
         ["17", "B+"],
         ["39", "C"],
         ["1710", "C+"],
         ["26", "C"],
         ["59", "DR"],
         ["2099", "F"],
         ["3", "F"],
         ["59", "B"],
         ["3", "D"],
         ["2099", "B+"],
         ["16", "B"],
         ["14", "C+"],
         ["36", "B"],
         ["20", "A"],
         ["60", "C+"],
         ["377", "B+"],
         ["370", "IP"],
         ["391", "IP"],
         ["41", "IP"],
         ["3", "ND"],
         ["46", "ND"],
         ["54", "ND"],
         ["763", "ND"],
         ["766", "ND"],
         ["843", "ND"],
         ["19", "ND"],
         ["32", "ND"],
         ["50", "ND"],
         ["384", "ND"],
         ["386", "ND"],
         ["387", "ND"],
         ["389", "ND"],
         ["390", "ND"],
         ["392", "ND"],
         ["393", "ND"],
         ["394", "ND"],
         ["396", "ND"],
         ["398", "ND"],
         ["4", "ND"],
         ["5", "ND"],
         ["34", "ND"],
         ["35", "ND"],
         ["44", "ND"],
         ["57", "ND"]
      ];

      $this->assertEquals($expected, $out);

   }

   public function test4()
   {
      $sc = new SettingsController('29', 'g');

      $taken = array();
      array_push($taken, "Fall$$&&2013$$&&COP2210$$&&Programming I$$&&A-$$&&4.00");
      array_push($taken, "Fall$$&&2013$$&&ENC3249$$&&Prof Tech Writing Comp$$&&B-$$&&3.00");
      array_push($taken, "Fall$$&&2013$$&&MAD2104$$&&Discrete Mathematics$$&&D-$$&&3.00");
      array_push($taken, "Fall$$&&2013$$&&STA3033$$&&Prob & Stat For Cs$$&&C$$&&3.00");
      array_push($taken, "Fall$$&&2013$$&&SYG2000$$&&Intro Sociology$$&&C$$&&3.00");
      array_push($taken, "Spring$$&&2014$$&&CDA3103$$&&Fund Computer System$$&&B+$$&&3.00");
      array_push($taken, "Spring$$&&2014$$&&CGS3095$$&&Technology in the Global Arena$$&&C$$&&3.00");
      array_push($taken, "Spring$$&&2014$$&&COP3337$$&&Programming II$$&&C+$$&&3.00");
      array_push($taken, "Spring$$&&2014$$&&MAD2104$$&&Discrete Mathematics$$&&C$$&&3.00");
      array_push($taken, "Summer$$&&2014$$&&CDA4101$$&&Structure Comp Org$$&&DR$$&&3.00");
      array_push($taken, "Summer$$&&2014$$&&COP4710$$&&Database Management$$&&F$$&&3.00");
      array_push($taken, "Summer$$&&2014$$&&COP3530$$&&Data Structures$$&&F$$&&3.00");
      array_push($taken, "Fall$$&&2014$$&&CDA4101$$&&Structure Comp Org$$&&B$$&&3.00");
      array_push($taken, "Fall$$&&2014$$&&COP3530$$&&Data Structures$$&&D$$&&3.00");
      array_push($taken, "Fall$$&&2014$$&&COP4710$$&&Database Management$$&&B+$$&&3.00");
      array_push($taken, "Fall$$&&2014$$&&COT3541$$&&Logic For Comp Sci$$&&B$$&&3.00");
      array_push($taken, "Summer$$&&2015$$&&COP4555$$&&Prin Of Prog Lang$$&&C+$$&&3.00");
      array_push($taken, "Summer$$&&2015$$&&MAD3512$$&&Theory Algorithms$$&&B$$&&3.00");
      array_push($taken, "Fall$$&&2015$$&&CEN4010$$&&Software Eng I$$&&A$$&&3.00");
      array_push($taken, "Fall$$&&2015$$&&CNT4713$$&&Net-centric Computing$$&&C+$$&&3.00");
      array_push($taken, "Fall$$&&2015$$&&COP4610$$&&Operating Syst Princ$$&&B+$$&&3.00");
      array_push($taken, "Spring$$&&2016$$&&CIS4911$$&&Senior Project$$&&A$$&&3.00");
      array_push($taken, "Spring$$&&2016$$&&COP4520$$&&Parallel Computing$$&&B$$&&3.00");
      array_push($taken, "Spring$$&&2016$$&&MAD3305$$&&Graph Theory$$&&C+$$&&3.00");
      array_push($taken, "Spring$$&&2015$$&&COP3530$$&&Data Structures$$&&IP$$&&3.00");
      array_push($taken, "Spring$$&&2015$$&&COP4338$$&&Programming III$$&&IP$$&&3.00");
      array_push($taken, "Spring$$&&2015$$&&COP4722$$&&Survey Database Sys$$&&IP$$&&3.00");

      $sc->testStub($taken, true);

      $db = new DatabaseConnector();
      $stmt = "SELECT courseInfoId, grade from StudentCourse WHERE userId = ?";
      $params = array('29');
      $out = $db->select($stmt, $params);

      $expected = [
         ["1703", "A-"],
         ["56", "B-"],
         ["26", "D-"],
         ["1596", "C"],
         ["1701", "C"],
         ["17", "B+"],
         ["39", "C"],
         ["1710", "C+"],
         ["26", "C"],
         ["59", "DR"],
         ["2099", "F"],
         ["3", "F"],
         ["59", "B"],
         ["3", "D"],
         ["2099", "B+"],
         ["16", "B"],
         ["14", "C+"],
         ["36", "B"],
         ["20", "A"],
         ["60", "C+"],
         ["377", "B+"],
         ["370", "A"],
         ["391", "B"],
         ["41", "C+"],
         ["3", "IP"],
         ["46", "ND"],
         ["54", "IP"],
         ["763", "ND"],
         ["766", "ND"],
         ["843", "ND"],
         ["19", "ND"],
         ["32", "ND"],
         ["50", "ND"],
         ["384", "ND"],
         ["386", "ND"],
         ["387", "ND"],
         ["389", "ND"],
         ["390", "ND"],
         ["392", "ND"],
         ["393", "ND"],
         ["394", "IP"],
         ["396", "ND"],
         ["398", "ND"],
         ["4", "ND"],
         ["5", "ND"],
         ["34", "ND"],
         ["35", "ND"],
         ["44", "ND"],
         ["57", "ND"]
      ];

      $this->assertEquals($expected, $out);
   }

}