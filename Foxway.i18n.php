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
	'foxway-desc' => 'Adds in the wikitext parser the ability to use the syntax and functions of PHP',
	'foxway-disabled-for-namespace' => 'Extension foxway disabled for this namespace $1',
	'foxway-php-fatal-error-undefined-function' => 'PHP fatal error: Call to undefined function $1() on page $2 line $3.',
	'foxway-php-not-variable-passed-by-reference' => 'PHP fatal error: Only variables can be passed by reference, function $1() on page $2 line $3.',
	'foxway-php-syntax-error-unexpected' => 'PHP parse error: Syntax error, unexpected $1 in command line code on line $2.',
	'foxway-php-warning-exception-in-function' => 'PHP warning: Function $1() on page $2 line $3 returns exception ($4).',
	'foxway-php-wrong-parameter-count' => 'PHP warning: Wrong parameter count for $1() on page $2 line $3.',
	'foxway-unexpected-result-work-function' => 'Unexpected result work function $1() of extension Foxway on page $2 line $3.',
);

/** Message documentation (Message documentation)
 * @author pastakhov
 */
$messages['qqq'] = array(
	'foxway-desc' => '{{desc|name=Foxway|url=https://www.mediawiki.org/wiki/Extension:Foxway}}',
	'foxway-disabled-for-namespace' => 'Error message when trying use this extension on the pages of the namespace where it is not permitted, parameters:
* $1 - the namespace name',
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
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'foxway-desc' => "Permite guardar un datu orientáu a oxetos y prepara'l so propiu tiempu d'execución pal códigu PHP de les páxines",
	'foxway-php-syntax-error-unexpected' => 'Error d\'análisis PHP: Error de sintaxis, "$1" inesperáu nel códigu de la llinia de comandos na llinia $2.',
);

/** German (Deutsch)
 * @author HvW
 * @author Metalhead64
 * @author Purodha
 */
$messages['de'] = array(
	'foxway-desc' => 'Erweitert den Wikitext-Parser um die Möglichkeit, die Syntax und Funktionen von PHP zu verwenden',
	'foxway-disabled-for-namespace' => 'Die Erweiterung Foxway ist für den Namensraum „$1“ deaktiviert',
	'foxway-php-fatal-error-undefined-function' => 'Fataler PHP-Fehler: Aufruf zur undefinierten Funktion $1() auf Seite $2, Zeile $3.',
	'foxway-php-not-variable-passed-by-reference' => 'Fataler PHP-Fehler: Es können nur Variablen von der Referenz übergeben werden, Funktion $1() auf Seite $2, Zeile $3.',
	'foxway-php-syntax-error-unexpected' => 'PHP-Parserfehler: Syntaxfehler, unerwartete $1 im Befehlszeilencode in Zeile $2.',
	'foxway-php-warning-exception-in-function' => 'PHP-Warnung: Funktion $1() auf Seite $2, Zeile $3 gibt Ausnahme zurück ($4).',
	'foxway-php-wrong-parameter-count' => 'PHP-Warnung: Falscher Parameterzähler für $1() auf Seite $2, Zeile $3.',
	'foxway-unexpected-result-work-function' => 'Unerwartete Ergebnisarbeitsfunktion $1() der Erweiterung Foxway auf Seite $2, Zeile $3.',
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
	'foxway-desc' => 'Ajoute à l’analyseur de wikitext la possibilité d’utiliser la syntaxe et les fonctions de PHP',
	'foxway-disabled-for-namespace' => 'Extension foxway désactivée pour cet espace de noms $1',
	'foxway-php-fatal-error-undefined-function' => 'Erreur PHP fatale : Appel à la fonction non définie $1() en page $2 ligne $3.',
	'foxway-php-not-variable-passed-by-reference' => 'Erreur PHP fatale : Seule les variables peuvent être passées par référence, fonction $1() en page $2 ligne $3.',
	'foxway-php-syntax-error-unexpected' => 'Erreur d’analyse PHP : Erreur de syntaxe, $1 non attendu dans le code de la ligne de commande à la ligne $2.',
	'foxway-php-warning-exception-in-function' => 'Avertissement PHP : La fonction $1() en page $2 ligne $3 a renvoyé une exception ($4).',
	'foxway-php-wrong-parameter-count' => 'Avertissement PHP : Mauvais nombre de paramètres pour $1() en page $2 ligne $3.',
	'foxway-unexpected-result-work-function' => 'Résultat de travail non attendu pour la fonction $1() de l’extension Foxway en page $2 ligne $3.',
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
	'foxway-desc' => 'ウィキテキストのパーサーに、PHP の構文や関数を処理できる機能を追加する',
	'foxway-disabled-for-namespace' => 'Foxway 拡張機能はこの $1 名前空間では無効になっています',
	'foxway-php-syntax-error-unexpected' => 'PHP 構文解析エラー: 構文エラーです。行 $2 のコードで予期しない $1 が見つかりました。',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'foxway-desc' => 'Määd_et möjjelesch, objägg_orrejänteerde Daat faßzehallde un brängg_en eije Ömjävong met sesch, öm <i lang="en">PHP</i>-Projramme loufe ze lohße.',
	'foxway-php-syntax-error-unexpected' => 'Ene <i lang="en">PHP</i>-Projrammfähler wood jevonge: e „$1“ es en däm Projramm en dä Reih $2, woh mer dat nit äwaade deiht.',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'foxway-php-syntax-error-unexpected' => 'PHP-Parser-Feeler:Syntaxfeeler, onerwaarte(n) $1 am Code vun der Programmatiounszeil $2.',
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'foxway-desc' => 'Овозможува складирање на објектно-ориентирани податоци и става свој извршител за PHP-код на страниците', # Fuzzy
	'foxway-php-fatal-error-undefined-function' => 'Кобна грешка со PHP: Повик на неодредена функција $1() на страницата $2, во редот $3.',
	'foxway-php-not-variable-passed-by-reference' => 'Кобна грешка во PHP: Со наведување можат да се даваат само променливи, функција $1() на страницата $2, ред $3.',
	'foxway-php-syntax-error-unexpected' => 'Грешка при парсирање на PHP: Синтаксна грешка - не се очекува $1 во кодот во ред бр. $2.',
	'foxway-php-warning-exception-in-function' => 'Предупредување за PHP: Функцијата $1() на страницата $2, ред $3 давфа исклучок ($4).',
	'foxway-php-wrong-parameter-count' => 'Предупредување за PHP: Погрешен број на параметри за $1() на страница $2, ред $3.',
	'foxway-unexpected-result-work-function' => 'Неочекуван исход од работната функција $1() на додатокот Foxway на страница $2, ред $3.',
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
	'foxway-desc' => "Aggiunge jndr'à 'n'analizzatore de uicchiteste l'abbilità de ausà 'a sindasse e le funziune de PHP",
	'foxway-disabled-for-namespace' => 'Estenzione foxway disabbiltiate pe stu namespace $1',
	'foxway-php-syntax-error-unexpected' => "Errore de analisi de PHP: Errore de sindasse, inaspettate $1 jndr'à 'u linèe de codece $2.",
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Justincheng12345
 */
$messages['zh-hant'] = array(
	'foxway-desc' => '容許於網頁上將物件導向的資料存儲並實現自己的PHP代碼運行', # Fuzzy
	'foxway-php-syntax-error-unexpected' => 'PHP解析錯誤：語法錯誤，$2行出現意外代碼$1。',
);
