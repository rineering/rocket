<?php
require_once 'page.php';
global $start, $page, $memcached, $bd, $reqsBD, $deltaBD;
//var_dump($memcached->get('logs'));
if (!isset($_SESSION['is_authorized'])) {
    header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/');
    die;
}
$uid = (int) $_SESSION['uid'];
if (isset($_SESSION['enterUpdateArena'])) {
    //возвращаем  статус "онлайн"
    $sqlStatus = 'UPDATE players SET status = 1 WHERE user_id = ' . $uid;
    $msc2 = microtime(true);
    $resStatus = mysqli_query($bd, $sqlStatus);
    $msc2 = microtime(true) - $msc2;
    addSqlStat($msc2);
    if (!$resStatus) {
        $_SESSION['error'] = mysqli_error($bd);
    } else {
        unset($_SESSION['error']);
    }
    unset($_SESSION['enterUpdateArena']);
}
if (isset($_SESSION['enemy'])) {
    if ($memcached->get($_SESSION['logKey'])) {
        $memcached->delete($_SESSION['logKey']);
    }
    unset($_SESSION['enemy']);
    unset($_SESSION['logKey']);
}

if (!empty($_POST) && isset($_POST['fight'])) {
    //устанавливаем статус "готов к бою"
    $sqlStatus = 'UPDATE players SET status = 2 WHERE user_id = ' . $uid;
    $msc = microtime(true);
    $resStatus = mysqli_query($bd, $sqlStatus);
    $msc = microtime(true) - $msc;
    addSqlStat($msc);
    if (!$resStatus) {
        $_SESSION['error'] = mysqli_error($bd);
    } else {
        unset($_SESSION['error']);
    }
    $location = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    $page->addMeta('http-equiv="refresh" content="3;url=' . $location . '/arena.php"');
    //header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/arena.php');
} else {
    if (!isset($_SESSION['ur'])) {
        //получаем рейтинг игрока
        $sql = 'SELECT rating FROM `players` WHERE user_id=' . $uid;
        $msc1 = microtime(true);
        $res = mysqli_query($bd, $sql);
        $msc1 = microtime(true) - $msc1;
        addSqlStat($msc1);
        $row = mysqli_fetch_row($res);
        $_SESSION['ur'] = current($row);
    }
}

$page->setTitle('Покои');
$page->addCss('style.css');

echo $page->getBeforeContent();
?>
    <div class="container">
        <div class="rtn-btn">
            <a class="orange" href="entrance.php"><input class="btn-orange" type="button" value="Вернуться"></a>
        </div>
        <div class="player-rating">Ваш рейтинг: <?php echo $_SESSION['ur']; ?></div>
        <form method="post">
            <div class="btn-container">
                <input type="hidden" name="fight" value="1">
                <input class="pretty duels" type="submit" value="Начать дуэль">
            </div>
        </form>
    </div>
<style>
    .btn-container .duels {
        background-color: paleturquoise;
    }
</style>
<?php
$finish = microtime(true) - $start;
$delta = formatDelta($finish);
echo "<div class='tech'>page:{$delta}ms;{$reqsBD}req({$deltaBD}ms)</div>";