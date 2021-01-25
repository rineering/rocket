<?php
/*memcached*/
$memcached = new Memcached();
$memcached->addServer('local.rocket', 11211);

/*БД MySql*/
$bd = mysqli_connect(
    '127.0.0.1',
    'rocket',
    '******',
    'localrocket'
);
if ($err = mysqli_connect_error()){
    die("Ошибка: Невозможно подключиться к MySQL " . $err);
}
mysqli_set_charset($bd, "utf8");