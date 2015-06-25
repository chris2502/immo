<?php

/**
 *  @class KilliJSONMethod
 *  @Revision $Revision: 4636 $
 *
 */

abstract class KilliJSONMethod
{
	protected $_hDB = NULL;

	//---------------------------------------------------------------------
	function __construct()
	{
		global $hDB;
		$this->_hDB = &$hDB;
	}
	//---------------------------------------------------------------------
	public function ping(array $data, array &$result)
	{
		$result = "pong";
	}
	//---------------------------------------------------------------------
	public function doyouknow(array $data, array &$result)
	{
		if(!isset($data['object']))
		{
			throw new JSONException('object required');
		}
		
		if(!isset($data['keys']))
		{
			throw new JSONException('key required');
		}
		
		if(!is_array($data['keys']))
		{
			throw new JSONException('key must be an array');
		}
		
		if(!isset($data['uid']))
		{
			throw new JSONException('uid required');
		}
		
		try
		{
			$hORM = ORM::getORMInstance($data['object']);
			
			$hInstance = ORM::getObjectInstance($data['object']);
		}
		catch (Exception $e)
		{
			throw new JSONException($e->getMessage());
		}
		
		if(!isset($hInstance::$_mtime))
		{
			throw new JSONException('doyouknow not implemented on this object');
		}
		
		// default
		foreach($data['keys'] as $key)
		{
			$result[$key] = NULL;
		}
		
		// request
		try
		{
			$hORM->browse ( $match_list, $total_record, array($hInstance::$_mtime, $data['uid']), array(array($data['uid'],'in',$data['keys'])));
		}
		catch (Exception $e)
		{
			throw new JSONException($e->getMessage());
		}
		
		// mapping
		foreach($match_list as $match)
		{
			$result[$match[$data['uid']]['value']] = $match[$hInstance::$_mtime]['timestamp'];
		}
	}
	//---------------------------------------------------------------------
	public function getobjectrights(array $data, array &$result)
	{
		foreach($data['object_list'] as $object_name)
		{
			if(!ORM::$_objects[$object_name]['rights'])
			{
				continue;
			}
			
			$result[$object_name]=array(
				'create'=>FALSE,
				'delete'=>FALSE,
				'view'=>FALSE
			);
			
			Rights::getCreateDeleteViewStatus($object_name,$result[$object_name]['create'], $result[$object_name]['delete'], $result[$object_name]['view']);
		}
	}
	//---------------------------------------------------------------------
	public function download(array $data, array &$result)
	{
		ORM::getControllerInstance($data['object'])->getContent($data['document_id']);
		
		die();
	}
	//---------------------------------------------------------------------
	public function read(array $data, array &$result)
	{
		/* Attributs :
		 *
		 * object (required) : Objet de l'ORM - string("nro")
		 * keys (required, not empty) : id des occurences à sélectionner - array(124,546,789,1233)
		 * fields : liste des attributs de l'objet à récuperer - array('code_nro','adresse_id')
		 *
		 */
		if (! isset ( $data ['object'] ))
		{
			throw new JSONException ( 'object not specified' );
		}

		try
		{
			$hORM = ORM::getORMInstance ( $data ['object'] );
		}
		catch ( Exception $e )
		{
			throw new JSONException ( $e->getMessage () );
		}

		if (! isset ( $data ['keys'] ) || ! (is_numeric($data ['keys']) || is_array ( $data ['keys'] )) || empty ( $data ['keys'] ) )
		{
			throw new JSONException ( 'keys not specified || keys must be an array || keys must not be empty' );
		}

		if (! isset ( $data ['fields'] ) || ! is_array ( $data ['fields'] ) || empty ( $data ['fields'] ))
		{
			$data ['fields'] = NULL;
		}

		Rights::getCreateDeleteViewStatus ( $data ['object'], $create, $delete, $view );

		if (! $view)
		{
			throw new JSONException ( 'forbidden, cannot view this object' );
		}

		$result = array ();

		$hORM->read ( $data ['keys'], $result, $data ['fields'] );

		// test si l'utilisateur peut lire les attributs
		$hInstance = ORM::getObjectInstance ( $data ['object'] );
		if (is_array($data ['keys']))
		{
			foreach ( $result as $object_id => $object )
			{
				$this->_readApplyAttributeRights($data ['object'], $hInstance, $result[$object_id]);
			}
		}
		else
		{
			$this->_readApplyAttributeRights($data ['object'], $hInstance, $result);
		}
		return TRUE;
	}

	protected function _readApplyAttributeRights($object_name, $hInstance, &$object)
	{
		foreach ( $object as $attribute_name => $attribute )
		{
			if (! property_exists ( $hInstance, $attribute_name ))
			{
				continue;
			}
			Rights::getRightsByAttribute ( $object_name, $attribute_name, $read, $write );

			if (! $read)
			{
				$object[$attribute_name] = array (
					'value' => NULL
				);
			}
		}
		return TRUE;
	}

	//---------------------------------------------------------------------
	public function search(array $data, array &$result)
	{
		/* Attributs :
		 *
		 * object (required) : Objet de l'ORM - string("nro")
		 * filter (required) : filtre de sélection - array(array('code_nro','like','C%'))
		 * limit : nombre d'occurence max - int(250)
		 * offset : curseur de départ - int(100)
		 * order : ordre de sélection - array('code_nro asc')
		 *
		 */
		if (! isset ( $data ['object'] ))
		{
			throw new JSONException ( 'object not specified' );
		}

		try
		{
			$hORM = ORM::getORMInstance ( $data ['object'] );
		}
		catch ( Exception $e )
		{
			throw new JSONException ( $e->getMessage () );
		}

		$data ['filter'] = isset ( $data ['filter'] ) ? $data ['filter'] : array ();
		
		if (!is_array ( $data ['filter'] ))
		{
			throw new JSONException ( 'filter must be an array !' );
		}
		
		$data ['limit'] = isset ( $data ['limit'] ) ? $data ['limit'] : NULL;

		if ($data ['limit'] != NULL && ! is_numeric ( $data ['limit'] ))
		{
			throw new JSONException ( 'limit must be numeric !' );
		}

		$data ['offset'] = isset ( $data ['offset'] ) ? $data ['offset'] : 0;
		
		if (! is_numeric ( $data ['offset'] ))
		{
			throw new JSONException ( 'offset must be numeric !' );
		}
		
		$data ['order'] = isset ( $data ['order'] ) ? $data ['order'] : array();

		if (! is_array ( $data ['order'] ))
		{
			throw new JSONException ( 'order must be array !' );
		}

		Rights::getCreateDeleteViewStatus ( $data ['object'], $create, $delete, $view );

		if (! $view)
		{
			throw new JSONException ( 'forbidden, cannot view this object' );
		}

		$result = array ();

		$hORM->search ( $result, $num, $data ['filter'], $data ['order'], $data ['offset'], $data ['limit'] );

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function count(array $data, array &$result)
	{
		/* Attributs :
		 *
		 * object (required) : Objet de l'ORM - string("nro")
		 * filter (required) : filtre de sélection - array(array('code_nro','like','C%'))
		 *
		 */
		if (! isset ( $data ['object'] ))
		{
			throw new JSONException ( 'object not specified' );
		}

		try
		{
			$hORM = ORM::getORMInstance ( $data ['object'] );
		}
		catch ( Exception $e )
		{
			throw new JSONException ( $e->getMessage () );
		}
		
		$data ['filter'] = isset ( $data ['filter'] ) ? $data ['filter'] : array ();

		if (! is_array ( $data ['filter'] ))
		{
			throw new JSONException ( 'filter must be an array' );
		}

		Rights::getCreateDeleteViewStatus ( $data ['object'], $create, $delete, $view );

		if (! $view)
		{
			throw new JSONException ( 'forbidden, cannot view this object' );
		}

		$result = 0;

		$hORM->count ( $result, $data ['filter'] );

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function browse(array $data, array &$result)
	{
		/* Attributs :
		 *
		 * object (required) : Objet de l'ORM - string("nro")
		 * filter (required) : filtre de sélection - array(array('code_nro','like','C%'))
		 * fields : liste des attributs de l'objet à récuperer - array('code_nro','adresse_id')
		 * limit : nombre d'occurence max - int(250)
		 * offset : curseur de départ - int(100)
		 * order : ordre de sélection - array('code_nro asc')
		 *
		 */
		
		if (! isset ( $data ['object'] ))
		{
			throw new JSONException ( 'object not specified' );
		}
		
		try
		{
			$hORM = ORM::getORMInstance ( $data ['object'] );
		}
		catch ( Exception $e )
		{
			throw new JSONException ( $e->getMessage () );
		}
		
		if (! isset ( $data ['fields'] ) || ! is_array ( $data ['fields'] ) || empty ( $data ['fields'] ))
		{
			$data ['fields'] = NULL;
		}
		
		$data ['filter'] = isset ( $data ['filter'] ) ? $data ['filter'] : array ();
		
		if (!is_array ( $data ['filter'] ))
		{
			throw new JSONException ( 'filter must be an array !' );
		}
		
		$data ['limit'] = isset ( $data ['limit'] ) ? $data ['limit'] : NULL;
		
		if ($data ['limit'] != NULL && ! is_numeric ( $data ['limit'] ))
		{
			throw new JSONException ( 'limit must be numeric !' );
		}
		
		$data ['offset'] = isset ( $data ['offset'] ) ? $data ['offset'] : 0;
		
		if (! is_numeric ( $data ['offset'] ))
		{
			throw new JSONException ( 'offset must be numeric !' );
		}
		
		$data ['order'] = isset ( $data ['order'] ) ? $data ['order'] : array();
		
		if (! is_array ( $data ['order'] ))
		{
			throw new JSONException ( 'order must be array !' );
		}
		
		$hORM->browse ( $result, $total_record, $data ['fields'], $data ['filter'], $data ['order'], $data ['offset'], $data ['limit']);
		
		$hInstance = ORM::getObjectInstance ( $data ['object'] );

		foreach ( $result as $object_id => $object )
		{
			$this->_readApplyAttributeRights($data ['object'], $hInstance, $result[$object_id]);
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function create(array $data, array &$result)
	{
		/* Attributs :
		 *
		 * object (required) : Objet de l'ORM - string("nro")
		 * data (required) : tableau de donnée de l'objet
		 *
		 */
		if (! isset ( $data ['object'] ))
		{
			throw new JSONException ( 'object not specified' );
		}

		try
		{
			$hORM = ORM::getORMInstance ( $data ['object'] );
		}
		catch ( Exception $e )
		{
			throw new JSONException ( $e->getMessage () );
		}
		
		$data ['data'] = isset ( $data ['data'] ) ? $data ['data'] : array ();

		if (! is_array ( $data ['data'] ))
		{
			throw new JSONException ( 'data must be array !' );
		}

		Rights::getCreateDeleteViewStatus ( $data ['object'], $create, $delete, $view );

		if (! $create)
		{
			throw new JSONException ( 'forbidden, cannot create this object' );
		}

		$result = array ();

		$hORM->create ( $data ['data'], $result );

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function unlink(array $data, array &$result)
	{
		/* Attributs :
		 *
		 * object (required) : Objet de l'ORM - string("nro")
		 * key (required) : id de l'objet à supprimer
		 *
		 */
		if (! isset ( $data ['object'] ))
		{
			throw new JSONException ( 'object not specified' );
		}

		try
		{
			$hORM = ORM::getORMInstance ( $data ['object'] );
		}
		catch ( Exception $e )
		{
			throw new JSONException ( $e->getMessage () );
		}

		if (! isset ( $data ['key'] ) || ! is_numeric ( $data ['key'] ))
		{
			throw new JSONException ( 'key not specified || key must be numeric !' );
		}

		Rights::getCreateDeleteViewStatus ( $data ['object'], $create, $delete, $view );

		if (! $delete)
		{
			throw new JSONException ( 'forbidden, cannot delete this object' );
		}

		$hORM->unlink ( $data ['key'] );

		$result = array ();

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function write(array $data, array &$result)
	{
		/* Attributs :
		 *
		 * object (required) : Objet de l'ORM - string("nro")
		 * data (required) : tableau de donnée de l'objet à éditer
		 * key (required) : id de l'objet à éditer
		 *
		 */
		if (! isset ( $data ['object'] ))
		{
			throw new JSONException ( 'object not specified' );
		}

		try
		{
			$hORM = ORM::getORMInstance ( $data ['object'] );
		}
		catch ( Exception $e )
		{
			throw new JSONException ( $e->getMessage () );
		}

		if (! isset ( $data ['data'] ) || ! is_array ( $data ['data'] ))
		{
			throw new JSONException ( 'data not specified || data must be array !' );
		}

		if (! isset ( $data ['key'] ) || ! is_numeric ( $data ['key'] ))
		{
			throw new JSONException ( 'key not specified || key must be numeric !' );
		}

		$affected = null;

		// test si l'utilisateur peut modifier les attributs
		foreach ( ORM::getObjectInstance ( $data ['object'] ) as $attribute_name => $attribute )
		{
			if (! ($attribute instanceof FieldDefinition))
			{
				continue;
			}

			if (! isset ( $data ['data'] [$attribute_name] ))
			{
				continue;
			}

			Rights::getRightsByAttribute ( $data ['object'], $attribute_name, $read, $write );

			if (! $write)
			{
				unset ( $data ['data'] [$attribute_name] );
			}
		}

		$hORM->write ( $data ['key'], $data ['data'], false, $affected );

		$result = array (
			'affected' => $affected
		);

		return TRUE;
	}
	//---------------------------------------------------------------------
	/**
	 * Gestion de toutes erreurs des app. Android :
	 * Enregistre en base les erreurs filtrées d'un genre identique pour chaque terminal
	 * @param $data contient un tableau de tableau de toutes erreurs "Throwable" de la forme :
	*		  arr {
	*			 0 => arr {
	*				"app_name" => "value",
	*				"device_data" => "value",
	*				"err_type" => "value",
	*				"err_cause" => "value",
	*				"err_msg" => "value",
	*				"err_backtrace" => "value"
	 *			 1 => arr {
	 *				...
	 *			 }
	 *		  }
	 * @param $result Retourne un tableau de correspondance d IDs client<->serveur si les enregistrements en base sont corrects.
	 */
	public function androidThrowableErrorRegister($data, &$result)
	{
		$directCall = null;
		$this->isCallDirectlyFromAndroidAuthorizedTerminal($directCall);

		if(!empty($data)) {
			$tab = array ();
			if($directCall === true) { // Appel direct depuis un terminal Android
				$tab = json_decode($_REQUEST['data'], true);
			}
			else { // Provenance de la passerelle Android
				$tab = json_decode($data[0], true);
			}
			AndroidAppErrorMethod::androidRecordThrowableErrors ( $tab, $result );
		}
		else {
			$result ['error_msg'] = 'No_data';
		}

		return TRUE;
	}

		// ---------------------------------------------------------------------
	/**
	 *
	 * @param boolean $directCall
	 *			vaudra true si l'origine de l'appel est un terminal Android autorisé sinon false (provenance de la passerelle Android, par exemple).
	 * @throws JSONException Exception levée si le terminal n'est pas du type attendu.
	 * @return boolean
	 */
	protected function isCallDirectlyFromAndroidAuthorizedTerminal(&$directCall) {
		$directCall = false;
		if (isset ( $_SERVER ['HTTP_USER_AGENT'] )) {
			/*
			 * Note : Si provenance d'un terminal Android, $_SERVER['HTTP_USER_AGENT'] est de la forme : 'Dalvik/1.6.0 (Linux; U; Android 4.3; GT-N7100 Build/JSS15J)'
			 */
			$lowerChain = strtolower ( $_SERVER ['HTTP_USER_AGENT'] );
			if (strpos ( $lowerChain, 'android' ) !== false && (strpos ( $lowerChain, 'gt-n7100' ) !== false || strpos ( $lowerChain, 'sm-n7505' ) !== false )) // Note II ou Note III Lite autorisés SM-N7505
			{
				$directCall = true;
			} else {
				throw new JSONException ( 'Type de terminal non-autorisé !!' );
			}
		}
		// NOTE : ON NE GENERE PAS D'EXCEPTION SI $_SERVER ['HTTP_USER_AGENT'] N'EST PAS DEFINI,
		//		C'EST LE CAS EN PROVENANCE DE LA PASSERELLE ANDROID ! => ON DEFINI JUSTE "$directCall" À FALSE.

		return TRUE;
	}

	//---------------------------------------------------------------------
	/**
	 * Gestionnaire de tâches distribuées.
	 *
	 */
	/* Récupération d'une tâche à effectuer. */
	public function getTask($data, &$result)
	{
		if(!isset($data['task_internal_name']))
		{
			$result = array('state' => 'error', 'error' => 'No task action provided !');
			return FALSE;
		}

		$task_internal_name = trim($data['task_internal_name']);
		if(empty($task_internal_name))
		{
			$result = array('state' => 'error', 'error' => 'Empty task action provided !');
			return FALSE;
		}

		/* On récupère une tâche à traiter. */
		$hORM = ORM::getORMInstance('task');
		$task_list = array();
		$hORM->browse($task_list, $total, array('task_id', 'object', 'object_id'), array(array('status_task', '=', WF_TASK_EN_ATTENTE), array('task_internal_name', '=', $task_internal_name)), array(), 0, 1);

		if(count($task_list) != 1)
		{
			$result = array('state' => 'notask', 'msg' => 'No waiting task');
			return TRUE;
		}

		$task = reset($task_list);
		$task_id = $task['task_id']['value'];
		$object = $task['object']['value'];
		$object_id = $task['object_id']['value'];

		$hORM = ORM::getORMInstance($object);
		$data = array();
		$hORM->read($object_id, $data, NULL);

		ORM::getControllerInstance('task')->setTaskRunning($task_id);

		$result = array('state' => 'provide', 'task_id' => $task_id, 'data' => $data);
		return TRUE;
	}

	/* Acquittement d'une tâche. */
	public function setTaskState($data, &$result)
	{
		if(!isset($data['state']) || !isset($data['task_id']))
		{
			$result = array('state' => 'error', 'error' => 'Missing parameters state or task_id');
			return FALSE;
		}

		$task_id = $data['task_id'];

		switch($data['state'])
		{
			case 'success':
				ORM::getControllerInstance('task')->setTaskSucceed($task_id);
				break;
			case 'failed':
				ORM::getControllerInstance('task')->setTaskFailed($task_id);
				break;
			default:
				throw new Exception('Unrecognized task state : ' . $data['state']);
		}

		$result = array('state' => 'registered');
		return TRUE;
	}

	/**
	 * Fonction de lecture / appel de fonction en masse.
	 *
	 * Doit être appelé avec un tableau data de la forme :
	 * 'object_name' => id_list
	 * OU
	 * 'object_name' => array(id_list, field_list)
	 * OU
	 * 'function_name' => function_data.
	 *
	 * La fonction data renvoi un tableau associant les même clefs
	 * aux résultats des opérations.
	 *
	 * Si les fields ne sont pas définis dans le cas d'un read,
	 * la clef primaire et la réference seront retournées.
	 */
	public function readBulk ($data, &$result)
	{
		$result = array();
		foreach ($data as $key => $args)
		{
			$res = array();
			if (class_exists($key))
			{
				$read = array('object' => $key);
				if (isset($args[1]) AND is_array($args[1]))
				{
					$read['keys'] = $args[0];
					$read['fields'] = $args[1];
				}
				else
				{
					$read['keys'] = $args;
					$hObj = ORM::getObjectInstance($key);
					$read['fields'] = array($hObj->primary_key);
					if (isset($hObj->reference))
					{
						$read['fields'][] = $hObj->reference;
					}
				}
				try {
					$this->read($read, $res);
					$result[$key] = $res;
				}
				catch (Exception $e)
				{
					$result[$key] = array(
						'error' => $e->getMessage()
					);
				}
			}
			elseif (method_exists($this, $key))
			{
				$this->$key($args, $res);
				$result[$key] = $res;
			}
			else
			{
				$result[$key] = array(
					'error' => $key.' NOT an object NOR a function.'
				);
			}
		}
		return $result;
	}
}
