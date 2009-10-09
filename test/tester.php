<?php

/**
 * @author Lucas Oman (me@lucasoman.com)
 * @description Class for performing unit tests
 */
class Tester {
	public function setSilent($silent=true) {/*{{{*/
		// set true if you don't want a test-by-test message
		$this->_silent = (bool)$silent;
	}/*}}}*/
	public function setGroup($label) {/*{{{*/
		// starts a new testing group
		// use this to separate tests for different modules, scripts, classes, etc.
		$this->closeBuffer();
		if (empty($this->_startTime)) {
			$this->_startTime = microtime(true);
			if (!$this->_silent) print("\nStarting tests...\n");
		}
		$this->_group = (!empty($this->_groupPrefix) ? $this->_groupPrefix.': ' : '').$label;
		if (isset($this->_passes[$this->_group])) {
			$this->_passes[$this->_group] = array();
			$this->_failures[$this->_group] = array();
		}
		if (!$this->_silent) print("* {$this->_group}\n");
		$this->openBuffer();
	}/*}}}*/
	public function setGroupPrefix($prefix) {
		$this->_groupPrefix = $prefix;
	}
	public function test($note,$is,$shouldBe=true) {/*{{{*/
		// does the dirty work
		// note - description of test
		// is - actual result of tested code
		// shouldbe - what the result should be
		$this->closeBuffer();
		$this->_testCount++;
		$return = ($is === $shouldBe);
		$count = str_pad($this->_testCount,3,'0',STR_PAD_LEFT);
		if ($return) {
			$this->_passes[$this->_group][$this->_testCount] = $note;
			if (!$this->_silent) print($count.": PASS - ".$note."\n");
		} else {
			$this->_failures[$this->_group][$this->_testCount] = array('note'=>$note,'is'=>$is,'shouldbe'=>$shouldBe);
			if (!$this->_silent) print($count.": FAIL - ".$note."\n");
		}
		$this->_endTime = microtime(true);
		$this->openBuffer();
	}/*}}}*/
	public function getResults() {/*{{{*/
		$totals = $this->_showTotals;
		$failing = $this->_showFailing;
		$passing = $this->_showPassing;
		$contents = $this->_showContents;
		// returns a report on how the tests went
		$string = "\n--------------------------\nTesting Results\n--------------------------\n\n";
		if ($contents) {
			$string .= $this->getContents();
		}
		if ($failing) {
			list($failstring,$fails) = $this->listNotes($this->_failures);
			if ($fails > 0)
				$string .= "Failing tests\n-------------\n{$failstring}\n";
		}
		list($passstring,$passes) = $this->listNotes($this->_passes);
		if ($passing && $passes > 0) {
			$string .= "Passing tests\n-------------\n.{$passstring}.\n";
		}
		if ($totals) {
			$percent = number_format($passes / ($this->_testCount < 1 ? 1 : $this->_testCount) * 100,0);
			$time = number_format($this->_endTime - $this->_startTime,2);
			$string .= "{$passes}/{$this->_testCount} ({$percent}%) passed in {$time} seconds\n";
		}
		$this->openBuffer();
		return $string;
	}/*}}}*/
	public function setEnv($vars) {/*{{{*/
		// sets the environment for the tests
		// this array will be extracted for every test
		$this->_environment = $vars;
	}/*}}}*/
	public function addEnv($vars) {/*{{{*/
		// adds vars to the environment
		$this->_environment = array_merge($this->_environment,$vars);
	}/*}}}*/
	public function runTests($files) {/*{{{*/
		$this->open();
		$onlyExists = false;
		foreach ($files as $file) {
			if ($file[1] === self::TESTONLY) {
				$onlyExists = true;
				$this->runTest($file[0]);
			}
		}
		if ($onlyExists) {
			$this->close();
			return;
		}
		foreach ($files as $file) {
			if ($file[1] !== self::TESTSKIP) {
				$this->runTest($file[0]);
			}
		}
		$this->close();
	}/*}}}*/
	public function setList($name,$list) {/*{{{*/
		$this->_lists[$name] = $list;
		$this->resetListCounter($name);
	}/*}}}*/
	public function resetListCounter($name) {/*{{{*/
		$this->_listCounters[$name] = 0;
	}/*}}}*/
	public function testList($name,$value) {/*{{{*/
		print("Is:\n".$value."\nShould be:\n".$this->_lists[$name][$this->_listCounters[$name]]);
		$this->test('List '.$name.': '.$this->_listCounters[$name],$value,$this->_lists[$name][$this->_listCounters[$name]]);
		$this->_listCounters[$name]++;
	}/*}}}*/
	public function setShowTotals($show=true) {/*{{{*/
		$this->_showTotals = $show;
	}/*}}}*/
	public function setShowFailing($show=true) {/*{{{*/
		$this->_showFailing = $show;
	}/*}}}*/
	public function setShowPassing($show=true) {/*{{{*/
		$this->_showPassing = $show;
	}/*}}}*/
	public function setShowContents($show=true) {/*{{{*/
		$this->_showContents = $show;
	}/*}}}*/
	private function open() {/*{{{*/
		// opens testing
		// only necessary if you're printing debugging info in your tests
		$this->_open = true;
		ob_start();
	}/*}}}*/
	private function close() {/*{{{*/
		// closes testing
		// only necessary if you open()ed testing
		$this->closeBuffer();
		$this->_open = false;
	}/*}}}*/
	private function runTest($file) {/*{{{*/
		extract($this->_environment);
		$tester = $this;
		require($file);
	}/*}}}*/
	private function __construct() {/*{{{*/
		$this->_showTotals = true;
		$this->_showFailing = true;
		$this->_showPassing = false;
		$this->_showContents = true;
	}
	public static function singleton() {
		if (!self::$_singleton) {
			$class = __CLASS__;
			self::$_singleton = new $class;
		}
		return self::$_singleton;
	}/*}}}*/
	private function closeBuffer() {/*{{{*/
		if ($this->_open) {
			$contents = ob_get_contents();
			ob_end_clean();
			if (!empty($contents)) $this->_contents .= "\n------------\n\n$contents\n";
		}
	}/*}}}*/
	private function openBuffer() {/*{{{*/
		if ($this->_open) {
			ob_start();
		}
	}/*}}}*/
	private function listNotes($groups) {/*{{{*/
		$string = '';
		$total = 0;
		foreach ($groups as $group=>$notes) {
			if (count($notes) > 0) {
				$string .= "* {$group}\n";
				foreach ($notes as $num => $note) {
					if (is_array($note)) {
						$shouldbe = (string)$note['shouldbe'];
						if (strlen($shouldbe) > 20) {
							$shouldbe = substr($shouldbe,0,20).'...';
						}
						$is = (string)$note['is'];
						if (strlen($is) > 20) {
							$is = substr($is,0,20).'...';
						}
						$testNum = str_pad($num,3,'0',STR_PAD_LEFT);
						$string .= "  {$testNum}: {$note['note']}; should be: ".serialize($shouldbe)." is: ".serialize($is)."\n";
					} else {
						$string .= "  {$note}\n";
					}
					$total++;
				}
			}
		}
		return array($string,$total);
	}/*}}}*/
	private function getContents() {/*{{{*/
		return "Printed Data{$this->_contents}\n\n";
	}/*}}}*/

	private $_testCount = 0;
	private $_passes = array();
	private $_failures = array();
	private $_startTime;
	private $_endTime;
	private $_silent = false;
	private $_open = false;
	private $_contents = '';
	private $_environment = array();
	private $_testFiles = array();
	private $_lists = array();
	private $_listCounters = array();
	private $_showTotals;
	private $_showFailing;
	private $_showPassing;
	private $_showContents;
	private $_group;
	private $_groupPrefix;
	private static $_singleton;

	const TESTSKIP = 0;
	const TESTRUN = 1;
	const TESTONLY = 2;
}

?>
