<?php
require_once 'page.php';
global $start, $page, $reqsBD, $deltaBD;

$page->setTitle('Войти в игру');
$page->addCss('style.css');

echo $page->getBeforeContent();
?>
    <div class="container">
        <div class="big_title"><span class="mdl">Приветствую!</span></div>
        <br/>
        <div class="btn-container">
            <a href="auth.php"><input class="pretty guest" type="button" value="Познакомиться"></a>
        </div>
        <div class="btn-container">
            <a href="login.php"><input class="pretty friend" type="button" value="Зайти"></a>
        </div>
    </div>
<style>
    .btn-container .guest {
        background-color: palegoldenrod;
    }
    .btn-container .friend {
        background-color: palegreen;
    }
</style>
<?php
$finish = microtime(true) - $start;
$delta = formatDelta($finish);
echo "<div class='tech'>page:{$delta}ms;{$reqsBD}req({$deltaBD}ms)</div>";