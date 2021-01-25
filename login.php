<?php
require_once 'page.php';
global $start, $bd, $page, $reqsBD, $deltaBD;

$page->setTitle('Отметиться');
$page->addCss('style.css');

$post = (int) (key_exists('clk', $_POST) ? $_POST['clk'] : false);
$error = null;
if ($post) {
    $name = htmlentities($_POST['name']);
    $pass = htmlentities($_POST['psw']);

    $sql = 'SELECT * FROM `users` WHERE `login` LIKE "' . $name . '" AND `password` LIKE "' . $pass . '"';
    $msc = microtime(true);
    $res = mysqli_query($bd, $sql);
    $msc = microtime(true) - $msc;
    addSqlStat($msc);

    if ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
        $_SESSION['is_authorized'] = true;
        $_SESSION['uid'] = $row['id'];
        $_SESSION['name'] = $row['login'];

        $sqlStatus = 'UPDATE `players` SET status = 1 WHERE user_id = ' . $row['id'];
        $msc1 = microtime(true);
        $resStatus = mysqli_query($bd, $sqlStatus);
        $msc1 = microtime(true) - $msc1;
        addSqlStat($msc1);
        if (!$resStatus) {
            $_SESSION['error'] = mysqli_error($bd);
        }
        $location = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $page->addMeta('http-equiv="refresh" content="3;url=' . $location . '/entrance.php"');
        //header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/entrance.php');
    } else {
        //проверяем ошибся ли пользователь паролем
        $sqlChecking = 'SELECT COUNT(*) AS count FROM `users` WHERE `login` LIKE "' . $name . '"';
        $msc2 = microtime(true);
        $resChecking = mysqli_query($bd, $sqlChecking);
        $msc2 = microtime(true) - $msc2;
        addSqlStat($msc2);
        $rowCheck = mysqli_fetch_row($resChecking);
        if (current($rowCheck)) {
            $error = 'Секретное слово неверно';
        } else {
            $error = 'Вас eщё не было в гостях<br /><a href="auth.php">Давайте познакомимся</a>';
        }
    }
}
echo $page->getBeforeContent();
?>
    <div class="container">
        <div class="rtn-btn">
            <a class="orange" href="/"><input class="btn-orange" type="button" value="Вернуться"></a>
        </div>
<form name="guest" method="post">
    <?php if ($error) { ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php } ?>
    <div class="fld-container">
        <div class="fld-dialog">
        <p>- Внесите участника</p>
        <p>- <input required name="name"></p>
        <p>- Вспомните тайное слово</p>
        <p>- <input type="password" name="psw"></p>
        <div class="form-btn-cnt btn-friend_cnt">
            <input type="hidden" name="clk" value="1">
            <input class="form-btn btn-friend" type="submit" value="Отметиться">
        </div>
        </div>
    </div>
</form>
    </div>
    <style>
        .btn-friend {
            background: lightblue;
        }
    </style>
<?php
$finish = microtime(true) - $start;
$delta = formatDelta($finish);
echo "<div class='tech'>page:{$delta}ms;{$reqsBD}req({$deltaBD}ms)</div>";