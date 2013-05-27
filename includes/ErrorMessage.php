<?php
namespace Foxway;
/**
 * ErrorMessage class of Foxway extension.
 *
 * @file ErrorMessage.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class ErrorMessage implements iRawOutput {
	/**
	 * __LINE__ of source code returned error
	 * @var int
	 */
	public $line;
	/**
	 * line parser source code that contains the error
	 * @var int
	 */
	public $tokenLine;
	/**
	 * Type of error
	 * @var int
	 */
	public $type;
	/**
	 * Params of error
	 * @var mixed
	 */
	public $params;
	private $caller;

	public function __construct( $line, $tokenLine, $type, $params ) {
		$this->line = $line;
		$this->tokenLine = $tokenLine;
		$this->type = $type;
		$this->params = $params;
		$this->caller = wfGetCaller();

		\MWDebug::log( "$line: " . $this->getMessage() );
	}

	public function __toString( ) {
		return \Html::rawElement(
				'span',
				array( 'class' => 'error', 'title' => 'Report from ' .htmlspecialchars($this->caller). 'line '.htmlspecialchars($this->line) ), // TODO wfMessage
				$this->getMessage()
				);
	}

	public function getMessage() {
		$return = false;
		switch ($this->type) {
			case E_PARSE:
				$unexpected = $this->params;
				$return =  wfMessage(
						'foxway-php-syntax-error-unexpected',
						is_string($unexpected) ? htmlspecialchars($unexpected) : token_name($unexpected),
						$this->tokenLine )->escaped();
				break;
		}
		return $return;
	}

}
