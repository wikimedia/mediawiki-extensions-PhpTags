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

	/**
	 *
	 * @var RValue
	 */
	protected $lastParam = null;

	/**
	 *
	 * @var array
	 */
	protected $listParams = array();
	protected $lastOperator = false;
	protected $variableOperator = false;
	protected $mathMemory = array();

	protected $stack = array();
	protected static $variables = array();
	protected static $staticVariables = array();
	protected static $globalVariables = array();
	protected $thisVariables;
	protected $args;
	protected $scope;

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
	private $countPrecedences;

	public function __construct( array $args, $scope ) {
		$this->args = $args;
		if( !isset(self::$variables[$scope]) ) {
			self::$variables[$scope] = array();
		}
		$this->thisVariables = &self::$variables[$scope];
		$this->thisVariables['argv'] = $args;
		$this->thisVariables['argc'] = count($args);
		$this->thisVariables['GLOBALS'] = &self::$globalVariables;
		$this->countPrecedences = count(self::$operatorsPrecedence)-1;
	}

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
		if( $this->lastOperator ) {
			$precedence = $this->getOperatorPrecedence( $this->lastOperator );
			$this->mathMemory[$precedence] = array($this->lastOperator, $this->lastParam);
			$this->lastOperator = false;
		}
		$this->pushStack();
		$this->lastCommand = $name;
	}

	/**
	 *
	 * @param string $variable Variable name
	 * @param integer $scope Variable scope (default, static, global)
	 * @return boolean Normally return true, false for already initialized static variables
	 */
	public function addParamVariable( $variable, $scope = T_VARIABLE ) {
		$return = true;
		$variable = substr($variable, 1);

		switch ($scope) {
			case T_STATIC:
				if( isset($this->thisVariables[$variable]) ) {
					return new ErrorMessage(__LINE__, null, E_PARSE, T_STATIC);
				}
				$args0 = isset($this->args[0]) ? $this->args[0] : '';
				if( !isset(self::$staticVariables[$args0]) ) {
					self::$staticVariables[$args0] = array();
				}
				if( !isset(self::$staticVariables[$args0][$variable]) ) {
					self::$staticVariables[$args0][$variable] = null;
				}else{
					$return = false;
				}
				$this->thisVariables[$variable] = &self::$staticVariables[$args0][$variable];
				break;
			case T_GLOBAL:
				if( !isset(self::$globalVariables[$variable]) ) {
					self::$globalVariables[$variable] = null;
				}
				$this->thisVariables[$variable] = &self::$globalVariables[$variable];
				return $return;
				break;
		}
		$this->addParam( new RVariable($variable, $this->thisVariables) );

		return $return;
	}

	public function addParamValue( $value ) {
		$this->addParam( new RValue($value) );
	}

	protected function addParam(RValue $param) {
		if( $this->lastOperator ) {
			$precedence = $this->getOperatorPrecedence( $this->lastOperator );
			$this->mathMemory[$precedence] = array($this->lastOperator, $this->lastParam);
			$this->lastOperator = false;
		}
		$this->lastParam = $param;
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
		if( count($this->listParams) ) {
			if( $this->lastParam instanceof RValue ) {
				$this->listParams[] = $this->lastParam->getValue();
			}
			$this->lastParam = $this->listParams;
		}
		$this->popStack();
	}

	public function addOperator( $operator ) {
		switch ($operator) {
			case ',':
				$this->doMath( $this->getOperatorPrecedence($operator) );
				if( $this->lastOperator == T_DOUBLE_ARROW ) {
					$this->lastOperator = false;
				}else{
					$this->listParams[] = $this->lastParam->getValue();
				}
				$this->lastParam = null;
				break;
			case '?':
				$this->doMath( $this->getOperatorPrecedence($operator) );
				return $this->lastParam->getValue();
				break;
			case '"(':
			case '(':
				$this->parenthesesOpen();
				break;
			case '")':
				$this->lastOperator = false;
				$this->parenthesesClose();
				break;
			case ',)':
				if( !is_null($this->lastParam) ) {
					$this->addOperator(',');
				}
				// break is not necessary here
			case ')':
				$this->parenthesesClose();
				switch ($this->lastCommand) {
					case false:
						break;
					case T_IF:
						$this->lastCommand = false;
						return array( T_IF, $this->lastParam->getValue() );
						break;
					case T_ARRAY:
						$this->lastCommand = false;
						$this->lastParam = new RValue( (array)$this->lastParam );
						$this->popStack();
						$this->doMath();
						break;
					default:
						$this->doCommand();
						break;
				}
				break;
			case '[':
				$this->addCommand( $this->lastParam );
				break;
			case ']':
				$this->doMath();
				$this->lastParam = new RArray( $this->lastCommand, $this->lastParam );
				$this->lastCommand = false;
				$this->popStack();
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
						case T_INC:
						case T_DEC:
							if( !isset($this->mathMemory[0]) ) {
								$this->mathMemory[0] = array();
							}
							$this->mathMemory[0][] = $operator;
							if( $this->lastParam instanceof RVariable && !$this->lastOperator ) {
								$this->lastOperator = $operator;
								$this->doMath(0);
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
	}

	protected function doMath( $precedence = false ) {

		if( isset($this->mathMemory[0]) ) {
			while( $mathZerroMemory = array_pop($this->mathMemory[0]) ) {
				$this->doOperation($mathZerroMemory);
			}
			unset($this->mathMemory[0]);
		}
		if($precedence === false){
			$precedence = $this->countPrecedences;
		}
		for($n = 1; $n <= $precedence; $n++) {
			if( isset($this->mathMemory[$n]) ) {
				$this->doOperation($this->mathMemory[$n][0], $this->mathMemory[$n][1]);
				unset($this->mathMemory[$n]);
			}
		}
	}

	/**
	 *
	 * @param mixed $operator
	 * @param RVariable $param
	 */
	protected function doOperation($operator, $param = null) {
		$lastParam = $this->lastParam->getValue();

		switch ($operator) {
			case T_INC: // ++
			case T_DEC: // --
				$lastParam = $this->lastOperator;
				$this->lastOperator = false;
				$param = $this->lastParam;
				// break is not necessary here
			case '=':
			case T_CONCAT_EQUAL:// .=
			case T_PLUS_EQUAL:// +=
			case T_MINUS_EQUAL:// -=
			case T_MUL_EQUAL: // *=
			case T_DIV_EQUAL: // /=
			case T_MOD_EQUAL: // %=
			case T_AND_EQUAL:// &=
			case T_OR_EQUAL:// |=
			case T_XOR_EQUAL:// ^=
			case T_SL_EQUAL:// <<=
			case T_SR_EQUAL:// >>=
				$this->lastParam = $param->doOperation( $operator, $lastParam );
				break;
			case T_DOUBLE_ARROW:// =>
				$this->listParams[$param->getValue()] = $lastParam;
				$this->lastOperator = T_DOUBLE_ARROW;
				break;
			case '.':
				$this->lastParam = new RValue( $param->getValue() . $lastParam );
				break;
			case '+':
				$this->lastParam = new RValue( $param->getValue() + $lastParam );
				break;
			case '-':
				$this->lastParam = $param === null ? new RValue( -$lastParam ) : new RValue( $param->getValue() - $lastParam );
				break;
			case '*':
				$this->lastParam = new RValue( $param->getValue() * $lastParam );
				break;
			case '/':
				if( $lastParam == 0 ) { // Division by zero
					$this->lastParam = new RValue( false );
				} else {
					$this->lastParam = new RValue( $param->getValue() / $lastParam );
				}
				break;
			case '%':
				if( $lastParam == 0 ) { // Division by zero
					$this->lastParam = new RValue( false );
				} else {
					$this->lastParam = new RValue( $param->getValue() % $lastParam );
				}
				break;
			case '&':
				$this->lastParam = new RValue( $param->getValue() & $lastParam );
				break;
			case '|':
				$this->lastParam = new RValue( $param->getValue() | $lastParam );
				break;
			case '^':
				$this->lastParam = new RValue( $param->getValue() ^ $lastParam );
				break;
			case T_SL: // <<
				$this->lastParam = new RValue( $param->getValue() << $lastParam );
				break;
			case T_SR: // >>
				$this->lastParam = new RValue( $param->getValue() >> $lastParam );
				break;
			case '~':
				$this->lastParam = new RValue( ~$lastParam );
				break;
			case T_INT_CAST:
				$this->lastParam = new RValue( (integer) $lastParam );
				break;
			case T_DOUBLE_CAST:
				$this->lastParam = new RValue( (float) $lastParam );
				break;
			case T_STRING_CAST:
				$this->lastParam = new RValue( (string) $lastParam );
				break;
			case T_ARRAY_CAST:
				$this->lastParam = new RValue( (array) $lastParam );
				break;
			case T_BOOL_CAST:
				$this->lastParam = new RValue( (bool) $lastParam );
				break;
			case '<':
				$this->lastParam = new RValue( $param->getValue() < $lastParam );
				break;
			case '>':
				$this->lastParam = new RValue( $param->getValue() > $lastParam );
				break;
			case T_IS_SMALLER_OR_EQUAL: // <=
				$this->lastParam = new RValue( $param->getValue() <= $lastParam );
				break;
			case T_IS_GREATER_OR_EQUAL: // >=
				$this->lastParam = new RValue( $param->getValue() >= $lastParam );
				break;
			case T_IS_EQUAL: // ==
				$this->lastParam = new RValue( $param->getValue() == $lastParam );
				break;
			case T_IS_NOT_EQUAL: // !=
				$this->lastParam = new RValue( $param->getValue() != $lastParam );
				break;
			case T_IS_IDENTICAL: // ===
				$this->lastParam = new RValue( $param->getValue() === $lastParam );
				break;
			case T_IS_NOT_IDENTICAL: // !==
				$this->lastParam = new RValue( $param->getValue() !== $lastParam );
				break;
			default:
				\MWDebug::log( __METHOD__ . " unknown operator '$operator'" );
				break;
		}
	}

	// Remember the child class RuntimeDebug
	public function getCommandResult( ) {
		if( $this->lastParam !== null ) {
			$this->addOperator(',');
		}
		$return = null;

		// Remember the child class RuntimeDebug
		switch ($this->lastCommand) {
			case T_ECHO:
				$return = array( $this->lastCommand, $this->listParams );
				break;
			case false:
				break; // exsample: $foo = 'foobar';
			default:
				// TODO
				$return = 'Error! Unknown command "' . htmlspecialchars($this->lastCommand) . '" in ' . __METHOD__;
				\MWDebug::log($return);
		}
		$this->popStack();
		//$this->lastParam = null;
		return $return;
	}

	private function doCommand() {
	}

}