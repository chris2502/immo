<?php

namespace Killi\Core\Route\Handler;

/**
 *  Traitant HTTP du routage
 *
 *  @package killi
 *  @class HttpHandler
 *  @Revision $Revision: 4683 $
 */

use \Performance;
use \UndeclaredObjectException;
use \ObjectDoesNotExistsException;
use \Exception;
use \BadUrlException;
use \NoRightsException;
use \CannotDeleteException;
use \SQLException;
use \UserException;

class HttpHandler extends AbstractHandler
{
	public function in()
	{
		parent::in();

		$_GET		= $this->decrypt_inputs($_GET);
		$_POST		= $this->decrypt_inputs($_POST);
		$_REQUEST	= $this->decrypt_inputs($_REQUEST);

		/**
		 * Décomposition de l'action.
		 */
		$object = NULL;
		$method = NULL;
		if (isset($_REQUEST['action']) && $_REQUEST['action']!='null')
		{
			$raw = explode('.', $_REQUEST['action']);

			if(count($raw) <= 1 || empty($raw[0]) || !array_key_exists(1, $raw))
			{
				throw new BadUrlException('Action must be "object.method"');
			}

			$object = $raw[0];
			$method = $raw[1];
		}

		$this->request = $_REQUEST;
		$this->method = $method;
		$this->object = $object;
	}

	public function dispatch_json()
	{
		header('Content-type: application/json');

		$method = Route::getMethod();
		$module = Route::getController();

		$hInstance = new $module();

		if (isset($_POST['urlencoded']) && $_POST['urlencoded'] == '1')
		{
			if ($method == 'CmdAcces')
			{
				// Quickfix pour CmdAcces : le caractère "+" disparaît lors du urldecode()
				// basique, mais pas avec rawurldecode().
				// Le "+" s'avère nécessaire pour passer des commandes de PTO à NC.
				$post_data = array_key_exists('data', $_POST)? rawurldecode($_POST['data']):'';
			}
			else
			{
				$post_data = array_key_exists('data', $_POST)? urldecode($_POST['data']):'';
			}
		}
		else
		{
			$post_data = array_key_exists('data', $_POST)?$_POST['data']:'';
		}
		
		$result = array();
		
		if(!method_exists($hInstance, $method) && preg_match('/^([a-z0-9]+)_(.+)/i', $method, $matches))
		{
			$class_name = 'JSON'.$matches[1].'Method';
			$method = $matches[2];
			$hInstance = new $class_name();
		}
		
		$hInstance->$method(json_decode($post_data,true),$result);

		if (!empty($_SESSION['_ERROR_LIST']) || !empty($_SESSION['_ALERT']['error']))
		{
			$errors = array();
			if(!empty($_SESSION['_ERROR_LIST']))
			{
				foreach($_SESSION['_ERROR_LIST'] AS $title => $message)
				{
					$errors[$title][] = $message;
				}
			}

			if(!empty($_SESSION['_ALERT']))
			{
				foreach($_SESSION['_ALERT']['error'] AS $title => $messages)
				{
					foreach($messages AS $message)
					{
						$errors[$title][] = $message;
					}
				}
			}

			echo json_encode(array('error' => $errors));
		}
		else
		{
			echo json_encode(array('data'=>$result));
		}

		unset($_SESSION['_USER']);
		session_destroy();

		return TRUE;
	}

	public function dispatch()
	{
		$module = Route::getController();
		$object = Route::getObject();
		$method = Route::getMethod();


		$strReferer = '';
		if (isset($_SERVER['HTTP_REFERER']))
		{
			$strReferer = $_SERVER['HTTP_REFERER'].((isset($_GET['inside_iframe']))? '&inside_iframe=1' : '');
		}
		$str_get_action = array_key_exists('action', $_GET)?strval($_GET['action']):'';
		//---Check token
		if (((count($_POST)>0) || (count($_GET)>0)) && isset($_GET['action']) &&
			((($_GET['action']!=='user.authentification') && ($_GET['action']!=='adresse.plaquesXtract')
				&& (strncmp($str_get_action,'json.',5) != 0)
				&& ($_GET['action'] !== 'IpeFT.processing') && ($_GET['action'] !== 'ImmoListe.sentByMail')
				&& ($_GET['action'] !== 'CmdLocalFtth.CmdAcces') && ($_GET['action'] !== 'CmdLocalFtth.ArCmdAcces')
				&& ($_GET['action'] !== 'CmdLocalFtth.CrCmd') && ($_GET['action'] !== 'CmdLocalFtth.CmdStOc')
				&& ($_GET['action'] !== 'CmdLocalFtth.CrCmdStoc') && ($_GET['action'] !== 'CmdLocalFtth.CrMadCmdAcces')
				&&  ($_GET['action'] !== 'CmdLocalFtth.CrMesCmdAcces') && !isset($_GET['crypt/login']))
				&& !isset($_REQUEST['token'])))
		{
			die('Accès direct à cette page interdite');
		}
		global $hDB;
		/**
		 * Vérification de domaine.
		 */
		//---Process contextual domain (from GET)
		if (isset($_GET['domain']))
		{
			$domain							= unserialize($_GET['domain']);
			$hObjectInstance				= ORM::getObjectInstance($object);
			$hObjectInstance->object_domain	= isset($hObjectInstance->object_domain) ? array_merge($hObjectInstance->object_domain,$domain) : $domain;
		}


		// Mode Maintenance
		if (MAINTENANCE)
		{
			$allowed_users = explode(',', ALLOWED_USER_MAINTENANCE);

			$current_user = NULL;
			if (isset($_SESSION['_USER']['login']['value']))
			{
				$current_user = $_SESSION['_USER']['login']['value'];
			}

			if (!in_array($current_user, $allowed_users))
			{
				if (isset($_GET['action']) && substr($_GET['action'], 0, 4) == 'json')
				{
					throw new JSONException('L\'application est en mode maintenance');
				}

				include('./library/killi/include/maintenance_page.html');
				exit(0);
			}
		}

		/**
		 * Chargement de l'objet.
		 */
		if($object != 'json' && $object != 'ajaxcable')
		{
			/* Chargement d'une instance de l'objet si l'objet existe. */
			try
			{
				$hObjectInstance = ORM::getObjectInstance($object);
			}
			catch(UndeclaredObjectException $e)
			{
				try
				{
					ORM::getControllerInstance($object.'Method');
				}
				catch(UndeclaredObjectException $e)
				{
					throw new ObjectDoesNotExistsException('l\'objet \''.$object.'\' ou le controleur \''.$object.'Method\' n\'existent pas');
				}

				// cas d'un appel à un controleur non rattaché à un objet
			}
		}

		/**
		 * Vérification des données d'entrées.
		 */
		//---Check if need to check Data Input
		$error_list = array();
		if ((isset($_POST['form_data_checking'])) &&(($_POST['form_data_checking']==1) &&((($method=='write') || ($method=='create')) && (Constraints::checkFormData($error_list)===False))))
		{
			if(isset($_SESSION['_POST']))
			{
				$_POST = $_SESSION['_POST'];
				unset($_SESSION['_POST']);
			}

			$_SESSION['_POST']	   = $_POST;
			$_SESSION['_ERROR_LIST'] = $error_list;
			header('Location: '.$strReferer);
			exit(0);
		}

		/**
		 * Vérification des droits d'accès.
		 */
		//---Check if allow to manipulate object
		if ((isset($_SESSION['_USER'])) && isset($hObjectInstance))
		{
			if (isset($_SESSION['_USER']['killi_user_id']['value']))
			{
				\KilliJetonMethod::jetonListToSession($_SESSION['_USER']['killi_user_id']['value']);
			}
			if (!$hObjectInstance->view)
			{
				$view = (isset($_GET['view'])) ? $_GET['view'] : 'search' ;
				
				if ($method=='edit' && ($view=='search' || $view=='form') && !\KilliJetonMethod::hasThisJeton($object, $method))
				{
					throw new NoRightsException('Pas de droit de lecture !');
				}
			}
		}
		
		//---Si json
		if($object === 'json')
		{
			return $this->dispatch_json();
		}

		unset($hObjectInstance);

		/**
		 * Instanciation du controleur.
		 */
		if(!class_exists($module))
		{
			throw new BadUrlException('La classe suivante n\'existe pas '.$module);
		}

		$hInstance = new $module();

		if (!method_exists($hInstance, $method) && !method_exists($hInstance, '__call'))
		{
			throw new BadUrlException('Method "' . $method . '" not implemented in "' . $module . '" !');
		}

		/* Le mode visuel peut avoir été changé lors de l'instanciation du module. */
		$view_mode = (isset($_GET['view'])) ? $_GET['view'] : 'search';

		/**
		 * Dispatch et exécution des actions.
		 */

		

		//---Traitement particulier des delete/create
		if($method === 'unlink')
		{
			//---Check rights
			Rights::getCreateDeleteViewStatus ( str_replace('Method','',$module), $create, $delete, $view );

			// Méthode à déclarer dans un objet pour définir les conditions nécessaires à
			// l'obtention d'une dérogation aux droits d'écriture.
			if (method_exists($hInstance, 'setRights'))
			{
				$hInstance->setRights(array($_REQUEST['primary_key']), $create, $delete);
			}

			if ($delete===FALSE)
			{
				throw new CannotDeleteException('Vous n\'avez pas les droits pour effectuer la suppression !');
			}

			try
			{
				$hInstance->unlink($_REQUEST['primary_key']);
			}
			catch(SQLException $e)
			{
				$hDB->db_rollback();
				$_SESSION ['_ERROR_LIST'] ['SYSTEM'] = $e->user_message;

				if(DISPLAY_ERRORS)
				{
					throw $e;
				}
			}
			catch(UserException $e)
			{
				throw $e;
			}
			catch(Exception $e)
			{
				throw new CannotDeleteException($e->getMessage());
			}

			if(!isset($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
			{
				$hDB->db_commit();
				Mailer::commit();
			}
			Performance::stop();

			if(isset($_GET['redirect']) && $_GET['redirect'] == '0')
			{
				header('Content-type: application/json');
				if(!isset($_SESSION['_ERROR_LIST']))
				{
					$data = array('success' => 'Deleted !');
				}
				else
				{
					$data = array('error' => $_SESSION['_ERROR_LIST']);
				}
				echo json_encode($data);
				exit(0);
			}
			if (isset($_GET['inside_popup']) && $_GET['inside_popup']==1)
			{
				//---Refresh page parent + fermeture popup

				UI::refreshOpener();

			}
			else
			{
				//---SI il y a des erreurs, on reste sur la vue formulaire
				if (isset($_SESSION['_ERROR_LIST']))
				{
					header('Location: '.$strReferer);
				}
				else if (isset($_GET['refresh']) && $_GET['refresh']==1)
				{
					UI::goBackWithoutBrutalExit();
				}
				else
				{
					header('Location: '.str_replace('&view=form','&view=search',$strReferer));
				}

				exit(0);
			}
		}
		else
		if($method==='create')
		{
			//---Check rights
			Rights::getCreateDeleteViewStatus ( str_replace('Method','',$module), $create, $delete, $view );

			if(method_exists($hInstance, 'setRights'))
			{
				$hInstance->setRights(NULL, $create, $delete);
			}

			if ($create===FALSE)
			{
				throw new CannotInsertObjectException('Vous n\'avez pas les droits pour effectuer la création !');
			}

			$data=array();

			foreach($_POST as $key=>$value)
				if (isset($_POST['object']))
					if (strncmp($key, $_POST['object'].'/',1+strlen($_POST['object']))==0)
						$data[str_replace($_POST['object'].'/','',$key)] = $value;

			foreach($_GET as $key=>$value)
				if (isset($_GET['object']))
					if (strncmp($key, $_GET['object'].'/',1+strlen($_GET['object']))==0)
						$data[str_replace($_GET['object'].'/','',$key)] = $value;

			try
			{
				$hInstance->create($data,$id);
			}
			catch(SQLException $e)
			{
				$hDB->db_rollback();
				$_SESSION ['_ERROR_LIST'] ['SYSTEM'] = $e->user_message;

				if(DISPLAY_ERRORS)
				{
					throw $e;
				}
			}
			catch(UserException $e)
			{
				throw $e;
			}
			catch(CannotInsertObjectException $e)
			{
				throw $e;
			}
			Performance::stop();

			//---Commit les modifs si pas d'erreurs
			if (!isset($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
			{
				$hDB->db_commit();
				Mailer::commit();
				if(isset($_GET['redirect']) && $_GET['redirect'] == '0')
				{
					header('Content-type: application/json');
					Security::crypt($id, $crypted_id);
					$data = array('success' => 'Created !', 'id' => $crypted_id);
					echo json_encode($data);
					exit(0);
				}
			}
			else
			{
				if(isset($_GET['redirect']) && $_GET['redirect'] == '0')
				{
					header('Content-type: application/json');
					$data = array('error' => $_SESSION['_ERROR_LIST']);

					echo json_encode($data);
					exit(0);
				}

				if(isset($_SESSION['_POST']))
				{
					$_POST = $_SESSION['_POST'];
					unset($_SESSION['_POST']);
				}

				$_SESSION['_POST']	   = $_POST;
				header('Location: '.$strReferer);
				exit(0);
			}

			UI::refreshOpener();
		}
		else
		if($method === 'write')
		{
			$data = $_POST;

			try
			{
				$hInstance->write($data);
			}
			catch(SQLException $e)
			{
				$hDB->db_rollback();
				$_SESSION ['_ERROR_LIST'] ['SYSTEM'] = $e->user_message;

				if(DISPLAY_ERRORS)
				{
					throw $e;
				}
			}
			catch(UserException $e)
			{
				throw $e;
			}
			catch(Exception $e)
			{
				throw $e;
			}

			//---Commit les modifs si pas d'erreurs
			if (!isset($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
			{
				$hDB->db_commit();
				Mailer::commit();
			}
			Performance::stop();
			if(isset($_GET['redirect']) && $_GET['redirect'] == '0')
			{
				header('Content-type: application/json');
				if(!isset($_SESSION['_ERROR_LIST']))
				{
					$data = array('success' => 'Wrote !');
				}
				else
				{
				    $data = array('error' => $_SESSION['_ERROR_LIST']);
				}
				echo json_encode($data);
				exit(0);
			}
			header('Location: '.str_replace('&mode=edition','',$strReferer));
			exit(0);
		}
		else
		if($method === 'edit')
		{
			if(defined('LOCK_WAIT_TIMEOUT'))
			{
				$hDB->db_setLockWaitTimeout(LOCK_WAIT_TIMEOUT); // Lock Wait Timeout à 30 secondes.
			}

			// Afficher les contraintes inhérentes à un noeud de WF.
			if (isset($_GET['workflow_node_id']))
			{
				WorkflowMethod::get_constraints();
			}

			if($view_mode=='form' && (!isset($_GET['primary_key']) || empty($_GET['primary_key'])))
			{
				throw new BadUrlException('Aucune primary key en vue formulaire !');
			}

			if($view_mode=='form' && !preg_match('/^[a-z0-9_,]+$/i', $_GET ['primary_key']))
			{
				throw new BadUrlException('bad pk');
			}

			$template_name = $hInstance->getTemplateFilename();
			$hInstance->edit($view_mode,$data,$num_data,$template_name);

			global $hUI;

			$hUI = new UI();
			$hUI->render('./template/' . $template_name, $num_data, $data);
			unset($hUI);
			unset($data);
		}
		else
		if($method === 'flexigrid')
		{
			if(!isset($_POST['method']))
			{
				$error = array('error' => 'Methode non défini');
				header('HTTP/1.0 400 Bad Request');
				header('Content-type: application/json');
				echo json_encode($error);
				Performance::stop();
				exit(0);
			}
			$fleximethod = $_POST['method'];
			if (!method_exists($hInstance,$fleximethod))
			{
				$error = array('error' => 'Method "' . $fleximethod . '" not implemented in "' . $module . '" !');
				header('HTTP/1.0 400 Bad Request');
				header('Content-type: application/json');
				echo json_encode($error);
				Performance::stop();
				exit(0);
			}

			if(!isset($_POST['object']))
			{
				$error = array('error' => 'Objet non défini');
				header('HTTP/1.0 400 Bad Request');
				header('Content-type: application/json');
				echo json_encode($error);
				Performance::stop();
				exit(0);
			}
			$flexiobject = $_POST['object'];

			$primary_key = isset($_POST['key']) ? $_POST['key'] : NULL;
			$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
			$rp = isset($_POST['rp']) ? (int)$_POST['rp'] : 20;
			$order = NULL;

			if(isset($_POST['sortname']) && !empty($_POST['sortname']) && $_POST['sortname'] != 'undefined' && isset($_POST['sortorder']))
			{
				$sortname = $_POST['sortname'];
				$sortorder = $_POST['sortorder'];
				$order = array($sortname . ' ' . $sortorder);
			}

			$qlike = isset($_POST['query']) && !empty($_POST['query']) ? $_POST['query'] : false;
			$qtype = isset($_POST['qtype']) && !empty($_POST['qtype']) ? $_POST['qtype'] : false;
			$data = array();

			$hInstance->$fleximethod($data,$num_data,($page-1),$rp,$order);

			header('Content-type: application/json');
			$jsonData = array('page'  => $page,
					'total' => $num_data,
					'rows'  => array());

			$hObj = ORM::getObjectInstance($flexiobject);
			foreach($data AS $key => $value) {
				$node = array();
				foreach($value AS $k => $v)
				{
					$read = false;
					/* Si le champ est un attribut de l'objet, on vérifie ses conditions d'accès. */
					if(property_exists($hObj, $k))
					{
						Rights::getRightsByAttribute ( $hObj->$k->objectName, $k, $read, $write );
					}
					else
					{
						$read = true;
					}

					if($read)
					{
						if($k == $primary_key)
						{
							Security::crypt($v['value'], $crypt_key);
							$url = './index.php?action='.$flexiobject.'.edit&inside_popup=1&view=form&token='.$_SESSION['_TOKEN'].'&crypt/primary_key='.$crypt_key;
							$link = '<a href="javascript:void(0)" onclick="window.open(\''.$url.'\',\'popup\',config=\'height=600, width=800, toolbar=no, scrollbars=yes\');">';
							$link .= '<img width="16px" border="0" alt="edit" src="'.KILLI_DIR.'/images/edit.png"/></a>';
							$node[$k] = $link;
						}
						else
						{
							if(isset($v['reference']))
								$node[$k] = $v['reference'];
							else
								$node[$k] = $v['value'];
						}
					}
					else
					{
						$node[$k] = '';
					}
				}
				Security::crypt($key, $crypt_key);

				$entry = array('id'   => $crypt_key,
						'cell' => $node,
				);
				$jsonData['rows'][] = $entry;
			}
			Debug::firephp_end();
			echo json_encode($jsonData);
			Performance::stop();
			exit(0);
		}
		else
		if($method === 'planning')
		{
			if(!isset($_POST['object']) && !isset($_POST['method']))
			{
				$error = array('error' => 'Objet ou method non défini');
				Debug::error('Objet ou method non défini');
				header('HTTP/1.0 400 Bad Request');
				header('Content-type: application/json');
				echo json_encode($error);
				exit(0);
			}
			$plan_object = isset($_POST['object']) ? $_POST['object'] : NULL;
			$plan_method = isset($_POST['method']) ? $_POST['method'] : NULL;

			if ($plan_method !== NULL && !method_exists($hInstance,$plan_method))
			{
				throw new Exception('Method "' . $plan_method . '" not implemented in "' . $module . '" !');
			}

			if(!isset($_POST['start']) || !isset($_POST['end']))
			{
				$error = array('error' => 'Intervalle de temps non défini');
				Debug::error('Intervalle de temps non défini');
				header('HTTP/1.0 400 Bad Request');
				header('Content-type: application/json');
				echo json_encode($error);
				Performance::stop();
				exit(0);
			}

			$start = date('Y-m-d', $_POST['start']);
			$end = date('Y-m-d', $_POST['end']);

			$field_prefix = '';

			if($plan_method !== NULL)
			{
				$hInstance->$plan_method($data, $start, $end, $object, $primary_key);
				$pKey = $primary_key;
				$obj = $object;
			}
			else
			if($plan_object !== NULL)
			{
				try {
					$objMethod = $plan_object.'Method';
					if(!class_exists($objMethod))
					{
						throw new Exception('Classe ' . $objMethod . ' inexistante !');
					}

					$hSInstance = new $objMethod();

					if(!method_exists($hSInstance, 'planning'))
					{
						throw new Exception('La classe ' . $objMethod . ' n\'étend pas la classe Common ou n\'implemente pas la méthode planning !');
					}

					$hSInstance->planning($data, $start, $end, $plan_object, $pKey);
					$obj = $plan_object;
					$hObjInstance = ORM::getObjectInstance($plan_object);
					if(isset($hObjInstance->field_prefix))
					{
						$field_prefix = $hObjInstance->field_prefix;
					}
				} catch(Exception $e)
				{
					$error = array('error' => $e->getMessage(), 'details' => $e->getTraceAsString(), 'last_query' => $hDB->last_query);
					Debug::error($e);
					Debug::error(array('Message' => $e->getMessage(), 'Trace' => $e->getTraceAsString()));

					header('HTTP/1.0 400 Bad Request');
					header('Content-type: application/json');
					echo json_encode($error);
					Performance::stop();
					exit(0);
				}
			}

			$jsonData = array();
			foreach($data AS $key => $value)
			{
				$event = array();
				Security::crypt($value[$pKey]['value'], $id);

				$event['id'] = $id;
				$event['title'] = isset($value[$pKey]['reference']) ? $value[$pKey]['reference'] : '';

				if(isset($value['unavailable']['value']) && $value['unavailable']['value'] == true)
				{
					$event['className'] = 'layer-event';
					$event['editable'] = false;
					$event['unavailable'] = true;
				}
				else
				{
					if(!(isset($value[$field_prefix . 'date']['editable'])
						&& isset($value[$field_prefix . 'debut']['editable'])
						&& isset($value[$field_prefix . 'fin']['editable'])
						&& $value[$field_prefix . 'date']['editable'] && $value[$field_prefix . 'debut']['editable'] && $value[$field_prefix . 'fin']['editable']))
					{
						$event['editable'] = false;
					}

					if (isset($value['editable']['value']))
					{
						$event['editable'] = $value['editable']['value'];
					}

					if (isset($value['deletable']['value']))
					{
						$event['deletable'] = $value['deletable']['value'];
					}

					if (isset($event['editable']) && $event['editable'] == true)
					{
						if (isset($value['resizable']['value']))
						{
							$event['disableResizing'] = !$value['resizable']['value'];
						}

						if (isset($value['draggable']['value']))
						{
							$event['disableDragging'] = !$value['draggable']['value'];
						}
					}

					$event['objectid'] = $id;
					$event['object'] = $obj;
				}

				if (isset($value['color']['value']))
				{
					$event['color'] = $value['color']['value'];
				}

				if (isset($value['opacity']['value']))
				{
					$event['opacity'] = $value['opacity']['value'];
				}

				$day = date('Y-m-d', $value[$field_prefix . 'date']['value']);
				$event['start'] = $day . 'T' . $value[$field_prefix . 'debut']['value'] . 'Z';
				$event['end'] = $day . 'T' . $value[$field_prefix . 'fin']['value'] . 'Z';
				$event['duration'] = (strtotime($value[$field_prefix . 'fin']['value']) - strtotime($value[$field_prefix . 'debut']['value'])) / 60;

				$jsonData[] = $event;
			}

			header('Content-type: application/json');
			Debug::firephp_end();
			echo json_encode($jsonData);
			Performance::stop();
			exit(0);
		}
		else
		if($method === 'moveEvent')
		{
			$data = array();
			if(!isset($_POST['allday']) || !isset($_POST['daydelta']) || !isset($_POST['minutedelta']) || !isset($_POST['id']) || !isset($_POST['object']))
			{
				$data['error'] = 'Missing parameters.';
			}
			else
			{
				Security::decrypt($_POST['id'], $id);
				$hInstance->moveEvent($data, $id, $_POST['allday'], $_POST['daydelta'], $_POST['minutedelta']);
			}

			if (!isset($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
			{
				$hDB->db_commit();
				Mailer::commit();
			}
			Debug::firephp_end();
			header('Content-type: application/json');
			echo json_encode($data);
			Performance::stop();
			exit(0);
		}
		else
		if($method === 'resizeEvent')
		{
			$data = array();
			if(!isset($_POST['daydelta']) || !isset($_POST['minutedelta']) || !isset($_POST['id']) || !isset($_POST['object']))
			{
				$data['error'] = 'Missing parameters.';
			}
			else
			{
				Security::decrypt($_POST['id'], $id);
				$hInstance->resizeEvent($data, $id, $_POST['daydelta'], $_POST['minutedelta']);
			}

			if (!isset($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
				$hDB->db_commit();
			Debug::firephp_end();
			header('Content-type: application/json');
			echo json_encode($data);
			Performance::stop();
			exit(0);
		}
		else
		if($method === 'autocomplete')
		{
			$data=array();
			$hInstance->autocomplete($data);
			echo json_encode($data);
			Performance::stop();
			exit(0);
		}
		else
		if($method === 'refresh')
		{
			$data = array();
			$fields = array();
			$objectMethods = array();
			foreach($_POST['fields'] AS $num => $field)
			{
				foreach($field as $key => $value)
				{
					if (strncmp($key,'crypt/',6)==0)
					{
						$decrypted_value = '';

						Security::decrypt($value, $decrypted_value);

						$substr_key_6			= substr($key,6);
						$field[$substr_key_6]	= $decrypted_value;
					}
				}
				if(!isset($objectMethods[$field['parent_object']]['class']))
				{
					$obm = strtoupper($field['parent_object'][0]).substr($field['parent_object'],1).'Method';
					if(class_exists($obm))
						$objectMethods[$field['parent_object']]['class'] = new $obm();
					else
						throw new Exception('La classe suivante n\'existe pas '.$obm);
				}
				$objectMethods[$field['parent_object']]['fields'][] = $field;
			}

			foreach($objectMethods AS $object => $request)
			{
				$objInstance = $request['class'];
				$output = array();
				$objInstance->refresh($output, $request['fields']);

				foreach($request['fields'] AS $num => $field)
				{
					if(isset($output[$field['key']]))
					{
						$data[$field['key']] = $output[$field['key']];
					}
				}
			}
			Performance::stop();
			Debug::firephp_end();
			header('Content-type: application/json');
			echo json_encode($data);
			exit(0);
		}
		else
		if($method === 'reporting')
		{
			$hInstance->reporting($data,$num_data,$template_name);

			if ($template_name==NULL)
				$template_name = $object.'.xml';

			global $hUI;

			$hUI = new UI();
			$hUI->render('./reporting/template/' . $template_name, $num_data, $data);
			unset($hUI);
		}
		else
		if($method === 'reportingexportcsv')
		{
			$hInstance->reportingexportcsv($data,$num_data,$object.'.xml');
		}
		else
		if(stripos($object, 'ajax') === 0 && $method==='call')
		{
			$hInstance->call();
		}
		else
		if($method === 'wizard') // BADFIX: Pour éviter le view mode...
		{
			$hInstance->$method();
		}
		else
		{
			if(method_exists($hInstance, 'pre_hook_'.$method))
				call_user_func(array(&$hInstance, 'pre_hook_'.$method), 'pre_hook_'.$method, $view_mode);

			if(!isset($_POST['selected']))
			{
				$_POST['selected']=array();
			}

			if(!isset($_POST['listing_selection']))
			{
				$_POST['listing_selection']=array();
			}

			$hInstance->$method($view_mode);

			if(method_exists($hInstance, 'post_hook_'.$method))
				call_user_func(array(&$hInstance, 'post_hook_'.$method), 'post_hook_'.$method, $view_mode);
		}
		unset($hInstance);
	}

	public function out()
	{
		global $hDB;

		/**
		 * Validation des changements en base de données.
		 */
		//---Commit les modifs si pas d'erreurs
		if(!isset($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
		{
			$hDB->db_commit();
			Mailer::commit();
		}
	}

	/**
	 * Decrypt les input (POST / GET / REQUEST)
	 */
	private function decrypt_inputs(array $array)
	{
		foreach($array as $c_key => $value)
		{
			if (strncmp($c_key, 'crypt/', 6) == 0)
			{
				$key   = substr($c_key,6);
				if(CHECK_SECURITY_ISSUE && isset($array[$key]))
				{
					throw new Exception('Un paramètre \''. $c_key.'\' de type POST crypté est présent avec sa valeur non crypté dans la requête.');
				}

				$decrypted_value = '';
				Security::decrypt($value, $decrypted_value);

				$array[$key] = $decrypted_value;
			}
			else
			if (is_array($value))
			{
				$array[$c_key] = $this->decrypt_inputs($value);
			}
		}

		return $array;
	}
}
