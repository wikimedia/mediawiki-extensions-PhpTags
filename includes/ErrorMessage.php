<?php
namespace Foxway;
/**
 * ErrorMessage class of Foxway extension.
 *
 * @file ErrorMessage.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 *
 * @property-read int $type Type of error
 * @property-read mixed $params Params of error
 */
class ErrorMessage implements iRawOutput {
	private $line;
	private $tokenLine;
	private $type;
	private $params;
	private $caller;

	public function __construct( $line, $tokenLine, $type, $params ) {
		$this->line = $line;
		$this->type = $type;
		$this->tokenLine = $tokenLine;
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

	public function __get($name) {
		switch ($name) {
			case 'type':
				return $this->type;
				break;
			case 'params':
				return $this->params;
				break;
		}
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
