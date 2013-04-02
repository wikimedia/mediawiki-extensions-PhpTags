<?php
/**
 * Internationalization file for the messages of the Foxway extension.
 *
 * @file Foxway.i18n.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 */

$messages = array();

/** English
 * @author pastakhov
 */
$messages['en'] = array(
	'foxway-desc' => 'Allows to store an object-oriented data and implements its own runtime for PHP code on pages',
	'foxway-php-syntax-error-unexpected' => 'PHP parse error: syntax error, unexpected $1 in command line code on line $2'
);

/** Message documentation (Message documentation)
 * @author pastakhov
 */
$messages['qqq'] = array(
	'foxway-desc' => '{{desc|name=Foxway|url=https://www.mediawiki.org/wiki/Extension:Foxway}}',
	'foxway-php-syntax-error-unexpected' => 'Error message, parameters:
* $1 - token or user-specified string a quoted
* $2 - the line number where the error occurred',
);
