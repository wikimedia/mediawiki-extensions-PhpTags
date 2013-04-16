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
	private $variableOperator = false;
	private $mathMemory = array();

	private $stack = array();
	private static $variables = array();

	// @see http://www.php.net/manual/ru/language.operators.precedence.php
	private $operatorsPrecedence = array(
		//			(int)			(float)		(string)		(array)		(bool)
		array('~', T_INT_CAST, T_DOUBLE_CAST, T_STRING_CAST, T_ARRAY_CAST, T_BOOL_CAST),
		array('!'),
		array('*', '/', '%'),
		array('+', '-', '.'),
		//		<<	>>
		array(T_SL, T_SR),
		//						<=						>=
		array('<', '>', T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL),
		//		==				!=				===				!==
		array(T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL),
		array('&'),
		array('^'),
		array('|'),
		array('&&'),
		array('||'),
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
		$this->stack[] = array($this->lastCommand, $this->lastDebug, $this->listParams, $this->lastOperator, $this->variableOperator, $this->mathMemory);
		$this->resetRegisters();
	}

	private function popStack() {
		if( count($this->stack) == 0 ) {
			$this->resetRegisters();
		} else {
			list($this->lastCommand, $this->lastDebug, $this->listParams, $this->lastOperator, $this->variableOperator, $this->mathMemory) = array_pop($this->stack);
		}
	}

	private function resetRegisters() {
		$this->lastCommand = false;
		$this->lastDebug = false;
		$this->lastParam = null;
		$this->listParams = array();
		$this->lastOperator = false;
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
//\MWDebug::log( "function addParam( '$param' ) @ lastOperator=" . var_export($this->lastOperator, true) );
		if( $this->lastOperator ) {
			$precedence = $this->getOperatorPrecedence( $this->lastOperator );
			$this->mathMemory[$precedence] = array($this->lastOperator, $this->lastParam);
			$this->lastOperator = false;
		} elseif( !is_null($this->lastParam) ) {
			$this->listParams[] = $this->lastParam; // TODO:it seems that this is unnecessary
		}
		$this->lastParam = $param;
		$this->doMath(0);
//\MWDebug::log("lastParam = $this->lastParam, lastOperator = $this->lastOperator, mathMemory = " . var_export($this->mathMemory, true) );
	}

	public function separateParams() {
//\MWDebug::log("function separateParams()");
		$this->doMath();
		$this->listParams[] = $this->lastParam;
		$this->lastParam = null;
	}

	public function addOperator( $operator ) {
		$precedence = $this->getOperatorPrecedence( $operator );
//\MWDebug::log("function addOperator( $operator ) @ lastOperator = '" . var_export($this->lastOperator, true) . "', lastParam = '" . var_export($this->lastParam, true) . "', precedence = '$precedence'" );
		//						For negative operator
		if( $precedence == 0 || $this->lastOperator || is_null($this->lastParam) ) {
			switch ($operator) {
				case '+':
					break; // ignore this
				case '-':
				case '~':
				case T_INT_CAST:
				case T_DOUBLE_CAST:
				case T_STRING_CAST:
				case T_ARRAY_CAST:
				case T_BOOL_CAST:
					if( !isset($this->mathMemory[0]) ) {
						$this->mathMemory[0] = array();
					}
					$this->mathMemory[0][] = $operator;
					break;
				default:
					\MWDebug::log( __METHOD__ . " unknown operator '$operator'" );
					break;
			}
		} else {
			//doOperation for higher precedence
			$this->doMath($precedence);
			$this->lastOperator = $operator;
		}
	}

	private function doMath( $precedence = 11 ) { //11 = count($operatorsPrecedence)-1
		if( isset($this->mathMemory[0]) ) {
			while( $mathZerroMemory = array_pop($this->mathMemory[0]) ) {
				$this->doOperation($mathZerroMemory);
			}
			unset($this->mathMemory[0]);
		}
		for($n = 1; $n <= $precedence; $n++) {
			if( isset($this->mathMemory[$n]) ) {
				$this->doOperation($this->mathMemory[$n][0], $this->mathMemory[$n][1]);
				unset($this->mathMemory[$n]);
			}
		}
	}

	private function doOperation($operator, $param = null) {
		switch ($operator) {
			case '.':
				$this->lastParam = $param . $this->lastParam;
				break;
			case '+':
				$this->lastParam += $param;
				break;
			case '-':
				if( is_null($param) ) { // Negation
					$this->lastParam = -$this->lastParam;
				} else { // Subtraction
					$this->lastParam = $param - $this->lastParam;
				}
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
			case '~':
				$this->lastParam = ~$this->lastParam;
				break;
			case T_INT_CAST:
				$this->lastParam = (integer) $this->lastParam;
				break;
			case T_DOUBLE_CAST:
				$this->lastParam = (float) $this->lastParam;
				break;
			case T_STRING_CAST:
				$this->lastParam = (string) $this->lastParam;
				break;
			case T_ARRAY_CAST:
				$this->lastParam = (array) $this->lastParam;
				break;
			case T_BOOL_CAST:
				$this->lastParam = (bool) $this->lastParam;
				break;
			case '<':
				$this->lastParam = $param < $this->lastParam;
				break;
			case '>':
				$this->lastParam = $param > $this->lastParam;
				break;
			case T_IS_SMALLER_OR_EQUAL: // <=
				$this->lastParam = $param <= $this->lastParam;
				break;
			case T_IS_GREATER_OR_EQUAL: // >=
				$this->lastParam = $param >= $this->lastParam;
				break;
			case T_IS_EQUAL: // ==
				$this->lastParam = $param == $this->lastParam;
				break;
			case T_IS_NOT_EQUAL: // !=
				$this->lastParam = $param != $this->lastParam;
				break;
			case T_IS_IDENTICAL: // ===
				$this->lastParam = $param === $this->lastParam;
				break;
			case T_IS_NOT_IDENTICAL: // !==
				$this->lastParam = $param !== $this->lastParam;
				break;
			default:
				\MWDebug::log( __METHOD__ . " unknown operator '$operator'" );
				break;
		}
//\MWDebug::log( "function doOperation('$operator', '$param') @ '$this->lastParam'");
	}

	public function getMathResult() {
		$this->doMath();
		$return = $this->lastParam;
		$this->lastParam = null;
		return $return;
	}

	public function getCommandResult( &$debug ) {
//		\MWDebug::log('function getCommandResult()');
		$this->doMath();
		$this->listParams[] = $this->lastParam;
		$return = null;

		switch ($this->lastCommand) {
			case false: // ++$variable OR --$variable;
				break;
			case T_ECHO:
				$return = array( T_ECHO, implode('', $this->listParams) );
				if( $this->lastDebug !== false ) {
					$debug[$this->lastDebug] = '<span style="color:#0000E6" title="'. token_name(T_ECHO) . ' do ' . htmlspecialchars($return[1]) . '">' . $debug[$this->lastDebug] . '</span>';
				}
				break;
			case T_IF:
				$return = array( T_IF, $this->lastParam );
				if( $this->lastDebug !== false ) {
					$debug[$this->lastDebug] = '<span style="color:#0000E6" title="'. ($this->lastParam ? 'TRUE' : 'FALSE') . '">' . $debug[$this->lastDebug] . '</span>';
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
						case false: // $variable++ OR $variable--;
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
					$return = 'Error in ' . __METHOD__ . 'lastCommand = \'' . htmlspecialchars( var_export($this->lastCommand, true) ) .'\'';
				}
				break;
		}
		$this->popStack();
		return $return;
	}

	public function setVariable( $name, $debug ) {
		$this->addCommand("\$$name", $debug);
	}

	public function setVariableValue( $name, $value) {
		self::$variables[$name] = $value;
	}

	public function setVariableOperator( $operator ) {
		$this->variableOperator = $operator;
	}

	public function getVariableValue( $name ) {
		if( isset(self::$variables[$name]) ) {
			return self::$variables[$name];
		} else {
			//TODO THROW
			return null;
		}
	}

	public function parenthesesOpen() {
		if( $this->lastOperator ) {
			$precedence = $this->getOperatorPrecedence( $this->lastOperator );
			$this->mathMemory[$precedence] = array($this->lastOperator, $this->lastParam);
			$this->lastOperator = false;
		}
		$this->pushStack();
	}

	public function parenthesesClose() {
		$this->doMath();
		$this->popStack();
		if( !is_null($this->lastCommand) && $this->lastCommand != T_ECHO && substr($this->lastCommand, 0, 1) != '$') {
			return $this->lastCommand;
		}
	}
}
