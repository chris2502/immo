<?php

/**
 *  Bootstrap de la suite de test !
 *
 *  @Revision $Revision: 2612 $
 *
 */

@session_start();

if(!defined('KILLI_DIR'))
{
	define('KILLI_DIR', '../');
}

define('KILLI_SCRIPT', TRUE);

if(!isset($APPLICATION_DIR))
{
	require_once KILLI_DIR . '/dev/config.php';

	if(!defined('RIGHTS_DATABASE'))
	{
		DEFINE('RIGHTS_DATABASE',TESTS_DATABASE);
	}

	if(!defined('LOG_FILE'))
	{
		DEFINE('LOG_FILE', KILLI_DIR . '/log/error.log');
	}

	if(!defined('SEARCH_VIEW_NUM_RECORDS'))
	{
		DEFINE('SEARCH_VIEW_NUM_RECORDS', 200);
	}

	if(!defined('DISPLAY_ERRORS'))
	{
		DEFINE('DISPLAY_ERRORS', TRUE);
	}

	if(!defined('HOME_PAGE'))
	{
		DEFINE('HOME_PAGE','user.home');
	}

	if(!defined('DBSI_DATABASE'))
	{
		DEFINE('DBSI_DATABASE', TESTS_DATABASE ) ;
	}

	if(!defined('DBSI_CHARSET'))
	{
		DEFINE('DBSI_CHARSET', 'utf8' ) ;
	}

	if(!defined('ADDRESS_DATABASE'))
	{
		DEFINE('ADDRESS_DATABASE', TESTS_DATABASE ) ;
	}

	if(!defined('ADMIN_PROFIL_ID'))
	{
		DEFINE("ADMIN_PROFIL_ID",1);
	}

	if(!defined('READONLY_PROFIL_ID'))
	{
		DEFINE("READONLY_PROFIL_ID",2);
	}

}

if(isset($APPLICATION_DIR))
{
	require_once($APPLICATION_DIR . '/include/config.php');
	//require_once($APPLICATION_DIR . '/dev/config.php');
}

require_once(KILLI_DIR . '/include/include.php');

if(isset($APPLICATION_DIR) && file_exists($APPLICATION_DIR . '/class/'))
{
	$old = getcwd();
	chdir($APPLICATION_DIR);
	include_classes('./class/');
	include_classes('./workflow/');
	if(file_exists('./include/index_include_start.php'))
	{
		require('./include/index_include_start.php');
	}
	chdir($old);
}

/* Reset du handler d'exception. */

restore_error_handler();
restore_exception_handler();

function killi_testErrorHandler($errno, $errstr, $errfile, $errline)
{
	try
	{
		throw new Exception($errstr);
	} catch(Exception $e)
	{
		$message  = $errstr . PHP_EOL;
		$message .= 'File : ' . $errfile . ':' . $errline . PHP_EOL;
		$message .= $e->getTraceAsString();

		throw new Exception($message);
	}
}

set_error_handler("killi_testErrorHandler");

srand($_SERVER['REQUEST_TIME']);

/* Variable inutile, mais évite un bug si elle est utilisé par le framework. */
$object_list = array();

class IgnoreCommitException extends Exception {};

/**
 * Ignore les commits pour les tests.
 */
class DbTest extends DbLayer {

	public static $throwExceptionOnCommit = true;

	public function db_commit() {
		if(self::$throwExceptionOnCommit)
		{
			throw new IgnoreCommitException('Error : Hidden commit !');
		}
	}
};

global $hDB;

class Killi_UITestCase extends PHPUnit_Extensions_SeleniumTestCase
{
	protected $_hDB;

	public function __construct($name = NULL, array $data = array(), $dataName = '', array $browser = array())
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,
				'users_id' => null,

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
						'ctype' => NULL
				)
		) ;

		$this->_hDB = new DbLayer( $dbconfig  );
		$this->_hDB->db_start();
		parent::__construct($name, $data, $dataName);
	}

	public function sql_execute($query)
	{
		$this->_hDB->db_execute($query);
		$this->_hDB->db_commit();
	}
}

class Killi_TestCase extends PHPUnit_Framework_TestCase
{
	public static $globalDatabaseInstance = NULL;
	public $hDB;

	public function setUp()
	{
		$_SESSION['_TOKEN'] = md5(session_id());

		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,
				'users_id' => null,

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
						'ctype' => NULL
				)
		) ;
		if(self::$globalDatabaseInstance === NULL)
			self::$globalDatabaseInstance = new DbTest( $dbconfig  );

		$this->hDB = self::$globalDatabaseInstance;

		global $hDB;
		$this->hDB->db_start();
		$hDB = $this->hDB;

		ORM::resetAllInstances();
	}

	protected function _cleanCreateObject($object)
	{
		$hORM = ORM::getORMInstance($object);
		try {
			$hORM->createObjectInDatabase();
		}
		catch (Exception $e)
		{
			$hORM->deleteObjectInDatabase();
			$hORM->createObjectInDatabase();
		}
		return TRUE;
	}

	public function showTables(array $tables)
	{
		foreach($tables AS $table)
		{
			$query = 'SELECT * FROM ' . $table;

			 $this->hDB->db_select($query, $result, $numrows);
			 $once = true;
			 echo "\n", $table, " :\n";
			 //---On deroule les resultats
			 while($row = $result->fetch_assoc())
			 {
				if($once)
				{
					foreach($row as $key => $value)
					{
						echo $key, "\t";
					}
					$once = false;
					echo "\n";
				}
				 foreach($row as $key => $value)
				 {
					echo $value, "\t|\t";
				 }

				 echo "\n";
			 }
			 echo "\n";
			 $result->free();
		}
	}
}

if (!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

class Framework_AllTests
{
	protected static $_tests = array();

	public static function addClass($classname)
	{
		self::$_tests[$classname] = $classname;
	}

	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('Killi Framework Test suite');
		foreach(self::$_tests AS $test)
		{
			$suite->addTestSuite($test);
		}
		return $suite;
	}
}

class AllTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('Killi Test suite');
		$suite->addTest(Framework_AllTests::suite());

		return $suite;
	}
}

PHPUnit_Extensions_SeleniumTestCase::shareSession(true);

/* Construction de la liste des tests. */
$ignore_list = array('UserObjectTest.php', 'DocumentTest.php');

$dir = opendir(KILLI_DIR . '/dev/tests/');
while (false !== ($file = readdir($dir)))
{
	if (substr($file,-4) === '.php')
	{
		require_once(KILLI_DIR . '/dev/tests/'.$file);
		if(substr($file, -8) === 'Test.php')
		{
			if(!isset($APPLICATION_DIR) || !in_array($file, $ignore_list))
			{
				Framework_AllTests::addClass(substr($file, 0, -4));
			}
		}
	}
}
closedir($dir);


if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
	AllTests::suite();
}

