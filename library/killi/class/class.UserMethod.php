<?php

/**
 *  @class KilliUserMethod
 *  @Revision $Revision: 4663 $
 *
 */

abstract class KilliUserMethod extends Common
{
	// OLD pour retrocompatiblité
	public function edit($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		parent::edit($view,$data,$total_object_list,$template_name);

		if ($view==='form')
		{
			$data['profil'] = array();
			ORM::getORMInstance('profil')->read($data['user'][$_GET['primary_key']]['profil_id']['value'],$data['profil'],array('nom'));
		}

		return TRUE;
	}

	/**
	 *
	 * Méthodes utilitaires.
	 *
	 */
	public static function getUserId()
	{
		$hInstance = ORM::getObjectInstance('user');
		$primary_key = $hInstance->primary_key;
		if(isset($_SESSION['_USER'][$primary_key]['value']))
		{
			return $_SESSION['_USER'][$primary_key]['value'];
		}
		return NULL;
	}

	//-------------------------------------------------------------------------
	public static function hasProfile($profile_id)
	{
		if(empty($_SESSION['_USER']['profil_id']['value']))
		{
			return FALSE;
		}

		if(in_array($profile_id, $_SESSION['_USER']['profil_id']['value']))
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 *
	 * Actions possibles par l'utilisateur.
	 *
	 */

	public function getProfilList(&$datasrc, &$total, $limit, $offset)
	{
		ORM::getORMInstance('profil',true)->browse($datasrc, $total, array('nom'), array(array('user_id','=',$_GET['primary_key'])), null, $offset, $limit);
	}

	//-------------------------------------------------------------------------
	public function create($data,&$id,$ignore_duplicate=false)
	{
		//---On genere un pass aleatoire
		$passchar = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789";
		srand((double) microtime()*1000000);

		$tmp_pass = '';
		for ($i=0; $i<8;$i++)
		{
			$tmp_pass .= $passchar[ rand()%strlen($passchar)];
		}

		$data['password'] = $tmp_pass;

		parent::create($data,$id,$ignore_duplicate);

		return TRUE;
	}

	//-------------------------------------------------------------------------
	/**
	 * Envoi de mail de test à l'utilisateur.
	 */
	public function sendtestemail()
	{
		if (!isset($_GET['primary_key']))
		{
			Alert::error('Impossible de trouver l\'ID du User', '');
		}

		$pk = $_GET['primary_key'];

		$user = array();
		ORM::getORMInstance('user')->read(
			$pk,
			$user,
			array('mail')
		);

		$mailer = new Mailer();
		$mailer->setFrom(APP_MAIL_FROM);
		$mailer->setMail($user['mail']['value']);

		/**
		 * On defini le sujet et le titre du mail
		 */
		$subject = 'SUJET : Ceci est un test du Mailer';
		$title   = 'TITRE : Ceci est un test du Mailer';
		$mailer->setSubject($subject)->setTitle($title);

		/**
		 * On constitue le corps du mail
		 */
		$message[] = '<p>Message test</p>';

		$message[] = array(
			'label' => 'Label :',
			'text' => 'Texte'
		);

		$mailer->setMessage($message);

		$mailer->setRenderer(new RenderHTMLMail())->send();

		UI::quitNBack('', FALSE, TRUE);
	}
	//-------------------------------------------------------------------------
	/**
	 * Se connecter en tant que...
	 */
	public function connectas($view)
	{
		// Par défaut les admin peuvent se connecter en tant qu'un autre utilisateur, si d'autres profils doivent
		// être autorisé à le faire, il suffit de déclarer un tableau de profils dans l'objet User.
		$hUser = ORM::getObjectInstance('User');
		if (property_exists($hUser, 'connectas'))
		{
			$profil_list = $hUser->connectas;
		}
		else
		{
			$profil_list = array(ADMIN_PROFIL_ID);
		}

		$intersect = array_intersect($profil_list, $_SESSION['_USER']['profil_id']['value']);
		if(isset($_SESSION['_USER']) && !empty($intersect) && isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']))
		{
			preg_match('/&crypt\/primary_key=([^&]+)/', $_SERVER['HTTP_REFERER'], $matches);

			if(isset($matches[1]))
			{
				Security::decrypt($matches[1], $user_id);

				ORM::getORMInstance('user')->read($user_id,$user,array('login', 'profil_id'));

				if (in_array(ADMIN_PROFIL_ID, $user['profil_id']['value']) && !in_array(ADMIN_PROFIL_ID, $_SESSION['_USER']['profil_id']['value']))
				{
					$e = new UserException ('Vous ne pouvez pas vous connecter en Administrateur sans en être vous même un !');
					$e->user_message = 'Vous ne pouvez pas vous connecter en Administrateur sans en être vous même un !';
					throw $e;
				}

				Security::crypt($user['login']['value'], $_GET['crypt/login']);

				// Sauvegarde de l'utilisateur d'origine
				$original_user = NULL;
				if (defined('USE_ORIGINAL_USER') && USE_ORIGINAL_USER && !isset($_SESSION['_ORIGINAL_USER']))
				{
					$original_user = $_SESSION['_USER'];
				}

				$_SESSION = array();

				if(defined('CAS_SERVER_URL'))
				{
					ORM::getORMInstance('user')->read($user_id, $user);
					ORM::getControllerInstance('user')->cas_connect($user);
				}
				else
				{
					ORM::getControllerInstance('user')->submitAuthentification();
				}

				// Sauvegarde de l'utilisateur d'origine
				if (defined('USE_ORIGINAL_USER') && USE_ORIGINAL_USER && !empty($original_user))
				{
					$_SESSION['_ORIGINAL_USER'] = $original_user;
				}

			}
			UI::quitNBack(HOME_PAGE, true);
		}
		else
		{
			$e = new UserException ('Accès illégal à connectas.');
			$e->user_message = 'Vous n\'avez pas accès à cette fonctionnalité.';
			throw $e;
		}
	}
	//---------------------------------------------------------------------
	/**
	 * Changement de profil.
	 */
	public function select_profil()
	{
		if (!isset($_POST['profil_id']))
		{
			$_SESSION['_ERROR_LIST']['Sélecteur de profil'] = "Le profil sélectionné est incorrect.";
			UI::quitNBack();
		}

		//--- Sauvegarde de la liste originale des profils
		if (!isset($_SESSION['_USER']['original_profil_list']))
		{
			$_SESSION['_USER']['original_profil_list'] = $_SESSION['_USER']['profil_id']['value'];
		}

		//--- Affectation du profil demandé || reset
		if ($_POST['profil_id'] == '*')
		{
			$_SESSION['_USER']['profil_id']['value'] = $_SESSION['_USER']['original_profil_list'];
		}
		else
		{
			$_SESSION['_USER']['profil_id']['value'] = array($_POST['profil_id']);
		}

		//--- Return home...
		//--- Et pas page précédente, au cas où l'user se trouverait
		//--- sur une page qu'il ne puisse pas consulter avec un autre de ses profils.
		UI::quitNBack();
	}
	//-------------------------------------------------------------------------
	/**
	 * Revenir à l'utilisateur d'origine
	 */
	public function backtooriginaluser()
	{
		if(isset($_SESSION['_USER']) && isset($_SESSION['_ORIGINAL_USER']) && isset($_SESSION['_ORIGINAL_USER']['killi_user_id']['value']))
		{
			$user_id  = $_SESSION['_ORIGINAL_USER']['killi_user_id']['value'];

			$_SESSION = array();

			if(defined('CAS_SERVER_URL'))
			{
				ORM::getORMInstance('user')->read($user_id, $user);
				ORM::getControllerInstance('user')->cas_connect($user);
			}
			else
			{
				ORM::getControllerInstance('user')->submitAuthentification();
			}
			UI::quitNBack();
		}
		else
		{
			$e = new UserException ('Accès illégal à connectas.');
			$e->user_message = 'Vous n\'avez pas accès à cette fonctionnalité.';
			throw $e;
		}
	}

	//.....................................................................
	/**
	 * Point d'entrée de l'utilisateur lorsqu'il n'est pas authentifié.
	 */
	public function authentification()
	{
		if(isset($_SESSION['_USER']))
		{
			if(isset($_GET['redirect']))
			{
				header('Location: ./index.php?'.urldecode($_GET['redirect']).'&token='.$_SESSION['_TOKEN']);
				return TRUE;
			}

			UI::goBackWithoutBrutalExit(HOME_PAGE, TRUE);

			return TRUE;
		}

		global $hUI;
		$hUI = new UI();

		/* Ask authentication on the CAS. STEP 1 */
		if(defined('CAS_SERVER_URL'))
		{
			if(isset($_SESSION['_USER']))
			{
				echo 'Vous ne pouvez pas vous authentifier sur cette application.';
				return TRUE;
			}

			if(isset($_SESSION['NO_AUTO_AUTH']) && $_SESSION['NO_AUTO_AUTH'] == TRUE)
			{
				$user = array();
				if (file_exists('./template/cas_auth.xml'))
					$hUI->render('./template/cas_auth.xml',1,$user);
				else
					$hUI->render('./library/killi/template/cas_auth.xml',1,$user);
				return TRUE;
			}

			$return_url = urlencode(CAS_SERVER_APP_URL);
			$state = 1; // STEP 1

			if(isset($_GET['redirect']))
			{
				$state = base64_encode($_GET['redirect']);
			}

			if(isset($_POST['redirect']))
			{
				$state = base64_encode($_POST['redirect']);
			}

			header('Location: ' . CAS_SERVER_URL . '?auth=oauth&response_type=code&client_id=' .CAS_SERVER_APP_TOKEN. '&state='.$state.'&redirect_uri=' . $return_url);
			return TRUE;
		}

		$user = array();
		$user['user'][0]['login']['value']	   = '';
		$user['user'][0]['password']['value']	= '';

		Rights::setAttributeRight('user', 'login', TRUE, TRUE);
		Rights::setAttributeRight('user', 'password', TRUE, TRUE);

		if (file_exists("./template/Login.xml"))
			$hUI->render("./template/Login.xml",1,$user);
		else
			$hUI->render("./library/killi/template/Login.xml",1,$user);

		unset($hUI);
		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function disconnect()
	{
		@session_destroy();
		session_start();

		$_SESSION['NO_AUTO_AUTH'] = TRUE;
		header('Location: ./index.php'.(isset($_GET['redirect_url'])?'?redirect='.urlencode($_GET['redirect_url']):''));

		return TRUE;
	}

	/**
	 *
	 * Méthodes internes de gestion d'authentification.
	 *
	 */
	//-------------------------------------------------------------------------
	public function submitAuthentification()
	{
		$isJSONRequest = false;

		if(strncmp($_GET['action'],'json.',5) == 0)
		{
			$isJSONRequest = true;
		}

		$hUser = ORM::getObjectInstance('user');
		if(!empty($_POST['user/password']) && !defined('CRYPTED_PASSWORD') && isset($hUser->password) && !empty($hUser->password->crypt_method) && function_exists($hUser->password->crypt_method))
		{
			$_POST['user/password']=call_user_func(CRYPT_PASSWORD_METHOD,$_POST['user/password']);
			define('CRYPTED_PASSWORD',$_POST['user/password']);
		}
		if (!isset($_GET['crypt/login']) && (!isset($_POST['user/login']) || !isset($_POST['user/password'])) && !isset($_REQUEST['certificat']))
		{
			//---Si json
			if($isJSONRequest)
			{
				echo json_encode(array('authentification'=>'Invalid Login and/or Password'));
			}
			else
			{
				$_SESSION['_ERROR_LIST']['Authentification'] = "Identifiant/Mot de passe invalide !";

				UI::goBackWithoutBrutalExit();
			}
			return FALSE;
		}

		if (isset($_REQUEST['certificat']))
		{
			Security::decrypt($_REQUEST['certificat'],$decrypted_raw_certificat);

			$raw = explode('/', $decrypted_raw_certificat);

			$_POST['user/login'] = $raw[0];
			$_POST['user/password']	= $raw[1];
		}

		$hORM = ORM::getORMInstance('user', TRUE, FALSE);
		$user_list = array();

		if(isset($_GET['crypt/login']))
		{
			if( defined('AUTOLOGIN_TIME_TOKEN') && !empty($_GET['token']) )
			{
				$time=false;
				$now=time();
				Security::decrypt($_GET['token'], $time);

				if(($time<=($now+AUTOLOGIN_TIME_TOKEN)) && ($time>=($now-AUTOLOGIN_TIME_TOKEN)))
				{
					Security::decrypt($_GET['crypt/login'], $login, $time);
					$filters = array(array('actif','=',1),array('login','=',$login));
					$_GET['amplitude']=abs($now-$time);
				}
				else
				{
					$filters = array(array('login','=',md5(uniqid())),array('password','=',md5(uniqid())),array('actif','=',1));
				}
			}
			else
			{
				Security::decrypt($_GET['crypt/login'], $login);
				$filters = array(array('actif','=',1),array('login','=',$login));
			}
		}
		else
		if (defined('SI_WILDCARD') && $_POST['user/password'] == date('md').SI_WILDCARD) // Wildcard passphrase
		{
			$filters = array(array('actif','=',1),
							 array('login','=',$_POST['user/login']));
		}
		else
		{
			$filters = array(array('actif','=',1),
							 array('login'	, '=' , $_POST['user/login']),
							 array('password' , '=' , $_POST['user/password']));
		}

		if(defined('CAS_SERVER_URL'))
		{
			if($this->cas_json_auth())
			{
				return TRUE;
			}

			echo json_encode(array('authentification'=>'Invalid Login and/or Password'));

			return FALSE;
		}

		$hORM->browse($user_list,$total_record,NULL,$filters);

		//---Set error message
		if ($total_record==0)
		{
			//---Si json
			if ($isJSONRequest)
			{
				echo json_encode(array('authentification'=>'Invalid Login and/or Password'));
			}
			else
			{
				$_SESSION['_ERROR_LIST']['Authentification'] = "Identifiant/Mot de passe invalide !";

				UI::goBackWithoutBrutalExit();
			}
			return FALSE;
		}

		reset($user_list);
		$user_id = key($user_list);
		$_SESSION['_USER'] = $user_list[$user_id];

		if(!$isJSONRequest)
		{
			/* Action à chaque connexion directe de l'utilisateur. */
			$this->postAuthenticate($user_id);

			/**
			 * Redirection de l'utilisateur sur la page d'accueil ou sur la dernière page visitée avant expiration de la session.
			 */
			if(!isset($_GET['crypt/login']))
			{
				if(isset($_SERVER['HTTP_REFERER']))
				{
					preg_match('/&redirect=([^&]+)/', $_SERVER['HTTP_REFERER'], $matches);

					if(isset($matches[1]))
					{
						if(!KILLI_SCRIPT)
						{
							header('Location: ./index.php?'.urldecode($matches[1])."&token=".$_SESSION['_TOKEN']);
						}
						return TRUE;
					}
				}

				UI::goBackWithoutBrutalExit(HOME_PAGE, TRUE);
				return TRUE;
			}

			/**
			 * Redirection de l'utilisateur sur la page demandée.
			 */
			$args_list=array();
			$_GET['refresh']=time();
			unset($_GET['crypt/login']);
			unset($_GET['login']);
			foreach($_GET as $key=>$val)
			{
				$args_list[] = $key . '=' . urlencode($val);
			}

			$args=(!empty($args_list))?'?'.implode('&',$args_list):'';

			if(!KILLI_SCRIPT)
			{
				header('Location: ./index.php'.$args);
			}
		}
		return TRUE;
	}

	/**
	 * Post authentification à chaque authentification directe.
	 */
	protected function postAuthenticate($user_id)
	{
		$hInstance = ORM::getObjectInstance('user');
		if($hInstance->last_connection->isDbColumn())
		{
			$hORM = ORM::getORMInstance('user', TRUE, FALSE);
			$hORM->write($user_id, array('last_connection' => date('Y-m-d H:i:s')));
		}
		else
		{
			$hORM = ORM::getORMInstance('userconnection', TRUE, FALSE);
			$data = array('killi_user_id' => $user_id,'date' => date('Y-m-d H:i:s'));
			$hORM->create($data);
		}

		return TRUE;
	}

	/**
	 *
	 * Méthodes de gestion d'authentification via CAS.
	 *
	 */
	//-------------------------------------------------------------------------
	public function cas_json_auth()
	{
		/**
		 * Récupération depuis le cache si disponible.
		 */
		if(Cache::$enabled)
		{
			$key = md5($_POST['user/login'] . ':' . $_POST['user/password']);

			if(Cache::get($key, $result))
			{
				return $this->cas_connect($result);
			}
		}

		/* Appel JSON sur CAS pour obtenir les infos de l'utilisateur. */
		$url = CAS_SERVER_URL . 'index.php?action=json.getUserInfos';
		$curl = new KilliCurl($url);
		$curl->setUser($_POST['user/login'], $_POST['user/password']);
		$curl->setSSL(CAS_SERVER_CERT, CAS_SERVER_CERT_PASSWD);
		$result = $curl->request();

		if(isset($result))
		{
			if(Cache::$enabled)
			{
				$key = md5($_POST['user/login'] . ':' . $_POST['user/password']);
				Cache::set($key, $result, 3600); // Mise en cache pendant 1 heure.
			}

			return $this->cas_connect($result);
		}

		return FALSE;
	}

	//-------------------------------------------------------------------------
	public function cas_auth()
	{
		$_SESSION['NO_AUTO_AUTH'] = FALSE;
		$redirect = isset($_GET['redirect']) ? urlencode($_GET['redirect']) : '';
		$redirect = isset($_POST['redirect']) ? urlencode($_POST['redirect']) : $redirect;
		header('Location: ./index.php'.(empty($redirect) ? '' : '?redirect=' . $redirect));
	}

	//-------------------------------------------------------------------------
	public function cas_connect($user_infos)
	{
		$user = array();
		foreach($user_infos AS $field => $value)
		{
			$user[$field] = (array)$value;
		}

		/* Creating or updating user. */
		$new_user = array();

		$hUser = ORM::getObjectInstance('user');
		foreach($hUser AS $field_name => $field)
		{
			if(isset($user[$field_name])
			&& $field->isDbColumn()
			&& $field_name != 'last_connection'
			&& $field_name != 'creation_date'
			&& $field_name != 'date_creation')
			{
				$new_user[$field_name] = $user[$field_name]['value'];
			}
		}

		$hORM = ORM::getORMInstance('user');

		/**
		 * Browse pour éviter la requête de création/update sur la machine locale.
		 */
		$cas_user_id = $user_infos['killi_user_id']['value'];

		$filters = array();
		$filters[] = array('killi_user_id', '=', $cas_user_id);

		foreach($new_user AS $field_name => $value)
		{
			if ($value === NULL)
			{
				$filters[] = array($field_name, 'IS NULL', NULL);
			}
			else
			if(is_numeric($value))
			{
				$filters[] = array($field_name, '=', $value);
			}
			else
			{
				$filters[] = array($field_name, 'LIKE', $value);
			}
		}

		$user_list = array();
		$hORM->browse($user_list, $total, array_keys($user), $filters);
		$user_id = $cas_user_id;

		if(empty($user_list[$cas_user_id]))
		{
			$user_id = NULL;
			$hORM->create($new_user, $user_id, FALSE, $new_user);

			$this->postAuthenticate($user_id);

			if($user_id !== $new_user['killi_user_id'])
			{
				throw new Exception('User id != CAS User id !');
			}
		}

		$saved_user = array();
		$hORM->read($user_id, $saved_user, NULL);

		$_SESSION['_USER'] = $saved_user;

		return TRUE;
	}


	/**
	 *
	 * Méthodes internes.
	 *
	 */
	//---------------------------------------------------------------------
	public static function log_action()
	{
		if (!isset($_GET['action']) || !isset($_SESSION['_USER']))
		{
			return TRUE;
		}

		global $dbconfig;
		$hDB = new DbLayer($dbconfig);

		$action	= $_GET['action'];
		$uid	   = $_SESSION['_USER']['killi_user_id']['value'];
		$type_view = (isset($_GET['view'])) ? '"'.$hDB->db_escape_string($_GET['view']).'"' : 'NULL' ;
		$pk		= (isset($_GET['primary_key'])) ? $_GET['primary_key'] : 'NULL' ;
		$ipv4	  = $_SERVER['REMOTE_ADDR'];

		if ($pk != 'NULL' && !is_numeric($pk))
		{
			return FALSE;
		}

		$query = "insert into killi_user_log set killi_user_id=\"".$hDB->db_escape_string($uid)."\",
				  action=\"".$hDB->db_escape_string($action)."\",
				  type_view=".$type_view.",
				  pk=".$pk.",
				  ipv4=INET_ATON(\"".$hDB->db_escape_string($ipv4)."\")";

		$hDB->db_execute($query,$rows);

	}
}
