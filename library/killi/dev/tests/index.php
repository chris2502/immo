<?php
if (isset($_SERVER['SERVER_SOFTWARE']))
{		
	/**
	 *  @class CurlTest
	 *  @Revision $Revision: 3214 $
	 *
	 * 	Index utilisé pour les testes de KilliCurl. Doit porter ce nom, 
	 * l'ORM appelant automatiquement l'index.php.
	 */

	class ThePublic
	{
		public function applaudir(array $data, array &$result)
		{
			$result['bruit'] = 'Clap Clap Clap';
			return TRUE;
		}
		
		public function goingPostal(array $data, array &$result)
		{
			$result = array_flip($_POST);
			return TRUE;			
		}
	}
	
	class ErrorGen
	{
		public function suddenDeath(array $data, array &$result)
		{
			die();
		}
		
		public function noData(array $data, array &$result)
		{
			$result['bruit'] = 'Clap Clap Clap';
			echo json_encode($result);
			die();
		}
	}
	
	define('KILLI_DIR', __DIR__.'/../../');
	if (!isset($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_USER']))
	{
		echo json_encode(array('authentification'=>'Invalid Login and/or Password'));			
	}
	else
	{
		$auth = explode(':', base64_decode($_SERVER['PHP_AUTH_USER']));
		$ok = array('Killi' => 'Nage');
		if (!(isset($ok[$auth[0]]) && $ok[$auth[0]] = $auth[1]))
		{
			echo json_encode(array('authentification'=>'Invalid Login and/or Password'));
		}
		else
		{
			if (!isset($_GET['action']))
			{
				echo json_encode(array('error' => 'No Action'));
			}
			else
			{
				$ex = explode('.', $_GET['action']);
				$obj = $ex[0];
				$action = $ex[1];
				if ($obj == 'json')
				{
					if (!isset($_POST['data']))
					{
						echo json_encode(array('error' => 'No data.'));
					}
					else
					{
						@session_start();						
						define('KILLI_SCRIPT', TRUE);
						require_once KILLI_DIR . '/dev/config.php';
						define('DBSI_DATABASE', TESTS_DATABASE);						
						require_once KILLI_DIR.'include/include.php';
						require_once 'ORMObjects.php';
												
						restore_error_handler();
						restore_exception_handler();
						class JSONMethod extends KilliJSONMethod
						{
							public function Power(array $data, array &$result)
							{
								$result['power'] = pow($data['X'], $data['p']);
								return TRUE;
							}							
						}
						$data = json_decode($_POST['data'], true);
						$obj = 'JSONMethod';
						
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
						);
						
						$hORM = ORM::getORMInstance('SimpleObject');
						try{
							$hORM->createObjectInDatabase();
						}
						catch (Exception $e)
						{
							$hORM->deleteObjectInDatabase();
							$hORM->createObjectInDatabase();
						}
						$hORM->create(array( 
							'simpleobject_id' => 1,
							'simpleobject_name' => 'FTTH',
							'simpleobject_value' => 3						
						), $id);
						
						$hORM->create(array( 
							'simpleobject_id' => 2,
							'simpleobject_name' => 'MEDEF',
							'simpleobject_value' => 4						
						), $id);
						
						$hORM->create(array( 
							'simpleobject_id' => 3,
							'simpleobject_name' => 'Gdcf',
							'simpleobject_value' => 15						
						), $id);						
						
						$result = array();
						$hInstance = new $obj();
						$hInstance->$action($data, $result);
						echo json_encode(array('data' => $result));
						$hORM->deleteObjectInDatabase();
					}
				}
				else
				{
					if (isset($_POST['data']))
					{
						$data = json_decode($_POST['data'], true);
					}
					else
					{
						$data = array();
					}
					$result = array();
					$hInstance = new $obj();
					$hInstance->$action($data, $result);
					echo json_encode(array('data' => $result));
				}				
			}
		}
	}
}

