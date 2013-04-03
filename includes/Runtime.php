<?php
namespace Foxway;
/**
 * Runtime class of Foxway extension.
 *
 * @file Runtime.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Runtime {

	private $lastCommand = false;
	private $lastDebug = false;
	private $lastParam = null;
	private $listParams = array();
	private $lastOperator = false;
	private $lastNegative = null;
	private $variableOperator = false;
	private $mathMemory = array();

	private $stack = array();
	private static $variables = array();

	// @see http://www.php.net/manual/ru/language.operators.precedence.php
	private $operatorsPrecedence = array(
		array('--', '++'),
		array('!'),
		array('*', '/', '%'),
		array('+', '-', '.'),
		array('<<', '>>'),
		array('<', '<=', '>', '>='),
		array('==', '!=', '===', '!==', '<>'),
		array('&'),
		array('^'),
		array('|'),
		array('&&'),
		array('||'),
		array('?', ':'),
	);

	private function getOperatorPrecedence( $operator ) {
		$precendence = 0;
		foreach ($this->operatorsPrecedence as $operators) {
			if( in_array($operator, $operators) ) {
				break;
			}
			$precendence ++;
		}
		return $precendence;
	}

	private function pushStack() {
		$this->stack[] = array($this->lastCommand, $this->lastDebug, $this->lastParam, $this->listParams, $this->lastOperator, $this->lastNegative, $this->variableOperator, $this->mathMemory);
		$this->resetRegisters();
	}

	private function popStack() {
		if( count($this->stack) == 0 ) {
			$this->resetRegisters();
		} else {
			list($this->lastCommand, $this->lastDebug, $this->lastParam, $this->listParams, $this->lastOperator, $this->lastNegative, $this->variableOperator, $this->mathMemory) = array_pop($this->stack);
		}
	}

	private function resetRegisters() {
		$this->lastCommand = false;
		$this->lastDebug = false;
		$this->lastParam = null;
		$this->listParams = array();
		$this->lastOperator = false;
		$this->lastNegative = null;
		$this->variableOperator = false;
		$this->mathMemory = array();
	}



	public function addCommand( $name, $debug ) {
		if( $this->lastCommand ) {
			$this->pushStack();
		}
		$this->lastCommand = $name;
		$this->lastDebug = $debug;
	}

	public function addParam( $param ) {
//\MWDebug::log( "function addParam( '$param' ) @ lastOperator=" . var_export($this->lastOperator, true) . " lastNegative=" . var_export($this->lastNegative, true));
		if( $this->lastNegative === true) {
			$param = -$param;
			$this->lastNegative = null;
		}

		if( $this->lastOperator ) {
			$precedence = $this->getOperatorPrecedence( $this->lastOperator );
			$this->mathMemory[$precedence] = array($this->lastOperator, $this->lastParam);
			$this->lastParam = $this->lastNegative === true ? -$param : $param;
			$this->lastNegative = null;
			$this->lastOperator = false;
		} else {
			if( !is_null($this->lastParam) ) {
				$this->listParams[] = $this->lastParam;
			}
			$this->lastParam = $param;
		}
//\MWDebug::log("lastParam = $this->lastParam, lastOperator = $this->lastOperator, mathMemory = " . var_export($this->mathMemory, true) );
	}

	public function separateParams() {
//\MWDebug::log("function separateParams()");
		while( count($this->mathMemory) != 0 ) {
			$mathMemory = array_pop($this->mathMemory);
			$this->doOperation($mathMemory[0], $mathMemory[1]);
		}
		$this->listParams[] = $this->lastParam;
		$this->lastParam = null;
	}

	public function addOperator( $operator ) {
//\MWDebug::log("function addOperator( $operator ) @ lastOperator = '$this->lastOperator', lastParam = '$this->lastParam'" );
		// For negative operator
		if( $this->lastOperator || is_null($this->lastParam) ) {
			if( $operator == '-' ) {
				//					  false or null
				$this->lastNegative = !$this->lastNegative ? true : false;
//\MWDebug::log( "lastNegative = " . var_export($this->lastNegative, true) );
			}
		} else {
			//doOperation for higher precedence
			$precedence = $this->getOperatorPrecedence( $operator );
			if( $precedence == 0 && !is_null($this->lastParam) ) {
				$this->doOperation($operator);
			} else {
				for($n = 0; $n <= $precedence; $n++) {
					if( isset($this->mathMemory[$n]) ) {
						$this->doOperation($this->mathMemory[$n][0], $this->mathMemory[$n][1]);
						unset($this->mathMemory[$n]);
					}
				}
			}
			$this->lastOperator = $operator;
		}
	}

	private function doOperation($operator, $param = null) {
		switch ($operator) {
			case '++':
				$this->lastParam++;
				break;
			case '--':
				$this->lastParam--;
				break;
			case '.':
				$this->lastParam = $param . $this->lastParam;
				break;
			case '+':
				$this->lastParam += $param;
				break;
			case '-':
				$this->lastParam = $param - $this->lastParam;
				break;
			case '*':
				$this->lastParam *= $param;
				break;
			case '/':
				$this->lastParam = $param / $this->lastParam;
				break;
			case '%':
				$this->lastParam = $param % $this->lastParam;
				break;
			case '&':
				$this->lastParam &= $param;
				break;
			case '|':
				$this->lastParam = $param | $this->lastParam;
				break;
			case '^':
				$this->lastParam = $param ^ $this->lastParam;
				break;
			case T_SL: // <<
				$this->lastParam = $param << $this->lastParam;
				break;
			case T_SR: // >>
				$this->lastParam = $param >> $this->lastParam;
				break;
			default:
				\MWDebug::log( __METHOD__ . " unknown operator '$operator'" );
				break;
		}
//\MWDebug::log( "function doOperation('$operator', '$param') @ '$this->lastParam'");
	}

	public function getCommandResult( &$debug ) {
		while( count($this->mathMemory) != 0 ) {
			$mathMemory = array_pop($this->mathMemory);
			$this->doOperation($mathMemory[0], $mathMemory[1]);
		}
		$this->listParams[] = $this->lastParam;
		$return = null;

		switch ($this->lastCommand) {
			case 'echo':
				$return = implode('', $this->listParams);
				if( $this->lastDebug !== false ) {
					$debug[$this->lastDebug] = '<span style="color:#0000E6" title="'. token_name(T_ECHO) . ' do ' . htmlspecialchars($return) . '">' . $debug[$this->lastDebug] . '</span>';
				}
				break;
			default:
				$lastCommand = $this->lastCommand;
				if( substr($lastCommand, 0, 1) == '$' ) {
					$varName = substr($lastCommand, 1);
					switch ($this->variableOperator) {
						case '=':
							self::$variables[$varName] = $this->lastParam;
							break;
						case T_CONCAT_EQUAL:// .=
							self::$variables[$varName] .= $this->lastParam;
							break;
						case T_PLUS_EQUAL:// +=
							self::$variables[$varName] += $this->lastParam;
							break;
						case T_MINUS_EQUAL:// -=
							self::$variables[$varName] -= $this->lastParam;
							break;
						case T_MUL_EQUAL: // *=
							self::$variables[$varName] *= $this->lastParam;
							break;
						case T_DIV_EQUAL: // /=
							self::$variables[$varName] /= $this->lastParam;
							break;
						case T_MOD_EQUAL: // %=
							self::$variables[$varName] %= $this->lastParam;
							break;
						case T_AND_EQUAL:// &=
							self::$variables[$varName] &= $this->lastParam;
							break;
						case T_OR_EQUAL:// |=
							self::$variables[$varName] |= $this->lastParam;
							break;
						case T_XOR_EQUAL:// ^=
							self::$variables[$varName] ^= $this->lastParam;
							break;
						case T_SL_EQUAL:// <<=
							self::$variables[$varName] <<= $this->lastParam;
							break;
						case T_SR_EQUAL:// >>=
							self::$variables[$varName] >>= $this->lastParam;
							break;
						default:
							// TODO exception
							$return = 'Error! Unknown operator "' . htmlspecialchars($this->variableOperator) . '" in ' . __METHOD__;
							\MWDebug::log($return);
							break 2;
					}
					if( $this->lastDebug !== false ) {
						$debug[$this->lastDebug] = '<span style="color:#6D3206" title="'.token_name(T_VARIABLE).' set '.htmlspecialchars( var_export(self::$variables[$varName], true) ).'">' . $lastCommand . '</span>';
					}

				} else {
					// TODO exception
					$return = 'Error in ' . __METHOD__;
				}
				break;
		}
		$this->popStack();
		return $return;
	}

	public function setVariable( $name, $debug ) {
		$this->addCommand($name, $debug);
	}

	public function setVariableOperator( $operator ) {
		$this->variableOperator = $operator;
	}

	public function getVariable( $name ) {
		if( isset(self::$variables[$name]) ) {
			return self::$variables[$name];
		} else {
			//TODO THROW
			return null;
		}
	}

}
