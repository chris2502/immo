<?php

use Killi\Core\Application\Middleware\ObjectRightsMiddleware;
/**
 *  @class JetonMethod
 *  @Revision $Revision: 672 $
 *
 */

class KilliJetonMethod extends Common
{
	public function edit($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		parent::edit($view, $data, $total_object_list, $template_name);
		
		// pour fonctionnement alt
		if (isset($_GET['jeton']) && !empty($_GET['jeton']))
		{
			$is_exist = FALSE;
			self::checkJetonExist($_GET['jeton'], TRUE, $is_exist);
			if ($is_exist == TRUE)
			{
				$is_linked = FALSE;
				self::checkJetonUser($_GET['jeton'], $_SESSION['_USER']['killi_user_id']['value'], TRUE, $is_linked);
				if ($is_linked == TRUE)
				{
					$jeton_id = NULL;
					self::getJetonId($_GET['jeton'], $jeton_id);
					self::goToURL($jeton_id);
				}
			}
		}
		
		if ($view == 'panel')
		{
			$this->getTypeJetonList($data['typejeton_list']);
		}
		
		return TRUE;
	}
	
	public static function getJetonURL($jeton_id, &$url)
	{
		$url = NULL;
		
		$jeton_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->read(array($jeton_id), $jeton_list, array('url'));
		
		$url = $jeton_list[$jeton_id]['url']['value'];
	}
	
	public static function getJetonDestinataire($jeton_id, &$destinataire_data)
	{
		$destinataire_data = array();
	
		$jeton_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->read(array($jeton_id), $jeton_list, array('destinataire_id'));
		
		$contact_list = array();
		$hORM_Contact = ORM::getORMInstance('cascontact');
		$hORM_Contact->read(array($jeton_list[$jeton_id]['destinataire_id']['value']), $contact_list, array('nom_complet', 'mail'));
		
		$destinataire_data = reset($contact_list);
	}
	
	public static function goToURL($jeton_id = NULL)
	{
		if (isset($_GET['crypt/primary_key']) && !empty($_GET['crypt/primary_key']))
		{
			$jeton_id = NULL;
			Security::decrypt($_GET['crypt/primary_key'], $jeton_id);
		}
			
		self::getJetonURL($jeton_id, $url);
		header('Location: '.$url);
		Alert::info('Aller vers', 'Vous avez été redirigé.');
		
		return TRUE;
	}
	
	public static function sendURL($jeton_id = NULL)
	{
		if (isset($_GET['crypt/primary_key']) && !empty($_GET['crypt/primary_key']))
		{
			$jeton_id = NULL;
			Security::decrypt($_GET['crypt/primary_key'], $jeton_id);
		}
		
		$url = NULL;
		self::getJetonURL($jeton_id, $url);
		
		$destinataire_data = array();
		self::getJetonDestinataire($jeton_id, $destinataire_data);
		
		$jeton_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->read(array($jeton_id), $jeton_list, array('name', 'type_jeton_id', 'object'));
		
		$name = $jeton_list[$jeton_id]['name']['value'];
		/*
		if (isset(ORM::getObjectInstance($jeton_list[$jeton_id]['object']['value'])->description) && !empty(ORM::getObjectInstance($jeton_list[$jeton_id]['object']['value'])->description))
		{
			$name = ORM::getObjectInstance($jeton_list[$jeton_id]['object']['value'])->description;
		}
		*/
		
		$title = 'Jeton : '.$name;
		$message = array();
		$message[0] = 'Vous recevez cet email car '.$_SESSION['_USER']['prenom']['value'].' '.$_SESSION['_USER']['nom']['value']. ' vous a accordé une authorisation spéciale sur '.HEADER_MESSAGE.'.';
		$message[1] = ' ';
		$message[2]['button']['label'] = 'Cliquez ici pour accédez à l\'interface';
		$message[2]['button']['url'] = $url;

		$mailer = new Mailer();
		$mailer->setFrom($_SESSION['_USER']['mail']['value'], $_SESSION['_USER']['nom_complet']['value'])
			->setSubject($title)->setTitle($title)
			->setMail($destinataire_data['mail']['value'], $destinataire_data['nom_complet']['value'])
			->setMessage($message)
			->isHTML(TRUE)
			->send();
		
		Alert::info('Envoyer le lien', 'Le lien a été envoyé vers le destinataire.');
		
		KilliUI::goBackWithoutBrutalExit();
	
		return TRUE;
	}
	
	public static function disableJeton($jeton_id = NULL, $need_alert = FALSE)
	{
		if (isset($_GET['crypt/primary_key']) && !empty($_GET['crypt/primary_key']))
		{
			$jeton_id = NULL;
			Security::decrypt($_GET['crypt/primary_key'], $jeton_id);
		}
		
		$jeton_data = array('actif' => 0);
		
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->write($jeton_id, $jeton_data);
		
		self::logThatJeton($jeton_id);
		
		if ($need_alert == TRUE)
		{
			Alert::info('Jeton', 'Jeton désactivé.');
		}
	
		return TRUE;
	}
	
	public static function enableJeton($jeton_id = NULL, $need_alert = FALSE)
	{
		if (isset($_GET['crypt/primary_key']) && !empty($_GET['crypt/primary_key']))
		{
			$jeton_id = NULL;
			Security::decrypt($_GET['crypt/primary_key'], $jeton_id);
		}
	
		$jeton_data = array('actif' => 1);
	
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->write($jeton_id, $jeton_data);
		
		self::logThatJeton($jeton_id);
	
		if ($need_alert == TRUE)
		{
			Alert::info('Jeton', 'Jeton activé.');
		}
	
		return TRUE;
	}
	
	public static function changeJetonState($jeton_id = NULL)
	{
		if (isset($_GET['crypt/primary_key']) && !empty($_GET['crypt/primary_key']))
		{
			$jeton_id = NULL;
			Security::decrypt($_GET['crypt/primary_key'], $jeton_id);
		}
		
		$jeton_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->read(array($jeton_id), $jeton_list, array('actif'));
		
		if ($jeton_list[$jeton_id]['actif']['value'] == 1)
		{
			self::disableJeton($jeton_id, TRUE);
		} else {
			self::enableJeton($jeton_id, TRUE);
		}
		
		KilliUI::goBackWithoutBrutalExit();
		
		return TRUE;
	}
	
	public static function checkJetonExist($jeton, $need_alert = FALSE, &$is_exist)
	{
		$is_exist = FALSE;
		
		$jeton_id_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->search($jeton_id_list, $total, array(array('code', '=', $jeton)));
		if (!empty($jeton_id_list))
		{
			$is_exist = TRUE;
		} else {
			if ($need_alert == TRUE)
			{
				Alert::error('Jeton', 'Ce jeton n\'existe pas.');
			}
		}
	}
	
	public static function checkJetonUser($jeton, $user_id, $need_alert = FALSE, &$is_linked)
	{
		$is_linked = FALSE;
		
		$jeton_id_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->search($jeton_id_list, $total, array(array('code', '=', $jeton), array('destinataire_id', '=', $user_id)));
		if (!empty($jeton_id_list))
		{
			$is_linked = TRUE;
		} else {
			if ($need_alert == TRUE)
			{
				Alert::error('Jeton', 'Ce jeton ne vous est pas attribué.');
			}
		}
	}
	
	public static function getJetonId($jeton, &$jeton_id)
	{
		$jeton_id = NULL;
		
		$jeton_id_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->search($jeton_id_list, $total, array(array('code', '=', $jeton)));
		
		$jeton_id = reset($jeton_id_list);
	}
	
	public static function getJeton($jeton_id, &$jeton)
	{
		$jeton = NULL;
	
		$jeton_id_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->search($jeton_id_list, $total, array(array('jeton_id', '=', $jeton_id)));
		if (!empty($jeton_id_list))
		{
			$jeton_list = array();
			$hORM_Jeton->read($jeton_id_list, $jeton_list, array('code'));
			
			$jeton = $jeton_list[$jeton_id]['code']['value'];
		} else {
			Alert::error('Jeton', 'Ce jeton n\'existe pas.');
		}
	
		$jeton_id = reset($jeton_id_list);
	}
	
	public static function hasJeton($user_id, &$jeton_id_list)
	{
		$jeton_id_list = array();
		
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->search($jeton_id_list, $total, array(array('destinataire_id', '=', $user_id), array('actif', '=', 1)));
	}
	
	public static function hasThisJeton($object = NULL, $method = NULL, $object_id = NULL)
	{
		// env
		$action_tab = NULL;
		if (isset($_GET['action']) && !empty($_GET['action']))
		{
			$action_tab = explode('.', $_GET['action']);
		}
		
		$crypt_primary_key = NULL;
		if (isset($_GET['crypt/primary_key']) && !empty($_GET['crypt/primary_key']))
		{
			$crypt_primary_key = $_GET['crypt/primary_key'];
		}
		
		// si pas def
		if ($object == NULL && isset($action_tab[0]))
		{
			$object = $action_tab[0];
		}
		
		if ($method == NULL && isset($action_tab[1]))
		{
			$method = $action_tab[1];
		}
		
		if ($object_id == NULL && isset($crypt_primary_key))
		{
			Security::decrypt($crypt_primary_key, $object_id);
		}
		
		if (isset($_SESSION['_JETON']) && !empty($_SESSION['_JETON']))
		{
			foreach ($_SESSION['_JETON'] as $kj => $vj)
			{
				if ($vj['object']['value'] == $object)
				{
					if (!isset($vj['method']['value']) || $vj['method']['value'] == $method)
					{
						if (!isset($vj['object_id']['value']) || $vj['object_id']['value'] == $object_id)
						{
							self::logThatJeton($kj, $object_id);
							
							$_SESSION['_JETON'][$kj]['actif']['value'] = 0;
							Alert::info('Utilisation d\'un Jeton', 'Le Jeton '.$vj['code']['value'].' est en cours d\'utilisation.');							
							return TRUE;
						}
					}
				}
			}
		}
		
		return FALSE;
	}
	
	public static function fakeProfil($user_id)
	{
		$userprofil_id_list = array();
		$hORM_UserProfil = ORM::getORMInstance('userprofil');
		$hORM_UserProfil->search($userprofil_id_list, $total, array(array('killi_user_id', '=', $user_id)));
		if (empty($userprofil_id_list))
		{
			$profil_id_list = array();
			$hORM_Profil = ORM::getORMInstance('profil');
			$hORM_Profil->search($profil_id_list, $total);
			if (!empty($profil_id_list))
			{
				$fakeprofil_id = intval(max($profil_id_list) + 1);
				$_SESSION['_USER']['profil_id']['value'][] = $fakeprofil_id;
			} else {
				throw new Exception('Pas de Profil existant dans l\'application !');
			}
		}
	}
	
	public static function fakeAuth($user_id)
	{
		$jeton_id_list = array();
		self::hasJeton($user_id, $jeton_id_list);
		if (!empty($jeton_id_list))
		{
			self::fakeProfil($user_id);
			//KilliUI::goBackWithoutBrutalExit();
		}
	}
	
	public static function cleanSession()
	{
		$action_tab = NULL;
		if (isset($_GET['action']) && !empty($_GET['action']))
		{
			$action_tab = explode('.', $_GET['action']);
		}
		
		if (isset($_SESSION['_JETON']) && !empty($_SESSION['_JETON']))
		{
			if ($action_tab[0] == 'user' && $action_tab[1] == 'disconnect')
			{
				foreach ($_SESSION['_JETON'] as $kj => $vj)
				{
					if ($vj['actif']['value'] != 1)
					{
						self::disableJeton($kj, FALSE);
					}
				}
			}
		}
		
		return TRUE;
	}
	
	public static function jetonListToSession($user_id)
	{
		self::cleanSession();
		
		$_SESSION['_JETON'] = array();
		
		$jeton_id_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->search($jeton_id_list, $total, array(array('destinataire_id', '=', $user_id), array('actif', '=', 1)));
		if (!empty($jeton_id_list))
		{
			$jeton_list = array();
			$hORM_Jeton->read($jeton_id_list, $jeton_list, array('code', 'object_id', 'object', 'method', 'actif'));
			$_SESSION['_JETON'] = $jeton_list;
		}
	}
	
	public function getJetonList(&$datasrc, &$total, $limit, $offset, $statut_list = NULL)
	{
		$jeton_id_list = array();
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->search($jeton_id_list, $total);
		if (!empty($jeton_id_list))
		{
			$jeton_list = array();
			$hORM_Jeton->read($jeton_id_list, $jeton_list, array('code', 'actif'));
			$datasrc = $jeton_list;
		}
		
		return TRUE;
	}
	
	public function getTypeJetonList(&$typejeton_list)
	{
		$typejeton_list = array();
		
		$typejeton_id_list = array();
		$hORM_TypeJeton = ORM::getORMInstance('typejeton');
		$hORM_TypeJeton->search($typejeton_id_list, $total);
		if (!empty($typejeton_id_list))
		{
			$hORM_TypeJeton->read($typejeton_id_list, $typejeton_list, array('type_jeton_id', 'name'));
		}
	}
	
	public function ajaxContactSearch()
	{
		$q = strtolower($_REQUEST['term']);
	
		$hORM_Contact = ORM::getORMInstance('cascontact');
	
		$search_field_list = array('nom', 'prenom', 'mail');
	
		$users_id_filter = NULL;
		
		$contact_id_list = array();
		foreach ($search_field_list as $field)
		{
			$args = NULL;
			$args[] = array($field, 'LIKE',  $q.'%');
	
			if (isset($users_id_filter))
			{
				$args[] = array('killi_user_id', 'IN', $users_id_filter);
			}
	
			$new_contact_id_list = array();
			$hORM_Contact->search(
					$new_contact_id_list,
					$total,
					$args
			);
			$contact_id_list = array_merge($new_contact_id_list, $contact_id_list);
		}
	
		$contact_list = array();
		$hORM_Contact->read(
				$contact_id_list,
				$contact_list,
				array('prenom', 'nom', 'mail', 'type_contact_id')
		);
	
		$response = array();
		foreach ($contact_list as $c_id => $c_data)
		{
			$c_id_crypted = '';
			Security::crypt($c_id, $c_id_crypted);
	
			$response[] = array(
					'label' => $c_data['prenom']['value'].' '.$c_data['nom']['value'].' ('.$c_data['type_contact_id']['reference'].') : '.$c_data['mail']['value'],
					'crypted_id' => $c_id_crypted,
					'prenom' => $c_data['prenom']['value'],
					'nom' => $c_data['nom']['value'],
					'mail' => $c_data['mail']['value'],
					'type' => $c_data['type_contact_id']['reference']
			);
		}
	
		echo json_encode($response);
	}
	
	public static function generateCode(&$code)
	{
		$code = NULL;
		$is_exist = 0;
		
		$code = uniqid(NULL, FALSE);
		
		self::checkJetonExist($code, FALSE, $is_exist);
		if ($is_exist == 1)
		{
			self::generateCode($code);
		}
	}
	
	public static function createJeton($destinataire_id, $type_jeton_id, $object_id = NULL, $end_time = NULL, &$jeton)
	{
		$jeton_id = NULL;
		$jeton = NULL;
		
		$jeton_data = array('destinataire_id' => $destinataire_id,
							'type_jeton_id' => $type_jeton_id,
							'killi_user_id' => $_SESSION['_USER']['killi_user_id']['value']);
		if (isset($object_id) && !empty($object_id))
		{
			$jeton_data['object_id'] = $object_id;
		}
		if (isset($end_time) && !empty($end_time))
		{
			$jeton_data['end_time'] = $end_time;
		}
		self::generateCode($jeton_data['code']);
		
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_Jeton->create($jeton_data, $jeton_id);
		
		self::sendURL($jeton_id);
		self::getJeton($jeton_id, $jeton);
	}
	
	public function write($data)
	{
		$jeton = NULL;
		$this->createJeton($data['destinataire_id'], $data['type_jeton_id'], NULL, NULL, $jeton);
		
		Alert::success('Jeton', 'Le jeton suivant a été créé : '.$jeton.'.');
		
		KilliUI::quitNBack(NULL, FALSE, TRUE);
		
		parent::write($data);
		
		return TRUE;
	}
	
	public static function logThatJeton($jeton_id, $object_id = NULL)
	{
		$hORM_Jeton = ORM::getORMInstance('jeton');
		$hORM_JetonLog = ORM::getORMInstance('jetonlog');
		
		$jetonlog_id = NULL;
		$jetonlog_data = array('killi_user_id' => $_SESSION['_USER']['killi_user_id']['value']);
		
		$fields = array('jeton_id', 'code', 'actif', 'object', 'method', 'end_time');
		if (!isset($object_id) || empty($object_id))
		{
			$fields[] = 'object_id';
		} else {
			$jetonlog_data['object_id'] = $object_id;
		}
		
		$jeton_list = array();
		$hORM_Jeton->read(array($jeton_id), $jeton_list, $fields);
		
		foreach ($fields as $kf => $vf)
		{
			if (!isset($object_id) || empty($object_id))
			{
				$jetonlog_data[$vf] = $jeton_list[$jeton_id][$vf]['value'];
			} else {
				if ($vf != 'object_id')
				{
					$jetonlog_data[$vf] = $jeton_list[$jeton_id][$vf]['value'];
				}
			}
		}
		
		$hORM_JetonLog->create($jetonlog_data, $jetonlog_id);
		if (!isset($jetonlog_id) || empty($jetonlog_id))
		{
			throw new Exception('Erreur lors de la tentative d\'inscription du suivi.');
		}
	}
}