<?php
	
class DB 
{

	protected static $PDO;

	//Соединение с бд
	public static function connect($db_arr)
	{

		$db_host = $db_arr['db_host'];
		$db_name = $db_arr['db_name'];
		$db_user = $db_arr['db_user'];
		$db_password = $db_arr['db_password'];

		$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8";
		$opt = array(
		    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		    PDO::ATTR_PERSISTENT 		 => true,
		    PDO::ATTR_EMULATE_PREPARES	 => true
		);

		self::$PDO = new PDO($dsn, $db_user, $db_password, $opt);


	}

	//Функция обычноого запроса, если есть селект 
	//то в $fetch заносим true, тобы выелся результат
	public static function query($query)
	{
		$stmt = self::$PDO->query( $query );
		$data = $stmt->fetchAll();
		return $data;

	}

	public static function select( $select = false, $from, $where = false, $like = false, $limit = false )
	{

		$query = "SELECT ";

		//Разберемся с параметром селект
		//Если предали не ложь, тогда будут знаечния, а не *
		if ( $select !== false )
		{
			//Если массив, то будет несколько значений
			if ( is_array($select) )
			{
				for ( $i = 0; $i < count($select); $i++ )
				{
					if ( $i == (count($select) - 1) )
						$query .= "$select[$i] ";
					else
						$query .= "$select[$i], ";
				}
			}
			//Если перменная, то одно значение
			else
			{
				$query .= "$select ";
			}
		}
		else
		{
			$query .= "* ";
		}

		//Разберемся с параметром from
		if ( $from !== false )
		{	
			$query .= "FROM ";
			//Если массив, то будет несколько значений
			if ( is_array($from) )
			{
				for ( $i = 0; $i < count($from); $i++ )
				{
					if ( $i == (count($from) - 1) )
						$query .= "$from[$i] ";
					else
						$query .= "$from[$i], ";
				}
			}
			//Если перменная, то одно значение
			else
			{
				$query .= "$from ";
			}
		}
		else
		{
			return false;
		}

		//А теперь и с where
		//И начнем с prepare

		if ( $where !== false )
		{
			$query .= "WHERE ";
			foreach ($where as $key => $value) 
			{
				//Если это последний жлменет массива, тогда не пишем AND
				if ( next($where) === false )
					$query .= "$key = :$key ";
				else
					$query .= "$key = :$key AND ";
			}
		}

		//Теперь like

		if ( $like !== false )
		{	
			if ( $where !== false )
				$query .= "AND ";
			else
				$query .= "WHERE ";
			
			foreach ($like as $key => $value) 
			{
				//Если это последний жлменет массива, тогда не пишем AND
				if ( next($like) === false )
					$query .= "$key LIKE :$key ";
				else
					$query .= "$key LIKE :$key AND ";
			}
		}

		//И про limit не забудем

		if ( $limit !== false )
		{

			$query .= "LIMIT ";
			if ( is_array($limit) )
				$query .= ":LIMIT1, :LIMIT2";
			else
				$query .= ":LIMIT";
		}

		$stmt = self::$PDO->prepare($query);

		//execute
		//Начнем с where
		if ( $where !== false )
			self::ArrBindParam($where, $stmt);

		//Теперь like

		if ( $like !== false )
		{
			foreach ($like as $key => $value)
				$stmt->bindValue(":$key", "%$like[$key]%");

		}

		//limit

		if ( $limit !== false )
		{
			if ( is_array( $limit ) )
			{
				for ( $i = 0; $i < count($limit); $i++ )
				{
					$a = $i + 1;
					$stmt->bindParam( ":LIMIT$a", $limit[$i], PDO::PARAM_INT);
				}
			}
			else
				$stmt->bindParam( ":LIMIT", $limit, PDO::PARAM_INT);
		}

		$stmt->execute();
		$data = $stmt->fetchAll();

		return $data;

	}

	public static function insert($table, $data)
	{

		//Формируем prepare запрос
		$query = "INSERT INTO $table (";

		foreach ($data as $key => $value) 
		{
			if ( next($data) === false )
				$query .= "$key) ";
			else
				$query .= "$key, ";
		}

		$query .= "VALUES (";

		foreach ($data as $key => $value) 
		{
			if ( next($data) === false )
				$query .= ":$key)";
			else
				$query .= ":$key, ";
		}

		$stmt = self::$PDO->prepare($query);

		//формируем execute
		self::ArrBindParam($data, $stmt);

		$insert = $stmt->execute();

		if ( $insert )
			return self::$PDO->lastInsertId();
		else 
			return $insert;

	}

	public static function update($table, $data, $where = false, $like = false)
	{

		$query = "UPDATE $table SET ";

		//Prepare
		foreach ($data as $key => $value) {
			if ( next($data) == false )
				$query .= "$key = :$key ";
			else
				$query .= "$key = :$key, ";
		}

		if ( $where !== false )
		{
			$query .= "WHERE ";
			foreach ($where as $key => $value) 
			{
				//Если это последний жлменет массива, тогда не пишем AND
				if ( next($where) === false )
					$query .= "$key = :$key ";
				else
					$query .= "$key = :$key AND ";
			}
		}


		if ( $like !== false )
		{
			$query .= "AND ";
			foreach ($like as $key => $value) 
			{
				//Если это последний жлменет массива, тогда не пишем AND
				if ( next($like) === false )
					$query .= "$key LIKE :$key ";
				else
					$query .= "$key LIKE :$key AND ";
			}
		}

		$stmt = self::$PDO->prepare($query);

		//execute
		//data различается от where только перменной, поэтому можем использовать
		self::ArrBindParam($data, $stmt);

		//where
		if ( $where !== false )
		{
			self::ArrBindParam($where, $stmt);
		}

		//Теперь like

		if ( $like !== false )
		{
			foreach ($like as $key => $value)
				$stmt->bindValue(":$key", "%$like[$key]%");

		}

		$update = $stmt->execute();
		return $update;

	}

	public static function delete($table, $where, $like = false)
	{

		$query = "DELETE FROM $table WHERE ";

		foreach ($where as $key => $value) 
		{
			//Если это последний жлменет массива, тогда не пишем AND
			if ( next($where) === false )
				$query .= "$key = :$key ";
			else
				$query .= "$key = :$key AND ";
		}


		if ( $like !== false )
		{
			$query .= "AND ";
			foreach ($like as $key => $value) 
			{
				//Если это последний жлменет массива, тогда не пишем AND
				if ( next($like) === false )
					$query .= "$key LIKE :$key ";
				else
					$query .= "$key LIKE :$key AND ";
			}
		}

		$stmt = self::$PDO->prepare($query);

		//execute

		self::ArrBindParam($where, $stmt);

		//Теперь like

		if ( $like !== false )
		{
			foreach ($like as $key => $value)
				$stmt->bindValue(":$key", "%$like[$key]%");

		}

		$delete = $stmt->execute();
		dd($delete);

	}

	//execute functions
	public function ArrBindParam($arr, $stmt)
	{
		foreach ($arr as $key => $value) 
		{
			switch($arr[$key][1])
			{

				case 'int':
					$stmt->bindParam(":$key", $arr[$key][0], PDO::PARAM_INT);
					break;

				case 'str':
					$stmt->bindParam(":$key", $arr[$key][0], PDO::PARAM_STR);
					break;

				case 'bool':
					$stmt->bindParam(":$key", $arr[$key][0], PDO::PARAM_BOOL);
					break;

				default:
					$stmt->bindParam(":$key", $arr[$key][0], PDO::PARAM_STR);
					break;

			}
		}
	}

}