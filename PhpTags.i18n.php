<?php
/**
 * Internationalization file for the extension PhpTags.
 *
 * @file PhpTags.i18n.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 */
$messages = array();

###########################################################################
######################### WARNING #########################################
#################### I'm not ready for translation ########################
############################ ATTENTION ####################################
###########################################################################

/** English
 * @author pastakhov
 */
$messages['en'] = array(
	'phptags-desc' => 'Enhances parser with Magic expression that has syntax of PHP language',
	'phptags-disabled-for-namespace' => 'Extension PhpTags disabled for namespace "$1"',
);

/** Message documentation
 * @author pastakhov
 */
$messages['qqq'] = array(
	'phptags-desc' => '{{desc|name=PhpTags|url=http://www.mediawiki.org/wiki/Extension:PhpTags}}',
	'phptags-disabled-for-namespace' => 'Error message when you try to use this extension for the namespace that have not been allowed, parameters:
* $1 - the namespace text',
);
