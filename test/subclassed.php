<?php

$tester->setGroupPrefix('Subclassed');
class TObjSub extends TObj {}
$myobj = new TObjSub();
$tester->setEnv(array('myobj'=>$myobj));
