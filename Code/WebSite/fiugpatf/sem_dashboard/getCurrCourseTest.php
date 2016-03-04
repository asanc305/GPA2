<?php
include_once 'getCurrCourse.php';


//ID: GPA2_term001_UT_001
function testTerm001() {
    $testMonth = "12";
    $return = term($testMonth);
    $assert = "fall";

    echo assert($return, $assert);

    /*if($return == $assert) {
        echo "testTerm001(): PASS\n";
    }
    else {
        echo "testTerm001(): FAIL\n";
    }*/
}

//ID: GPA2_term001_UT_002
function testTerm002() {
    $testMonth = "01";
    $return = term($testMonth);
    $assert = "spring";

    if($return == $assert) {
        echo "testTerm002(): PASS\n";
    }
    else {
        echo "testTerm002(): FAIL\n";
    }
}

//ID: GPA2_term001_UT_003
function testTerm003() {
    $testMonth = "06";
    $return = term($testMonth);
    $assert = "summer";

    if($return == $assert) {
        echo "testTerm003(): PASS\n";
    }
    else {
        echo "testTerm003(): FAIL\n";
    }
}

function testTimePeriod001() {
    $date = '2015-08-17';
    $term = 'fall';

    $return = timePeriod($term, $date);
    $assert = '09-07';

    if($return == $assert) {
        echo "testTimePeriod001(): PASS\n";
    }
    else {
        echo "testTimePeriod001(): FAIL\n";
    }
}




//CALL ALL TEST FUNCTIONS
testTerm001();
testTerm002();
testTerm003();
testTimePeriod001();

?>
