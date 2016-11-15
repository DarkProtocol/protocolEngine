<?php

define('APP_DIR', __DIR__ .'/app');
define('MAIN_DIR', __DIR__);

return [
	'debug' => false,
	'db_host' => '',
	'db_name' => '',
	'db_user' => '',
	'db_password' => ''
];

/*

Подсказка

return [
	'debug' => false, - дебаг
	'db_host' => 'localhost', хост бд
	'db_name' => 'kbtour',	имя бд
	'db_user' => 'root',	пользователь бд
	'db_password' => '1234',	пароль бд
	'main_modules' => ['test1', 'test2'] важные модули, которые нудны подключать
];

модули лежат в папке core/modules
пример имени файла модуля name.module.php
достаточно писать только имя для подключения, то есть name

*/