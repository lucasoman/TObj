<?php

define('except','___except');
define('alias','___alias');
define('required','___required');
define('construct','___construct');

class TObjUnknownItemException extends Exception {
}

class TObj {
	// array of applied traits
	private $___applied;
	// dictionary of externalName=>array(traitName,itemName)
	private $___dictionary;
	// stack of which trait we're in for context in resolving item names
	private $___traitStack;
	// collection of all traits defined in the software
	static private $___traits = array();

	const STATUS_ACTIVE = 1;
	const STATUS_UNSET = 2;

	// applies optional role for instantiating on the fly!
	public function __construct($role=null,$opts=null) {
		$this->___applied = array();
		$this->___traitStack = array();
		$this->___dictionary = array();
		if ($role !== null) {
			$this->apply($role,$opts);
		}
	}

	// checks if given trait is defined
	static public function traitDefined($name) {
		return isset(self::$___traits[$name]);
	}

	// checks if given "class" name is applied
	public function applied($name) {
		return isset($this->___applied[$name]);
	}

	// applies a role (array of things) to object
	public function apply($traitName,$opts=null) {
		if (!isset(self::$___traits[$traitName])) {
			throw new Exception('Trait "'.$traitName.'" not defined.');
		}
		$trait = self::$___traits[$traitName];
		$except = $alias = $required = array();
		if (isset($opts[except])) {
			$except = $opts[except];
		}
		if (isset($opts[alias])) {
			$alias = $opts[alias];
		}
		if (isset($trait[required])) {
			$required = $trait[required];
		}
		// make sure requirements are met
		foreach ($required as $funcName) {
			if (!isset($this->___dictionary[$funcName])) {
				throw new Exception('Required method "'.$funcName.'" not defined for class "'.$traitName.'"');
			}
		}
		// reconstruct trait for storing in this object
		$thisTrait = array();
		foreach ($trait as $itemName=>$item) {
			$externalName = $itemName;
			// is it aliased?
			if (isset($alias[$itemName])) {
				$externalName = $alias[$itemName];
			}
			// if it's not an exception, continue
			/*
				 Why completely exclude an item instead of just excluding it from the dictionary?
				 1) there would be no way to override a trait method because of the way
				 traits are aliased (if called from a method within the same trait, methods maintain their original name)
				 2) if you want to make a method inaccessible from the outside, simply alias it obscurely (hackish, I know)
				 */
			if (!isset($except[$itemName])) {
				// if it's already in the dictionary, then there's a conflict
				if (isset($this->___dictionary[$externalName])) {
					throw new Exception('Conflict: "'.$externalName.'" is already set.');
				}
				if ($this->isMethod($item)) {
					$thisTrait[$itemName] = $item;
				} else {
					$thisTrait[$itemName] = array(
							'value'=>$item,
							'status'=>self::STATUS_ACTIVE,
							);
				}
				// save to the dictionary
				$this->___dictionary[$externalName] = array('traitName'=>$traitName,'itemName'=>$itemName);
			}
		}
		$this->___applied[$traitName] = $thisTrait;
		if (isset($thisTrait[construct])) {
			$trait[construct]($this);
		}
	}

	// is it a special TObject method/attribute?
	private function isInternal($name) {
		return (substr($name,0,3) === '___');
	}

	// is it a method or an attribute?
	private function isMethod($item) {
		return (is_object($item) && get_class($item) == 'Closure');
	}

	// handles calls to lambdas
	public function __call($funcName,$args) {
		try {
			list($traitName,$itemName) = $this->getItemLocation($funcName);
		} catch (TObjUnknownItemException $e) {
			throw new Exception('Method "'.$e->getMessage().'" does not exist.');
		}
		$func = $this->___applied[$traitName][$itemName];
		if ($this->isMethod($func)) {
			$this->___traitStack[] = $traitName;
			array_unshift($args,$this);
			$val = call_user_func_array($func,$args);
			array_pop($this->___traitStack);
			return $val;
		} else {
			throw new Exception('Method "'.$funcName.'" does not exist.');
		}
	}

	// handles calls to attributes
	public function __get($attrName) {
		try {
			list($traitName,$itemName) = $this->getItemLocation($attrName);
		} catch (TObjUnknownItemException $e) {
			throw new Exception('Attribute "'.$e->getMessage().'" does not exist.');
		}
		$item = $this->___applied[$traitName][$itemName];
		if ($this->isMethod($item) || $item['status'] == self::STATUS_UNSET) {
			throw new Exception('Attribute "'.$attrName.'" does not exist.');
		}
		return $this->___applied[$traitName][$itemName]['value'];
	}

	// handles setting attributes
	public function __set($attrName,$value) {
		try {
			list($traitName,$itemName) = $this->getItemLocation($attrName);
		} catch (TObjUnknownItemException $e) {
			throw new Exception('Attribute "'.$e->getMessage().'" does not exist.');
		}
		if ($this->isMethod($this->___applied[$traitName][$itemName])) {
			throw new Exception('Attribute "'.$attrName.'" does not exist.');
		}
		$this->___applied[$traitName][$itemName]['value'] = $value;
		$this->___applied[$traitName][$itemName]['status'] = self::STATUS_ACTIVE;
	}

	public function __isset($attrName) {
		try {
			$this->getItemLocation($attrName);
		} catch (TObjUnknownItemException $e) {
			return false;
		}
		return true;
	}

	public function __unset($attrName) {
		try {
			list($traitName,$itemName,$dictionary) = $this->getItemLocation($attrName);
		} catch (TObjUnknownItemException $e) {
			return;
		}
		$this->___applied[$traitName][$itemName]['status'] = self::STATUS_UNSET;
	}

	// gets location of requested item (method or attribute)
	private function getItemLocation($name) {
		$dictionary = true;
		if (empty($this->___traitStack)) {
			// it's not being requested from one of this object's trait methods
			if (!isset($this->___dictionary[$name])) {
				// never heard of this item before
				throw new TObjUnknownItemException($name);
			}
			$which = $this->___dictionary[$name];
			$traitName = $which['traitName'];
			$itemName = $which['itemName'];
		} else {
			// it's being requested within one of this object's trait methods
			// get the current trait context
			$traitName = $this->___traitStack[count($this->___traitStack) - 1];
			// first, check if something by that name exists in this trait
			// otherwise, just use the dictionary
			if (isset($this->___applied[$traitName][$name])) {
				$itemName = $name;
				$dictionary = false;
			} else {
				if (!isset($this->___dictionary[$name])) {
					throw new TObjUnknownItemException($name);
				}
				$which = $this->___dictionary[$name];
				$traitName = $which['traitName'];
				$itemName = $which['itemName'];
			}
		}
		return array($traitName,$itemName,$dictionary);
	}

	// create a new role.
	// arguments follow this format:
	// roleName,varOrMethName1,varOrMethValue1,varOrMethName2,varOrMethValue2,...
	static public function Trait() {
		$args = func_get_args();
		$numArgs = func_num_args();
		for ($i=0;$i<$numArgs;$i++) {
			if ($i == 0) {
				$name = $args[$i];
				define($name,$name);
				self::$___traits[$name] = array();
			} elseif (($i % 2) == 1) {
				$funcName = $args[$i];
			} else {
				self::$___traits[$name][$funcName] = $args[$i];
			}
		}
	}
}

?>
