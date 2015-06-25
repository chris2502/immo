<?php

namespace Killi\Core\Application\Middleware;

/**
 * Classe permettant la mise en oeuvre de l'authentification centralisée via OAuth.
 *
 *
 * @class  OAuthMiddleware
 * @Revision $Revision: 4563 $
 *
 */
use \Closure;
use \Killi\Core\Application\Http\Request;
use \Killi\Core\Application\Http\Response;

class OAuthMiddleware extends AbstractMiddleware
{
	/**
	 * @inherit
	 */
	public function handle(Request $request, Closure $next)
	{
		global $hDB;
		$oauth_redirect = false;

		/**
		 * Variables d'entrées.
		 *
		 */
		$auth			= $request->input('auth');
		$code			= $request->input('code');
		$response_type	= $request->input('response_type');
		$client_id		= $request->input('client_id');
		$redirect_uri	= $request->input('redirect_uri');
		$scope			= $request->input('scope');
		$state			= $request->input('state');
		$grant_type		= $request->input('grant_type');

		/* STEP 2 */
		if($auth == 'oauth')
		{
			if(!$request->has('response_type'))
			{
				throw new Exception('Authentication with oauth needs response_type parameter !');
			}

			if(!$request->has('client_id'))
			{
				throw new Exception('Authentication with oauth needs client_id parameter !');
			}

			$client_id = $request->input('client_id');

			$hORM = ORM::getORMInstance('application');
			$app_list = array();
			$hORM->browse($app_list, $total, array('killi_application_id', 'redirect_uri'), array(array('app_token', '=', $client_id), array('active', '=', 1)));

			if(count($app_list) != 1)
			{
				Alert::error('Application non autorisée', 'L\'application n\'a pas été identifiée comme valide pour se connecter via ce serveur d\'authentification !');
				header('Location: index.php');
				exit(0);
			}

			$app = reset($app_list);

			if($request->has('redirect_uri') || !empty($app['redirect_uri']['value']))
			{
				if(!$request->has('redirect_uri') || $app['redirect_uri']['value'] != $redirect_uri)
				{
					Alert::error('Application non autorisée', 'L\'application n\'a pas été identifiée comme valide pour se connecter via ce serveur d\'authentification !');
					header('Location: index.php');
					exit(0);
				}
			}

			if($response_type !== 'code')
			{
				throw new Exception('Authentication with oauth needs response_type parameter setted to scope !');
			}

			/* The user is authenticated, redirection with an authorization token */
			if(isset($_SESSION['_USER']))
			{
				$code = md5(uniqid());
				$user_id = $_SESSION['_USER']['killi_user_id']['value'];
				$app_id = $app['killi_application_id']['value'];

				$hORM = ORM::getORMInstance('applicationtoken');
				$app_token_id = NULL;
				$data = array('killi_application_id' => $app_id, 'killi_user_id' => $user_id, 'token_type_id' => 1, 'token' => $code);
				$hORM->create($data, $app_token_id, false, array('token' => $code));
				$hDB->db_commit();
				header('Location: ' . $redirect_uri . '?code=' . $code . '&state=' . $state);
				exit(0);
			}

			/* Redirect on the authentication page. */
			$oauth_redirect_url = 'auth=oauth&response_type=code&scope=' . $scope . '&state=' . $state . '&client_id=' . $client_id;
			if($request->has('redirect_uri'))
			{
				$oauth_redirect_url .= '&redirect_uri=' . $redirect_uri;
			}
			$redirect = '&redirect=' . urlencode($oauth_redirect_url);

			header('Location: ./index.php?action=user.authentification&view=form&mode=edition'.$redirect);
			exit(0);
		}

		/* STEP 3 */
		if(!isset($_SESSION['_USER']) && $request->has('code') && $grant_type != 'authorization_code')
		{
			$url = CAS_SERVER_URL . '?auth=app';

			/* CURL Authentication. */
			$hCurl = new KilliCurl($url);
			$hCurl	->setPost('grant_type', 'authorization_code')
					->setPost('code', $code)
					->setPost('redirect_uri', CAS_SERVER_APP_URL)
					->setPost('client_id', CAS_SERVER_APP_TOKEN)
					->setUser(CAS_SERVER_APP_TOKEN, CAS_SERVER_APP_PASSWD)
					->setSSL(CAS_SERVER_CERT, CAS_SERVER_CERT_PASSWD);

			$result = $hCurl->requestNoData();

			/* STEP 5 */
			$killi_user = (object)$result;

			if(!isset($killi_user->user))
			{
				throw new Exception('Authentication failed !');
			}

			$hUserMethod = ORM::getControllerInstance('user');
			$hUserMethod->cas_connect($killi_user->user);

			$redirect = base64_decode($state);

			$hDB->db_commit();
			header('Location: index.php?' . $redirect . '&token='.$_SESSION['_TOKEN']);
			exit(0);
		}

		/* STEP 4 : Application <=> CAS */
		if($auth == 'app')
		{
			if($grant_type != 'authorization_code')
			{
				throw new JSONException('Wrong grant type !');
			}

			$auth     = $request->getUser();
			$auth_str = base64_decode($auth);
			$raw      = explode(':', $auth_str);

			if(count($raw) != 2)
			{
				throw new JSONException('Client Authentication failed !');
			}

			$client_id	= $raw[0];
			$client_pwd	= $raw[1];

			/* Check authentication. */
			$app_list = array();
			ORM::getORMInstance('application')->browse(
				$app_list,
				$total,
				array('killi_application_id', 'app_token', 'redirect_uri'),
				array(
					array('app_token'  , '=' , $client_id)  ,
					array('app_secret' , '=' , $client_pwd) ,
					array('active'     , '=' , 1)));

			if(count($app_list) != 1)
			{
				throw new JSONException('Client Authentication failed !');
			}

			$app = reset($app_list);

			if($app['app_token']['value'] != $client_id)
			{
				throw new JSONException('Client Token failed !');
			}

			if($app['redirect_uri']['value'] != $redirect_uri)
			{
				throw new JSONException('Wrong redirect URI !');
			}

			/* Check authorization code. */
			$app_id        = $app['killi_application_id']['value'];
			$apptoken_list = array();

			ORM::getORMInstance('applicationtoken')->browse(
				$apptoken_list,
				$total,
				array('killi_user_id'),
				array(
					array('killi_application_id' , '=' , $app_id)           ,
					array('token'                , '=' , $code)             ,
					array('validity_date'        , '<' , date('Y-m-d H:i:s' , time()+600))));

			if(count($apptoken_list) != 1)
			{
				throw new JSONException('Invalid token !');
			}

			$token = reset($apptoken_list);

			/* Authentication success */
			header('Content-Type: application/json;charset=UTF-8');

			$user_id = $token['killi_user_id']['value'];
			$hORM = ORM::getORMInstance('user');
			$hORM->read($user_id, $user, NULL);

			if(!defined('NO_FAKE_PASSWORD'))
			{
				$user['password']['value'] = md5(uniqid()); // Fake Password
			}

			$hORM = ORM::getORMInstance('applicationtoken');

			$app_token_id = NULL;
			$access_token = md5(uniqid());
			$data = array('killi_application_id' => $app_id, 'killi_user_id' => $user_id, 'token_type_id' => 3, 'token' => $access_token);
			$hORM->create($data, $app_token_id, false, array('token' => $code));

			$app_token_id = NULL;
			$refresh_token = md5(uniqid());
			$data = array('killi_application_id' => $app_id, 'killi_user_id' => $user_id, 'token_type_id' => 2, 'token' => $refresh_token);
			$hORM->create($data, $app_token_id, false, array('token' => $code));

			echo json_encode(array('access_token' => $access_token, 'refresh_token' => $refresh_token, 'expires_in' => 3600, 'token_type' => 'user_token', 'user' => $user));
			$hDB->db_commit();
			exit(0);
		}

		return $next($request);
	}
}
