<?php

/*
	 Test traits
	 */

TObj::Trait('MyTrait',
		'returnsTestMethod',function($obj) {
			return 'testMethod';
		},
		'callsReturnsTestMethod',function($obj) {
			return $obj->returnsTestMethod();
		}
		);

TObj::Trait('MyArrayTrait',array(
			'someMethod'=>function($obj) {
				return 'yar';
			},
			'someAttribute'=>'blah',
			)
		);

TObj::Trait('MyRequireTrait',
		required,array('someNonexistentMethod')
		);

TObj::Trait('MyTrait2',
		'returnsBlah',function($obj) {
			return 'blah';
		},
		'callsReturnsBlah',function($obj) {
			return $obj->returnsBlah();
		}
		);

TObj::Trait('MyConflictingTrait',
		'returnsTestMethod',function($obj) {
			return 'this will never work';
		}
		);

TObj::Trait('MyAttributeTrait',
		'attr1','my attribute'
		);

TObj::Trait('MyAttributeTrait2',
		'attr1','my aliased attribute',
		'returnsAttr1',function($obj) {
			return $obj->attr1;
		},
		'setsAttr1',function($obj) {
			$obj->attr1 = 'set by method';
		},
		'unsetsAttr1',function($obj) {
			unset($obj->attr1);
		}
		);


/*
	 Trait definition tests
	 */

$tester->setGroup('Defining Traits');

$tester->test('trait defined',TObj::traitDefined('MyTrait'));
$tester->test('trait name constant',defined('MyTrait'));
$tester->test('second trait defined',TObj::traitDefined('MyTrait2'));
$tester->test('can define using array',TObj::traitDefined('MyArrayTrait'));

?>
