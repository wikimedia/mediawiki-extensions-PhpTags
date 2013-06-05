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
	'foxway-php-fatal-error-undefined-function' => 'PHP fatal error: Call to undefined function $1() on page $2 line $3.',
	'foxway-php-not-variable-passed-by-reference' => 'PHP fatal error: Only variables can be passed by reference, function $1() on page $2 line $3.',
	'foxway-php-syntax-error-unexpected' => 'PHP parse error: Syntax error, unexpected $1 in command line code on line $2.',
	'foxway-php-warning-exception-in-function' => 'PHP warning: Function $1() on page $2 line $3 returns exception ($4).',
	'foxway-php-wrong-parameter-count' => 'PHP warning: Wrong parameter count for $1() on page $2 line $3.',
	'faxway-unexpected-result-work-function' => 'Unexpected result work function $1() of extension Foxway on page $2 line $3.',
);

/** Message documentation (Message documentation)
 * @author pastakhov
 */
$messages['qqq'] = array(
	'foxway-desc' => '{{desc|name=Foxway|url=https://www.mediawiki.org/wiki/Extension:Foxway}}',
	'foxway-php-fatal-error-undefined-function' => 'Error message, parameters:
* $1 - user-specified function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
	'foxway-php-not-variable-passed-by-reference' => 'Error message, parameters:
* $1 - user-specified function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
	'foxway-php-syntax-error-unexpected' => 'Error message, parameters:
* $1 - token or user-specified string a quoted
* $2 - the line number where the error occurred',
	'foxway-php-warning-exception-in-function' => 'Error message, parameters:
* $1 - function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred
* $4 - error message from exception',
	'foxway-php-wrong-parameter-count' => 'Error message, parameters:
* $1 - function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
	'faxway-unexpected-work-function' => 'Error message, parameters:
* $1 - Foxway extension function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
);

/** German (Deutsch)
 * @author HvW
 * @author Metalhead64
 * @author Purodha
 */
$messages['de'] = array(
	'foxway-desc' => 'Ermöglicht die Speicherung von objektorientierten Daten und implementiert eine eigene Laufzeitumgebung für PHP-Code auf Seiten',
	'foxway-php-syntax-error-unexpected' => 'PHP-Parserfehler: Syntaxfehler, unerwartete $1 im Befehlszeilencode in Zeile $2.',
);

/** Spanish (español)
 * @author Fitoschido
 */
$messages['es'] = array(
	'foxway-desc' => 'Permite almacenar datos orientados a objetos e implementa su propio entorno de ejecución de código PHP en las páginas',
	'foxway-php-syntax-error-unexpected' => 'Error de análisis de PHP: Error de sintaxis, no se esperaba $1 en el código de línea de órdenes, en la línea $2.',
);

/** French (français)
 * @author Gomoko
 */
$messages['fr'] = array(
	'foxway-desc' => 'Autorise le stockage de données orientées objet et implémente son propre moteur pour le code PHP sur les pages',
	'foxway-php-syntax-error-unexpected' => 'Erreur d’analyse PHP : Erreur de syntaxe, $1 non attendu dans le code de la ligne de commande à la ligne $2.',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'foxway-desc' => 'Permite o almacenamento de datos orientados a obxectos e introduce o seu propio tempo de execución para o código PHP nas páxinas',
	'foxway-php-syntax-error-unexpected' => 'Erro de análise PHP: Erro de sintaxe; "$1" inesperado no código da liña de comandos na liña $2.',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'foxway-desc' => 'オブジェクト指向データを格納できるようにし、ページで PHP コード専用の実行環境を実現する',
	'foxway-php-syntax-error-unexpected' => 'PHP 構文解析エラー: 構文エラーです。行 $2 のコードで予期しない $1 が見つかりました。',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'foxway-desc' => 'Määd_et möjjelesch, objägg_orrejänteerde Daat faßzehallde un brängg_en eije Ömjävong met sesch, öm <i lang="en">PHP</i>-Projramme loufe ze lohße.',
	'foxway-php-syntax-error-unexpected' => 'Ene <i lang="en">PHP</i>-Projrammfähler wood jevonge: e „$1“ es en däm Projramm en dä Reih $2, woh mer dat nit äwaade deiht.', # Fuzzy
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'foxway-desc' => 'Овозможува складирање на објектно-ориентирани податоци и става свој извршител за PHP-код на страниците',
	'foxway-php-syntax-error-unexpected' => 'Грешка при парсирање на PHP: Синтаксна грешка - не се очекува $1 во кодот во ред бр. $2.',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'foxway-desc' => 'Membolehkan penyimpanan data berorientasikan objek serta melaksanakan masa jalan sendiri untuk kod PHP pada halaman',
	'foxway-php-syntax-error-unexpected' => 'Ralat huraian PHP: Ralat sintaks, $1 tak dijangka dalam kod baris perintah pada baris $2.',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'foxway-desc' => "Maakt het mogelijk om objectgeoriënteerde gegevens op te slaan en implementeert een eigen runtime voor PHP-code op pagina's",
	'foxway-php-syntax-error-unexpected' => 'Verwerkingsfout in PHP: syntaxisfout, "$1" is onverwacht in regel $2.',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'foxway-desc' => "Permette de stipà 'nu date oriendate a l'oggette e 'mblemende 'u combortamede in esecuzione pu codece PHP sus a le pàggene",
	'foxway-php-syntax-error-unexpected' => "Errore de analisi de PHP: Errore de sindasse, inaspettate $1 jndr'à 'u linèe de codece $2.",
);
