<?php

namespace Killi\Core\Application\Middleware;

/**
 * Classe permettant la mise en oeuvre de l'authentification standard de killi.
 *
 *
 * @class  AuthMiddleware
 * @Revision $Revision: 4666 $
 *
 */
use \Closure;
use \Killi\Core\Application\Http\Request;
use \Killi\Core\Application\Http\Response;

class AuthMiddleware extends AbstractMiddleware
{
	/**
	 * @inherit
	 */
	public function handle(Request $request, Closure $next)
	{

		/**
		 * Déjà authentifié, on skip.
		 */
		if(isset($_SESSION['_USER']))
		{
			return $next($request);
		}

		/**
		 * Gestion de l'authentification.
		 */
		$action = explode('.', $request->input('action'));

		/* Authentification des web services */
		if($action[0] == 'json')
		{
			if (isset($_SERVER['PHP_AUTH_USER']))
			{
				$auth = explode(':', base64_decode($_SERVER['PHP_AUTH_USER']));

				if(isset($auth[0]) && isset($auth[1]))
				{
					$_POST['user/login'] = $auth[0];
					$_POST['user/password'] = $auth[1];
				}
			}

			if(!isset($_GET['crypt/login']) && !isset($_POST['user/login']) && !isset($_POST['user/password']))
			{
				$_POST['user/login']	= isset($_POST['login']) ? $_POST['login'] : NULL;
				$_POST['user/password'] = isset($_POST['password']) ? $_POST['password'] : NULL;
			}

			if(!ORM::getControllerInstance('user')->submitAuthentification())
			{
				header('HTTP/1.1 403 Forbidden');
				header('Content-type: application/json');
				session_destroy();
				die();
			}

			global $hDB;

			$hDB->db_execute('SET @users_id='.$_SESSION['_USER']['killi_user_id']['value']) ;

			ORM::resetAllInstances();
		}
		else
		/* Authentification de l'utilisateur. */
		if((isset($_GET['crypt/login']) && isset($_GET['action'])) || ((isset($_GET['action'])) && $_GET['action'] == 'user.submitAuthentification'))
		{
			// authentification auto si l'utilisateur est en authentification automatique sur le login
			ORM::getControllerInstance('user')->submitAuthentification();
			ORM::resetAllInstances();
		}
		else
		/* Formulaire d'authentification. */
		if ((!isset($_GET['action'])) || (!isset($_SESSION['_USER']) && ( ($_GET['action']!='user.authentification') && ($_GET['action']!=='adresse.plaquesXtract') && ($_GET['action']!='user.register') && ($_GET['action']!='user.commit_register') && ($_GET['action']!='user.submitAuthentification') && $_GET['action'] != 'user.cas_auth') ))
		{
			$redirect='';
			if(isset($_GET['redirect']))
			{
				$redirect='&redirect='.urlencode($_GET['redirect']);
			}
			else
			if($_SERVER['QUERY_STRING']!='')
			{
				$redirect='&redirect='.urlencode(preg_replace('/&token=([^&]+)/', '', $_SERVER['QUERY_STRING']));
			}
			header('Location: ./index.php?action=user.authentification&view=form&mode=edition'.$redirect);
			exit(0);
		}

		return $next($request);
	}
}
