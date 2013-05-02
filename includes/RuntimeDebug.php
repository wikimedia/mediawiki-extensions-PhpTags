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
class RuntimeDebug extends Runtime {

	private $debug = array();

	public function getDebug() {
		$return = implode('<br>', $this->debug);
		$this->debug = array();
		return $return;
	}

	protected function doOperation($operator, $param = null) {
		if( $operator == ',' || $operator == '?' ) {
			return parent::doOperation($operator, $param);
		}

		if( substr($param, 0, 2) == "\0$" ) {
			$variableParam = substr($param, 2);
			$undefined = '';
			if( !isset(self::$variables[$variableParam]) ) {
				$undefined = \Html::element('span', array('class'=>'foxway_undefined'), "Undefined ");
				$thisparam = null;
			}else{
				$thisparam = self::$variables[$variableParam];
			}
			$thisparam = $undefined . \Html::element('span', array('class'=>'foxway_variable'), "\$$variableParam") .
					'(' . self::getHTMLForValue($thisparam) . ')';
		} else {
			$thisparam = self::getHTMLForValue($param);
		}

		if( substr($this->lastParam, 0, 2) == "\0$" ) {
			$variableLastParam = substr($this->lastParam, 2);
			$undefined = '';
			if( !isset(self::$variables[$variableLastParam]) ) {
				$undefined = \Html::element('span', array('class'=>'foxway_undefined'), "Undefined ");
				$lastParam = null;
			} else {
				$lastParam = self::$variables[$variableLastParam];
			}
			$lastParam = $undefined . \Html::element('span', array('class'=>'foxway_variable'), "\$$variableLastParam") .
					'(' . self::getHTMLForValue($lastParam) . ')';
		} else {
			$lastParam = self::getHTMLForValue($this->lastParam);
		}

		if( $operator == '=' ){
			$return = \Html::element('span', array('class'=>'foxway_variable'), "\$$variableParam") . '&nbsp;<b>=></b>&nbsp;=&nbsp;';
		} else {
			if( $variableParam || $variableLastParam ) {
				$return = ($variableParam ? \Html::element('span', array('class'=>'foxway_variable'), "\$$variableParam") : self::getHTMLForValue($thisparam)) .
						self::getHTMLForOperator($operator) .
						($variableLastParam ? \Html::element('span', array('class'=>'foxway_variable'), "\$$variableLastParam") : self::getHTMLForValue($lastParam)) .
						" <b>=></b> ";
			}
			$return = $thisparam . self::getHTMLForOperator($operator) . $lastParam . '&nbsp;<b>=></b>&nbsp;';
		}

		parent::doOperation($operator, $param);

		$this->debug[] = $return . self::getHTMLForValue($this->lastParam);
	}

	public function addOperator($operator) {
		if( ($operator == T_INC || $operator == T_DEC) && (!$this->lastOperator && !is_null($this->lastParam)) ) {
			$variableLastParam = substr($this->lastParam, 2);
			$variable = '';
			$value = null;
			if( !isset(self::$variables[$variableLastParam]) ) {
				$variable = \Html::element('span', array('class'=>'foxway_undefined'), 'Undefined ');
			}else{
				$value = self::$variables[$variableLastParam];
			}
			$this->debug[] = $variable . \Html::element('span', array('class'=>'foxway_variable'), "\$$variableLastParam") .
					'(' . self::getHTMLForValue($value) . ')' .
					($operator == T_INC ? '++' : '--') .
					'&nbsp;<b>=></b>&nbsp;' .
					'(' . self::getHTMLForValue($operator == T_INC ? $value+1 : $value-1) . ')&nbsp' . self::getHTMLForValue($value);
		}

		$return = parent::addOperator($operator);

		if( $operator == '?' ) {

			if( substr($this->lastParam, 0, 2) == "\0$" ) {
				$variableLastParam = substr($this->lastParam, 2);
				$variable = '';
				if( !isset(self::$variables[$variableLastParam]) ) {
					$variable = \Html::element('span', array('class'=>'foxway_undefined'), 'Undefined ');
				}
				$variable .= \Html::element('span', array('class'=>'foxway_variable'), "\$$variableLastParam");
				$variable .= '(' . self::getHTMLForValue($return) . ')';
			}else{
				$variable = self::getHTMLForValue($return);
			}
			$this->debug[] = $variable . "&nbsp;?&nbsp;<b>=></b>&nbsp;" . self::getHTMLForValue($return?true:false);
		}elseif( $operator == ')' && is_array($return) ) {
			list($command, $result) = $return;
			switch ($command) {
				case T_IF:
					if( substr($this->lastParam, 0, 2) == "\0$" ) {
						$variableLastParam = substr($this->lastParam, 2);
						$variable = '';
						if( !isset(self::$variables[$variableLastParam]) ) {
							$variable = \Html::element('span', array('class'=>'foxway_undefined'), 'Undefined ');
						}
						$variable .= \Html::element('span', array('class'=>'foxway_variable'), "\$$variableLastParam");
						$variable .= '(' . self::getHTMLForValue($result) . ')';
					}else{
						$variable = self::getHTMLForValue($result);
					}
					$t = self::getHTMLForCommand($command) . "(&nbsp;" . $variable . "&nbsp;)&nbsp;<b>=></b>&nbsp;";
					if( $result ) {
						$t .= self::getHTMLForValue(true);
					} else {
						$t .= self::getHTMLForValue(false);
					}
					$this->debug[] = $t;
					break;
				default:
					$this->debug[] = self::getHTMLForCommand($command) . "( " . self::getHTMLForValue($result) . " )";
					break;
			}
		}
		return $return;
	}

	// This is a modified copy of the parent
	public function getCommandResult( ) {
		$this->doMath();
		$this->doOperation(',', $this->lastParam);
		$return = null;

		// Remember the child class RuntimeDebug
		switch ($this->lastCommand) {
			case T_ECHO:
				$return = array( $this->lastCommand, implode('', $this->listParams) );
				$this->debug[] = self::getHTMLForCommand($this->lastCommand) . '&nbsp;' . $this->getHTMLForListParams() . ';';
				$this->debug[] = $return[1];
				break;
			case false:
				break; // exsample: $foo = 'foobar';
			default:
				// TODO
				$return = 'Error! Unknown command "' . htmlspecialchars($this->lastCommand) . '" in ' . __METHOD__;
				$this->debug[] = $return;
				\MWDebug::log($return);
		}
		$this->popStack();
		$this->lastParam = null;
		return $return;
	}

	private function getHTMLForListParams() {
		$return = array();
		foreach ($this->listParams as $value) {
			$return[] = self::getHTMLForValue($value);
		}
		return implode(',&nbsp;', $return);
	}

	private static function getHTMLForValue($param) {
		$class = false;
		if( $param === true ) {
			$class = 'foxway_construct';
			$param = 'true';
		}elseif( $param === false ) {
			$class = 'foxway_construct';
			$param = 'false';
		}elseif( $param === null ) {
			$class = 'foxway_construct';
			$param = 'null';
		}elseif( is_string($param) ) {
			$class = 'foxway_string';
			$param = "'$param'";
		}elseif( is_numeric($param) ) {
			$class = 'foxway_number';
		}

		if( $class ) {
			return \Html::element('span', array('class'=>$class), $param);
		}
		return strtr( $param, array('&'=>'&amp;', '<'=>'&lt;') );
	}

	private static function getHTMLForCommand($command) {
		switch ($command) {
			case T_ECHO:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'echo');
				break;
			case T_IF:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'if');
				break;
			default:
				$return = $command;
				break;
		}
		return $return;
	}

	private static function getHTMLForOperator($operator) {
		switch ($operator) {
			case T_CONCAT_EQUAL:// .=
				$operator = '.=';
				break;
			case T_PLUS_EQUAL:// +=
				$operator = '+=';
				break;
			case T_MINUS_EQUAL:// -=
				$operator = '-=';
				break;
			case T_MUL_EQUAL: // *=
				$operator = '*=';
				break;
			case T_DIV_EQUAL: // /=
				$operator = '/=';
				break;
			case T_MOD_EQUAL: // %=
				$operator = '%=';
				break;
			case T_AND_EQUAL:// &=
				$operator = '&=';
				break;
			case T_OR_EQUAL:// |=
				$operator = '|=';
				break;
			case T_XOR_EQUAL:// ^=
				$operator = '^=';
				break;
			case T_SL_EQUAL:// <<=
				$operator = '<<=';
				break;
			case T_SR_EQUAL:// >>=
				$operator = '>>=';
				break;
			case T_INC:// ++
				$operator = '++';
				break;
			case T_DEC:// --
				$operator = '--';
				break;
			case T_IS_SMALLER_OR_EQUAL: // <=
				$operator = '<=';
				break;
			case T_IS_GREATER_OR_EQUAL: // >=
				$operator = '>=';
				break;
			case T_IS_EQUAL: // ==
				$operator = '==';
				break;
			case T_IS_NOT_EQUAL: // !=
				$operator = '!=';
				break;
			case T_IS_IDENTICAL: // ===
				$operator = '===';
				break;
			case T_IS_NOT_IDENTICAL: // !==
				$operator = '!==';
				break;
			case T_INT_CAST:
				$operator = \Html::element('span', array('class'=>'foxway_construct'), '(integer)');
				break;
			case T_DOUBLE_CAST:
				$operator = \Html::element('span', array('class'=>'foxway_construct'), '(float)');
				break;
			case T_STRING_CAST:
				$operator = \Html::element('span', array('class'=>'foxway_construct'), '(string)');
				break;
			case T_ARRAY_CAST:
				$operator = \Html::element('span', array('class'=>'foxway_construct'), '(array)');
				break;
			case T_BOOL_CAST:
				$operator = \Html::element('span', array('class'=>'foxway_construct'), '(bool)');
				break;
		}
		return "&nbsp;$operator&nbsp;";
	}

}
