<?php

//Берем все данные, конфиг и сам экшн из аякс запроса
require('../core/ajax-settings.php');
$config_arr = require('../ajax-config.php');
$action = $_REQUEST['action'];

foreach ($config_arr as $key => $value) 
{
	//Если экшн из аякс равен ключу массива конфига
	if ( $key == $action )
	{
		//Подключаем файл с функцией
		require_once( APP_DIR . '/controllers/' .$value['controller']. '.php' );

		//создаем класс
		$controller_name = $value['controller'];
		$controller = new $controller_name();

		//Выполняем метод из конфига
		$method_name = $value['method'];
		$controller->$method_name();

		//и нам уже не нужен цикл
		break;
	}
}
