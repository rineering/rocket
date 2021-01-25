<?php
//начинаем считать выполнение страницы
$start = microtime(true);
$reqsBD = $deltaBD = 0;

require_once 'connect.php';
/*подключаем html helpers*/
require_once 'HtmlHelper.php';
require_once 'Page.php';

$page = new \html\game\Page();

session_start();
var_dump($_SESSION);
if (isset($_SESSION['error'])) {
    unset($_SESSION['error']);
}

/**
 * @param float $msc delta to format
 * @return float - delta
 */
function formatDelta($msc)
{
    return round(($msc * 1000), 2);
}

/**
 * @param float $msc
 */
function addSqlStat($msc)
{
    global $reqsBD, $deltaBD;
    $mscFormat = formatDelta($msc);
    $deltaBD += $mscFormat;
    $reqsBD++;
}