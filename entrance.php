<?php
require_once 'page.php';
global $start, $page, $bd, $reqsBD, $deltaBD;

if (!isset($_SESSION['is_authorized'])) {
    header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/');
}

if (isset($_POST['exit']) && $_POST['exit']) {
    $uid = (int) $_SESSION['uid'];
    //смена статуса offline
    $sqlStatus = 'UPDATE `players` SET `status` = 0 WHERE `user_id` =' . $uid;
    $msc = microtime(true);
    $resStatus = mysqli_query($bd, $sqlStatus);
    $msc = microtime(true) - $msc;
    addSqlStat($msc);

    $location = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    $page->addMeta('http-equiv="refresh" content="3;url=' . $location . '/"');
    //очистка сессии
    $_SESSION = [];
    session_destroy();
    //header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/');
}

$page->setTitle('Парадная');
$page->addCss('style.css');

echo $page->getBeforeContent();
?>
    <div class="container">
        <div class="btn-container">
            <a href="room.php"><input class="pretty duels" type="button" value="Дуэли"></a>
        </div>
        <form method="post">
            <div class="btn-container">
                <input type="hidden" name="exit" value="1">
                <input class="pretty exit" type="submit" value="Выход">
            </div>
        </form>
    </div>
<style>
    .btn-container .duels {
        background-color: lightcoral;
    }
    .btn-container .exit {
        background-color: lightsteelblue;
    }
</style>

<?php
$finish = microtime(true) - $start;
$delta = formatDelta($finish);
echo "<div class='tech'>page:{$delta}ms;{$reqsBD}req({$deltaBD}ms)</div>";