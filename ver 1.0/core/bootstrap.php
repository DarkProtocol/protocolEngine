<?php

class ProtocolEngine{

	public
		$config, //Файл конфига
		$app,	//Переменная для текущего приложения
		$current_url, //Текущий урл
		$route_vars; //переменные роута

	public function __construct($config)
	{
		$this->config = $config;
		$this->current_url = urldecode(preg_replace('/\?.*/iu','', $_SERVER['REQUEST_URI']));

		//Метод роутинга, там определение текущей ссылки и сравнение со ссылкой роут
		$this->process_route();

		//метод баз данных
		$this->process_db();

		//Метод л/для подключений функций безопасности
		$this->secure();
		
		//метод вызова контроллера или вьюхи
		$this->process_controller();


		//покдлючаем модули, которые в конфиге и в роуте
		$this->include_modules();

	}

	//finding path and controller of path

	public function process_route()
	{

		$routes = require(MAIN_DIR . '/routes.php'); 

		for( $i = 0; $i < count($routes); $i++ )
		{

			$route = $routes[$i];
			$path = $route['path'];

			//разделим ссылку на перемнные роута и тд
			$url_arr = $this->convert_url($path);
			

			//а теперь свалидируем
			$validate_arr = $this->validate_url($url_arr, $this->current_url);


			if ( $validate_arr['status'] )
			{
				//Передадим перменные с роута и сохраним
				$this->route_vars = $validate_arr['vars'];

				if ( isset($route['controller']) )
				{
					$this->app = array(
						'path' => $path,
						'controller' => $route['controller'],
						'method' => $route['method']
					);

					//если есть модули
					if( isset($route['modules']) )
						$this->app['modules'] = $route['modules'];

				}
				elseif ( isset($route['view']) )
				{
					$this->app = array(
						'path' => $path,
						'view' => $route['view'],
						'vars' => $this->route_vars
					);

					//если есть модули
					if( isset($route['modules']) )
						$this->app['modules'] = $route['modules'];
				}
				break;
			}

		}

	}

	//Inluding route conroller
	public function process_controller()
	{
		if ( !empty($this->app) && isset($this->app['controller']) )
		{

			require(MAIN_DIR . '/app/controllers/' . $this->app['controller'] . '.php');
			$controller_name = $this->app['controller'];
			$method_name = $this->app['method'];
			

			$controller = new $controller_name();

			$controller->$method_name($this->route_vars);
			
		}
		elseif ( !empty($this->app) && isset($this->app['view']) )
		{
			view( $this->app['view'], $this->app['vars']);
		}
		else
		{
			echo "404! Not Found";
			echo '</br>' . $this->current_url;
		}

	}

	public function process_db()
	{

		require('system/db.php');

		$db_arr = [

			'db_host' => $this->config['db_host'],
			'db_name' => $this->config['db_name'],
			'db_user' => $this->config['db_user'],
			'db_password' => $this->config['db_password']
		];

		DB::connect($db_arr);

	}

	public function secure()
	{
		csrf();
		scaner_banner();
		secure_headers();

	}


	public function include_modules()
	{

		if( isset($this->app['modules']) )
		{
			$route_modules = $this->app['modules'];
			for ( $i = 0; $i < count($route_modules);$i++ )
			{
				require_once('modules/'.$route_modules[$i].'.module.php');
			}
		}
		
		if ( isset($this->config['main_modules']) )
		{
			$main_modules = $this->config['main_modules'];
			for ( $i = 0; $i < count($main_modules);$i++ )
			{
				require_once('modules/'.$main_modules[$i].'.module.php');
			}
		}

	}


	//конвертирование url

	public function convert_url( $url )
	{
		$open_brackets = [];
		$close_brackets = [];
		$url_arr = [
			'url' => '',
			'parts' => [],
			'vars'=> [] 
		];

		//найдем и запишем места фигурных скобок в роуте
		for( $i = 0; $i < strlen($url); $i++ )
		{

			if ( $url[$i] == '{' )
				$open_brackets[] = $i;
			elseif ( $url[$i] == '}' )
				$close_brackets[] = $i;
		}

		//если сходятся разделительные фигурные скобки
		if ( count($open_brackets) == count($close_brackets) )
		{
			$url_arr['url'] = $url;

			//если есть переменные
			if ( count($open_brackets) !== 0 )
			{
				//разделитель, по которму будет обрезать строки
				$separator = ':';

				for( $i = 0; $i < count($open_brackets); $i++ )
				{	
					//на места фигурных скобок разделители
					$start_len = $open_brackets[$i] + 1;
					$var_len = $close_brackets[$i] -  $start_len;
					$var = substr($url, $start_len, $var_len);
					$url_arr['vars'][] = $var;
					
				}

				//теперь все обрезаем
				$url = preg_replace('/\{.+?\}/', $separator, $url);
				$url_arr['parts'] = explode($separator, $url);

				//очистим пустые символы в массиве
				$clean = false;
				while ( true )
				{
					$find = array_search('', $url_arr['parts']);
					if( $find === false )
						break;
					array_splice($url_arr['parts'],$find, 1);

				}
			}

			return $url_arr;
		}
		else
		{
			exit('Error vars in Route!');
		}
	}

	//валидация урл

	public function validate_url( $url_arr, $current_url )
	{
		$validate_arr = [
			'status' => false, //статус ссылки, по умолчанию не подходит 
			'vars' => []
		];

		//Првоерим, простая ли ссылка или с перемнными
		if ( empty($url_arr['vars']) && empty($url_arr['parts']) && !empty($url_arr['url']) )
		{	
			$url = $url_arr['url'];

			//проверим, есть ли у роута в конце слеш
			//если нет, то добавим
			$last_simmbol = strlen($url) - 1;
			if( $url[$last_simmbol] != '/' )
				$url .= '/';

			//Если ссылки схожи, значит пропускаем
			if ( $url == $current_url )
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


}