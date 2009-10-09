<?php

require('test/tester.php');
require('tobj.php');

$tester = Tester::singleton();
$tester->runTests(array(
			array('test/defineTraits.php',Tester::TESTRUN),
			array('test/direct.php',Tester::TESTRUN),
			array('test/suite.php',Tester::TESTRUN),
			array('test/subclassed.php',Tester::TESTRUN),
			array('test/suite.php',Tester::TESTRUN),
			));

$tester->setShowTotals();
$tester->setShowFailing();
$tester->setShowPassing(false);
$tester->setShowContents();
print($tester->getResults());

?>
