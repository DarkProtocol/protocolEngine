<?php

//Самые важные функции

function dd(...$vars)
{
	for( $i = 0; $i < count($vars); $i++ )
	{
		echo '<pre>';
		var_dump($vars[$i]);
		echo '</pre>';
	}
}

function view( $view, $vars = array() )
{
	if ( $vars )
	{
		foreach ($vars as $key => $value) {
			${$key} = $value;
		}
	}

	require( APP_DIR . '/views/'. $view . '.php');
}



function validate_url( $url_arr, $current_url )
{
	$validate_arr = [
		'status' => false, //статус ссылки, по умолчанию не подходит 
		'vars' => []
	];

	//Првоерим, простая ли ссылка или с перемнными
	if ( empty($url_arr['vars']) && empty($url_arr['parts']) && !empty($url_arr['url']) )
	{
		//Если ссылки схожи, значит пропускаем
		if ( $url_arr['url'] == $current_url )
		{
			$validate_arr['status'] = true;
			return $validate_arr;
		}
		else
		{
			return $validate_arr;
			return false;
		}

	}
	//Если пришел массив с перемнными 
	elseif ( !empty($url_arr['vars']) && !empty($url_arr['parts']) )
	{
		$separator = ':'; //Разделитель
		$separator_url = $current_url; //Строкка, которую удем изменять

		for ( $i = 0; $i < count($url_arr['parts']);  $i++ )
		{	
			//Найдем первое вхождение строки из массива в текущий урл
			$pos = strpos($separator_url,$url_arr['parts'][$i]);
			//проверим, есть ли подстроки полученные из convert_url() в текущей ссылке
			//если да, то поменяем их вхождения на разделитель, чтобы потом занести в перменные
			if ( $pos !== false )
			{
				$separator_url = substr_replace($separator_url, $separator, $pos, strlen($url_arr['parts'][$i]));
			}
			//если нет, то текущая ссылка не та, что указана в роуте
			//Значитвыходим из функции и не изменяем наш итоговый массив
			else
			{	
				return $validate_arr;
				return false;
			}

		}
		//Занесем перемнные из строки во временный масив
		$tmp_vars_arr['vars'] = explode($separator, $separator_url);

		// чистим его от пустотых значений, которые могут быть
		$clean = false;
		while ( true )
		{
			$find = array_search('', $tmp_vars_arr['vars']);
			if( $find === false )
				break;
			array_splice( $tmp_vars_arr['vars'], $find, 1);

		}

		
		//проверим, совпадает ли по перменным
		//Если да, продолжаем веселье
		if ( count($url_arr['vars']) ==  count($tmp_vars_arr['vars']))
		{
			//Ссылка нам сто процентов подходит
			$validate_arr['status'] = true;
			//Даем имена переменным, а заодно и присвоим им значения
			for ($i = 0; $i < count($url_arr['vars']); $i++) 
			{ 
				$validate_arr['vars']["{$url_arr['vars'][$i]}"] = $tmp_vars_arr['vars'][$i];
			}

		}
		//если нет, то текущая ссылка не та, что указана в роуте
		else
		{
			return $validate_arr;
			return false;
		}

		return $validate_arr;
	}
	//Если вообще пришла фигня
	else{
		exit("Error url_arr in validate_url");
	}
}

//Функции для безопасности
function csrf()
{
	if ( isset($_SESSION['csrf_token']) )
		return $_SESSION['csrf_token'];
	else
	{
		$bytes = random_bytes(32);
		$_SESSION['csrf_token'] = bin2hex($bytes);
		return $_SESSION['csrf_token'];
	}
}

//Забаним сканеры

function scaner_banner(){
	//Паттерн для сканеров
	$pattern = "/(nmap|nikto|wikto|sf|sqlmap|bsqlbf|w3af|acunetix|havij|appscan)/";
	//Если сканер
	if ( preg_match($pattern, $_SERVER['HTTP_USER_AGENT']) )
	{
		exit('No pasaran!');
	}
}

//Обработаем заголовки

function secure_headers()
{
	
	//Это спасет наш ресурс от возможного DDOS'a через iframe
	header("X-Frame-Options: DENY");

	//Это скажет IE, что нет необходимости автоматически определять Content-Type,
	header("X-Content-Type-Options: nosniff");

	//Так же заголовок для IE. Активирует встроенную XSS-защиту.
	header("X-XSS-Protection '1; mode=block;'");

	//Раскомментировать при перезде на https!
	//header("Strict-Transport-Security: max-age=expireTime");

	//Ну а теперь самое главное
	//Здесь будем разрешать библиотеки для скриптов
	$default = "Content-Security-Policy:";
	$firefox = "X-Content-Security-Policy:";
	$safari = "X-Webkit-CSP:";

	$params	="default-src 'self' pp.vk.me scontent.xx.fbcdn.net;"
		."script-src 'self' 'unsafe-inline' www.google.com www.gstatic.com;"
		."style-src 'self' 'unsafe-inline';"
		."frame-src www.google.com;";

	header($default . $params);
	header($firefox . $params);
	header($safari . $params);

}


//Текущий урл
function current_url()
{
  $url = ''; // Пока результат пуст
  $port = 80; // Порт по-умолчанию
 
  //какое соединение и порт
  if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] =='on')) {
    $url .= 'https://';
    $port = 443;
  } 
  else 
  {
    $url .= 'http://';
  }

  $url .= $_SERVER['SERVER_NAME'];
 
  //если порт не дефолтный
  if ($_SERVER['SERVER_PORT'] != $port) 
  {
    $url .= ':'.$_SERVER['SERVER_PORT'];
  }

  //добавляем параметры
  $url .= $_SERVER['REQUEST_URI'];
  return $url;

}


//функция текущего адреса сайта
function site_url()
{
	$url = '';

	if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] =='on'))
    	$url .= 'https://';
    else
    	$url .= 'http://';

    $url .= $_SERVER['SERVER_NAME'];
    return $url;

}

//функция подключение модуля

function get_module( $module )
{
	require_once(__DIR__."/modules/$module.module.php");
}

//функция для проверки логина
function is_login()
{
	$login = false;

	if( isset($_SESSION['current_user']) )
	{
		if ( $_SESSION['current_user']['IP'] == $_SERVER['REMOTE_ADDR'] )
			$login = true;
	}	

	return $login;

}

function logout()
{
	unset($_SESSION['current_user']);
}

//перенаправление

function redirect($link)
{
	header("Location: $link");
}

function current_user()
{	
	$user = [
		'type' => $_SESSION['current_user']['type'],
		'ID' => $_SESSION['current_user']['ID'],
		'user_hash' => $_SESSION['current_user']['user_hash']
	];

	return $user;
}