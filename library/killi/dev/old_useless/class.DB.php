<?php

class DB
{
	private $_hDB        = NULL ;
	private $_hDBs       = NULL ;
	private $_hDBb       = NULL ;
	private $_DBhost     = NULL ;
	private $_DBuser     = NULL ;
	private $_DBpwd      = NULL ;
	private $_DBname     = NULL ;
	private $_DBacommit  = NULL ;
	private $_DBThreadId = NULL ;
	private $_DBshost    = NULL ;
	private $_DBsuser    = NULL ;
	private $_DBspwd     = NULL ;
	private $_DBsname    = NULL ;
	private $_result     = NULL ;

	public $_cumulateProcessTime = 0.0;

	//-------------------------------------------------------------------------
	function __construct()
	{
		unset( $this->_hDB ) ;
		unset( $this->_hDBs ) ;
	}
	//-------------------------------------------------------------------------
	function __destruct()
	{
		$this->disconnect();
	}
	//-------------------------------------------------------------------------
	function forceMaster()
	{
		$this->_hDBb = $this->_hDBs ;
		$this->_hDBs = $this->_hDB ;
	}
	//-------------------------------------------------------------------------
	function unforceMaster()
	{
		if( isset( $this->_hDBb ) )
		{
			$this->_hDBs = $this->_hDBb ;
		}
	}
	//-------------------------------------------------------------------------
	function slaveConnection( $hostname, $user, $password, $database )
	{
		if( !isset( $hostname ) || $hostname == "" )
		{
			trigger_error( '[ERROR] <$hostname> invalide' ) ;
			return -1 ;
		}

		if( !isset( $user ) || $user == "" )
		{
			trigger_error( '[ERROR] <$user> invalide' ) ;
			return -1 ;
		}

		if( !isset( $password ) )
		{
			trigger_error( '[ERROR] <$password> invalide' ) ;
			return -1 ;
		}

		if( !isset( $database ) || $database == "" )
		{
			trigger_error( '[ERROR] <$database> invalide' ) ;
			return -1 ;
		}

		$this->_DBshost    = $hostname ;
		$this->_DBsuser    = $user ;
		$this->_DBspwd     = $password ;
		$this->_DBsname    = $database ;

		if( isset( $this->_hDBs ) )
		{
			trigger_error( '[WARNING] Slave Already connected' ) ;
			return 0 ;
		}

		$this->_hDBs = mysql_connect( $this->_DBshost, $this->_DBsuser, $this->_DBspwd, 1) ;
		if ( $this->_hDBs == FALSE )
		{
			trigger_error( "[ERROR] SLAVE database connection failed: $this->_hDBs" ) ;
			return -1 ;
		}
		

        if(!defined('DONT_SET_NAMES'))
        {
            DEFINE('DONT_SET_NAMES', FALSE);
        }
        if(!DONT_SET_NAMES)
        {
            mysql_query("SET NAMES utf8",$this->_hDBs);
        }

		$this->_DBsThreadId = mysql_thread_id( $this->_hDBs ) ;

		if ( !mysql_select_db( $this->_DBsname, $this->_hDBs ) )
		{
			trigger_error( "[ERROR] SLAVE database use failed: $this->_DBsname" ) ;
			return -1 ;
		}
		
		return 0;
	}
	//------------------------------------------------------------------------
	function masterConnection( $hostname, $user, $password, $database, $autocommit )
	{
		if( !isset( $hostname ) || $hostname == "" )
		{
			trigger_error( '[ERROR] <$hostname> invalide' ) ;
			return -1 ;
		}

		if( !isset( $user ) || $user == "" )
		{
			trigger_error( '[ERROR] <$user> invalide' ) ;
			return -1 ;
		}

		if( !isset( $password ) )
		{
			trigger_error( '[ERROR] <$password> invalide' ) ;
			return -1 ;
		}

		if( !isset( $database ) || $database == "" )
		{
			trigger_error( '[ERROR] <$database> invalide' ) ;
			return -1 ;
		}

		if( !isset( $autocommit ) || ( $autocommit != 0 && $autocommit!= 1 ) )
		{
			trigger_error( '[ERROR] <$autocommit> invalide' ) ;
			return -1 ;
		}

		$this->_DBacommit = $autocommit ;
		$this->_DBhost    = $hostname ;
		$this->_DBuser    = $user ;
		$this->_DBpwd     = $password ;
		$this->_DBname    = $database ;

		if( isset( $this->_hDB ) )
		{
			throw new Exception("[WARNING] Already connected");
		}

		$this->_hDB = mysql_connect( $this->_DBhost, $this->_DBuser, $this->_DBpwd, 1 ) ;
		
		if(!defined('DONT_SET_NAMES'))
        {
            DEFINE('DONT_SET_NAMES', FALSE);
        }
        if(!DONT_SET_NAMES)
        {
            //---Use utf8
		    mysql_query("SET NAMES utf8",$this->_hDB);
        }
		
		$this->_DBThreadId = mysql_thread_id($this->_hDB) ;
		if (!mysql_select_db($this->_DBname, $this->_hDB)) throw new Exception (mysql_error($this->_hDB));
			
		$this->setautocommit() ;
        		
		return 0 ;
	}

	//-------------------------------------------------------------------------
	function disconnect()
	{
		if( !is_null( $this->_hDBb ) )
		{
			$this->unforceMaster() ;
			$this->_hDBb = NULL ;
		}

		if ( $this->_hDB != NULL )
		{
			//---On ferme la connexion (master)
			mysql_close( $this->_hDB ) ;
		}
		else
		{	
			return 0 ;
		}

		if ( $this->_hDBs != NULL )
		{
			//---On ferme la connexion slave
			mysql_close( $this->_hDBs ) ;
		}

		$this->_hDB  = NULL;
		$this->_hDBs = NULL;

		return 0 ;
	}
	//-------------------------------------------------------------------------
	/*
	*  myselect(): Process SELECT query
	*  param list :
	*           - Query
	*           - Ref array (store results)
	*           - Num rows counter
	*/
	//-------------------------------------------------------------------------
	function select( $query, &$rResult, &$numrows, $useMaster = 0 )
	{
		$this->_result = NULL ;
                
		if( $useMaster == 1 )
		{
			Log::logInfo("[DB QUERY] USE MASTER",LOG_DB_QUERY);

			if ( mysql_ping( $this->_hDB ) == FALSE )
			{
				trigger_error( '[ERROR] Lost database connexion' ) ;
				return -1;
			}

			$ThreadId = mysql_thread_id( $this->_hDB ) ;
			if( $ThreadId != $this->_DBThreadId )
			{
				$this->setautocommit($this->_DBacommit) ;
			}
			$this->_DBThreadId =  $ThreadId ;

			$HdbToUse = $this->_hDB ;
		}
		else
		{
			if( isset( $this->_hDBs ) )
			{
				if (mysql_ping( $this->_hDBs )===FALSE)
				{
					trigger_error( '[ERROR] Lost SLAVE database connexion' ) ;
					return -1;
				}

				$HdbToUse = $this->_hDBs ;
			}
			else
			{
				if ( mysql_ping( $this->_hDB ) === FALSE )
				{
					trigger_error( '[ERROR] Lost database connexion' ) ;
					return -1;
				}

				$ThreadId = mysql_thread_id( $this->_hDB ) ;

				if( $ThreadId != $this->_DBThreadId )
				{
					$this->setautocommit($this->_DBacommit) ;
				}

				$this->_DBThreadId =  $ThreadId ;

				$HdbToUse = $this->_hDB ;
			}
		}

		//---Time counter START
		list($usec, $sec) = explode(" ", microtime());
		$start_time = ((float)$usec + (float)$sec);

		if (($this->_result = mysql_query( $query, $HdbToUse ))===False) throw new Exception(mysql_error($HdbToUse));
		
		//---Time counter END
		list($usec, $sec) = explode(" ", microtime());
		$end_time = ((float)$usec + (float)$sec);
		$ellapsed_time = $end_time - $start_time;
		$this->_cumulateProcessTime += $ellapsed_time;
        
		if ($this->_result!=NULL)
		  $numrows = mysql_num_rows($this->_result);
		else
		  $numrows = 0;

		$rResult = $this->_result ;
        		
		return 0 ;
	}
	//-------------------------------------------------------------------------
	/*
	*  mydo(): Process  UPDATE, REPLACE, DELETE query
	*  param list :
	*           - Query
	*           - Ref array (store results)
	*           - Affected rows counter
	*/
	//-------------------------------------------------------------------------
	function myDo( $query, &$affectedrows, $useSlave=0 )
	{
		$this->_result = NULL ;

		if( !isset( $query ) || $query == "" )
		{
			trigger_error( '[ERROR] Param <$query> invalide' ) ;
			return -1 ;
		}

		if( $useSlave == 1 )
		{
			if( isset( $this->_hDBs ) )
			{
				if ( mysql_ping( $this->_hDBs ) == FALSE )
				{
					trigger_error( '[ERROR] Lost SLAVE database connexion' ) ;
					return -1;
				}
				$HdbToUse = $this->_hDBs ;
			}
			else
			{
				return -1 ;
			}
		}
		else
		{
			if ( mysql_ping( $this->_hDB ) == FALSE )
			{
				trigger_error( '[ERROR] Lost database connexion' ) ;
				return -1;
			}

			$ThreadId = mysql_thread_id( $this->_hDB ) ;
			if( $ThreadId != $this->_DBThreadId )
			{
				$this->setautocommit($this->_DBacommit) ;
			}

			$this->_DBThreadId =  $ThreadId ;

			$HdbToUse = $this->_hDB ;
		}

		//---Time counter START
		list($usec, $sec) = explode(" ", microtime());
		$start_time = ((float)$usec + (float)$sec);

		$this->_result = mysql_query( $query, $HdbToUse ) ;
		if ( $this->_result == NULL)
		{
		    $this->errorManager(mysql_errno($HdbToUse),mysql_error($HdbToUse));
		    
		    return -1;
		}

		//---Time counter END
		list($usec, $sec) = explode(" ", microtime());
		$end_time = ((float)$usec + (float)$sec);
		$ellapsed_time = $end_time - $start_time;
		$this->_cumulateProcessTime += $ellapsed_time;

		$affectedrows = mysql_affected_rows($HdbToUse);

		return 0 ;
	}
	//-------------------------------------------------------------------------
	/*
	*  mylastinsertid(): Find the last insert id
	*  param list :
	*           - Ref string
	*/
	//-------------------------------------------------------------------------
	function getLastInsertID( &$id )
	{
		$query = "SELECT LAST_INSERT_ID() AS id" ;

		Log::logInfo("[DB QUERY] $query",LOG_DB_QUERY);

		$numrows = 0;
		if( $this->myselect( $query , $rDb, $numrows, 1 ) != 0 )
		{
			return -1 ;
		}

		$result = mysql_fetch_object( $rDb );
		$rDb->free();

		$id = $result->id ;

		return 0 ;
	}
	//---------------------------------------------------------------------------
	/*
	*  setautocommit(): Set auto commit to 0 or 1
	*/
	//---------------------------------------------------------------------------
	function setautocommit($autocommit=0) //---Default 1
	{
		//----Check coherence
		if (($autocommit!=1) && ($autocommit!=0))
		{
			trigger_error("[ERROR] Valeur autocmmit invalide !");
			return -1;
		}

		$this->_DBacommit = $autocommit;

		$query = 'SET @@AUTOCOMMIT = ' . $this->_DBacommit ;

		if( $this->mydo( $query, $affectedrows ) == -1 )
		{
			return -1 ;
		}

		return 0 ;
	}
	//-------------------------------------------------------------------------
	/*
	*  mystart(): Start transaction
	*/
	//-------------------------------------------------------------------------
	function mystart()
	{
		$query = "START TRANSACTION" ;

		Log::logInfo("[DB QUERY] $query",LOG_DB_QUERY);

		if( $this->mydo( $query, $affectedrows ) == -1 )
		{
			return -1 ;
		}

		return 0 ;
	}
	//-------------------------------------------------------------------------
	/*
	*  mycommit(): Commit change(s)
	*/
	//-------------------------------------------------------------------------
	function mycommit()
	{
		$query = "COMMIT" ;
        
		if( $this->mydo( $query, $affectedrows ) == -1 )
		{
			return -1 ;
		}

		return 0 ;
	}
	//-------------------------------------------------------------------------
	/*
	* myrollback(): Rollback
	*/
	//-------------------------------------------------------------------------
	function myrollback()
	{
		$query = "ROLLBACK" ;

		trigger_error("[WARNING] Appel a la fonction TRANSACTIONELLE CALLBACK");

		if( $this->mydo( $query, $affectedrows ) == -1 )
		{
			return -1 ;
		}

		return 0 ;
	}
	//---------------------------------------------------------------------------
	private function errorManager($errno, $errmsg)
	{
	    switch($errno)
	    {
	        case 1062:
	            if (!isset($_SESSION['_ERROR_LIST']))
	                $_SESSION['_ERROR_LIST'] = array();
	                
                $_SESSION['_ERROR_LIST']['BDD'] = "La création/mise à jour des données viole une contrainte d'unicité !";	            
	            break;
	            
            case 1216:
	            if (!isset($_SESSION['_ERROR_LIST']))
	                $_SESSION['_ERROR_LIST'] = array();
	                
                $_SESSION['_ERROR_LIST']['BDD'] = "Ne peux créer/mettre à jour les données : une contrainte de clé étrangère n'est pas respectée !";	            
	            break;
	            
            case 1217:
	            if (!isset($_SESSION['_ERROR_LIST']))
	                $_SESSION['_ERROR_LIST'] = array();
	                
                $_SESSION['_ERROR_LIST']['BDD'] = "Ne peux supprimer/mettre à jour les données : une contrainte de clé étrangère n'est pas respectée !";	            
	            break;
	            
	         case 1451:
	            if (!isset($_SESSION['_ERROR_LIST']))
	                $_SESSION['_ERROR_LIST'] = array();
	                
                $_SESSION['_ERROR_LIST']['BDD'] = "Ne peux supprimer l'enregistrement. D'autre objects font référence à cet enregistrement !";	            
	            break;   
	            
	        default:
	            throw new Exception("Erreur MySQL N°$errno : $errmsg");
	            break;
	    }
	    
	    return TRUE;
	}
	//-------------------------------------------------------------------------
	
	

} ;


