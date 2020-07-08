<?php
namespace PhpTags;

use Exception;

if ( false === defined( 'T_ONUMBER' ) ) { // T_ONUMBER defined in HHVM only
	define( 'T_ONUMBER', -20140814094314 );
}

/**
 * The compiler class of the extension PhpTags.
 * This class converts a php code as data for the class Runtime
 *
 * @file Compiler.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Compiler {

	//											+=			-=				*=			/=			.=				%=				&=			|=			^=			<<=			>>=
	protected static $assignmentOperators =	array( '=', T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_CONCAT_EQUAL, T_MOD_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_XOR_EQUAL, T_SL_EQUAL, T_SR_EQUAL );

	/**
	 * Operator Precedence
	 * @see http://www.php.net/manual/en/language.operators.precedence.php
	 * @var array
	 */
	protected static $operatorsPrecedence = array(
		//array('['),
		//		++		--		(int)			(float)		(string)		(array)		(bool)			(unset)
		array( T_INC, T_DEC, '~', T_INT_CAST, T_DOUBLE_CAST, T_STRING_CAST, T_ARRAY_CAST, T_BOOL_CAST, T_UNSET_CAST ),
		array( '!' ),
		array( '*', '/', '%' ),
		array( '+', '-', '.' ),
		//		<<	>>
		array( T_SL, T_SR ),
		//						<=						>=
		array( '<', '>', T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL ),
		//		==				!=				===				!==
		array( T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL ),
		array( '&' ),
		array( '^' ),
		array( '|' ),
		array( T_BOOLEAN_AND ), // &&
		array( T_BOOLEAN_OR ), // ||
		array( '?', ':' ),
		null, // self::$assignmentOperators
		array( T_LOGICAL_AND ), // and
		array( T_LOGICAL_XOR ), // xor
		array( T_LOGICAL_OR ), // or
		array( ',' ),
		array( ';' ),
	);

	/**
	 * Conformity operators PHP and PHPTAGS
	 * @var array
	 */
	protected static $runtimeOperators = array(
		'~' => Runtime::T_NOT,
		'!' => Runtime::T_IS_NOT,
		'*' => Runtime::T_MUL,
		'/' => Runtime::T_DIV,
		'%' => Runtime::T_MOD,
		'+' => Runtime::T_PLUS,
		'-' => Runtime::T_MINUS,
		'.' => Runtime::T_CONCAT,
		'<' => Runtime::T_IS_SMALLER,
		'>' => Runtime::T_IS_GREATER,
		'&' => Runtime::T_AND,
		'^' => Runtime::T_XOR,
		'|' => Runtime::T_OR,
		'=' => Runtime::T_EQUAL,
		'"' => Runtime::T_QUOTE,
		'@' => Runtime::T_IGNORE_ERROR,
		'?' => Runtime::T_TERNARY,
		T_LOGICAL_OR => Runtime::T_LOGICAL_OR,
		T_BOOLEAN_OR => Runtime::T_LOGICAL_OR,
		T_LOGICAL_XOR => Runtime::T_LOGICAL_XOR,
		T_LOGICAL_AND => Runtime::T_LOGICAL_AND,
		T_BOOLEAN_AND => Runtime::T_LOGICAL_AND,
		T_SR_EQUAL => Runtime::T_SR_EQUAL,
		T_SL_EQUAL => Runtime::T_SL_EQUAL,
		T_XOR_EQUAL => Runtime::T_XOR_EQUAL,
		T_OR_EQUAL => Runtime::T_OR_EQUAL,
		T_AND_EQUAL => Runtime::T_AND_EQUAL,
		T_MOD_EQUAL => Runtime::T_MOD_EQUAL,
		T_CONCAT_EQUAL => Runtime::T_CONCAT_EQUAL,
		T_DIV_EQUAL => Runtime::T_DIV_EQUAL,
		T_MUL_EQUAL => Runtime::T_MUL_EQUAL,
		T_MINUS_EQUAL => Runtime::T_MINUS_EQUAL,
		T_PLUS_EQUAL => Runtime::T_PLUS_EQUAL,
		T_IS_NOT_IDENTICAL => Runtime::T_IS_NOT_IDENTICAL,
		T_IS_IDENTICAL => Runtime::T_IS_IDENTICAL,
		T_IS_NOT_EQUAL => Runtime::T_IS_NOT_EQUAL,
		T_IS_EQUAL => Runtime::T_IS_EQUAL,
		T_IS_GREATER_OR_EQUAL => Runtime::T_IS_GREATER_OR_EQUAL,
		T_IS_SMALLER_OR_EQUAL => Runtime::T_IS_SMALLER_OR_EQUAL,
		T_SL => Runtime::T_SL,
		T_SR => Runtime::T_SR,
		T_UNSET_CAST => Runtime::T_UNSET_CAST,
		T_BOOL_CAST => Runtime::T_BOOL_CAST,
		T_ARRAY_CAST => Runtime::T_ARRAY_CAST,
		T_STRING_CAST => Runtime::T_STRING_CAST,
		T_DOUBLE_CAST => Runtime::T_DOUBLE_CAST,
		T_INT_CAST => Runtime::T_INT_CAST,
		T_DEC => Runtime::T_DEC,
		T_INC => Runtime::T_INC,
		T_BREAK => Runtime::T_BREAK,
		T_CONTINUE => Runtime::T_CONTINUE,
		T_UNSET => Runtime::T_UNSET,
		T_ISSET => Runtime::T_ISSET,
		T_EMPTY => Runtime::T_EMPTY,
		T_NEW => Runtime::T_NEW,
		T_GLOBAL => Runtime::T_GLOBAL,
		T_STATIC => Runtime::T_STATIC,
	);

	/**
	 * Unfurled operator precedence
	 * key - operator
	 * value - precedence
	 * @var array
	 */
	private static $precedencesMatrix=array();

	private $stack = array();
	private $tokens;
	private $id;
	private $text;
	private $tokenLine;
	private $place;
	private $debug = array();
	private $stackMemory = array();
	private $ignoreErrors = false;

	/**
	 * Compiler constructor.
	 */
	function __construct() {
		if ( !self::$precedencesMatrix ) {
			self::$operatorsPrecedence[13] = self::$assignmentOperators;
			foreach ( self::$operatorsPrecedence as $key => &$value ) {
				self::$precedencesMatrix += array_fill_keys( $value, $key );
			}
		}
	}

	/**
	 * Make The Lexical analysis and fill $this->tokens
	 * @param string $source PHP source code
	 * @throws PhpTagsException
	 */
	private function setTokensFromSource( $source ) {
		$tokens = token_get_all( "<?php $source ?>" );

		$this->tokens = $tokens;
		reset( $this->tokens );
		$this->tokenLine = 0;
		$this->stepUP( false );
	}

	/**
	 * @param $source
	 * @param string $place
	 * @return array
	 * @throws PhpTagsException
	 */
	public static function compile( $source, $place = 'Command line code' ) {
		$instance = new self();
		return $instance->getBytecode( $source, $place );
	}

	/**
	 * @param $source
	 * @param $place
	 * @return array
	 * @throws PhpTagsException
	 */
	private function getBytecode( $source, $place ) {
		$this->place = $place;
		$this->setTokensFromSource( $source );
		$this->stepBlockOperators( T_CLOSE_TAG, false );
		return $this->stack;
	}

	/**
	 * @param $endToken
	 * @param bool $throwEndTag
	 * @throws PhpTagsException
	 */
	private function stepBlockOperators( $endToken, $throwEndTag = true ) {
		while ( $this->id != $endToken ) {
			$this->stepFirstOperator( $throwEndTag ) ||	$this->stepFirsValue( $throwEndTag );
		}
	}

	/**
	 * @param bool $throwEndTag
	 * @return bool
	 * @throws PhpTagsException
	 */
	private function stepFirstOperator( $throwEndTag = true ) {
		$id = $this->id;
		$text = $this->text;
		switch ( $id ) {
			case T_ECHO:
				$this->stepUP( true );
				$value =& $this->getNextValue();
				if( !$value ) { // Example: echo ;
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				do {
					$echo = array(
						Runtime::B_COMMAND => Runtime::T_PRINT,
						Runtime::B_PARAM_1 => null,
						Runtime::B_RESULT => null,
						Runtime::B_TOKEN_LINE => $this->tokenLine,
						Runtime::B_DEBUG => $text,
					);
					$this->addValueIntoStack( $value, $echo, Runtime::B_PARAM_1 );
					$this->stack[] =& $echo;
					unset( $echo );
					if ( current($this->tokens) != ',' ) {
						break;
					}
					$this->stepUP( true );
				} while ( $value =& $this->getNextValue() );

				if ( $this->id != ';' ) { // Example echo "foo"%
					// PHP Parse error:  syntax error, unexpected $id, expecting ';'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "';'" ), $this->tokenLine, $this->place );
				}
				$this->stepUP( $throwEndTag );
				return true;
			case T_IF:
				$tmp =& $this->stepIfConstruct( true, $throwEndTag );
				if ( $tmp !== true && $tmp !== false ) {
					$this->stack[] =& $tmp;
				}
				return true;
			case T_DO:
				return $this->stepDoConstruct( $throwEndTag ); // return true
			case T_WHILE:
				return $this->stepWhileConstruct( $throwEndTag ); // return true
			case T_FOREACH:
				return $this->stepForeachConstruct( $throwEndTag ); // return true
			case T_FOR:
				return $this->stepForConstruct( $throwEndTag ); // return true
//			case T_SWITCH:
//				return $this->stepSwitchConstruct( $throwEndTag ); // return true
			case T_CONTINUE:
			case T_BREAK:
				$this->stepUP( true );
				$value =& $this->getNextValue();

				if ( $this->id != ';' ) { // Example: continue 5#
					// PHP Parse error:  syntax error, unexpected $id, expecting ';'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "';'" ), $this->tokenLine, $this->place );
				}
				if( !$value ) { // Example: continue;
					unset( $value );
					$value = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>1 );
				}
				$operator = array( Runtime::B_COMMAND=>self::$runtimeOperators[$id], Runtime::B_RESULT=>null, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
				$this->addValueIntoStack( $value, $operator, Runtime::B_RESULT );
				$this->stack[] = $operator;
				return true;
			case T_GLOBAL:
				$variables = array();
				do {
					$this->stepUP( true );
					$value =& $this->getNextValue();

					if ( $this->id != ',' && $this->id != ';' ) { // Example: global $foo#
						// PHP Parse error:  syntax error, unexpected $id, expecting ',' or ';'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "';'" ), $this->tokenLine, $this->place );
					}
					if ( $value === false ) { // Example: global;
						// PHP Parse error:  syntax error, unexpected $id,
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					if ( $value[Runtime::B_COMMAND] !== Runtime::T_VARIABLE ) { // Example global $foo=5;
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[Runtime::B_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
					}
					if ( isset($value[Runtime::B_ARRAY_INDEX]) ) {
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[', "','", "';'" ), $this->tokenLine, $this->place );
					}
					$variables[] = $value[Runtime::B_PARAM_1];
				} while ( $this->id === ',' );
				$this->stepUP( $throwEndTag );

				$this->stack[] = array(
					Runtime::B_COMMAND => Runtime::T_GLOBAL,
					Runtime::B_PARAM_1 => $variables,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);
				return true;
			case T_STATIC:
				$this->stack_push_memory();
				do {
					$this->stepUP( true );
					$value =& $this->getNextValue();

					if ( $this->id != ',' && $this->id != ';' ) { // Example: static $foo#
						// PHP Parse error:  syntax error, unexpected $id, expecting ',' or ';'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "';'" ), $this->tokenLine, $this->place );
					}
					if ( $value === false ) { // Example: static;
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, token_name($id) ), $this->tokenLine, $this->place );
					}
					if ( $this->stack ) {
						throw new PhpTagsException( PhpTagsException::PARSE_ERROR_EXPRESSION_IN_STATIC, null, $this->tokenLine, $this->place );
					}
					$this->stack_pop_memory();
					if ( $value[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) {
						if ( isset($value[Runtime::B_ARRAY_INDEX]) ) {
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[', "','", "';'" ), $this->tokenLine, $this->place );
						}
						$this->stack[] = array(
							Runtime::B_COMMAND => Runtime::T_STATIC,
							Runtime::B_PARAM_1 => $value[Runtime::B_PARAM_1],
							Runtime::B_RESULT => null,
							Runtime::B_TOKEN_LINE => $this->tokenLine,
							Runtime::B_DEBUG => $text );
					} elseif ( $value[Runtime::B_COMMAND] === Runtime::T_EQUAL ) {
						if ( isset($value[Runtime::B_PARAM_1][Runtime::B_ARRAY_INDEX]) ) {
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[', "','", "';'" ), $this->tokenLine, $this->place );
						}
						$operator = array(
							Runtime::B_COMMAND => Runtime::T_STATIC,
							Runtime::B_PARAM_1 => $value[Runtime::B_PARAM_1][Runtime::B_PARAM_1],
							Runtime::B_RESULT => &$value[Runtime::B_PARAM_2],
							Runtime::B_TOKEN_LINE => $this->tokenLine,
							Runtime::B_DEBUG => $text );
						$this->stack[] = $operator;
					} else { // Example static 5+5;
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[Runtime::B_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
					}
					$this->stack_push_memory();
				} while ( $this->id === ',' );
				$this->stepUP( $throwEndTag );
				$this->stack_pop_memory();
				return true;
			case ';': // Example: ;;;;
				$this->stepUP( $throwEndTag );
				return true;
		}
		return false;
	}

	/**
	 * @param $functionName
	 * @param bool $objectName
	 * @return array
	 * @throws PhpTagsException
	 */
	private function & getFunctionParameters( $functionName, $objectName = false ) {
		$funcKey = $functionName[Runtime::B_RESULT] ? strtolower( $functionName[Runtime::B_RESULT] ) : null;
		$result = array();
		$i = 0;
		while ( $value =& $this->getNextValue() ) {
			if ( $value[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) {
				$value[Runtime::B_PARAM_2] =& $result;
				$value[Runtime::B_AIM] = $i;
				$result[$i] = null;
			} else {
				$result[$i] =& $value[Runtime::B_RESULT];
			}
			if ( $value[Runtime::B_COMMAND] ) {
				$this->stack[] =& $value;
			}
			if ( $objectName ) {
				$ref =& $objectName[0];
				$objectKey = $ref[Runtime::B_OBJECT_KEY] ?: null;
				$hookType = $ref[Runtime::B_HOOK_TYPE];
			} else {
				$ref = false;
				$objectKey = false;
				$hookType = Runtime::H_FUNCTION;
			}
			$hookCheckParam = array(
				Runtime::B_COMMAND => Runtime::T_HOOK_CHECK_PARAM,
				Runtime::B_HOOK_TYPE => $hookType,
				Runtime::B_OBJECT => &$ref,
				Runtime::B_OBJECT_KEY => $objectKey,
				Runtime::B_METHOD => false, // Function or method name
				Runtime::B_METHOD_KEY => $funcKey,
				Runtime::B_PARAM_2 => $value[Runtime::B_COMMAND] === Runtime::T_VARIABLE,
				Runtime::B_AIM => $i,
				Runtime::B_RESULT => &$result,
				Runtime::B_TOKEN_LINE => $this->tokenLine,
			);
			$func = $functionName; // clone the function name for use in the next loop
			$this->addValueIntoStack( $func, $hookCheckParam, Runtime::B_METHOD );
			$this->stack[] =& $hookCheckParam;
			unset( $ref, $hookCheckParam, $func );

			if ( current($this->tokens) != ',' ) {
				break;
			}

			$this->stepUP();
			$i++;
		}
		return $result;
	}

	/**
	 * @param string $operator
	 * @param bool $owner
	 * @return array|bool
	 * @throws PhpTagsException
	 */
	private function & getNextValue( $operator = ',', $owner = false ) {
		$val =& $this->stepValue( $owner ); // Get a value
		if ( $val !== false ) { // The value was received
			if ( $val[Runtime::B_COMMAND] === Runtime::T_INC || $val[Runtime::B_COMMAND] === Runtime::T_DEC ) { // Operators Incrementing have the highest priority
				$tmp = array( Runtime::B_COMMAND=>false, Runtime::B_RESULT=>&$val[Runtime::B_RESULT] );
				$this->stack[] =& $val;
				$val =& $tmp;
			}
			// Look for operator
			$operatorPrecedence = self::$precedencesMatrix[ $operator ]; // The precedence of the operator
			$oper =& $this->getOperator( $val, $operatorPrecedence ); // Get arithmetic operator
			if ( $oper ) {
				return $oper;
			}
		} // Value was not received
		return $val;
	}

	/**
	 * @param bool $throwEndTag
	 * @throws PhpTagsException
	 */
	private function stepUP( $throwEndTag = true ) {
		$id = $text = false;

		while ( $token = next($this->tokens) ) {
			if ( is_string($token) ) {
				$id = $text = $token;
				$this->debug[] = array( $text );
			} else {
				list( $id, $text, $this->tokenLine ) = $token;
				$this->debug[] = array( $text, $id );
			}

			if ( $throwEndTag && $id === T_CLOSE_TAG ) {
				// PHP Parse error:  syntax error, unexpected '$end'
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '$end' ), $this->tokenLine, $this->place );
			} elseif ( $id != T_COMMENT && $id != T_DOC_COMMENT && $id != T_WHITESPACE ) {
				break;
			} else {
				$this->tokenLine += preg_match_all( '#\n#', $text );
			}
		}
		if ( $token === false ) {
			// PHP Parse error:  syntax error, unexpected '$end'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '$end' ), $this->tokenLine, $this->place );
		}

		$this->id = $id;
		$this->text = $text;
	}

	/**
	 *
	 */
	private function stack_push_memory() {
		$this->stackMemory[] = $this->stack;
		$this->stack = array();
	}

	/**
	 *
	 */
	private function stack_pop_memory() {
		if ( $this->stackMemory ) {
			$this->stack = array_pop( $this->stackMemory );
//		} else {
//			throw new Exception;  // @todo
		}
	}

	/**
	 * @param bool|array $owner
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private function & stepValue( $owner = false ) {
		$result = false;

		$id = $this->id;
		$text = $this->text;
		switch ( $id ) {
			case T_LNUMBER:
			case T_ONUMBER:
			case T_NUM_STRING:
				if ( isset($text[0]) && $text[0] == 0 ) {
					if ( isset($text[1]) && ($text[1] == 'x' || $text[1] == 'X') ) {
						$tmp = intval( $text, 16 );
					} else {
						$tmp = intval( $text, 8 );
					}
				} else {
					$tmp = $text + 0;
				}
				$result = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>$tmp, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
				break;
			case T_DNUMBER:
				$tmp = $text + 0;
				$result = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>$tmp, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
				break;
			case T_CONSTANT_ENCAPSED_STRING:
				if ( $text[0] === '\'' ) {
					static $pattern_apostrophe = array(
						'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\\'/', # (\\)*\'
						'/\\\\\\\\/', #							\\
					);
					static $replacement_apostrophe = array( '$1\'', '\\' );
					$tmp = preg_replace( $pattern_apostrophe, $replacement_apostrophe, substr($text, 1, -1) );
					$result = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>$tmp, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
					break; // ************** EXIT **************
				}
				$text = substr($text, 1, -1);
				// break is not necessary here
			case T_ENCAPSED_AND_WHITESPACE:
				static $pattern = array(
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\"/', # (\\)*\"
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\n/', # (\\)*\n
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\r/', # (\\)*\r
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\t/', # (\\)*\t
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\v/', # (\\)*\v
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\\$/', # (\\)*\$
					'/\\\\\\\\/', #						  \\
				);
				static $replacement = array( '$1"', "$1\n", "$1\r", "$1\t", "$1\v", '$1$', '\\' );
				$tmp = preg_replace( $pattern, $replacement, $text );
				$result = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>$tmp, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
				break;
			case T_STRING:
				if( strcasecmp($text, 'true') == 0 ) { // $id here must be T_STRING
					$tmp = true;
				} elseif( strcasecmp($text, 'false') == 0 ) {
					$tmp = false;
				} elseif( strcasecmp($text, 'null') == 0 ) {
					$tmp = null;
				} else { // constant, function, etc...

					$this->stepUP();

					$result = array( // define blank hook as the constant
						Runtime::B_COMMAND => Runtime::T_HOOK,
						Runtime::B_HOOK_TYPE => Runtime::H_GET_CONSTANT,
						Runtime::B_METHOD => $text,  // function or method name
						Runtime::B_METHOD_KEY => strtolower( $text ),
						Runtime::B_PARAM_2 => false, // &$functionParameters
						Runtime::B_OBJECT => false, // false or &object
						Runtime::B_OBJECT_KEY => false,
						Runtime::B_RESULT => null,
						Runtime::B_TOKEN_LINE => $this->tokenLine,
						Runtime::B_DEBUG => $text,
					);

					if ( $this->id === '(' ) { // it is function or method
						$this->stepFunction( $result, array(Runtime::B_COMMAND=>false, Runtime::B_RESULT=>$text), $owner );
					} elseif ( $owner !== false ) { // it is an objects property. Example: it's 'bar' for FOO::bar
						$result[Runtime::B_HOOK_TYPE] = $owner[1] === true ? Runtime::H_GET_OBJECT_CONSTANT : Runtime::H_GET_OBJECT_PROPERTY;
						$this->addValueIntoStack( $owner[0], $result, Runtime::B_OBJECT );
						$result[Runtime::B_OBJECT_KEY] = $owner[0][Runtime::B_RESULT] ? strtolower( $owner[0][Runtime::B_RESULT] ) : null;
					} elseif ( $this->id === T_DOUBLE_COLON ) { // it is static constant or method of an object. Examples: FOO::property or FOO::method()
						$result[Runtime::B_COMMAND] = false;
						$result[Runtime::B_RESULT] = $text;

						$result = & $this->stepMethodChaining( $result, true );
					}

					return $result;
				}
				$result = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>$tmp, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
				break;
			case T_START_HEREDOC:
				if( substr( $text, 3, 1 ) === '\'' ) { // heredoc
					$this->stepUP();
					if ( $this->id !== T_ENCAPSED_AND_WHITESPACE ) {
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "T_ENCAPSED_AND_WHITESPACE" ), $this->tokenLine, $this->place );
					}
					$result = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>$this->text, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
					$this->stepUP();
					if ( $this->id !== T_END_HEREDOC ) {
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "T_ENCAPSED_AND_WHITESPACE" ), $this->tokenLine, $this->place );
					}
					break;
				} // nowdoc
				// break is not necessary here
			case '"':
				$this->stepUP();
				$strings = array();
				$i = 0;
				while ( $this->id !== '"' ) {
					if ( $this->id === T_CURLY_OPEN || $this->id === '}' ) {
						$this->stepUP();
					} else {
						$val =& $this->stepValue();
						if ( $val ) { // echo "abcd$foo
							$strings[$i] = null;
							$this->addValueIntoStack( $val, $strings, $i );
						} else if ( $this->id === T_END_HEREDOC ) {
							break;
						} else {
							// PHP Parse error:  syntax error, unexpected $id, expecting '"'
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'\"'" ), $this->tokenLine, $this->place );
						}
						$i++;
					}
				}
				$result = array( Runtime::B_COMMAND=>Runtime::T_QUOTE, Runtime::B_PARAM_1=>&$strings, Runtime::B_RESULT=>null, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
				break;
			case T_VARIABLE:
				$cannotRead = false;

				$variable = array( Runtime::B_COMMAND=>Runtime::T_VARIABLE, Runtime::B_PARAM_1=>substr($text, 1), Runtime::B_PARAM_2=>null, Runtime::B_RESULT=>null, Runtime::B_TOKEN_LINE=>$this->tokenLine, Runtime::B_DEBUG=>$text );
				$this->stepUP();

checkOperators:
				if ( $this->id === '(' ) { // it is function
					$variable =& $this->stepFunctionFromVariable( $variable, $text, $owner );
				} elseif ( $this->id === '[' ) { // There is array index
					$variable[Runtime::B_ARRAY_INDEX] = array();
					$i = 0;
					do { // Example: $foo[
						$this->stepUP();
						$indexVal =& $this->getNextValue();
						if ( $this->id != ']' ) { // Example: $foo[1] or $foo[]
							// PHP Parse error:  syntax error, unexpected $id, expecting ']'
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "']'" ), $this->tokenLine, $this->place );
						}
						if ( $indexVal ) { // Example: $foo[1]
							$variable[Runtime::B_ARRAY_INDEX][$i] = null;
							$this->addValueIntoStack( $indexVal, $variable[Runtime::B_ARRAY_INDEX], $i );
						} else { // Example: $foo[]
							$variable[Runtime::B_ARRAY_INDEX][$i] = INF;
							$cannotRead = true;
						}
						$this->stepUP();
						$i++;
					} while ( $this->id === '[' );
				} // There is not array index

				$id = $this->id;
				$text = $this->text;
				if ( in_array( $id, self::$assignmentOperators ) ) { // It is assignment operator
					$this->stepUP();
					$val =& $this->getNextValue( '=' );
					if ( $val == false ) { // Example: $foo=;
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					if ( $owner === false && $variable[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) { // $foo = <value> or $foo += <value>
						$return = array(
							Runtime::B_COMMAND => self::$runtimeOperators[$id],
							Runtime::B_PARAM_1 => $variable,
							Runtime::B_PARAM_2 => null,
							Runtime::B_RESULT => null,
							Runtime::B_TOKEN_LINE => $this->tokenLine,
							Runtime::B_DEBUG => $text,
							Runtime::B_FLAGS => ($cannotRead === true && $id !== '=') ? Runtime::F_DONT_CHECK_PARAM1 : 0,
						);
						$this->addValueIntoStack( $val, $return, Runtime::B_PARAM_2 );
						return $return; // *********** EXIT ***********
					} elseif ( $owner === false ) {
						// $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_HOOK
						switch ( $variable[Runtime::B_HOOK_TYPE] ) {
							case Runtime::H_GET_OBJECT_PROPERTY: // Example: $foo = new FOO(); $foo->bar =
								$variable[Runtime::B_HOOK_TYPE] = Runtime::H_SET_OBJECT_PROPERTY;
								break;
							case Runtime::H_GET_STATIC_PROPERTY: // Example: $foo = new FOO(); $foo::$bar =
								$variable[Runtime::B_HOOK_TYPE] = Runtime::H_SET_STATIC_PROPERTY;
								break;
							default :  // Example: FOO->$bar() =
								// PHP Parse error:  syntax error, unexpected $id
								throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
						}
						$this->addValueIntoStack( $val, $variable, Runtime::B_PARAM_2 );
						return $variable; // *********** EXIT ***********
					} else {
						$return = array( // define hook
							Runtime::B_COMMAND => Runtime::T_HOOK,
							Runtime::B_HOOK_TYPE => $owner[1] === true ? Runtime::H_SET_STATIC_PROPERTY : Runtime::H_SET_OBJECT_PROPERTY,
							Runtime::B_METHOD => $owner[1] === true ? $variable[Runtime::B_PARAM_1] : false,  // function or method
							Runtime::B_METHOD_KEY => null,
							Runtime::B_PARAM_2 => false, // &$functionParameters
							Runtime::B_OBJECT => false, // false or &object
							Runtime::B_OBJECT_KEY => false,
							Runtime::B_RESULT => null,
							Runtime::B_TOKEN_LINE => $this->tokenLine,
							Runtime::B_DEBUG => $text,
						);
						if ( $owner[1] !== true ) { // Property name as variable. Example: $foo = new FOO(); $bar='anyproperty'; $foo->$bar =
							$this->addValueIntoStack( $variable, $return, Runtime::B_METHOD ); // property name
						}
						$this->addValueIntoStack( $val, $return, Runtime::B_PARAM_2 ); // value
						$this->addValueIntoStack( $owner[0], $return, Runtime::B_OBJECT ); // object
						$return[Runtime::B_OBJECT_KEY] = $owner[0][Runtime::B_RESULT] ? strtolower( $owner[0][Runtime::B_RESULT] ) : null;
						return $return;
					}
				} elseif ( $id === T_INC || $id === T_DEC ) {
					if ( $variable[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) {
						$variable = array(
							Runtime::B_COMMAND => self::$runtimeOperators[$id],
							Runtime::B_PARAM_1 => $variable,
							Runtime::B_PARAM_2 => true, // Example: $foo++
							Runtime::B_RESULT => null,
							Runtime::B_TOKEN_LINE => $this->tokenLine,
							Runtime::B_DEBUG => $text,
						);
						$this->stepUP();
					} else { // $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_HOOK Example: FOO->$bar()++
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
				} elseif ( $cannotRead ) { // Example: echo $foo[];
					if ( $owner !== T_LIST ) {
						// PHP Fatal error:  Cannot use [] for reading
						throw new PhpTagsException( PhpTagsException::FATAL_CANNOT_USE_FOR_READING, null, $this->tokenLine, $this->place );
					}
				} elseif ( $owner !== false && $owner !== T_LIST ) {
					if ( $variable[Runtime::B_COMMAND] === Runtime::T_HOOK ) { // Example: $bar = 'anymethod'; echo $foo->$bar();
						return $variable;
					} // Example: $bar = 'anyproperty'; echo $foo->$bar
					$return = array( // define hook
						Runtime::B_COMMAND => Runtime::T_HOOK,
						Runtime::B_HOOK_TYPE => $owner[1] === true ? Runtime::H_GET_STATIC_PROPERTY : Runtime::H_GET_OBJECT_PROPERTY,
						Runtime::B_METHOD => $owner[1] === true ? $variable[Runtime::B_PARAM_1] : false,  // function or method
						Runtime::B_METHOD_KEY => null,
						Runtime::B_PARAM_2 => false, // &$functionParameters
						Runtime::B_OBJECT => false, // false or &object
						Runtime::B_OBJECT_KEY => false,
						Runtime::B_RESULT => null,
						Runtime::B_TOKEN_LINE => $this->tokenLine,
						Runtime::B_DEBUG => $text,
					);
					if ( $owner[1] !== true ) { // Property name as variable. Example: $foo = new FOO(); $bar='anyproperty'; echo $foo->$bar;
						$this->addValueIntoStack( $variable, $return, Runtime::B_METHOD ); // property name
					}
					$this->addValueIntoStack( $owner[0], $return, Runtime::B_OBJECT ); // object
					$return[Runtime::B_OBJECT_KEY] = $owner[0][Runtime::B_RESULT] ? strtolower( $owner[0][Runtime::B_RESULT] ) : null;

					$id = $this->id;
					if ( $id === T_OBJECT_OPERATOR || $id === T_DOUBLE_COLON ) {
						$return =& $this->stepMethodChaining( $return, $id === T_DOUBLE_COLON );
					}
					return $return;
				} elseif ( $id === T_OBJECT_OPERATOR || $id === T_DOUBLE_COLON ) { // Example: $foo->
					$this->stepUP();
					$variable =& $this->stepValue( array(&$variable, $id == T_DOUBLE_COLON) );
					if ( $variable == false || $variable[Runtime::B_COMMAND] !== Runtime::T_HOOK ) { // Example: $foo->;
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}

					$id = $this->id;
					if ( $id === T_OBJECT_OPERATOR || $id === T_DOUBLE_COLON ) {
						$variable =& $this->stepMethodChaining( $variable, $id === T_DOUBLE_COLON );
					}
					goto checkOperators;
				}
				return $variable; // *********** EXIT ***********
			case T_INC:
			case T_DEC:
				$this->stepUP();
				if ( $this->id === T_VARIABLE ) {
					$variable = $this->stepValue();
					//$value[PHPTAGS_STACK_RESULT] =& $variable;
					//$value[PHPTAGS_STACK_AIM] = ;
					//$variable[PHPTAGS_STACK_AIM]
					$result = array(
						Runtime::B_COMMAND => self::$runtimeOperators[$id],
						Runtime::B_PARAM_1 => $variable,
						Runtime::B_PARAM_2 => false, // Example: ++$foo
						Runtime::B_RESULT => null,
						Runtime::B_TOKEN_LINE => $this->tokenLine,
						Runtime::B_DEBUG => $text,
					);
				} else {
					// PHP Parse error:  syntax error, unexpected $id, expecting 'T_VARIABLE'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, 'T_VARIABLE' ), $this->tokenLine, $this->place );
				}
				return $result; // *********** EXIT ***********
			case '(':
				$this->stepUP();
				$result =& $this->getNextValue();
				if ( $this->id !== ')' ) {
					// PHP Parse error:  syntax error, unexpected $tmp_id, expecting ')'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
				}
				break;
			case '+': // The left operators
			case '-':
			case '~':
			case '!':
			case T_ARRAY_CAST:	// (array)
			case T_INT_CAST:	// (int)
			case T_DOUBLE_CAST:	// (double)
			case T_STRING_CAST:	// (string)
			case T_BOOL_CAST:	// (bool)
			case T_UNSET_CAST:	// (unset)
				$this->stepUP();
				$tmp =& $this->stepValue();
				if ( $tmp ) {
					$result = array(
						Runtime::B_COMMAND => self::$runtimeOperators[$id],
						Runtime::B_RESULT => null,
						Runtime::B_PARAM_2 => null,
						Runtime::B_PARAM_1 => 0,
						Runtime::B_TOKEN_LINE => $this->tokenLine,
						Runtime::B_DEBUG => $text,
					);
					$this->addValueIntoStack( $tmp, $result, Runtime::B_PARAM_2, true );
				}
				return $result;
			case T_ARRAY:
				$this->stepUP();
				if ( $this->id !== '(' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting '('
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
				}
				// break is not necessary here
			case '[':
				$this->stepUP();
				$result =& $this->stepArrayConstruct( $id );
				break;
			case T_LIST:
				$this->stepUP();
				if ( $this->id !== '(' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting '('
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
				}
				$param = array();
				$i = -1;
				do {
					$this->stepUP();
					if ( $this->id === T_LIST ) { // T_LIST inside T_LIST. Example: list( $foo, list
						$value =& $this->stepValue( T_LIST );
					} else {
						$value =& $this->getNextValue( ',', T_LIST );
						if ( $value === false ) { // Example: list($foo, ,
							$value = null;
						} elseif ( $value[Runtime::B_COMMAND] !== Runtime::T_VARIABLE && $value[Runtime::B_COMMAND] !== Runtime::T_LIST ) { // Example: unset( $foo+1 );
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[Runtime::B_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
						}
					}
					$tmp = array( Runtime::B_COMMAND=>null, Runtime::B_RESULT=>&$value );
					$param[++$i] = null;
					$this->addValueIntoStack( $tmp, $param, $i );
					unset( $value, $tmp );
				} while ( $this->id === ',' );
				if ( $this->id != ')' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting ')'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
				}
				$result = array(
					Runtime::B_COMMAND => Runtime::T_LIST,
					Runtime::B_PARAM_1 => &$param,
					Runtime::B_PARAM_2 => null,
					Runtime::B_RESULT => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);
				if ( $owner !== T_LIST ) {
					$this->stepUP();
					if ( $this->id != '=' ) { // It is not assignment operator
						// PHP Parse error:  syntax error, unexpected $id, expecting '='
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'='" ), $this->tokenLine, $this->place );
					}
					$this->stepUP();
					$val =& $this->getNextValue( '=' );
					if ( $val == false ) {
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					$this->addValueIntoStack( $val, $result, Runtime::B_PARAM_2 );
					return $result; // *********** EXIT ***********
				}
				break;
			case T_PRINT:
				$this->stepUP();
				$value =& $this->getNextValue();
				if ( $this->id != ';' ) { // Example print "foo"%
					// PHP Parse error:  syntax error, unexpected $id, expecting ';'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "';'" ), $this->tokenLine, $this->place );
				}
				if( $value === false ) { // Example: print ;
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				$result = array(
					Runtime::B_COMMAND => Runtime::T_PRINT,
					Runtime::B_PARAM_1 => null,
					Runtime::B_RESULT => 1,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text
				);
				$this->addValueIntoStack( $value, $result, Runtime::B_PARAM_1 );
				return $result;
			case T_EMPTY:
			case T_ISSET:
			case T_UNSET:
				$this->stepUP();
				if ( $this->id !== '(' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting '('
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
				}
				$param = array();
				do {
					$this->stepUP();
					$value =& $this->getNextValue();
					if ( $value === false ) { // Example: unset();
						// PHP Parse error:  syntax error, unexpected $id,
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					if ( $value[Runtime::B_COMMAND] !== Runtime::T_VARIABLE ) { // Example: unset( $foo+1 );
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[Runtime::B_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
					}
					$param[] =& $value;
				} while ( $this->id === ',' );
				if ( $this->id !== ')' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting ')'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
				}
				$result = array(
					Runtime::B_COMMAND => self::$runtimeOperators[$id],
					Runtime::B_PARAM_1 => $param,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);
				break;
			case T_NEW:
				$result = array(
					Runtime::B_COMMAND => self::$runtimeOperators[$id],
					Runtime::B_HOOK_TYPE => Runtime::H_OBJECT_METHOD,
					Runtime::B_PARAM_2 => null,
					Runtime::B_OBJECT => null, // object name
					Runtime::B_OBJECT_KEY => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);
				$this->stepUP();

				if ( $this->id === T_STRING ) {
					$result[Runtime::B_OBJECT] = $this->text;
					$result[Runtime::B_OBJECT_KEY] = strtolower( $this->text );
					$this->stepUP();
				} elseif ( $this->id === T_VARIABLE ) {
					$value =& $this->stepValue();
					$this->addValueIntoStack( $value, $result, Runtime::B_OBJECT, false ); // $result[Runtime::B_OBJECT_KEY] is NULL
				} else {
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array($this->id), $this->tokenLine, $this->place );
				}
				if ( $this->id === '(' ) { // it has parameters
					$this->stepUP();
					$objectParameters =& $this->getFunctionParameters(
							array( Runtime::B_COMMAND => false, Runtime::B_RESULT => 1 ),
							array( &$result )
						);
					if ( $this->id !== ')' ) {
						// PHP Parse error:  syntax error, unexpected $tmp_id, expecting ')'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
					}
					$this->stepUP();
					$result[Runtime::B_PARAM_2] =& $objectParameters;
				} else {
					$result[Runtime::B_PARAM_2] = null;
				}
				return $result;
			case '@': // Error Control Operator
				$this->stepUP();
				if ( $this->ignoreErrors === false ) {
					$this->stack[] = array( Runtime::B_COMMAND => Runtime::T_IGNORE_ERROR, Runtime::B_PARAM_1 => true );
					$this->ignoreErrors = true;
				}
				$result =& $this->stepValue( $owner );
				$this->ignoreErrors = null;
				return $result;
			case T_END_HEREDOC:
				return $result; // false
		}
		if ( $result !== false ) {
			$this->stepUP();
		}
		return $result;
	}

	/**
	 * @param $value
	 * @param $precedence
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private function & getOperator( &$value, $precedence ) {
		$result = false;
		while ( true ) {
			$id = $this->id;
			$text = $this->text;
			if ( array_key_exists($id, self::$precedencesMatrix) && self::$precedencesMatrix[$id] < $precedence ) { // @todo isset()
				// if the current token is an operator and it precedence is less the variable $precedence
				// else leave loop

				// Ternary operators handled function getTernaryOperator()
				$ternary =& $this->getTernaryOperator( $value );
				if ( $ternary !== false ) {
					$value =& $ternary;
					$result =& $ternary;
					continue;
				}

				$this->stepUP();

				// Make the operator
				$operator = array(
					Runtime::B_COMMAND => self::$runtimeOperators[$id],
					Runtime::B_PARAM_1 => null, //&$value[PHPTAGS_STACK_RESULT],
					Runtime::B_PARAM_2 => null, //&$nextValue[PHPTAGS_STACK_RESULT],
					Runtime::B_RESULT => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
					Runtime::B_FLAGS => 0,
				);
				$didit = $this->addValueIntoStack( $value, $operator, Runtime::B_PARAM_1, false ); // Add the first value into the stack

				$nextValue =& $this->getNextValue( $id ); // Get the next value, it is the second value for the operator
				// $nextValue can be as the result of other operators if them the precedence larger the precedence of the current operator
				// Example: 1*2+3; $nextValue will be '2'
				// Example: 1+2*3; $nextValue will be the result of the operator '2*3'
				if ( $nextValue === false ) { // $nextValue must be not false, throw the exception
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				$this->addValueIntoStack( $nextValue, $operator, Runtime::B_PARAM_2, $didit ); // Add the second value into the stack
				$value =& $operator; // Set the operator as the value for the next loop
				$result =& $operator;
				unset( $operator );
			} else {
				break;
			}
		}
		return $result;
	}

	/**
	 * @param $value
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private function & getTernaryOperator( &$value ) {
		static $ternaryOperators = array();
		$result = false;
		$id = $this->id;
		$text = $this->text;
		switch ( $id ) {
			case '?':
				// Make the operator without the second value
				$ternary = array(
					Runtime::B_COMMAND => Runtime::T_TERNARY,
					Runtime::B_PARAM_1 => null,
					Runtime::B_PARAM_2 => null,
					Runtime::B_RESULT => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);
				array_unshift( $ternaryOperators, $ternary );
				$didValue = $this->addValueIntoStack( $value, $ternaryOperators[0], Runtime::B_PARAM_1, false );
				if ( $didValue && $ternaryOperators[0][Runtime::B_PARAM_1] === null ) {
					$ternaryOperators[0][Runtime::B_PARAM_1] = false;
				}

				$this->stepUP();
				$this->stack_push_memory();
				$result =& $this->getNextValue(); // Get next value, it must be the ternary operator
				if ( $result !== false || $this->id != ':' ) {
					break;
				}
				// Example: $foo ?:
				$value_clone = $value;
				unset( $value );
				$value = $value_clone;
				$id = $this->id;
				// break is not necessary here
			case ':':
				if ( !isset($ternaryOperators[0]) ) {
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $id ), $this->tokenLine, $this->place );
				}

				$ternaryOperators[0][Runtime::B_PARAM_2] = array(
					Runtime::B_RESULT => null,
					Runtime::B_PARAM_1 => null,
					Runtime::B_PARAM_2 => null,
				);
				if ( $value[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) {
					$copy1 = array( Runtime::B_COMMAND=>Runtime::T_COPY, Runtime::B_PARAM_1=>null, Runtime::B_RESULT=>&$ternaryOperators[0][Runtime::B_PARAM_2][Runtime::B_PARAM_1] );
					$this->addValueIntoStack( $value, $copy1, Runtime::B_PARAM_1, false );
					$this->stack[] =& $copy1;
				} else {
					$this->addValueIntoStack( $value, $ternaryOperators[0][Runtime::B_PARAM_2], Runtime::B_PARAM_1, false );
				}

				if ( $this->stack ) {
					$stack_true = $this->stack;
					$this->stack = array();
				} else {
					$stack_true = false;
				}

				$this->stepUP();
				$nextValue =& $this->getNextValue( ':' ); // Get the next value, it is the right part of the ternary operator
				if ( $nextValue === false ) { // $nextValue must be not false, otherwise throw the exception
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				if ( $nextValue[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) {
					$copy2 = array( Runtime::B_COMMAND=>Runtime::T_COPY, Runtime::B_PARAM_1=>null, Runtime::B_RESULT=>&$ternaryOperators[0][Runtime::B_PARAM_2][Runtime::B_PARAM_2] );
					$this->addValueIntoStack( $nextValue, $copy2, Runtime::B_PARAM_1, false );
					$this->stack[] =& $copy2;
				} else {
					$this->addValueIntoStack( $nextValue, $ternaryOperators[0][Runtime::B_PARAM_2], Runtime::B_PARAM_2, false );
				}
				$stack_false = $this->stack ?: false;
				$this->stack_pop_memory();

				if ( $ternaryOperators[0][Runtime::B_PARAM_1] == true ) { // Example: echo true ? ...
					$result = array( Runtime::B_COMMAND=>false, Runtime::B_RESULT=>&$ternaryOperators[0][Runtime::B_PARAM_2][Runtime::B_PARAM_1] );
					if ( $stack_true !== false ) {
						$this->stack = array_merge( $this->stack, $stack_true );
					}
				} elseif ( $ternaryOperators[0][Runtime::B_PARAM_1] !== null && $ternaryOperators[0][Runtime::B_PARAM_1] == false ) { // Example: echo false ? ...
					$result = array( Runtime::B_COMMAND=>false, Runtime::B_RESULT=>&$ternaryOperators[0][Runtime::B_PARAM_2][Runtime::B_PARAM_2] );
					if ( $stack_false !== false ) {
						$this->stack = array_merge( $this->stack, $stack_false );
					}
				} else { // It is not static value, Example: echo $foo ? ...
					$ternaryOperators[0][Runtime::B_PARAM_2][Runtime::B_DO_TRUE] = $stack_true;
					$ternaryOperators[0][Runtime::B_PARAM_2][Runtime::B_DO_FALSE] = $stack_false;
					$result =& $ternaryOperators[0];
				}
				array_shift( $ternaryOperators );
				break;
			case T_BOOLEAN_AND:	// &&
			case T_LOGICAL_AND:	// and
				$this->stepUP();
				$this->stack_push_memory();
				$nextValue =& $this->getNextValue( $id );
				if ( $nextValue === false ) {
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}

				$result = array(
					Runtime::B_COMMAND => Runtime::T_TERNARY,
					Runtime::B_PARAM_1 => null,
					Runtime::B_PARAM_2 => array(Runtime::B_DO_FALSE=>false, Runtime::B_PARAM_2=>false),
					Runtime::B_RESULT => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);

				$operator = array(
					Runtime::B_COMMAND => Runtime::T_BOOL_CAST,
					Runtime::B_PARAM_2 => null,
					Runtime::B_RESULT => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);

				$this->addValueIntoStack( $nextValue, $operator, Runtime::B_PARAM_2, true );
				$stack_true = $this->stack ?: false;
				$this->stack_pop_memory();
				$done = $this->addValueIntoStack( $value, $result, Runtime::B_PARAM_1, true );

				if ( $stack_true === false && $operator[Runtime::B_PARAM_2] == false || $done === true && $operator[Runtime::B_PARAM_1] == false ) {
					$result = array( Runtime::B_COMMAND => null,	Runtime::B_RESULT => false ); // it's always false
					break;
				}
				if ( $stack_true === false && $operator[Runtime::B_PARAM_2] == true && $done === true && $operator[Runtime::B_PARAM_1] == true ) {
					$result = array( Runtime::B_COMMAND => null,	Runtime::B_RESULT => true ); // it's always true
					break;
				}

				if ( $stack_true === false ) {
					$stack_true = array( &$operator );
				} else {
					$stack_true[] =& $operator;
				}

				$result[Runtime::B_PARAM_2][Runtime::B_DO_TRUE] = $stack_true;
				$result[Runtime::B_PARAM_2][Runtime::B_PARAM_1] =& $operator[Runtime::B_RESULT];
				break;
			case T_BOOLEAN_OR:	// ||
			case T_LOGICAL_OR:	// or
				$this->stepUP();
				$this->stack_push_memory();
				$nextValue =& $this->getNextValue( $id );
				if ( $nextValue === false ) {
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}

				$result = array(
					Runtime::B_COMMAND => Runtime::T_TERNARY,
					Runtime::B_PARAM_1 => null,
					Runtime::B_PARAM_2 => array(Runtime::B_DO_TRUE=>false, Runtime::B_PARAM_1=>true),
					Runtime::B_RESULT => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);

				$operator = array(
					Runtime::B_COMMAND => Runtime::T_BOOL_CAST,
					Runtime::B_PARAM_2 => null,
					Runtime::B_RESULT => null,
					Runtime::B_TOKEN_LINE => $this->tokenLine,
					Runtime::B_DEBUG => $text,
				);

				$this->addValueIntoStack( $nextValue, $operator, Runtime::B_PARAM_2, true );
				$stack_false = $this->stack ?: false;
				$this->stack_pop_memory();
				$done = $this->addValueIntoStack( $value, $result, Runtime::B_PARAM_1, true );

				if ( $stack_false === false && $operator[Runtime::B_PARAM_2] == true || $done === true && $operator[Runtime::B_PARAM_1] == true ) {
					$result = array( Runtime::B_COMMAND => null,	Runtime::B_RESULT => true ); // it's always true
					break;
				}
				if ( $stack_false === false && $operator[Runtime::B_PARAM_2] == false && $done === true && $operator[Runtime::B_PARAM_1] == false ) {
					$result = array( Runtime::B_COMMAND => null,	Runtime::B_RESULT => false ); // it's always false
					break;
				}

				if ( $stack_false === false ) {
					$stack_false = array( &$operator );
				} else {
					$stack_false[] =& $operator;
				}

				$result[Runtime::B_PARAM_2][Runtime::B_DO_FALSE] = $stack_false;
				$result[Runtime::B_PARAM_2][Runtime::B_PARAM_2] =& $operator[Runtime::B_RESULT];
				break;
		}
		return $result;
	}

	/**
	 * @param bool $allowElse
	 * @param $throwEndTag
	 * @param bool $isDoWhile
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private function & stepIfConstruct( $allowElse, $throwEndTag, $isDoWhile = false ) {
		$return = false;
		$text = $this->text; // if
		$tokenLine = $this->tokenLine;
		$this->stepUP();

		if ( $this->id != '(' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting '('
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		$value =& $this->getNextValue();
		if ( $value === false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $this->id !== ')' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting ')'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		// Make the 'if' operator
		$if = array(
			Runtime::B_COMMAND => Runtime::T_IF,
			Runtime::B_PARAM_1 => null,
			Runtime::B_RESULT => null,
			Runtime::B_TOKEN_LINE => $tokenLine,
			Runtime::B_DEBUG => $text,
		);
		$this->addValueIntoStack( $value, $if, Runtime::B_PARAM_1, false );

		if ( $isDoWhile === true ) {
			return $if;
		}

		$this->stack_push_memory();
		if ( $this->id === '{' ) {
			$this->stepUP();
			$this->stepBlockOperators( '}' );
			$this->stepUP( $throwEndTag );
		} else {
			$this->stepFirstOperator( $throwEndTag ) ||	$this->stepFirsValue( $throwEndTag );
		}
		if ( $this->stack ) {
			$if[Runtime::B_DO_TRUE] = $this->stack;
			$this->stack = array();
		}

		if ( $allowElse ) {
			if ( $this->id === T_ELSE ) {
				$this->stepUP();
				if ( $this->id === '{' ) {
					$this->stepUP();
					$this->stepBlockOperators( '}' );
					$this->stepUP( $throwEndTag );
				} else {
					$this->stepFirstOperator( $throwEndTag ) ||	$this->stepFirsValue( $throwEndTag );
				}
				if ( $this->stack ) {
					$if[Runtime::B_DO_FALSE] = $this->stack;
				}
			} elseif ( $this->id === T_ELSEIF ) {
				$tmp =& $this->stepIfConstruct( true, $throwEndTag );
				if ( $tmp !== true  && $tmp !== false ) {
					$this->stack[] =& $tmp;
					$if[Runtime::B_DO_FALSE] = $this->stack;
				} elseif ( $this->stack ) {
					$if[Runtime::B_DO_FALSE] = $this->stack;
				}
			}
		}

		$this->stack_pop_memory();
		if ( $if[Runtime::B_PARAM_1] == true ) {
			if ( $if[Runtime::B_DO_TRUE] ) {
				$this->stack = array_merge( $this->stack, $if[Runtime::B_DO_TRUE] );
			}
			$return = true;
		} elseif ( $if[Runtime::B_PARAM_1] !== null && $if[Runtime::B_PARAM_1] == false ) {
			if ( isset($if[Runtime::B_DO_FALSE]) && $if[Runtime::B_DO_FALSE] ) {
				$this->stack = array_merge( $this->stack, $if[Runtime::B_DO_FALSE] );
			}
			// $return = false; it is already false
		} else {
			$return =& $if;
		}
		return $return;
	}

	/**
	 * @param $startToken
	 * @return array
	 * @throws PhpTagsException
	 */
	private function & stepArrayConstruct( $startToken ) {
		$key = false;
		$result = false;
		$endToken = $startToken === '[' ? ']' : ')';
		$array = array();
		$i = 0;
		$r = 0;
		while ( $value =& $this->getNextValue() ) {
			switch ( $this->id ) {
				case ',':
				case $endToken:
					if ( $key === false ) {
						if ( $value[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) {
							$array[$i] = null;
							$copy = array( Runtime::B_COMMAND=>Runtime::T_COPY, Runtime::B_PARAM_1=>null, Runtime::B_RESULT=>&$array[$i] );
							$this->addValueIntoStack( $value, $copy, Runtime::B_PARAM_1, false );
							$this->stack[] =& $copy;
							unset( $copy );
						} else {
							$this->addValueIntoStack( $value, $array, $i );
						}
						++$i;
					} else {
						if ( $result === false && $key[Runtime::B_COMMAND] === null ) {
							$this->addValueIntoStack( $value, $array, $key[Runtime::B_RESULT] );
						} else {
							if ( $result === false ) {
								$result = array(
									Runtime::B_COMMAND => Runtime::T_ARRAY,
									Runtime::B_PARAM_1 => array( &$array ) ,
									Runtime::B_PARAM_2 => array( array(null, null) ),
									Runtime::B_RESULT => null,
									Runtime::B_TOKEN_LINE => $this->tokenLine,
								);
							} else {
								$result[Runtime::B_PARAM_1][$r] = &$array;
								$result[Runtime::B_PARAM_2][$r] = array( null, null );
							}
							$this->addValueIntoStack( $key, $result[Runtime::B_PARAM_2][$r], 0 );
							$this->addValueIntoStack( $value, $result[Runtime::B_PARAM_2][$r], 1 );
							++$r;
							unset( $array );
							$array = array();
						}
						unset( $key );
						$key = false;
					}
					if ( $this->id === $endToken ) {
						break 2;
					}
					break;
				case T_DOUBLE_ARROW:
					if ( $key !== false ) {
						// PHP Parse error:  syntax error, unexpected $id, expecting ',' or ')'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "'$endToken'" ), $this->tokenLine, $this->place );
					}
					$key = &$value;
					unset( $value );
					if ( $key[Runtime::B_COMMAND] ) {
						$this->stack[] = &$key; // Add the command for receive value into the stack
					}
					break;
			}
			$this->stepUP();
		}
		if ( $key !== false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $result === false ) { // It is simple array and can be compiled, example: $foo = array( 1, 2, 3 );
			$result = array( Runtime::B_COMMAND=>false, Runtime::B_RESULT=>&$array );
		} elseif ( $array ) {
			$result[Runtime::B_PARAM_1][] =& $array;
		}
		return $result;
	}

	/**
	 * @param $throwEndTag
	 * @return bool
	 * @throws PhpTagsException
	 */
	private function stepDoConstruct( $throwEndTag ) {
		$this->stack_push_memory();
		$this->stepUP();
		if ( $this->id === '{' ) {
			$this->stepUP();
			$this->stepBlockOperators( '}' );
			$this->stepUP( $throwEndTag );
		} else {
			$this->stepFirstOperator( $throwEndTag ) ||	$this->stepFirsValue( $throwEndTag );
		}
		if ( $this->id !== T_WHILE ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting T_WHILE
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, 'T_WHILE' ), $this->tokenLine, $this->place );
		}
		$operator =& $this->stepIfConstruct( false, $throwEndTag, true );
		if ( $this->id !== ';' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting ';'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, '";"' ), $this->tokenLine, $this->place );
		}
		$stack = $this->stack;
		$this->stack_pop_memory();

		if ( $operator[Runtime::B_PARAM_1] != true ) {
			$operator[Runtime::B_DO_FALSE] = array( array(Runtime::B_COMMAND=>Runtime::T_BREAK, Runtime::B_RESULT=>1) );
			$operator[Runtime::B_DO_TRUE] = false;
			$stack[] =& $operator;
			$stack[] = array( Runtime::B_COMMAND=>Runtime::T_CONTINUE, Runtime::B_RESULT=>1 ); // Add operator T_CONTINUE to the end of the cycle
		} elseif ( $operator[Runtime::B_PARAM_1] !== null && $operator[Runtime::B_PARAM_1] == false ) { // Example: do { ... } while ( false );
			$stack[] = array(Runtime::B_COMMAND=>Runtime::T_BREAK, Runtime::B_RESULT=>1);
		}
		$this->stack[] = array( Runtime::B_COMMAND=>Runtime::T_WHILE, Runtime::B_DO_TRUE=>$stack );
		return true;
	}

	/**
	 * @param bool $throwEndTag
	 * @return bool
	 * @throws PhpTagsException
	 */
	private function stepWhileConstruct( $throwEndTag = true ) {
		$this->stack_push_memory();
		$operator =& $this->stepIfConstruct( false, $throwEndTag );
		$stack = $this->stack;
		$this->stack_pop_memory();
		if ( $operator !== false ) {
			if ( $operator !== true ) {
				$stack[] =& $operator;
				$stack = array_merge( $stack, $operator[Runtime::B_DO_TRUE] );
				$operator[Runtime::B_DO_FALSE] = array( array(Runtime::B_COMMAND=>Runtime::T_BREAK, Runtime::B_RESULT=>1) );
				$operator[Runtime::B_DO_TRUE] = false;
			}
			$stack[] = array( Runtime::B_COMMAND=>Runtime::T_CONTINUE, Runtime::B_RESULT=>1 ); // Add operator T_CONTINUE to the end of the cycle

			$this->stack[] = array( Runtime::B_COMMAND=>Runtime::T_WHILE, Runtime::B_DO_TRUE=>$stack );
		}
		return true;
	}

	/**
	 * @param $throwEndTag
	 * @return bool
	 * @throws PhpTagsException
	 */
	private function stepForConstruct( $throwEndTag ) {
		$text = $this->text; // for
		$tokenLine = $this->tokenLine;
		$this->stepUP();

		if ( $this->id !== '(' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting '('
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		// ***** cycle start *****
		if ( $this->id !== ';' ) { // Example: for ( ...
			self::stepForExpression( ';' );
		} // else Example: for ( ;
		$this->stepUP(); // eat ';'

		// ***** cycle condition *****
		$this->stack_push_memory();
		if ( $this->id !== ';' ) { // Example: for ( ; ...
			$value =& $this->getNextValue();
			if ( $value === false || $this->id !== ';' ) {
				// PHP Parse error:  syntax error, unexpected $id, expecting ';'
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "';'" ), $this->tokenLine, $this->place );
			}
			$if = array(
				Runtime::B_COMMAND => Runtime::T_IF,
				Runtime::B_PARAM_1 => null,
				Runtime::B_RESULT => null,
				Runtime::B_DO_FALSE => array( array(Runtime::B_COMMAND=>Runtime::T_BREAK, Runtime::B_RESULT=>1) ),
				Runtime::B_DO_TRUE => false,
			);
			$this->addValueIntoStack( $value, $if, Runtime::B_PARAM_1, false );
			$this->stack[] =& $if;
		} // else Example: for ( ; ;
		$this->stepUP(); // eat ';'

		// ***** cycle end *****
		if ( $this->id !== ')' ) { // Example: for ( ; ; ...
			$this->stack_push_memory();
			self::stepForExpression( ')' );
			$stackEnd = $this->stack; // save cycle end
			$this->stack_pop_memory();
		} else { // Example: for ( ; ; )
			$stackEnd = array();
		}
		$this->stepUP(); // eat ')'

		// ***** cycle body *****
		if ( $this->id === '{' ) {
			$this->stepUP();
			$this->stepBlockOperators( '}' );
			$this->stepUP( $throwEndTag );
		} else {
			$this->stepFirstOperator( $throwEndTag ) ||	$this->stepFirsValue( $throwEndTag );
		}
		$cycleStack = array_merge( $this->stack, $stackEnd );
		$cycleStack[] = array( Runtime::B_COMMAND=>Runtime::T_CONTINUE, Runtime::B_RESULT=>1 ); // Add operator T_CONTINUE to the end of the cycle
		$this->stack_pop_memory();
		$this->stack[] = array( Runtime::B_COMMAND=>Runtime::T_WHILE, Runtime::B_DO_TRUE=>$cycleStack );
		return true;
	}

	/**
	 * @param $end
	 * @throws PhpTagsException
	 */
	private function stepForExpression( $end ) {
		while ( true ) {
			$value =& $this->getNextValue();
			if( !$value ) { // Example: for ( $foo++,;
				// PHP Parse error:  syntax error, unexpected $id
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
			}
			if ( $value[Runtime::B_COMMAND] != false ) {
				$this->stack[] =& $value;
			}
			if ( $this->id === ',' ) {
				$this->stepUP();
				continue;
			}
			if ( $this->id === $end ) {
				break;
			}
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, ',', ';' ), $this->tokenLine, $this->place );
		}
	}

	/**
	 * @param $throwEndTag
	 * @todo
	 */
	private function stepSwitchConstruct( $throwEndTag ) {

	}

	/**
	 * @param bool $throwEndTag
	 * @return bool
	 * @throws PhpTagsException
	 */
	private function stepForeachConstruct( $throwEndTag = true ) {
		$text = $this->text; // foreach
		$tokenLine = $this->tokenLine;
		$this->stepUP();

		if ( $this->id !== '(' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting '('
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		$arrayExpression =& $this->stepValue();
		if ( $arrayExpression === false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $arrayExpression[Runtime::B_COMMAND] !== Runtime::T_VARIABLE && $arrayExpression[Runtime::B_COMMAND] !== Runtime::T_ARRAY ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting T_VARIABLE
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $arrayExpression[Runtime::B_COMMAND], 'T_VARIABLE', 'T_ARRAY' ), $this->tokenLine, $this->place );
		}

		if ( $this->id !== T_AS ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting 'T_AS'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, 'T_AS' ), $this->tokenLine, $this->place );
		}
		$text_as = $this->text;
		$this->stepUP();

		$value =& $this->stepValue();
		if ( $value === false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $value[Runtime::B_COMMAND] !== Runtime::T_VARIABLE ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting T_VARIABLE
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[Runtime::B_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
		}
		if ( isset($value[Runtime::B_ARRAY_INDEX]) ) {
			// PHP Parse error:  syntax error, unexpected '['
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[' ), $this->tokenLine, $this->place );
		}
		$t_as = array(
			Runtime::B_COMMAND => Runtime::T_AS,
			Runtime::B_RESULT => null,
			Runtime::B_PARAM_1 => $value[Runtime::B_PARAM_1], // Variable name
			Runtime::B_PARAM_2 => false,
			Runtime::B_TOKEN_LINE => $this->tokenLine,
			Runtime::B_DEBUG => $text_as,
		);

		if ( $this->id === T_DOUBLE_ARROW ) { // =>
			$this->stepUP();
			$value =& $this->stepValue();
			if ( $value === false ) {
				// PHP Parse error:  syntax error, unexpected $id
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
			}
			if ( $value[Runtime::B_COMMAND] !== Runtime::T_VARIABLE ) {
				// PHP Parse error:  syntax error, unexpected $id, expecting T_VARIABLE
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[Runtime::B_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
			}
			if ( isset($value[Runtime::B_ARRAY_INDEX]) ) {
				// PHP Parse error:  syntax error, unexpected '['
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[' ), $this->tokenLine, $this->place );
			}
			$t_as[Runtime::B_PARAM_2] = $t_as[Runtime::B_PARAM_1];
			$t_as[Runtime::B_PARAM_1] = $value[Runtime::B_PARAM_1]; // Variable name
		}

		if ( $this->id !== ')' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting T_DOUBLE_ARROW or ')'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $arrayExpression[Runtime::B_COMMAND], 'T_DOUBLE_ARROW', "')'" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		$this->stack_push_memory();
		$asExpression = $arrayExpression;
		$this->stack[] =& $t_as;

		if ( $this->id === '{' ) {
			$this->stepUP();
			$this->stepBlockOperators( '}' );
			$this->stepUP( $throwEndTag );
		} else {
			$this->stepFirstOperator( $throwEndTag ) ||	$this->stepFirsValue( $throwEndTag );
		}

		$this->stack[] = array( Runtime::B_COMMAND=>Runtime::T_CONTINUE, Runtime::B_RESULT=>1, Runtime::B_TOKEN_LINE=>$tokenLine ); // Add operator T_CONTINUE to the end of the cycle
		$foreach = array(
			Runtime::B_COMMAND => Runtime::T_FOREACH,
			Runtime::B_PARAM_1 => &$t_as,
			Runtime::B_DO_TRUE => $this->stack,
			Runtime::B_TOKEN_LINE => $tokenLine,
			Runtime::B_DEBUG => $text,
		);
		$this->stack_pop_memory();
		$this->addValueIntoStack( $asExpression, $t_as, Runtime::B_RESULT );
		$this->stack[] =& $foreach;
		return true;
	}

	/**
	 * @param $result
	 * @param $isStatic
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private function & stepMethodChaining( &$result, $isStatic ) {
		do {
			$this->stepUP();
			$val =& $this->stepValue( array(&$result, $isStatic) );
			if ( $isStatic ) { // Static is the first calls only
				$isStatic = false;
			}
			if ( $val == false ) { // Example: FOO::bar-> ;
				// PHP Parse error:  syntax error, unexpected $id
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
			}
			switch ( $val[Runtime::B_COMMAND] ) {
				case Runtime::T_HOOK: // Examples: FOO::bar->too
					$result =& $val;
					break;
				case Runtime::T_VARIABLE: // Example: FOO::bar->$variable
					$tmpresult = array( // define hook as the constant
						Runtime::B_COMMAND => Runtime::T_HOOK,
						Runtime::B_METHOD => false,  // function or method
						Runtime::B_METHOD_KEY => $val[Runtime::B_RESULT] ? strtolower( $val[Runtime::B_RESULT] ) : null,
						Runtime::B_PARAM_2 => false, // &$functionParameters
						Runtime::B_OBJECT => false, // false or &object
						Runtime::B_OBJECT_KEY => $result[Runtime::B_RESULT] ? strtolower( $result[Runtime::B_RESULT] ) : null,
						Runtime::B_RESULT => null,
						Runtime::B_TOKEN_LINE => $this->tokenLine,
						Runtime::B_DEBUG => $this->text,
					);
					$this->addValueIntoStack( $result, $tmpresult, Runtime::B_OBJECT );
					$this->addValueIntoStack( $val, $tmpresult, Runtime::B_METHOD );
					$result =& $tmpresult;
					break;
				default: // Example: FOO::bar-> #
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
			}
		} while ( $this->id === T_OBJECT_OPERATOR || $this->id === T_DOUBLE_COLON ); // Example: FOO::bar->
		return $result;
	}

	/**
	 * This function adds $value to the $aim in $command
	 * If $value has a variable then it will be added to stack
	 * If the values of the command are scalar then command can be processed
	 * and if $doit is TRUE then the command will be processed
	 * and in this case the function returns TRUE otherwise FALSE.
	 * @param array $value Array with PHPTAGS_STACK_COMMAND
	 * @param array $command Array with $aim
	 * @param string $aim The aim in $command
	 * @param boolean $doit Process the command if possible
	 * @return boolean Returns TRUE, if the command was processed
	 */
	private function addValueIntoStack( &$value, &$command, $aim, $doit = false ) {
		if ( $value[Runtime::B_COMMAND] === Runtime::T_VARIABLE ) {
			$value[Runtime::B_PARAM_2] =& $command;
			$value[Runtime::B_AIM] = $aim;
		} else {
			$command[$aim] =& $value[Runtime::B_RESULT];
		}

		if ( $value[Runtime::B_COMMAND] === null ) {
			// The values of the command are scalar
			if ( $doit ) {
				$tmp = array( Runtime::B_COMMAND => Runtime::T_RETURN, Runtime::B_PARAM_1 => &$command[Runtime::B_RESULT] );
				$runtimeReturn = Runtime::run( array($command, $tmp),	array('PhpTags\\Compiler') );
				if ( $runtimeReturn instanceof PhpTagsException ) {
					if ( $this->ignoreErrors === null ) {
						$this->stack[] = array( Runtime::B_COMMAND => Runtime::T_IGNORE_ERROR, Runtime::B_PARAM_1 => false );
						$this->ignoreErrors = false;
					}
					return false;
				}
				$command = array(
					Runtime::B_COMMAND => null, // Mark the operator as the already processed.
					Runtime::B_RESULT => $runtimeReturn,
				);
			}
			return true;
		} elseif ( $value[Runtime::B_COMMAND] !== false ) {
			$this->stack[] =& $value;
		}

		if ( $this->ignoreErrors === null ) {
			$this->stack[] = array( Runtime::B_COMMAND => Runtime::T_IGNORE_ERROR, Runtime::B_PARAM_1 => false );
			$this->ignoreErrors = false;
		}
		return false;
	}

	/**
	 * Returns Hook as function() or $object->method()
	 * @param array $hook The blank hook
	 * @param array $funcName The function name
	 * @param mixed $owner Object as array or FALSE for function
	 * @return array Hook
	 * @throws PhpTagsException
	 */
	private function & stepFunction( &$hook, $funcName, $owner ) {
		$this->stepUP();

		if ( $owner !== false ) { // $owner is object
			$hook[Runtime::B_HOOK_TYPE] = $owner[1] === true ? Runtime::H_STATIC_METHOD : Runtime::H_OBJECT_METHOD;
			$this->addValueIntoStack( $owner[0], $hook, Runtime::B_OBJECT );
			$hook[Runtime::B_OBJECT_KEY] = $owner[0][Runtime::B_RESULT] ? strtolower( $owner[0][Runtime::B_RESULT] ) : null;
			$hook[Runtime::B_PARAM_2] =& $this->getFunctionParameters( $funcName, array( &$hook ) );
		} else { // it is function
			$hook[Runtime::B_HOOK_TYPE] = Runtime::H_FUNCTION;
			$hook[Runtime::B_PARAM_2] =& $this->getFunctionParameters( $funcName );
		}

		if ( $this->id != ')' ) {
			// PHP Parse error:  syntax error, unexpected $tmp_id, expecting ')'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();
		return $hook;
	}

	/**
	 * Returns Hook as $function() or objects->$method() where name will be received from variable
	 * @param array $variable The variable as function or method name
	 * @param string $text Text of variable for debug
	 * @param mixed $owner Object as array or FALSE for function
	 * @return array Hook
	 * @throws PhpTagsException
	 */
	private function & stepFunctionFromVariable( $variable, $text, $owner ) {
		$hook = array( // define the blank hook
			Runtime::B_COMMAND => Runtime::T_HOOK,
			Runtime::B_HOOK_TYPE => false,
			Runtime::B_METHOD => false, // function or method name from $variable
			Runtime::B_METHOD_KEY => null,
			Runtime::B_PARAM_2 => false, // &$functionParameters
			Runtime::B_OBJECT => false, // false or &object
			Runtime::B_OBJECT_KEY => null,
			Runtime::B_RESULT => null,
			Runtime::B_TOKEN_LINE => $this->tokenLine,
			Runtime::B_DEBUG => $text,
		);

		$return =& $this->stepFunction( $hook, $variable, $owner );
		$this->addValueIntoStack( $variable, $return, Runtime::B_METHOD ); // Add function or method name to hook
		return $return;
	}

	/**
	 * @param $throwEndTag
	 * @throws PhpTagsException
	 */
	private function stepFirsValue( $throwEndTag ) {
		$value =& $this->getNextValue();
		if ( $value ) { // Example: $foo=1;
			$dummy = array( Runtime::B_RESULT=>null );
			$this->addValueIntoStack( $value, $dummy, Runtime::B_RESULT );
			if ( $this->id != ';' ) { // Example: $foo=1,
				// PHP Parse error:  syntax error, unexpected $id, expecting ';'
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "';'" ), $this->tokenLine, $this->place );
			}
			$this->stepUP( $throwEndTag );
		} else if ( $this->id !== T_CLOSE_TAG ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
	}

}
