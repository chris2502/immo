<?php

namespace Killi\Core\Route\Handler;

/**
 *  Traitant CLI du routage
 *
 *  @package killi
 *  @class CliHandler
 *  @Revision $Revision: 4630 $
 */

use \Mailer;

class CliHandler extends AbstractHandler
{
	public function in()
	{
		parent::in();

		global $argc, $argv;

		/**
		 *  Bootstrap :
		 *  Ce fichier est un point d'entrée du framework Killi pour l'éxécution de
		 *  script.
		 *
		 *  @Revision $Revision: 4630 $
		 *
		 */
		if(!isset($_SERVER['SHELL']))
		{
			echo 'Bootstrap must be called from a shell.', PHP_EOL, PHP_EOL;
			exit(1);
		}

		/**
		 * Vérification des paramètres CLI.
		 */
		if($argc < 3)
		{
			echo 'Usage: ' . $_SERVER['SCRIPT_NAME'] . ' -k=[app path] -d=[debug_level] [object] [method]', PHP_EOL, PHP_EOL;
			exit(1);
		}

		/**
		 * Changement de contexte pour exécution via CRON.
		 * Configuration du debugeur
		 * [[ya pas plus simple ???]]
		 */
		$options = getopt("k::d::");
		$empty_options_d = empty($options['d']);

		if(!empty($options['k']))
		{
			chdir($options['k']);

			if($empty_options_d)
			{
				$this->object = $argv[2];
				$this->method = $argv[3];
				$this->request = array_slice($argv, 4);
			}
			else
			{
				$this->object = $argv[3];
				$this->method = $argv[4];
				$this->request = array_slice($argv, 5);
			}
		}
		else
		{
			if($empty_options_d)
			{
				$this->object = $argv[1];
				$this->method = $argv[2];
				$this->request = array_slice($argv, 3);
			}
			else
			{
				$this->object = $argv[2];
				$this->method = $argv[3];
				$this->request = array_slice($argv, 4);
			}
		}

		/**
		 * Paramètrage du debugeur
		 */
		if(!$empty_options_d)
		{
			Debug::setLevelListener($options['d']);
		}
	}

	public function dispatch()
	{
		/**
		 * Exécution de la commande.
		 */
		call_user_func_array(array(ORM::getControllerInstance(Route::getObject()), Route::getMethod()), Route::getRequest());
	}

	public function out()
	{
		global $hDB;
		/**
		 * Commit final de la base de données.
		 */
		if(isset($hDB) && empty($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
		{
			$hDB->db_commit();
			Mailer::commit();
		}

		// retourne un error_code 0 pour indiquer que tout s'est bien passé.
		exit(0);
	}
}
