<?php
/**
 * Internationalization file for the messages of the extension PHP Tags.
 *
 * @file PhpTags.i18n.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 */

$messages = array();

/** English
 * @author pastakhov
 */
$messages['en'] = array(
	'php-tags-desc' => 'Adds in the wikitext parser the ability to use the syntax and functions of PHP',
	'php-tags-disabled-for-namespace' => 'Extension PHP disabled for this namespace $1',
	'php-tags-error-bad-delimiter' => 'Delimiter must not be alphanumeric or backslash',
	'php-tags-error-no-ending-matching-delimiter' => 'No ending matching delimiter "$1" found',
	'php-tags-error-unknown-modifier' => 'Unknown modifier "$1"',
	'php-tags-php-fatal-error-cannot-break-continue' => 'PHP fatal error: Cannot break/continue $1 levels on page $2 line $3.',
	'php-tags-php-fatal-error-max-execution-time' => 'PHP fatal error: Maximum execution time of {{PLURAL:$1|$1 second|$1 seconds}} exceeded on page $2.',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'PHP fatal error: Maximum execution time of $1 second exceeded on page $2 line $3.',
	'php-tags-php-fatal-error-undefined-function' => 'PHP fatal error: Call to undefined function $1() on page $2 line $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'PHP fatal error: Only variables can be passed by reference, function $1() on page $2 line $3.',
	'php-tags-php-syntax-error-unexpected' => 'PHP parse error: Syntax error, unexpected $1 in command line code on line $2.',
	'php-tags-php-warning-exception-in-function' => 'PHP warning: Function $1() on page $2 line $3 returns exception ($4).',
	'php-tags-php-wrong-parameter-count' => 'PHP warning: Wrong parameter count for $1() on page $2 line $3.',
	'php-tags-unexpected-result-work-function' => 'Unexpected result work function $1() of extension PHP on page $2 line $3.',
);

/** Message documentation (Message documentation)
 * @author Shirayuki
 * @author pastakhov
 */
$messages['qqq'] = array(
	'php-tags-desc' => '{{desc|name=PHP|url=http://www.mediawiki.org/wiki/Extension:PHP}}',
	'php-tags-disabled-for-namespace' => 'Error message when trying use this extension on the pages of the namespace where it is not permitted, parameters:
* $1 - the namespace name',
	'php-tags-error-bad-delimiter' => 'Error message. Delimiter for function preg_replace()',
	'php-tags-error-no-ending-matching-delimiter' => 'Error message. Parameters:
* $1 - delimiter',
	'php-tags-error-unknown-modifier' => 'Error message. Parameters:
* $1 - modifier',
	'php-tags-php-fatal-error-cannot-break-continue' => 'Error message, parameters:
* $1 - the number of user defined level
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
	'php-tags-php-fatal-error-max-execution-time' => 'Error message, parameters:
* $1 - the number of seconds
* $2 - the name of the page on which the error occurred',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'Error message, parameters:
* $1 - the number of seconds
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
	'php-tags-php-fatal-error-undefined-function' => 'Used as error message. Parameters:
* $1 - user-specified function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred
See also:
* {{msg-mw|PHPphp-unexpected-result-work-function}}',
	'php-tags-php-not-variable-passed-by-reference' => 'Error message, parameters:
* $1 - user-specified function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
	'php-tags-php-syntax-error-unexpected' => 'Error message, parameters:
* $1 - token or user-specified string a quoted
* $2 - the line number where the error occurred',
	'php-tags-php-warning-exception-in-function' => 'Error message, parameters:
* $1 - function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred
* $4 - error message from exception',
	'php-tags-php-wrong-parameter-count' => 'Error message, parameters:
* $1 - function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred',
	'php-tags-unexpected-result-work-function' => 'Used as error message. Parameters:
* $1 - function name
* $2 - the name of the page on which the error occurred
* $3 - the line number where the error occurred
See also:
* {{msg-mw|PHPphp-php-fatal-error-undefined-function}}',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'php-tags-desc' => "Añade al analizador de testu wiki la capacidá d'utilizar la sintaxis y funciones de PHP",
	'php-tags-disabled-for-namespace' => 'La estensión PHP ta desactivada pal espaciu de nomes $1',
	'php-tags-error-bad-delimiter' => 'El delimitador nun pue ser un caráuter alfanumbéricu nin una barra invertida',
	'php-tags-error-no-ending-matching-delimiter' => 'Non s\'alcontró nengún delimitador final que coincidiera con "$1"',
	'php-tags-error-unknown-modifier' => 'Modificador desconocíu "$1"',
	'php-tags-php-fatal-error-cannot-break-continue' => 'Error fatal de PHP: Nun puen interrumpise/continuase $1 niveles na páxina $2 llinia $3.',
	'php-tags-php-fatal-error-max-execution-time' => "Error fatal de PHP: Superóse'l tiempu máximu d'execución de $1 segundos na páxina $2.",
	'php-tags-php-fatal-error-max-execution-time-scope' => "Error fatal de PHP: Superóse'l tiempu máximu d'execución de $1 segundos na páxina $2 na llinia $3.",
	'php-tags-php-fatal-error-undefined-function' => 'Error fatal de PHP: Llamada a una función indefinida $1() na páxina $2 llinia $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'Error fatal de PHP: Sólo pueden pasase por referencia les variables, función $1() na páxina $2 llinia $3.',
	'php-tags-php-syntax-error-unexpected' => 'Error d\'análisis PHP: Error de sintaxis, "$1" inesperáu nel códigu de la llinia de comandos na llinia $2.',
	'php-tags-php-warning-exception-in-function' => 'Avisu de PHP: La función $1() na páxina $2 llinia $3 devuelve una esceición ($4)',
	'php-tags-php-wrong-parameter-count' => 'Avisu de PHP: Númberu de parámetros incorreutu pa $1() na páxina $2 llinia $3.',
	'php-tags-unexpected-result-work-function' => 'Resultáu inesperáu de trabayu pa la función $1() de la estensión PHP na páxina $2 llinia $3.',
);

/** German (Deutsch)
 * @author HvW
 * @author Metalhead64
 * @author Purodha
 */
$messages['de'] = array(
	'php-tags-desc' => 'Erweitert den Wikitext-Parser um die Möglichkeit, die Syntax und Funktionen von PHP zu verwenden',
	'php-tags-disabled-for-namespace' => 'Die Erweiterung PHP ist für den Namensraum „$1“ deaktiviert',
	'php-tags-error-bad-delimiter' => 'Trennzeichen darf nicht alphanumerisch oder ein Backslash sein',
	'php-tags-error-no-ending-matching-delimiter' => 'Es wurde kein Ende mit dem Trennzeichen „$1“ gefunden',
	'php-tags-error-unknown-modifier' => 'Unbekannter Modifikator „$1“',
	'php-tags-php-fatal-error-cannot-break-continue' => 'Fataler PHP-Fehler: $1 Ebenen auf der Seite $2, Zeile $3 konnten nicht unterbrochen/fortgeführt werden.',
	'php-tags-php-fatal-error-max-execution-time' => 'Fataler PHP-Fehler: Maximale Ausführungszeit von {{PLURAL:$1|einer Sekunde|$1 Sekunden}} auf Seite $2 überschritten.',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'Fataler PHP-Fehler: Maximale Ausführungszeit von $1 Sekunden auf Seite $2, Zeile $3 überschritten.',
	'php-tags-php-fatal-error-undefined-function' => 'Fataler PHP-Fehler: Aufruf zur undefinierten Funktion $1() auf Seite $2, Zeile $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'Fataler PHP-Fehler: Es können nur Variablen von der Referenz übergeben werden, Funktion $1() auf Seite $2, Zeile $3.',
	'php-tags-php-syntax-error-unexpected' => 'PHP-Parserfehler: Syntaxfehler, unerwartete $1 im Befehlszeilencode in Zeile $2.',
	'php-tags-php-warning-exception-in-function' => 'PHP-Warnung: Funktion $1() auf Seite $2, Zeile $3 gibt Ausnahme zurück ($4).',
	'php-tags-php-wrong-parameter-count' => 'PHP-Warnung: Falscher Parameterzähler für $1() auf Seite $2, Zeile $3.',
	'php-tags-unexpected-result-work-function' => 'Unerwartete Ergebnisarbeitsfunktion $1() der Erweiterung PHP auf Seite $2, Zeile $3.',
);

/** Spanish (español)
 * @author Fitoschido
 */
$messages['es'] = array(
	'php-tags-desc' => 'Permite almacenar datos orientados a objetos e implementa su propio entorno de ejecución de código PHP en las páginas',
	'php-tags-php-syntax-error-unexpected' => 'Error de análisis de PHP: Error de sintaxis, no se esperaba $1 en el código de línea de órdenes, en la línea $2.',
);

/** French (français)
 * @author Gomoko
 */
$messages['fr'] = array(
	'php-tags-desc' => 'Ajoute à l’analyseur de wikitext la possibilité d’utiliser la syntaxe et les fonctions de PHP',
	'php-tags-disabled-for-namespace' => 'Extension ext-php désactivée pour cet espace de noms $1',
	'php-tags-error-bad-delimiter' => 'Le délimiteur ne doit pas être alphanumérique ou la barre oblique inversée',
	'php-tags-error-no-ending-matching-delimiter' => 'Aucun délimiteur de fin correspondant à « $1 » trouvé',
	'php-tags-error-unknown-modifier' => 'Modificateur « $1 » inconnu',
	'php-tags-php-fatal-error-cannot-break-continue' => 'Erreur PHP fatale : Impossible d’interrompre/continuer $1 niveaux sur la page $2 en ligne $3.',
	'php-tags-php-fatal-error-max-execution-time' => 'Erreur PHP fatale : Temps d’exécution maximal de $1 secondes dépassé sur la page $2.',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'Erreur PHP fatale : Temps d’exécution maximal de $1 secondes dépassé sur la page $2 en ligne $3.',
	'php-tags-php-fatal-error-undefined-function' => 'Erreur PHP fatale : Appel à la fonction non définie $1() en page $2 ligne $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'Erreur PHP fatale : Seule les variables peuvent être passées par référence, fonction $1() en page $2 ligne $3.',
	'php-tags-php-syntax-error-unexpected' => 'Erreur d’analyse PHP : Erreur de syntaxe, $1 non attendu dans le code de la ligne de commande à la ligne $2.',
	'php-tags-php-warning-exception-in-function' => 'Avertissement PHP : La fonction $1() en page $2 ligne $3 a renvoyé une exception ($4).',
	'php-tags-php-wrong-parameter-count' => 'Avertissement PHP : Mauvais nombre de paramètres pour $1() en page $2 ligne $3.',
	'php-tags-unexpected-result-work-function' => 'Résultat de travail non attendu pour la fonction $1() de l’extension PHP en page $2 ligne $3.',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'php-tags-desc' => 'Engade ao analizador de texto wiki a posibilidade de utilizar a sintaxe e funcións do PHP',
	'php-tags-disabled-for-namespace' => 'A extensión PHP está desactivada para o espazo de nomes $1',
	'php-tags-error-bad-delimiter' => 'O delimitador non pode ser un carácter alfanumérico nin unha barra invertida',
	'php-tags-error-no-ending-matching-delimiter' => 'Non se atopou ningún delimitador de fin que coincidise con "$1"',
	'php-tags-error-unknown-modifier' => 'Descoñécese o modificador "$1"',
	'php-tags-php-fatal-error-cannot-break-continue' => 'Erro fatal de PHP: Non se poden interromper/continuar $1 niveis na páxina "$2" na liña $3.',
	'php-tags-php-fatal-error-max-execution-time' => 'Erro fatal de PHP: Superouse o número máximo de {{PLURAL:$1|$1 segundo|$1 segundos}} de execución na páxina "$2".',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'Erro fatal de PHP: Superouse o número máximo de $1 segundos de execución na páxina "$2" na liña $3.',
	'php-tags-php-fatal-error-undefined-function' => 'Erro fatal de PHP: Chamada a unha función $1() non definida na páxina "$2" na liña $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'Erro fatal de PHP: Unicamente as variables poden pasarse por referencia, función $1() na páxina "$2" na liña $3.',
	'php-tags-php-syntax-error-unexpected' => 'Erro de análise PHP: Erro de sintaxe; "$1" inesperado no código da liña de comandos na liña $2.',
	'php-tags-php-warning-exception-in-function' => 'Advertencia de PHP: A función $1() na páxina "$2" na liña $3 devolve unha excepción ($4).',
	'php-tags-php-wrong-parameter-count' => 'Advertencia de PHP: Número de parámetros incorrecto para $1() na páxina "$2" na liña $3.',
	'php-tags-unexpected-result-work-function' => 'Resultado de traballo inesperado para a función $1() da extensión PHP na páxina "$2" na liña $3.',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'php-tags-desc' => 'ウィキテキストのパーサーに、PHP の構文や関数を処理できる機能を追加する',
	'php-tags-disabled-for-namespace' => 'PHP 拡張機能はこの $1 名前空間では無効になっています',
	'php-tags-error-no-ending-matching-delimiter' => '対応する終了の区切り文字「$1」が見つかりません',
	'php-tags-error-unknown-modifier' => '不明な修飾子「$1」です',
	'php-tags-php-fatal-error-max-execution-time' => 'PHP 致命的エラー: ページ $2 の実行時間が最大値の {{PLURAL:$1|$1 秒}}を超えました。',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'PHP 致命的エラー: ページ $2 の $3 行目の実行時間が最大値の $1 秒を超えました。',
	'php-tags-php-syntax-error-unexpected' => 'PHP 構文解析エラー: 構文エラーです。行 $2 のコードで予期しない $1 が見つかりました。',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'php-tags-desc' => 'Määd_et möjjelesch, objägg_orrejänteerde Daat faßzehallde un brängg_en eije Ömjävong met sesch, öm <i lang="en">PHP</i>-Projramme loufe ze lohße.',
	'php-tags-php-syntax-error-unexpected' => 'Ene <i lang="en">PHP</i>-Projrammfähler wood jevonge: e „$1“ es en däm Projramm en dä Reih $2, woh mer dat nit äwaade deiht.',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'php-tags-disabled-for-namespace' => 'PHP-Erweiderung ass fir den Nummraum $1 ausgeschalt',
	'php-tags-error-bad-delimiter' => "Trennzeechen däerf net alphanumeeresch oder e 'Backslash' sinn",
	'php-tags-error-unknown-modifier' => 'Onbekannte Modificateur "$1"',
	'php-tags-php-fatal-error-max-execution-time' => 'Fatale PHP-Feeler: Maximal Ausféirungszäit vu(n) $1 Sekonnen op der Säit $2 depasséiert.',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'Fatale PHP-Feeler: Maximal Ausféirungszäit vu(n) $1 Sekonnen op an der Linn $3 vun der Säit $2 depasséiert.',
	'php-tags-php-fatal-error-undefined-function' => 'Fatale PHP-Feeler: Opruff vun enger net-definéierter Fonctioun $1() op der Säit $2, Zeil $3.',
	'php-tags-php-syntax-error-unexpected' => 'PHP-Parser-Feeler:Syntaxfeeler, onerwaarte(n) $1 am Code vun der Programmatiounszeil $2.',
	'php-tags-php-warning-exception-in-function' => "PHP Warnung: D'Fonctioun $1() op der Säit $2 Linn $3 gëtt d'Ausnahm ($4) zréck",
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'php-tags-desc' => 'На парсерот за викитекст муѕ ја дава можноста да користи синтакса и функции на PHP',
	'php-tags-disabled-for-namespace' => 'Додатокот PHP е оневозможен за именскиот простор „$1“',
	'php-tags-error-bad-delimiter' => 'Разграничувачот не може да биде азбучен, бројчен или надесна коса црта',
	'php-tags-error-no-ending-matching-delimiter' => 'Не пронајдов завршеток што одговара на разграничувачот „$1“',
	'php-tags-error-unknown-modifier' => 'Непознат изменител „$1“',
	'php-tags-php-fatal-error-cannot-break-continue' => 'Кобна грешка во PHP: Не можам да прекинам/продолжам $1 нивоа на страницата $2, ред $3.',
	'php-tags-php-fatal-error-max-execution-time' => 'Кобна грешка во PHP: Надминат е рокот од $1 {{PLURAL:$1|$1 секунда|$1 секунди}} за извршување на наредбата на страницата $2.',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'Кобна грешка во PHP: Надминат е рокот од $1 секунди за извршување на наредбата на страницата $2, ред $3.',
	'php-tags-php-fatal-error-undefined-function' => 'Кобна грешка со PHP: Повик на неодредена функција $1() на страницата $2, во редот $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'Кобна грешка во PHP: Со наведување можат да се даваат само променливи, функција $1() на страницата $2, ред $3.',
	'php-tags-php-syntax-error-unexpected' => 'Грешка при парсирање на PHP: Синтаксна грешка - не се очекува $1 во кодот во ред бр. $2.',
	'php-tags-php-warning-exception-in-function' => 'Предупредување за PHP: Функцијата $1() на страницата $2, ред $3 давфа исклучок ($4).',
	'php-tags-php-wrong-parameter-count' => 'Предупредување за PHP: Погрешен број на параметри за $1() на страница $2, ред $3.',
	'php-tags-unexpected-result-work-function' => 'Неочекуван исход од работната функција $1() на додатокот PHP на страница $2, ред $3.',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'php-tags-desc' => 'Menambahkan keupayaan untuk menggunakan sintaks dan fungsi PHP dalam penghurai wikiteks',
	'php-tags-php-syntax-error-unexpected' => 'Ralat huraian PHP: Ralat sintaks, $1 tak dijangka dalam kod baris perintah pada baris $2.',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'php-tags-desc' => 'Voor aan de wikitekstparser de mogelijkheid toe om syntaxis en functies van PHP op te nemen',
	'php-tags-disabled-for-namespace' => 'De uitbreiding ext-php is uitgeschakeld voor de naamruimte "$1"',
	'php-tags-error-bad-delimiter' => 'Het scheidingsteken mag niet alfanumeriek of een backslash zijn',
	'php-tags-error-no-ending-matching-delimiter' => 'Er is geen laatste scheidingsteken "$1" aangetroffen',
	'php-tags-error-unknown-modifier' => 'Onbekende modifier "$1"',
	'php-tags-php-fatal-error-cannot-break-continue' => 'Onherstelbare PHP-fout: kan niet afbreken of doorgaan uit $1 niveaus op pagina $2 regel $3.',
	'php-tags-php-fatal-error-max-execution-time' => 'Onherstelbare PHP-fout: maximale uitvoertijd van $1 seconden overschreden op pagina $2.',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'Onherstelbare PHP-fout: maximale uitvoertijd van $1 seconden overschreden op pagina $2 regel $3.',
	'php-tags-php-fatal-error-undefined-function' => 'Onherstelbare PHP-fout: aanroep van ongedefinieerde functie $1() op pagina $2, regel $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'Onherstelbare PHP-fout: alleen variabelen kunnen doorgegeven worden als referentie, functie $1() op pagina $2, regel $3.',
	'php-tags-php-syntax-error-unexpected' => 'Verwerkingsfout in PHP: syntaxisfout, "$1" is onverwacht in regel $2.',
	'php-tags-php-warning-exception-in-function' => 'PHP-waarschuwing: functie $1() op pagina $2, regel $3 geeft een uitzondering terug ($4).',
	'php-tags-php-wrong-parameter-count' => 'PHP-waarschuwing: onjuist parameteraantal voor $1() op pagina $2, regel $3.',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'php-tags-desc' => "Aggiunge jndr'à 'n'analizzatore de uicchiteste l'abbilità de ausà 'a sindasse e le funziune de PHP",
	'php-tags-disabled-for-namespace' => 'Estenzione PHP disabbiltiate pe stu namespace $1',
	'php-tags-php-syntax-error-unexpected' => "Errore de analisi de PHP: Errore de sindasse, inaspettate $1 jndr'à 'u linèe de codece $2.",
);

/** Ukrainian (українська)
 * @author Andriykopanytsia
 */
$messages['uk'] = array(
	'php-tags-desc' => 'Додає в аналізатор вікі можливість використовувати синтаксис і функції PHP',
	'php-tags-disabled-for-namespace' => 'Розширення PHP вимкнено для цього простору імен $1',
	'php-tags-error-bad-delimiter' => 'Роздільник не має бути буквенно-цифровим або зворотною косою рискою',
	'php-tags-error-no-ending-matching-delimiter' => 'Не знайдено збігів з кінцевим роздільником "$1"',
	'php-tags-error-unknown-modifier' => 'Невідомий модифікатор "$1"',
	'php-tags-php-fatal-error-cannot-break-continue' => 'PHP фатальна помилка: не вдається перервати/продовжити  $1  рівнів на сторінці  $2,  рядок $3 .',
	'php-tags-php-fatal-error-max-execution-time' => 'PHP фатальна помилка: максимальний час виконання з  {{PLURAL:$1|$1 секунда|$1 секунди|$1 секунд|$1 секунди}} перевищений на сторінці $2.',
	'php-tags-php-fatal-error-max-execution-time-scope' => 'PHP фатальна помилка: максимальний час виконання з  $1  секунд перевищений на сторінці  $2, рядок  $3 .',
	'php-tags-php-fatal-error-undefined-function' => 'PHP фатальна помилка: виклик невизначеної функції  $1() на сторінці  $2, рядок  $3.',
	'php-tags-php-not-variable-passed-by-reference' => 'PHP фатальна помилка: лише змінні можуть пройти по посиланню, функція  $1() на сторінці $2, рядок  $3.',
	'php-tags-php-syntax-error-unexpected' => 'PHP Помилка аналізу: синтаксична помилка, неочікуваний  $1 в командному рядку коду у рядку $2 .',
	'php-tags-php-warning-exception-in-function' => 'PHP попередження: функція  $1() на сторінці $2 рядка $3  повертає виняток ($4).',
	'php-tags-php-wrong-parameter-count' => 'PHP попередження: неправильне число параметрів для $1() на сторінці  $2, рядку $3.',
	'php-tags-unexpected-result-work-function' => 'Неочікуваний результат роботи функції  $1() — розширення PHP на сторінці  $2, рядок $3.',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Justincheng12345
 */
$messages['zh-hant'] = array(
	'php-tags-desc' => '容許於網頁上將物件導向的資料存儲並實現自己的PHP代碼運行', # Fuzzy
	'php-tags-php-syntax-error-unexpected' => 'PHP解析錯誤：語法錯誤，$2行出現意外代碼$1。',
);
