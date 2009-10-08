<?php

require('test/tester.php');
require('tobj.php');

$tester = Tester::singleton();
$tester->runTests(array(
			array('test/tobjtest.php',Tester::TESTRUN),
			));

print($tester->getResults(true,true,false,true));

?>
