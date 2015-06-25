<?php

/**
 *  @class DbLayer
 *  @Revision $Revision: 4535 $
 *
 */

class DbLayer
{
	public static $lock = FALSE ;
	protected $_link = array() ;
	protected $_link_autocommit = array() ;

	public $_cumulateProcessTime = 0.0;
	public $_numberQuery = 0;
	public $_numberRows = 0;
	public $last_query = null;

	public static $queries_memory_leak  = array();
	public static $duplicate_queries	= array();
	public static $slow_queries			= array();
	public static $queries = array();
	private static $sql_manipulation=array('insert','update','delete','truncate','drop','alter','create');
	private static $last_error = null;

	/*
	 * Structure array de connexion
	 *
	 [ 'dbname' ]		= 'database name'
	 [ 'charset' ]	   = 'charset name'
	 [ 'users_id' ]	  = 'user id'

	 [ 'rw' ][ 'host' ]  = 'hostname'
	 [ 'rw' ][ 'user' ]  = 'user name'
	 [ 'rw' ][ 'pwd' ]   = 'password'
	 [ 'rw' ][ 'ctype' ] = 'connexion type [persistent|NULL]'

	 [ 'r' ][ 'host' ]   = 'hostname'
	 [ 'r' ][ 'user' ]   = 'user name'
	 [ 'r' ][ 'pwd' ]	= 'password'
	 [ 'r' ][ 'ctype' ]  = 'connexion type [persistent|NULL]'
	 */
	public function __construct( array $config )
	{
		if(is_array(self::$sql_manipulation))
		{
			// pre implode
			self::$sql_manipulation = implode('|',self::$sql_manipulation);
		}

		$con  = array( 'rw', 'r' ) ;

		for( $i = 0 ; $i < count( $con ) ; $i++  )
		{
			$setConnection = array() ;

			$setConnection[ 'dbname' ] = $config[ 'dbname' ] ;
			if( isset( $config[ $con[ $i ] ] ) )
			{
				$setConnection[ 'host' ] = $config[ $con[ $i ] ][ 'host' ] ;
				$setConnection[ 'user' ] = $config[ $con[ $i ] ][ 'user' ] ;
				$setConnection[ 'pwd' ]  = $config[ $con[ $i ] ][ 'pwd' ] ;
				$setConnection[ 'port' ] = !empty($config[ $con[ $i ] ][ 'port' ]) ? $config[ $con[ $i ] ][ 'port' ] : '3306';

				$this->db_connect(
					$con[ $i ],
					$setConnection[ 'host' ],
					$setConnection[ 'user' ],
					$setConnection[ 'pwd' ],
					$setConnection[ 'dbname' ],
					isset($config[ $con[ $i ] ][ 'ctype' ]) && $config[ $con[ $i ] ][ 'ctype' ] === 'persistent',
					$setConnection[ 'port' ]
				) ;

				if( isset( $config[ 'users_id' ] ) )
				{
					$this->_db_setUsersId( $config[ 'users_id' ] ) ;
				}

				if( isset( $config[ 'charset' ] ) )
				{
					$this->_db_setCharset( $con[ $i ], $config[ 'charset' ] ) ;
				}
			}
		}
	}
	//---------------------------------------------------------------------
	public function __destruct()
	{
		foreach( $this->_link as $rw => $link )
		{
			if($link->thread_id !== null)
			{
				if( $rw == 'rw' )
				{
					$this->db_rollback() ;
				}

				$link->close() ;
			}
		}
	}
	//---------------------------------------------------------------------
	public function db_connect( $rw, $host, $user, $pwd, $dbname, $persistent = false, $port = '3306')
	{
		// @codeCoverageIgnoreStart
		if ( !function_exists( 'mysqli_connect' ) )
		{
			throw new Exception( 'fonction PHP MySQL non disponible' ) ;
		}
		// @codeCoverageIgnoreEnd

		try
		{
			$this->_link[ $rw ] = new mysqli(($persistent ===  TRUE ? 'p:' : '').$host, $user, $pwd, $dbname, $port);
		}
		catch (Exception $e)
		{
			throw new SQLConnectionException( $e->getMessage() ) ;
		}

		if($rw == 'rw')
		{
			// mode transactionnel desactivé par défaut
			$this->_link_autocommit[ 'rw' ] = true;
		}

		return true ;
	}
	//---------------------------------------------------------------------
	public function db_use( $rw, $dbname )
	{
		if (  $this->_link[ $rw ]->select_db( $dbname ) === false )
		{
			throw new Exception( 'Echec USE ' . $dbname ) ;
		}

		return true ;
	}
	//---------------------------------------------------------------------
	public function db_insert_id(&$id, $rw = 'rw')
	{
		$id = $this->_link[$rw]->insert_id;

		return true;
	}
	//---------------------------------------------------------------------
	public function db_select( $query, &$result, &$rows=0)
	{
		if( preg_match( '/^\s*('.self::$sql_manipulation.')/i', $query ) != 0 )
		{
			throw new SQLOperationException( 'Opérations d\'écriture interdite avec db_select' ) ;
		}

		// force l'utilisation de la connexion lecture seule si disponible
		$this->db_execute($query, $rows, $result, 'r');

		return true;
	}
	//---------------------------------------------------------------------
	public function db_execute( $query, &$rows = 0, &$result=null, $rw='rw')
	{
		$this->last_query = $query;
		$this->_numberQuery++;

	 	if( $rw == 'r' && !isset( $this->_link[ 'r' ] ))
		{
			 // passage automatique sur la connexion lecture/écriture
			$rw = 'rw' ;
		}

		if( !isset( $this->_link[ $rw ] ) )
		{
			throw new SQLOperationException( 'Aucune connexion flaguée "'.$rw.'" disponible') ;
		}

		if( preg_match( '/^\s*('.self::$sql_manipulation.')/i', $query ) != 0 )
		{
			if($rw=='r')
			{
				throw new SQLOperationException( 'Utilisation dangereuse d\'une connexion en lecture seule pour une opération d\'écriture') ;
			}

			// on est pas encore en transactionnel
			if($this->_link_autocommit[ 'rw' ] === 'auto')
			{
				// passage en mode transactionnel
				$this->_link[ 'rw' ]->autocommit(false);
				
				self::$lock = TRUE;

				$this->_link_autocommit[ 'rw' ] = false;

				Debug::log('Db begin transaction');
			}
		}

		$result = NULL ;

		//---Time counter START
		$start_time = microtime(true);

		//---Query
		$result = $this->_link[ $rw ]->query($query) ;

		//---Time counter END
		$query_time=microtime(true) - $start_time;

		$this->_cumulateProcessTime += $query_time;

		if(DISPLAY_ERRORS)
		{
			//backtrace
			$file='unknown_file';
			$line='unknown_line';

			$bt=debug_backtrace(false);

			if(isset($bt[1]))
			{
				if(isset($bt[1]['file'])) $file=$bt[1]['file'];
				if(isset($bt[1]['line'])) $line=$bt[1]['line'];
			}

			$stack = array();
			foreach($bt AS $call_id => $call)
			{
				$stack[$call_id]['file'] = isset($call['file']) ? $call['file'] : '';
				$stack[$call_id]['line'] = isset($call['line']) ? $call['line'] : '';
				$stack[$call_id]['function'] = isset($call['function']) ? $call['function'] : '';
			}

			//log query
			self::$queries[] = array('query' => $query, 'stack' => $stack);

			if($query_time>TRACE_SLOW_QUERIES_TIME_TOLERANCE/1000)
			{
				self::$slow_queries[]=array('query'=>$query,'time'=>$query_time,'file'=>$file,'line'=>$line);
			}

			//duplicate queries
			$md5=md5($query);
			if(!isset(self::$duplicate_queries[$md5])) self::$duplicate_queries[$md5]=array('query'=>$query,'nb'=>0);
			self::$duplicate_queries[$md5]['nb']++;

			//warnings SQL
			$j = $this->_link[$rw]->warning_count;

			if ($j > 0 && THROW_SQL_WARNINGS === TRUE)
			{
				throw new SQLWarningException($this->_link[$rw]->get_warnings()->message);
			}
		}

		if( $result === false )
		{
			$this-> _errorManager( $this->_link[ $rw ] ) ;
			return false ;
		}

		// la requete retourne un resultat (select, show, desc etc)
		if( $result !== false &&  $result !== true)
		{
			$rows = $result->num_rows;
			$this->_numberRows+=$rows;

			if(DISPLAY_ERRORS)
			{
				//memory leak
				self::$queries_memory_leak[]=array('result'=>$result,'request'=>$query,'file'=>$file,'line'=>$line);
			}
		}
		else
		{
			$rows = $this->_link[ $rw ]->affected_rows ;
		}

		return true ;
	}
	//---------------------------------------------------------------------
	public function db_call($procedure, &$result=NULL, $rw='rw')
	{
		if( $rw == 'r' && !isset( $this->_link[ 'r' ] ))
		{
			// passage automatique sur la connexion lecture/écriture
			$rw = 'rw' ;
		}

		if( !isset( $this->_link[ $rw ] ) )
		{
			throw new SQLOperationException( 'Aucune connexion flaguée "'.$rw.'" disponible') ;
		}
		$this->_link[ $rw ]->real_query('CALL ' . $procedure);
		$result = $this->_link[ $rw ]->store_result();
		$this->_link[ $rw ]->next_result();

		if( $result === false )
		{
			$this-> _errorManager( $this->_link[ $rw ] ) ;
			return false ;
		}
	}
	//---------------------------------------------------------------------
	public function db_start()
	{
		// on ne passe pas en transactionnel manuelement, uniquement lors du premier update, delete, insert, etc
		$this->_link_autocommit[ 'rw' ]='auto';

		return true ;
	}
	//---------------------------------------------------------------------
	public function db_escape_string( $str )
	{
		$rw = 'rw';

		if(!isset($this->_link[ $rw ]))
		{
			$rw = 'r';
		}
		return $this->_link[ $rw ]->real_escape_string($str) ;
	}
	//---------------------------------------------------------------------
	public function db_commit()
	{
		if(isset($this->_link_autocommit[ 'rw' ]) && $this->_link_autocommit[ 'rw' ] === false)
		{
			$this->_link[ 'rw' ]->commit();

			Debug::log('Db commit');
		}

		return true ;
	}
	//---------------------------------------------------------------------
	public function db_rollback()
	{
		if(!isset($this->_link_autocommit[ 'rw' ]))
		{
			throw new SQLOperationException( 'Rollback refusé en mode lecture seule' ) ;
		}

		if($this->_link_autocommit[ 'rw' ] === false)
		{
			$this->_link[ 'rw' ]->rollback();

			Debug::log('Db rollback');
		}

		return true ;
	}
	//---------------------------------------------------------------------
	private function _db_setUsersId( $id )
	{
		if($id===0 )
		{
			return;
		}

		if(!($id>=1) )
		{
			throw new Exception( 'id invalide' ) ;
		}

		if(isset($this->_link_autocommit[ 'rw' ]))
		{
			$this->db_execute('SET @users_id='.$id) ;
		}

		return true ;
	}
	//---------------------------------------------------------------------
	private function _db_setCharset( $rw, $charset )
	{
		if(!DONT_SET_NAMES)
		{
			$this->_link[ $rw ]->set_charset($charset);
		}

		return true ;
	}
	//---------------------------------------------------------------------
	public function db_setLockWaitTimeout($timeout)
	{
		if(!is_integer($timeout))
		{
			throw new Exception('Wrong timeout value : ' . $timeout);
		}

		$this->db_select('SET innodb_lock_wait_timeout=' . $timeout, $res, $row);

		return true;
	}
	//---------------------------------------------------------------------
	public function db_stat($handle = null)
	{
		if($handle == null)
		{
			if(isset($this->_link['rw']))
			{
				$handle = $this->_link['rw'];
			}
			else if(isset($this->_link['r']))
			{
				$handle = $this->_link['r'];
			}
			else
			{
				return null;
			}
		}

		if(property_exists($handle, 'stat') && $handle->stat)
		{
			return $handle->stat;
		}

		return null;
	}
	//---------------------------------------------------------------------
	public function db_last_error($handle = null)
	{
		if($handle == null)
		{
			if(isset($this->_link['rw']))
			{
			   $handle = $this->_link['rw'];
			}
			else if(isset($this->_link['r']))
			{
			   $handle = $this->_link['r'];
			}
			else
			{
				return null;
			}
		}

		if($handle->error)
		{
			return self::$last_error = $handle->error.' ('.$handle->errno.')';
		}
		else if(self::$last_error !== null)
		{
			return self::$last_error;
		}

		return null;
	}
	//---------------------------------------------------------------------
	// https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
	private function _errorManager($handle)
	{
		$error_message = sprintf ( "%d - %s", $handle->errno, $handle->error );

		switch ($handle->errno)
		{
			case 1062 :
				throw new SQLDuplicateException ( $error_message );
			break;

			case 1216 :
			case 1452 :
				throw new SQLCreateUpdateException ( $error_message );
			break;

			case 1217 :
			case 1451 :
				throw new SQLDeleteUpdateException ( $error_message );
			break;

			// Custom error
			case 1644 :
				throw new SQLCustomException ( $error_message );
			break;

			default :
				throw new Exception ( $error_message );
			break;
		}

		return FALSE;
	}

	/*
	 *
	 * DEBUG TRACE && DEV TOOLS && DEPRECATED
	 *
	 *
	 */

	// @codeCoverageIgnoreStart

	/**
	 * OBSOLETE
	 * passage en mode transactionnel automatique
	 *
	 * @return boolean
	 */
	//---------------------------------------------------------------------
	static function trace_memory_leak()
	{
		$table="<table class='table_list' style='table-layout: fixed'><tr><th style='text-align:left;width:100px'>Rows</th><th style='text-align:left'>Query</th></tr>";

		$memory_leak=0;
		$total_rows=0;
		foreach(self::$queries_memory_leak as $query)
		{
			try
			{
				if($query['result']->data_seek(0))
				// on tente de déplacer le pointeur à la première ligne du resultat
				// en cas d'echec, le result a été vidé, dans le cas contraire, la mémoire n'a pas été liberée
				// on a désactivé les gestionnaires d'erreur car data_seek(0) generer une erreur fatale en cas d'echec, impossible à catcher...
				{
					$memory_leak++;
	
					$total_rows+=$query['result']->num_rows;
	
					$table.="<tr".($memory_leak%2==0?' style="background-color:#eee;"':'')."><td style='width:100px'>".$query['result']->num_rows."</td><td style='text-align:left;word-wrap:break-word'>".$query['file'].':'.$query['line']."<br/><br/><pre>".$query['request'].'</pre></td></tr>';
	
					$query['result']->free();
				}
			}
			catch (Exception $e) {}
		}

		if($memory_leak!=0)
			echo "<h3 style='margin:5px'>TRACE_SQL_MEMORY_LEAK activé : ".$memory_leak.' requête(s) non libérée(s) ('.($total_rows).' rows) !</h3>'.$table."</table>";
	}
	//---------------------------------------------------------------------
	static function trace_duplicate_queries()
	{
		$table="<table class='table_list' style='table-layout: fixed'><tr><th style='text-align:left;width:100px'>Time</th><th style='text-align:left'>Query</th></tr>";

		$i=0;
		foreach(self::$duplicate_queries as $query)
		{
			if($query['nb']>=TRACE_DUPLICATE_QUERIES_TOLERANCE)
			{
				$i++;
				$table.="<tr".($i%2==0?' style="background-color:#eee;"':'')."><td style='width:100px'>".$query['nb']."</td><td style='text-align:left;word-wrap:break-word'><pre>".$query['query'].'</pre></td></tr>';
			}
		}

		if($i!=0)
	   		echo "<h3 style='margin:5px'>TRACE_DUPLICATE_QUERIES activé : ".$i." requête(s) exécutée(s) plus de ".TRACE_DUPLICATE_QUERIES_TOLERANCE." fois !</h3>".$table."</table>";
	}
	//---------------------------------------------------------------------
	static function trace_slow_queries()
	{
		if(count(self::$slow_queries)==0) return true;

		$table="<table class='table_list' style='table-layout: fixed'><tr><th style='text-align:left;width:100px'>Count</th><th style='text-align:left'>Query</th></tr>";

		foreach(self::$slow_queries as $i=>$query)
		{
			$table.="<tr".($i%2==0?' style="background-color:#eee;"':'')."><td style='width:100px'>".round($query['time']*1000,3)."ms</td><td style='text-align:left;word-wrap:break-word'>".$query['file'].':'.$query['line']."<br/><br/><pre>".$query['query'].'</pre></td></tr>';
		}

		echo "<h3 style='margin:5px'>TRACE_SLOW_QUERIES activé : ".count(self::$slow_queries)." requête(s) exécutée(s) ont duré plus de ".TRACE_SLOW_QUERIES_TIME_TOLERANCE."ms !</h3>".$table."</table>";
	}
	//---------------------------------------------------------------------
	static function trace_queries()
	{
		echo '<table class="table_list" style="table-layout: fixed"><tr><th style="text-align:left">Query</th></tr>';
		foreach(self::$queries AS $i => $query)
		{
			echo "<tr".($i%2==0?' style="background-color:#eee;"':'').">";
			echo "<td style='text-align:left;word-wrap:break-word'>";
			foreach($query['stack'] AS $stack_id => $stack)
			{
				echo $stack['file'].':'.$stack['line'].' - ' . $stack['function'] . "<br/>";
			}
			echo "<br/><pre>".$query['query'].'</pre></td></tr>';
		}
		echo '</table>';
	}

	public function fail_on_warning()
	{
		$j = $this->_link['rw']->warning_count;

		if ($j > 0)
		{
			throw new SQLWarningException($this->_link['rw']->get_warnings()->message);
		}
	}
	// @codeCoverageIgnoreEnd
}
