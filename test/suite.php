<?php

/*
	 Method tests
	 */

$tester->setGroup('Methods');
$tester->test('empty object',$myobj->applied('MyTrait'),false);

$myobj->apply(MyTrait);

$tester->test('applied trait',$myobj->applied(MyTrait));
$tester->test('correct return',$myobj->returnsTestMethod(),'testMethod');
// this method is calling another method in the trait
$tester->test('correct return of a return',$myobj->callsReturnsTestMethod(),'testMethod');

$myobj->apply(MyArrayTrait);

$tester->test('applied array-defined trait',$myobj->applied(MyArrayTrait));
$tester->test('correct return for array-defined method',$myobj->someMethod(),'yar');

// this trait requires a method/attribute that doesn't exist
try {
	$myobj->apply(MyRequireTrait);
	$tester->test('fails on nonexistent required method',false);
} catch (Exception $e) {
	$tester->test('fails on nonexistent required method',true);
}

$myobj->apply(MyTrait2,array(
			alias=>array('returnsBlah'=>'returnsBlah2')
			)
		);

$tester->test('applied second trait',$myobj->applied(MyTrait2));
$tester->test('method aliased properly',$myobj->returnsBlah2(),'blah');
// the aliased method should be accessible by its original name to methods within the same trait
$tester->test('aliased method available within trait scope',$myobj->callsReturnsBlah(),'blah');

// implements a method with the same name as another
try {
	$myobj->apply(MyConflictingTrait);
	$tester->test('conflicting method name',false);
} catch (Exception $e) {
	$tester->test('conflicting method name',true);
}


/*
	 Attribute tests
	 */

$tester->setGroup('Attributes');

$myobj->apply(MyAttributeTrait);

$tester->test('attribute accessible',$myobj->attr1,'my attribute');

$myobj->apply(MyAttributeTrait2,array(
			alias=>array('attr1'=>'attr2')
			)
		);

$tester->test('aliased attribute',$myobj->attr2,'my aliased attribute');
// attr1 should not have changed because second one is aliased
$tester->test('previous attribute untouched',$myobj->attr1,'my attribute');
// aliased attribute should be accessible by original name to methods within the same trait
$tester->test('aliased attribute still accessible within trait',$myobj->returnsAttr1(),'my aliased attribute');

$myobj->attr1 = 'new attribute value';

$tester->test('attribute\'s value should change',$myobj->attr1,'new attribute value');

$myobj->attr2 = 'new aliased attribute value';

$tester->test('aliased attribute\'s value should change',$myobj->attr2,'new aliased attribute value');
$tester->test('unaliased attribute should be unchanged',$myobj->attr1,'new attribute value');

$myobj->setsAttr1();

// aliased attribute can be set by original name by methods within the same trait
$tester->test('aliased attribute set within trait',$myobj->attr2,'set by method');
$tester->test('unaliased attribute untouched',$myobj->attr1,'new attribute value');


/*
	 isset/unset/reset
	 */

$tester->test('isset unaliased',isset($myobj->attr1));
unset($myobj->attr1);
try {
	$temp = $myobj->attr1;
	$tester->test('unset unaliased',false);
} catch (Exception $e) {
	$tester->test('unset unaliased',true);
}
$myobj->attr1 = 'yarrr';

$tester->test('reset unaliased',$myobj->attr1,'yarrr');
$tester->test('isset aliased',isset($myobj->attr2));
unset($myobj->attr2);
try {
	$temp = $myobj->attr2;
	$tester->test('unset aliased',false);
} catch (Exception $e) {
	$tester->test('unset aliased',true);
}
$myobj->attr2 = 'blah';
$tester->test('reset aliased',$myobj->attr2,'blah');

$myobj->unsetsAttr1();

try {
	$temp = $myobj->attr2;
	$tester->test('unset of aliased within trait',false);
} catch (Exception $e) {
	$tester->test('unset of aliased within trait',true);
}

?>
