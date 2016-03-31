<?php
class importReqTest extends PHPUnit_Framework_TestCase
{

    function importReqTest()
    {
        $sc = new SettingsController(12, 'newuser20');

        $sc->importReq();

        $db = new DatabaseConnector();

        $params = array(4);
        $buckets = $db->select("Select description from MajorBucket Where majorID = ?",$params);
        print_r(array_values($buckets));

        $expected = [
            ["CIS4911", 2, 2],
            ["COP4555", 2, 2],
            ["COP4610", 2, 3],
            ["MAD3512", 2, 2],
            ["COM3417", 3, 1],
            ["CRW2001", 1, 2],
            ["SPC2608", 1, 3],
            ["CNT4713", 3, 1]
        ];

        $this->assertEquals($buckets, $expected);
    }
}