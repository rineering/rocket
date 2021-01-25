<?php
require_once 'page.php';
global $start, $page, $memcached, $bd, $reqsBD, $deltaBD;

if (!isset($_SESSION['is_authorized'])) {
    header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/');
    die;
}

//подсчет захода на страницу
if (!isset($_SESSION['enterUpdateArena'])) {
    $_SESSION['enterUpdateArena'] = 0;
}
$_SESSION['enterUpdateArena'] += 1;

if (!$memcached->get('logs')) {
    $memcached->add('logs', []);
}

$page->setTitle('Арена');
$page->addCss('style.css');
$page->addCss('arena.style.css');

$uid = $_SESSION['uid'];
$enemy = isset($_SESSION['enemy']) && !empty($_SESSION['enemy']);
$writingLog = $existingTimer = $attack = false;

if ($_SESSION['enterUpdateArena'] <= 1) { //только зашли на страницу
    //очищаем таймер
    if (isset($_SESSION['timer'])) {
        unset($_SESSION['timer']);
    }
    /**
     * записываем свои параметры в memcached,
     * $_SESSION['uid'] - id,
     * $_SESSION['ur'] - рейтинг,
     * $_SESSION['name'] - login
     */
    $myParams = [
            'name' => $_SESSION['name'],
    ];
    $tempKey = 'player_' . $uid;
    $sqlMy = 'SELECT * FROM `parameters` WHERE user_id = ' . $uid;
    $msc = microtime(true);
    $resultMy = mysqli_query($bd, $sqlMy);
    $msc = microtime(true) - $msc;
    addSqlStat($msc);
    while ($row = mysqli_fetch_array($resultMy, MYSQLI_ASSOC)) {
        $myParams += $row;
        //устанавливаем свои параметры в сессию
        $_SESSION['health'] = $row['health'];
        $_SESSION['power'] = $row['power'];

        $memcached->add($tempKey, $myParams);
    }

    $page->addMeta('http-equiv="refresh" content="3"');
} elseif (!isset($_SESSION['enemy'])) { //нужно подобрать противника
    $tempKey = 'player_' . $uid;
    if (!$memcached->get($tempKey)) { //еще раз записываемся в memcached, чтб противник взял наши параметры
        $myParams = [
            'name' => $_SESSION['name'],
            'user_id' => $uid,
            'health' => $_SESSION['health'],
            'power' => $_SESSION['power']
        ];

        $memcached->add($tempKey, $myParams);
    }

    $tempArr = $memcached->get('logs');
    $existLog = false;
    if (!empty($tempArr)) {
        foreach ($tempArr as $logId) {
            $existLog = stripos($logId, $uid) === false ? false : $logId;
        }
    }
    if ($existLog === false) { //log боя с id игрока еще не заведен
        $sql = 'SELECT user_id FROM `players` WHERE `status` = 2 AND user_id != ' . $_SESSION['uid'];
        $msc1 = microtime(true);
        $res = mysqli_query($bd, $sql);
        $msc1 = microtime(true) - $msc1;
        addSqlStat($msc1);
        $enemies = mysqli_fetch_row($res);

        //нет игроков ожидающих боя
        if (!empty($enemies)) {
            $eCount = count($enemies);
            $key = rand(0, ($eCount - 1));
            $enemyId = $enemies[$key];
            $existLog = $uid . '_' . $enemyId . '_log';
            $tempArr[] = $existLog;
            $memcached->set('logs', $tempArr);
            $memcached->add($existLog, '');
        }
    } else { //другой игрок уже подобрал игрока в противники и завел лог
        //вычисляем id противника
        $logParts = explode('_', $existLog);
        $tempE = array_filter($logParts, function ($p) use ($uid) {
            return $p != $uid && $p != 'log';
        });
        $enemyId = current($tempE);
    }
    if (isset($enemyId)) {
        //записываем в сессию параметры противника
        $enemy = $memcached->get('player_' . $enemyId);
    }
    if ($enemy) { //если нашли противника, то меняем статус на "сражающегося"
        $_SESSION['enemy'] = $enemy;
        $_SESSION['logKey'] = $existLog;

        $sqlStatus = 'UPDATE `players` SET status = 3 WHERE user_id = ' . $_SESSION['uid'];
        $msc2 = microtime(true);
        $resStatus = mysqli_query($bd, $sqlStatus);
        $msc2 = microtime(true) - $msc2;
        addSqlStat($msc2);
        if (!$resStatus) {
            $_SESSION['error'] = mysqli_error($bd);
        }
        //таймер
        $_SESSION['timer'] = 30;

        $page->addMeta('http-equiv="refresh" content="1"');
    } else {
        $page->addMeta('http-equiv="refresh" content="3"');
    }
} elseif ($enemy) { //противник подобран
    //таймер
    $existingTimer = isset($_SESSION['timer']) ? $_SESSION['timer'] : false;
    if ($existingTimer === false) {
        //атаки
        $existingLog = isset($_SESSION['logKey']) && $memcached->get($_SESSION['logKey']);
        $writingLog = isset($_SESSION['logKey']) && $memcached->getResultCode() != $memcached::RES_NOTFOUND;
        $attackValue = 0;
        if ($enemy && $writingLog) {
            $stringLog = $memcached->get($_SESSION['logKey']);
            $temp = explode('_', $_SESSION['logKey']);
            $symbol = $temp[0] == $uid ? '<' : '>';

            if (!empty($_POST) && key_exists($uid, $_POST)) {
                //урон противнику
                $_SESSION['enemy']['damage'] = isset($_SESSION['enemy']['damage']) ? $_SESSION['enemy']['damage'] + $_POST[$uid] : $_POST[$uid];
                $clone = $stringLog;
                $stringLog = "$symbol {$_SESSION['name']} ударил {$_SESSION['enemy']['name']} на {$_POST[$uid]} урона" . (empty($clone) ? '' : "\n\n" . $stringLog);
                $memcached->setOption(Memcached::OPT_COMPRESSION, false);
                $memcached->set($_SESSION['logKey'], $stringLog);
                $stringLog = $memcached->get($_SESSION['logKey']);
            }
            $tempArr = explode("\n\n", $stringLog);
            $tempArr = array_filter($tempArr, function ($attackString) {
                return !empty($attackString);
            });
            $countAttack = count($tempArr);
            if ($temp[0] != $uid && !($countAttack & 1)) {
                $attack = false;
            } elseif ($temp[0] != $uid && ($countAttack & 1)) {
                $attack = true;
            }
            if ($temp[0] == $uid && !($countAttack & 1)) {
                $attack = true;
            } elseif ($temp[0] == $uid && ($countAttack & 1)) {
                $attack = false;
            }

            //ранения
            $n = $_SESSION['enemy']['name'];
            $damages = array_filter($tempArr, function ($attackString) use ($n) {
                return stripos($attackString, $n) <= 3;
            });
            array_walk($damages, function (&$s) {
                $matches = [];
                preg_match('/\s\d+\s/', $s, $matches);
                $s = !empty($matches) ? trim(current($matches)) : 0;
            });
            var_dump($damages);
            $_SESSION['damage'] = array_sum((array)$damages);

            if ($attack) {
                $attackValue = rand(1, $_SESSION['power']);
            } else {
                $page->addMeta('http-equiv="refresh" content="5"');
            }
        } else {
            //если противник вышел из игры
            $winner = 'noone';
        }
    }

    //имитация отсчета
    if (is_int($existingTimer)) {
        $_SESSION['timer'] -= 1;
        if ($existingTimer == 0) {
            unset($_SESSION['timer']);
        }
        $page->addMeta('http-equiv="refresh" content="1"');
    }
}
$play = $enemy && $writingLog && !$existingTimer;
if ($play) { //бой идет
    //подсчитываем здоровье
    $myHealth = $_SESSION['health'] - $_SESSION['damage'];
    $enHealth = $_SESSION['enemy']['health'] - (isset($_SESSION['enemy']['damage']) ? $_SESSION['enemy']['damage'] : 0);

    //когда здоровье одного из игроков заканчивается - бой окончен
    if ($enHealth <= 0 || $myHealth<= 0) {
        //добавляем себе игроку урон и жизнь
        $sqlParams = 'UPDATE `parameters` SET power = ' . ($_SESSION['power'] + 1) . ', health = ' . ($_SESSION['health'] + 1)
            . ' WHERE user_id = ' . $_SESSION['uid'];
        $msc3 = microtime(true);
        $resParams = mysqli_query($bd, $sqlParams);
        $msc3 = microtime(true) - $msc3;
        addSqlStat($msc3);
        if (!$resParams) {
            $_SESSION['error'] = mysqli_error($bd);
        }

        if ($enHealth <= 0) { //если проиграл противник
            $winner = true;
            $memcached->setOption(Memcached::OPT_COMPRESSION, false);
            $memcached->prepend($_SESSION['logKey'], $_SESSION['name'] . ' убил ' . $_SESSION['enemy']['name']);

            //меняем статус на онлайн и добавляем рейтинг
            $sqlStatus = 'UPDATE `players` SET status = 1, rating = ' . ($_SESSION['ur'] + 1)
                . ' WHERE user_id = ' . $_SESSION['uid'];
            $msc4 = microtime(true);
            $resStatus = mysqli_query($bd, $sqlStatus);
            $msc4 = microtime(true) - $msc4;
            addSqlStat($msc4);
            if (!$resStatus) {
                $_SESSION['error'] = mysqli_error($bd);
            }
        } else { //если проиграл игрок
            $myHealth = 0;
            $winner = false;

            //меняем статус на онлайн и убавляем рейтинг
            $sqlStatus = 'UPDATE `players` SET status = 1, rating = ' . ($_SESSION['ur'] - 1)
                . ' WHERE user_id = ' . $_SESSION['uid'];
            $msc5 = microtime(true);
            $resStatus = mysqli_query($bd, $sqlStatus);
            $msc5 = microtime(true) - $msc5;
            addSqlStat($msc5);
            if (!$resStatus) {
                $_SESSION['error'] = mysqli_error($bd);
            }
        }

        //удаляем устаревшие данные из memcached
        $memcached->delete('player_' . $uid);
        $tmpLogs = $memcached->get('logs');
        $tmpLogs = array_diff($tmpLogs, [$_SESSION['logKey']]);
        $memcached->set('logs', $tmpLogs);

        $enemy = false;
        $page->deleteAllMeta();
        unset($_SESSION['ur']);
    }
}

echo $page->getBeforeContent();
?>
    <div class="container">
        <div class="rtn-btn">
            <a class="orange" href="room.php"><input class="btn-orange" type="button" value="Вернуться"></a>
        </div>
        <div class="card me">
            <div class="player">
                <?php if ($play) { ?>
                    <div class="health-progress"><span style="width: <?php
                        echo $myHealth * 100 / $_SESSION['health'];
                        ?>%;" class="health"></span></div>
                <?php } ?>
                <div class="inner-card <?php echo $play ? '' : 'waiting'; ?>">
                    <span><?php echo $play
                            ? $_SESSION['name'] . '<br />Урон: ' . $_SESSION['power'] . '<br />Здоровье: ' . $myHealth
                            : $_SESSION['name']; ?></span>
                </div>
            </div>
        </div>
        <div class="card between">
            <?php if ($existingTimer) { ?>
                <div class="inner-bg inner-bg-first"></div>
                <div class="inner-bg inner-bg-second"></div>
            <?php } ?>
            <div class="indicators">
                <?php if ($play) { ?>
                    <form name="attack" method="post">
                        <div class="btn-container">
                            <input type="hidden" name="<?php echo $uid; ?>" value="<?php echo $attackValue; ?>">
                            <input class="pretty attack" type="submit" <?php echo $attack ? '' : 'disabled'; ?> value="Атаковать">
                        </div>
                    </form>
                <?php } else { ?>
                    <div class="inner-card waiting">
                        <?php echo $existingTimer
                            ? "<span class='round-timer'>{$_SESSION['timer']}</span>"
                            : "<span>&nbsp;</span>"; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="card enemy">
            <div class="player">
                <?php if ($play) { ?>
                    <div class="health-progress"><span style="width: <?php
                        echo $enHealth * 100 / $_SESSION['enemy']['health'];
                        ?>%;" class="health"></span></div>
                <?php } ?>
                <div class="inner-card <?php echo $play ? '' : 'waiting'; ?>">
                    <span><?php echo $enemy
                            ? ($play
                                ? $_SESSION['enemy']['name'] . '<br />Урон: ' . $_SESSION['enemy']['power'] . '<br />Здоровье: ' . $enHealth
                                : $_SESSION['enemy']['name'])
                            : '?' ; ?></span>
                </div>
            </div>
        </div>
        <?php if ($play) { ?>
            <div class="log-container">
                <textarea rows="15" cols="80" readonly><?php
                    echo (string) str_replace([$_SESSION['name'] . ' ударил', 'ударил ' . $_SESSION['name']], ['Вы ударили', 'ударил вас'], $stringLog);
                    ?></textarea>
            </div>
        <?php } ?>

        <?php if (isset($winner)) { ?>
            <div class="container__winner-msg">
                <div style="border-color: <?php echo is_string($winner) || !$winner ? 'silver' : 'darkgoldenrod'; ?>;" class="winner-message">
                    <div class="win-msg"><span><?php echo is_string($winner) ? 'Противник покинул бой'  : ($winner ? 'Вы победили!' : 'Вы проиграли'); ?><span></div>
                    <div class="btn-container">
                        <a href="room.php"><input class="pretty rest" type="button" value="Вернуться в покои"></a>
                    </div>
                </div>
            </div>
        <?php } ?>

    </div>
<?php
$finish = microtime(true) - $start;
$delta = formatDelta($finish);
echo "<div class='tech'>page:{$delta}ms;{$reqsBD}req({$deltaBD}ms)</div>";