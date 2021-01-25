<?php
require_once 'page.php';
global $start, $page, $bd, $reqsBD, $deltaBD;

$page->setTitle('Познакомиться');
$page->addCss('style.css');

$post = (int) (key_exists('clk', $_POST) ? $_POST['clk'] : false);
$error = null;
if ($post) {
    $name = htmlentities($_POST['name']);
    $pass = htmlentities($_POST['psw']);
    $mail = htmlentities($_POST['addmail']);

    $sql = 'SELECT login FROM `users` WHERE login LIKE "' . $name . '" OR email LIKE "' . $mail . '"';
    $msc = microtime(true);
    $res = mysqli_query($bd, $sql);
    $msc = microtime(true) - $msc;
    addSqlStat($msc);
    $row = mysqli_fetch_row($res);
    if (empty($row)) {
        //создаем пользователя
        $sqlIns = 'INSERT INTO `users` (login, password, email) VALUES("' . $name . '", "' . $pass . '", "' . $mail . '")';
        $msc1 = microtime(true);
        $resIns = mysqli_query($bd, $sqlIns);
        $msc1 = microtime(true) - $msc1;
        addSqlStat($msc1);
        if (!$resIns) {
            $_SESSION['error'] = mysqli_error($bd);
        } else {
            $uid = mysqli_insert_id($bd);

            $_SESSION['uid'] = $uid;
            $_SESSION['name'] = $name;

            //создаем игрока
            $sqlInsPl = 'INSERT INTO `players` (user_id, status) VALUES(' . $uid . ', 1)';
            $msc2 = microtime(true);
            $resInsPl = mysqli_query($bd, $sqlInsPl);
            $msc2 = microtime(true) - $msc2;
            addSqlStat($msc2);

            //создаем параметры
            $sqlInsPr = 'INSERT INTO `parameters` (user_id) VALUES(' . $uid . ')';
            $msc3 = microtime(true);
            $resInsPr = mysqli_query($bd, $sqlInsPr);
            $msc3 = microtime(true) - $msc3;
            addSqlStat($msc3);
            if (!$resInsPl && !$resInsPr) {
                $_SESSION['error'] = mysqli_error($bd);
            }
            $_SESSION['is_authorized'] = true;
            $location = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
            $page->addMeta('http-equiv="refresh" content="3;url=' . $location . '/entrance.php"');
            //header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/entrance.php');
        }
    } else {
        if ($name === current($row)) {
            $error = 'Гость с таким именем уже был<br />Это вы? Тогда <a href="login.php">Отметьтесь и проходите</a>';
        } else {
            $error = 'Такой почтовый адрес уже встречался<br /><a href="login.php">Отметьтесь и проходите</a>';
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
        <p>- Назовитесь</p>
        <p>- <input required name="name"></p>
        <p>- Придумайте тайное слово</p>
        <p>- <input required type="password" name="psw"></p>
        <p>- Позвольте узнать почтовый адрес</p>
        <p>- <input required type="email" name="addmail"></p>
        <div class="form-btn-cnt">
            <input type="hidden" name="clk" value="1">
            <input class="form-btn btn-guest" type="submit" value="Представиться">
        </div>
        </div>
    </div>
</form>
    </div>
<style>
    .btn-guest {
        background: paleturquoise;
    }
</style>
<?php
$finish = microtime(true) - $start;
$delta = formatDelta($finish);
echo "<div class='tech'>page:{$delta}ms;{$reqsBD}req({$deltaBD}ms)</div>";