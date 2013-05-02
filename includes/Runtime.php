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
	protected $lastCommand = false;
	protected $lastParam = null;
	protected $listParams = array();
	protected $lastOperator = false;
	protected $variableOperator = false;
	protected $mathMemory = array();

	protected $stack = array();
	protected static $variables = array();

	// @see http://www.php.net/manual/ru/language.operators.precedence.php
	protected static $operatorsPrecedence = array(
		//array('['),
		//		++		--		(int)			(float)		(string)		(array)		(bool)
		array(T_INC, T_DEC, '~', T_INT_CAST, T_DOUBLE_CAST, T_STRING_CAST, T_ARRAY_CAST, T_BOOL_CAST),
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
		array('?', ':'),
		//				+=			-=				*=			/=			.=				%=				&=			|=			^=			<<=			>>=				=>
		array('=', T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_CONCAT_EQUAL, T_MOD_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_XOR_EQUAL, T_SL_EQUAL, T_SR_EQUAL, T_DOUBLE_ARROW),
		array(T_LOGICAL_AND), // and
		array(T_LOGICAL_XOR), // xor
		array(T_LOGICAL_OR), // or
		array(','),
	);

	public function getOperators() {
		static $operators = array();
		if( count($operators) == 0 ) {
			foreach (self::$operatorsPrecedence as $value) {
				$operators = array_merge($operators, $value);
			}
		}
		return $operators;
	}

	protected function getOperatorPrecedence( $operator ) {
		$precendence = 0;
		foreach (self::$operatorsPrecedence as $operators) {
			if( in_array($operator, $operators) ) {
				break;
			}
			$precendence ++;
		}
		return $precendence;
	}

	protected function pushStack() {
		$this->stack[] = array($this->lastCommand, $this->listParams, $this->lastOperator, $this->variableOperator, $this->mathMemory);
		$this->resetRegisters();
	}

	protected function popStack() {
		if( count($this->stack) == 0 ) {
			$this->resetRegisters();
		} else {
			list($this->lastCommand, $this->listParams, $this->lastOperator, $this->variableOperator, $this->mathMemory) = array_pop($this->stack);
		}
	}

	protected function resetRegisters() {
		$this->lastCommand = false;
		$this->lastParam = null;
		$this->listParams = array();
		$this->lastOperator = false;
		$this->variableOperator = false;
		$this->mathMemory = array();
	}

	public function addCommand( $name ) {
		if( $this->lastCommand ) {
			$this->pushStack();
		}
		$this->lastCommand = $name;
	}

	public function addParam( $param ) {
		if( $this->lastOperator ) {
			$precedence = $this->getOperatorPrecedence( $this->lastOperator );
			$this->mathMemory[$precedence] = array($this->lastOperator, $this->lastParam);
			$this->lastOperator = false;
		}
		$this->lastParam = $param;
		$this->doMath(0);
	}

	protected function parenthesesOpen() {
		if( $this->lastOperator ) {
			$precedence = $this->getOperatorPrecedence( $this->lastOperator );
			$this->mathMemory[$precedence] = array($this->lastOperator, $this->lastParam);
			$this->lastOperator = false;
		}
		$this->pushStack();
	}

	protected function parenthesesClose() {
		$this->doMath();
		$this->popStack();
		if( !is_null($this->lastCommand) && $this->lastCommand != T_ECHO ) {
			return $this->lastCommand;
		}
	}

	public function addOperator( $operator ) {
		switch ($operator) {
			case '"(':
			case '(':
				$this->parenthesesOpen();
				break;
			case '")':
			case ')':
				$this->parenthesesClose();
				if( $this->lastCommand && $this->lastCommand != T_ECHO ) {
					if( substr($this->lastParam, 0, 2) == "\0$" ) {
						$variableLastParam = substr($this->lastParam, 2);
						if( !isset(self::$variables[$variableLastParam]) ) {
							//TODO
							return array( $this->lastCommand, null);
						} else {
							return array( $this->lastCommand, self::$variables[$variableLastParam]);
						}
					}
					return array( $this->lastCommand, $this->lastParam);
				}
				break;
			default:
				$precedence = $this->getOperatorPrecedence( $operator );
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
						case T_INC: // TODO
							if( $this->lastOperator || is_null($this->lastParam) ) {
								if( !isset($this->mathMemory[0]) ) {
									$this->mathMemory[0] = array();
								}
								$this->mathMemory[0][] = $operator;
							} else {
								$variableLastParam = false;
								if( substr($this->lastParam, 0, 2) == "\0$" ) {
									$variableLastParam = substr($this->lastParam, 2);
									if( !isset(self::$variables[$variableLastParam]) ) {
										self::$variables[$variableLastParam] = null;
									}
									$this->lastParam = self::$variables[$variableLastParam];
									self::$variables[$variableLastParam]++;
								}
							}
							break;
						case T_DEC: // TODO
							if( $this->lastOperator || is_null($this->lastParam) ) {
								if( !isset($this->mathMemory[0]) ) {
									$this->mathMemory[0] = array();
								}
								$this->mathMemory[0][] = $operator;
							} else {
								$variableLastParam = false;
								if( substr($this->lastParam, 0, 2) == "\0$" ) {
									$variableLastParam = substr($this->lastParam, 2);
									if( !isset(self::$variables[$variableLastParam]) ) {
										self::$variables[$variableLastParam] = null;
									}
									$this->lastParam = self::$variables[$variableLastParam];
									self::$variables[$variableLastParam]--;
								}
							}
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
				break;
		}
		if( substr($this->lastParam, 0, 2) == "\0$" ) {
			$variableLastParam = substr($this->lastParam, 2);
			if( !isset(self::$variables[$variableLastParam]) ) {
				//TODO
				return null;
			} else {
				return self::$variables[$variableLastParam];
			}
		}
		return $this->lastParam;
	}

	protected function doMath( $precedence = 17 ) { //17 = count($operatorsPrecedence)-1
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

	protected function doOperation($operator, $param = null) {
		$variableParam = false;
		if( substr($param, 0, 2) == "\0$" ) {
			$variableParam = substr($param, 2);
			if( !isset(self::$variables[$variableParam]) ) {
				self::$variables[$variableParam] = null;
				// TODO show warning
			}
			$param = self::$variables[$variableParam];
		}

		$variableLastParam = false;
		if( substr($this->lastParam, 0, 2) == "\0$" ) {
			$variableLastParam = substr($this->lastParam, 2);
			if( !isset(self::$variables[$variableLastParam]) ) {
				self::$variables[$variableLastParam] = null;
				// TODO show warning
			}
			$this->lastParam = self::$variables[$variableLastParam];
		}

		switch ($operator) {
			case '=':
				self::$variables[$variableParam] = $this->lastParam;
				break;
			case T_CONCAT_EQUAL:// .=
				self::$variables[$variableParam] .= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_PLUS_EQUAL:// +=
				self::$variables[$variableParam] += $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_MINUS_EQUAL:// -=
				self::$variables[$variableParam] -= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_MUL_EQUAL: // *=
				self::$variables[$variableParam] *= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_DIV_EQUAL: // /=
				self::$variables[$variableParam] /= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_MOD_EQUAL: // %=
				self::$variables[$variableParam] %= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_AND_EQUAL:// &=
				self::$variables[$variableParam] &= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_OR_EQUAL:// |=
				self::$variables[$variableParam] |= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_XOR_EQUAL:// ^=
				self::$variables[$variableParam] ^= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_SL_EQUAL:// <<=
				self::$variables[$variableParam] <<= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_SR_EQUAL:// >>=
				self::$variables[$variableParam] >>= $this->lastParam;
				$this->lastParam = self::$variables[$variableParam];
				break;
			case T_INC: // ++$variable
				$this->lastParam = ++self::$variables[$variableLastParam];
				break;
			case T_DEC: // --$variable
				$this->lastParam = --self::$variables[$variableLastParam];
				break;
			case ',':
				$this->listParams[] = $param;
				break;
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
			case '?':
				break;
			default:
				\MWDebug::log( __METHOD__ . " unknown operator '$operator'" );
				break;
		}
	}

	// Remember the child class RuntimeDebug
	public function getCommandResult( ) {
		$this->doMath();
		$this->doOperation(',', $this->lastParam);
		$return = null;

		// Remember the child class RuntimeDebug
		switch ($this->lastCommand) {
			case T_ECHO:
				$return = array( $this->lastCommand, implode('', $this->listParams) );
				break;
			case false:
				break; // exsample: $foo = 'foobar';
			default:
				// TODO
				$return = 'Error! Unknown command "' . htmlspecialchars($this->lastCommand) . '" in ' . __METHOD__;
				\MWDebug::log($return);
		}
		$this->popStack();
		$this->lastParam = null;
		return $return;
	}

}
