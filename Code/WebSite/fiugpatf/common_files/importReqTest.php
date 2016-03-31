<?php
class importReqTest extends PHPUnit_Framework_TestCase
{

    function test001()
    {
        $sc = new SettingsController(12, 'newuser20');

        $sc->importReqDriver('nursing.xml');

        $db = new DatabaseConnector();

        $params = array(4);
        $buckets = $db->select("Select description from MajorBucket Where majorID = ?",$params);

        $output = array();
        $b = array();
        foreach($buckets as $bucket)
        {
            array_push($b, $bucket[0]);
        }
        array_push($output, $b);

        $courses = $db->select("SELECT CourseInfo.courseID FROM CourseInfo
          INNER JOIN MajorBucketRequiredCourses ON MajorBucketRequiredCourses.courseInfoID = CourseInfo.courseInfoID
          WHERE MajorBucketRequiredCourses.bucketID IN (SELECT bucketID FROM MajorBucket WHERE majorID = ?)", $params);

        $c = array();
        foreach ($courses as $course)
        {
            array_push($c, $course[0]);
        }
        array_push($output, $c);

        $expected = [
            [
                "Chemistry & Lab",
                "Human Anatomy & Lab",
                "Human Growth & Development",
                "Human Physiology & Lab",
                "Intro to Ethics",
                "Intro to Psychology",
                "Junior 1: Semester 1",
                "Junior 2: Semester 2",
                "Junior 2: Semester 3",
                "Microbiology & Lab",
                "Nursing Core",
                "Nutrition",
                "Prerequisites",
                "Senior 3: Semester 1",
                "Senior 4: Semester 2",
                "Statistics"
            ],
            [
                "ZOO3731",
                "ZOO3731L",
                "PCB2099",
                "PCB2099L",
                "MCB2000",
                "MCB2000L",
                "CHM1045L",
                "CHM1045",
                "STA2023",
                "HUN2201",
                "DEP2000",
                "PSY2012",
                "PHI2600",
                "NUR3029",
                "NUR3029C",
                "NUR3029L",
                "NUR3125",
                "NUR3066C",
                "NUR3226",
                "NUR3226L",
                "NUR3145",
                "NUR3666",
                "NSP3801",
                "NUR3821",
                "NUR3227",
                "NUR3227L",
                "NUR4455",
                "NUR4455L",
                "NUR3685L",
                "NUR3535",
                "NUR3535L",
                "NUR4355",
                "NUR4355L",
                "NUR4686L",
                "NUR4667",
                "NUR4636C",
                "NUR4286",
                "NUR4940",
                "NUR4945L"
        ]
        ];

        $this->assertEquals($output, $expected);
    }
}
