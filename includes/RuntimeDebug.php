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
	private $listParamsDebug = array();
	private $savedListParams;
	private $stackDebug = array();
	private $lastCommandDebug;

	protected function pushStack() {
		parent::pushStack();
		$this->stackDebug[] = $this->listParamsDebug;
		$this->listParamsDebug = array();
	}

	protected function popStack() {
		parent::popStack();

		$this->savedListParams = $this->listParamsDebug;
		if( count($this->stackDebug) == 0 ) {
			$this->listParamsDebug = array();
		} else {
			$this->listParamsDebug = array_pop($this->stackDebug);
		}
	}

	protected function parenthesesClose() {
		if( $this->lastParam !== null ) {
			$this->listParamsDebug[] = self::getHTMLForValue($this->lastParam);
		}

		parent::parenthesesClose();

		$this->lastCommandDebug = $this->lastCommand;
	}

	public function getDebug() {
		$return = implode('<br>', $this->debug);
		$this->debug = array();
		return $return;
	}

	/**
	 *
	 * @param mixed $operator
	 * @param RVariable $param
	 */
	protected function doOperation($operator, $param = null) {
		if( $operator == '=' ) {
			if( $param instanceof RArray ) {
				$i = $param->getIndex();
				$return = \Html::element( 'span', array('class'=>'foxway_variable'), '$'.$param->getParent()->getName() ) .
						( $i instanceof RValue ? '['.self::getHTMLForValue($i).']' : '[]' ) .
						'&nbsp;<b>=></b>&nbsp;=&nbsp;';
			}else{
				$return = \Html::element( 'span', array('class'=>'foxway_variable'), '$'.$param->getName() ) .
						'&nbsp;<b>=></b>&nbsp;=&nbsp;';
			}
		} else {
			if( ($operator == T_INC || $operator == T_DEC) && $this->lastOperator ) {
				$v = $this->lastParam->getValue();
				$return = self::getHTMLForValue($this->lastParam) .
					self::getHTMLForOperator($operator) .
					' = (' . self::getHTMLForValue( new RValue($operator == T_INC ? $v+1 : $v-1) ) . ')' .
					'&nbsp;<b>=></b>&nbsp;';
			} elseif( $operator != T_DOUBLE_ARROW ) {
				$return = ($param === null ? '' : self::getHTMLForValue($param)) .
						self::getHTMLForOperator($operator) .
						self::getHTMLForValue($this->lastParam) .
						'&nbsp;<b>=></b>&nbsp;';
			}
		}

		parent::doOperation($operator, $param);

		if( $operator != T_DOUBLE_ARROW ) {
			$this->debug[] = $return . self::getHTMLForValue( $this->lastParam );
		}
	}

	public function addOperator($operator) {
		if( $operator == ',' ) {
			$this->doMath();
			$this->listParamsDebug[] = self::getHTMLForValue($this->lastParam);
		}

		$return = parent::addOperator($operator);

		if( $operator == '?' ) {
			$this->debug[] = self::getHTMLForValue($this->lastParam) . "&nbsp;?&nbsp;<b>=></b>&nbsp;" . self::getHTMLForValue( new RValue($return?true:false) );
		}elseif( $operator == ')' ) {
			switch ($this->lastCommandDebug) {
				case false:
				case T_ARRAY:
				case T_ECHO:
				case T_PRINT:
				case T_CONTINUE:
				case T_BREAK:
					break;
				case T_WHILE:
				case T_IF:
					$this->debug[] = self::getHTMLForCommand( $this->lastCommandDebug ) .
							"(&nbsp;" . self::getHTMLForValue( $this->lastParam ) .
							"&nbsp;)&nbsp;<b>=></b>&nbsp;" .
							self::getHTMLForValue( new RValue($this->lastParam->getValue() ? true : false) );
					break;
			}
		}
		return $return;
	}

	public function getCommandResult( ) {
		$lastCommand = $this->lastCommand;

		$return = parent::getCommandResult();
		if( $return instanceof ErrorMessage ){
			$lastCommand = false;
		}

		switch ($lastCommand) {
			case T_ECHO:
			case T_PRINT:
				$this->debug[] = self::getHTMLForCommand($lastCommand) . '&nbsp;' . implode(', ', $this->savedListParams) . ';';
				$this->debug[] = implode('', $return[1]);
				break;
			case T_CONTINUE:
			case T_BREAK:
				$this->debug[] = self::getHTMLForCommand($lastCommand) . '&nbsp;' . implode(', ', $this->savedListParams) . ';';
				break;
			default :
				$this->debug[] = is_array($return) ? $return[1] : $return ;
				break;
		}
		return $return;
	}

	/**
	 *
	 * @param RVariable $param
	 * @return string
	 */
	private static function getHTMLForValue( $param) {
		$value = $param->getValue();
		$class = false;
		switch( gettype($value) ) {
			case 'boolean':
				$class = 'foxway_construct';
				$value = $value ? 'true' : 'false';
				break;
			case 'NULL':
				$class = 'foxway_construct';
				$value = 'null';
				break;
			case 'string':
				$class = 'foxway_string';
				$value = "'$value'";
				break;
			case 'integer':
			case 'double':
				$class = 'foxway_number';
				break;
			case 'array': // @todo normalize it
				if( count($value,  COUNT_RECURSIVE) <= 3 ) {
					$value = var_export($value, true);
				} else {
					$value = 'array';
				}
				break;
		}
		if( $class ) {
			$value = \Html::element('span', array('class'=>$class), $value);
		} else {
			$value = strtr( $value, array('&'=>'&amp;', '<'=>'&lt;') );
		}

		if( $param instanceof RArray ) {
			$indexes = array();
			do {
				if( $param->getIndex() === null ) {
					array_unshift( $indexes, '[]' );
				}elseif( $param->is_set() ) {
					array_unshift( $indexes, '[' . self::getHTMLForValue( $param->getIndex() ) . ']' );
				} else {
					array_unshift( $indexes, \Html::element( 'span', array('class'=>'foxway_undefined'), "[" ) .
							self::getHTMLForValue( $param->getIndex() ) .
							\Html::element( 'span', array('class'=>'foxway_undefined'), "]" ) );
				}
				$param = $param->getParent();
			} while ( $param instanceof RArray );
			return ($param->is_set() ? '' : \Html::element( 'span', array('class'=>'foxway_undefined'), "Undefined " ) ) .
					\Html::element( 'span', array('class'=>'foxway_variable'), '$'.$param->getName() ) .
					implode('', $indexes) .	"($value)";
		} elseif( $param instanceof RVariable ) {
			return ($param->is_set() ? '' : \Html::element( 'span', array('class'=>'foxway_undefined'), "Undefined " ) ) .
					\Html::element( 'span', array('class'=>'foxway_variable'), '$'.$param->getName() ) . "($value)";
		}
		return $value;
	}

	private static function getHTMLForCommand($command) {
		switch ($command) {
			case T_ECHO:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'echo');
				break;
			case T_PRINT:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'print');
				break;
			case T_CONTINUE:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'continue');
				break;
			case T_BREAK:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'break');
				break;
			case T_WHILE:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'while');
				break;
			case T_IF:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'if');
				break;
			case T_ARRAY:
				$return = \Html::element('span', array('class'=>'foxway_construct'), 'array');
				break;
			case 'isset':
			case 'unset':
			case 'empty':
				$return = \Html::element('span', array('class'=>'foxway_construct'), $command);
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
			case T_DOUBLE_ARROW:// =>
				$operator = '=>';
				break;
			case T_INC:// ++
				return '++';
				break;
			case T_DEC:// --
				return '--';
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
			case T_SL: // <<
				$operator = '<<';
				break;
			case T_SR: // >>
				$operator= '>>';
				break;
			case T_BOOLEAN_AND: // &&
				$operator = '&&';
				break;
			case T_BOOLEAN_OR: // ||
				$operator = '||';
				break;
			case T_LOGICAL_AND:
				$operator = 'and';
				break;
			case T_LOGICAL_XOR:
				$operator = 'xor';
				break;
			case T_LOGICAL_OR:
				$operator = 'or';
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

	protected function doCommand() {
		$listParams = $this->savedListParams;

		$return = parent::doCommand();

		$this->debug[] = self::getHTMLForCommand( $this->lastCommandDebug ) .
				"(" . implode( ', ', $listParams ) .	")&nbsp;<b>=></b>&nbsp;" .
				self::getHTMLForValue( $this->lastParam );

		return $return;
	}

}
