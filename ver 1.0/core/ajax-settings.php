<?php

//Подключим функции и созаддим бд, а так же все базовые контроллеры

require_once('core/functions.php');
require_once('core/system/db.php');
require_once('core/system/BaseController.php');
require_once('core/system/Controller.php');
require_once('core/system/Model.php');

$config = require_once('config.php');

//создаем переменную с работой бд

$db_arr = [

		'db_host' => $config['db_host'],
		'db_name' => $config['db_name'],
		'db_user' => $config['db_user'],
		'db_password' => $config['db_password']
	];

DB::connect($db_arr);