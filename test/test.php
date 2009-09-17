<?php

require('tester.php');
require('../tobj.php');

$tester = Tester::singleton();
$tester->runTests(array(
			array('tobjtest.php',Tester::TESTRUN),
			));

print($tester->getResults(true,true,false,true));

?>
