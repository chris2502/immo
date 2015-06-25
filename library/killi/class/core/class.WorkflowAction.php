<?php

/**
 *  @class WorkflowAction
 *  @Revision $Revision: 4670 $
 *
 */

class WorkflowAction
{
	protected	$_hDB				= NULL ;   //---Handle sur une connexion MySQL active
	protected	$_node_links		= NULL;

	protected static $_node_name_cache = array();

	/**
	 * @return void
	 */
	function __construct()
	{
		global $hDB; //---On la recup depuis l'index ;-)
		$this->_hDB = &$hDB;
	}

	public function __call($name, $arguments)
	{
		if(strncmp($name, 'pre_insert_', 11) == 0)
		{
			$id_list			= $arguments[0];
			$from_workflow_name	= $arguments[1];
			$from_node_name		= $arguments[2];
			$from_object		= $arguments[3];
			$to_object			= $arguments[4];

			return $this->parent_pre_insert($id_list, $from_workflow_name, $from_node_name, $from_object, $to_object);
		}

		throw new BadUrlException('Method "' . $name . '" not implemented in "' . get_class($this) . '" !');

		return FALSE;
	}

	/**
	 *  Fonction utilitaire interne permettant de convertir les node name en node id.
	 */
	protected function getWorkflowNodeIdByName(&$workflow_id, &$node_id, &$object_name, $workflow_name, $node_name)
	{
		if(isset(self::$_node_name_cache[$workflow_name]) && isset(self::$_node_name_cache[$workflow_name][$node_name]))
		{
			$workflow_id = self::$_node_name_cache[$workflow_name][$node_name]['workflow_id'];
			$node_id = self::$_node_name_cache[$workflow_name][$node_name]['node_id'];
			$object_name = self::$_node_name_cache[$workflow_name][$node_name]['object_name'];
			return TRUE;
		}

		$hORM_workflow		 = ORM::getORMInstance('workflow');
		$hORM_node			 = ORM::getORMInstance('node', FALSE, FALSE);

		/* Récupération du workflow id */
		$workflow_id_list = array();
		$hORM_workflow->search($workflow_id_list, $num_workflow, array(array('workflow_name','=',$workflow_name)));
		if(count($workflow_id_list) != 1)
		{
			throw new Exception(sprintf('Impossible de trouver le workflow name %s ', $workflow_name));
		}
		$workflow_id = reset($workflow_id_list);

		/* Récupération du node id */
		$node_id_list = array();
		$hORM_node->search($node_id_list, $num_node_to, array(array('workflow_id' , '=' , $workflow_id), array('node_name'  , '=' , $node_name)));
		if(count($node_id_list) != 1)
		{
			throw new Exception(sprintf('Impossible de trouver le node from avec le node name %s et le workflow_id %u', $node_name, $workflow_id));
		}
		$node_id = reset($node_id_list);

		$node = NULL;
		$hORM_node->read($node_id, $node, array('object'));

		$object_name = $node['object']['value'];

		self::$_node_name_cache[$workflow_name][$node_name] = array('workflow_id' => $workflow_id, 'node_id' => $node_id, 'object_name' => $object_name);

		return TRUE;
	}

	//---------------------------------------------------------------------
	/**
	 * @param array $id_list
	 * @param string $from_workflow_name
	 * @param string $from_node_name
	 * @param string $to_workflow_name
	 * @param string $to_node_name
	 * @return boolean
	 * @throws Exception
	 */
	public function createTokenTo($id_list,$from_workflow_name,$from_node_name,$to_workflow_name,$to_node_name)
	{		
		if (empty($id_list))
		{
			return TRUE;
		}

		//---On recup le workflow FROM
		$hORM_workflow		= ORM::getORMInstance('workflow');
		$workflow_id_list	= array();
		$num_workflow		= 0;

		$hORM_workflow->search($workflow_id_list, $num_workflow, array(array('workflow_name', '=', $from_workflow_name)));

		if(count($workflow_id_list) !== 1)
		{
			throw new Exception('Impossible de trouver le workflow ' . $from_workflow_name);
		}

		$from_workflow_id	= reset($workflow_id_list);
		$hORM_node			= ORM::getORMInstance('node',FALSE, FALSE);
		$from_node_id_list	= array();
		$to_node_id_list	= array();
		$node_link_id_list	= array();
		$num_to_node		= 0;
		$num_from_node		= 0;
		$num_node_link		= 0;

		$to_workflow_id = $from_workflow_id ;

		//---On recup le workflow TO
		if($from_workflow_name !== $to_workflow_name)
		{
			$workflow_id_list = array();
			$hORM_workflow->search($workflow_id_list,$num_workflow,array(array('workflow_name', '=', $to_workflow_name)));
			$to_workflow_id = reset($workflow_id_list);
		}

		//---On recup le noeud FROM correspondant
		$hORM_node->search($from_node_id_list, $num_to_node, array(array('workflow_id', '=', $from_workflow_id), array('node_name', '=', $from_node_name)));
		if(count($from_node_id_list) !== 1)
		{
			throw new Exception('Impossible de trouver le node ' . $from_node_name);
		}

		//---On recup le noeud TO correspondant
		$hORM_node->search($to_node_id_list,$num_from_node,array(array('workflow_id','=',$to_workflow_id),array('node_name','=',$to_node_name)));
		if(count($to_node_id_list) !== 1)
		{
			throw new Exception('Impossible de trouver le node ' . $to_node_name);
		}

		$to_node_id = reset($to_node_id_list);

		//---On verifie l'existance d'un lien
		$hORM_nodelink = ORM::getORMInstance('nodelink');
		$hORM_nodelink->search($node_link_id_list,$num_node_link,array(array('output_node','=',$to_node_id),array('input_node','=',strval($from_node_id_list[0]))));

		if(count($node_link_id_list) == 0)
		{
			throw new Exception('No link between Node ' . $from_workflow_name . '::' . $from_node_name . ' and ' . $to_workflow_name . '::' . $to_node_name);
		}

		$workflow_class_name = $to_workflow_name.'Method' ;
		$new_token_id_list   = array();
		$hORM_workflowtoken  = ORM::getORMInstance('workflowtoken');

		if(class_exists($workflow_class_name) === true)
		{
			$hInstance		= new $workflow_class_name();
			$method_name	= 'pre_create_' . $to_node_name;

			if(method_exists($hInstance, $method_name))
			{
				$hInstance->$method_name($id_list);
			}
		}

		//---Creation des token
		foreach($id_list AS $def)
		{
			$objet['node_id']		= $to_node_id;
			
			foreach($def as $key_def=>$value_def)
			{
				$objet[$key_def]= $value_def;
			}
			
			$token_id				= NULL;

			$objet['qualification_id']	=  (isset($def['qualification_id'])		&& is_numeric($def['qualification_id'])) ? $def['qualification_id' ] : NULL;
			$objet['commentaire']		=  (isset($def['commentaire'])			&& trim($def['commentaire']) != '') ? $def['commentaire'] : NULL;

			$hORM_workflowtoken->create($objet, $token_id);
			array_push($new_token_id_list, $token_id);
		}

		if(class_exists($workflow_class_name) === true)
		{
			$hInstance		= new $workflow_class_name();
			$method_name	= 'post_create_' . $to_node_name;

			if(method_exists($hInstance, $method_name))
			{
				$hInstance->$method_name($new_token_id_list, $id_list);
			}
		}
		return TRUE;
	}

	/**
	 *  Déplace des tokens :
	 * - Vérification des droits et de l'existence du lien
	 * - Vérification des contraintes en BDD
	 * - Appel à la fonction de vérification des éléments à déplacer : check_'nodename'
	 * - Appel à la fonction pre_insert_'nodename' : Conversion d'objet
	 * - Déplacement des tokens
	 * - Appel à la fonction post_insert_'nodename'
	 *
	 * @param array $id_list
	 * @param string $from_workflow_name
	 * @param string $from_node_name
	 * @param string $to_workflow_name
	 * @param string $to_node_name
	 * @throws Exception
	 */
	public function moveTokenTo(array &$id_list,$from_workflow_name,$from_node_name,$to_workflow_name,$to_node_name,$ignore_duplicate=false)
	{
		if (empty($id_list))
		{
			return TRUE;
		}

		/**
		 * Appel du moveTokenTo sur la bonne instance.
		 */
		$hInstance = $this;
		$workflow_class_name = $to_workflow_name . 'Method';
		if(strtolower(get_class($this)) != strtolower($workflow_class_name) && class_exists($workflow_class_name)===true)
		{
			$hInstance = new $workflow_class_name();
			if(method_exists($hInstance, 'moveTokenTo'))
			{
				return $hInstance->moveTokenTo($id_list, $from_workflow_name, $from_node_name, $to_workflow_name, $to_node_name, $ignore_duplicate);
			}
		}

		//---On recup le workflow FROM
		$hORM_workflow		 = ORM::getORMInstance('workflow');
		$hORM_node			 = ORM::getORMInstance('node',FALSE, FALSE);
		$hORM_token	= ORM::getORMInstance('workflowtoken',FALSE, FALSE);

		/**
		 * Récupération des identifiants et noeuds de workflow.
		 */
		$from_workflow_id = NULL;
		$from_node_id = NULL;
		$to_workflow_id = NULL;
		$to_node_id = NULL;
		$from_object = NULL;
		$to_object = NULL;
		$this->getWorkflowNodeIdByName($from_workflow_id, $from_node_id, $from_object, $from_workflow_name, $from_node_name);
		$this->getWorkflowNodeIdByName($to_workflow_id, $to_node_id, $to_object, $to_workflow_name, $to_node_name);

		/**
		 * Vérification des droits et de l'existence des liens.
		 */
		$this->_node_links = NULL; // Force la récupération des nodes links.
		$link_id_list = array();
		if(!$this->checkLinksBetweenNodes($from_node_id, $to_node_id))
		{
			if (defined('USE_LINK_RIGHTS'))
			{
				throw new NoLinkRightsException("No link you can use between nodes $from_workflow_name::$from_node_name and $to_workflow_name::$to_node_name");
				//throw new Exception( "No link you can use between Node $from_workflow_name::$from_node_name and $to_workflow_name::$to_node_name" );
			}

			throw new Exception( "No link between Node $from_workflow_name::$from_node_name and $to_workflow_name::$to_node_name" );
		}

		/**
		 * Vérification des contraintes de déplacement.
		 */
		if(!$this->checkLinksConstraints($id_list, $from_node_id, $to_node_id))
		{
			//throw new Exception('Get links constraints !');
			return FALSE;
		}

		/**
		 *  Appel de la fonction de vérification des éléments déplacés si elle existe.
		 */
		$check_method = 'check_insert_' . $to_node_name;

		if(method_exists($hInstance, $check_method))
		{
			if(!$this->$check_method($id_list, $from_workflow_name, $from_node_name))
			{
				// throw new Exception('Check insert failed !');
				return FALSE;
			}
		}

		/**
		 *  Appel à la fonction pre_insert_'nodename'
		 */
		$method_name = 'pre_insert_' . $to_node_name;
		if(!$this->$method_name($id_list, $from_workflow_name, $from_node_name, $from_object, $to_object))
		{
			throw new Exception('Pre insert failed (' . $method_name . ') !');
			return FALSE;
		}

		/**
		 *  Exécution des déplacements de tokens.
		 */
		$new_token_id_list = array();
		foreach($id_list as $id_from => $to)
		{
			//---On recup le token
			$token_id_list	 = array();

			$object['node_id'] = $to_node_id;
			if(!(is_array($to) && array_key_exists('id', $to)))
			{
				throw new Exception('id_list doit etre un liste de token_id');
			}

			$object['id'] = $to['id'];

			$hORM_token->search( $token_id_list, $num_token, array(array('node_id','=',$from_node_id),array('id','=',$id_from)));
			if(empty($token_id_list))
			{
				throw new CanNotBeFoundTokenException($from_node_id, $id_from);
			}

			$token_id = reset($token_id_list);
			$object[ 'qualification_id' ] = NULL ;
			$object[ 'commentaire' ]	  = NULL ;

			if( isset( $to[ 'qualification_id' ] ) && is_numeric( $to[ 'qualification_id' ] ) )
			{
				$object[ 'qualification_id' ] = $to[ 'qualification_id' ] ;
			}

			if( isset( $to[ 'commentaire' ] ) && trim( $to[ 'commentaire' ] ) != ''  )
			{
				$object[ 'commentaire' ] = $to[ 'commentaire' ] ;
			}

			$hORM_token->write($token_id,$object,$ignore_duplicate);
			array_push($new_token_id_list, $token_id);
		}

		/**
		 *  Appel à la fonction post_insert_'nodename'
		 */
		$method_name = 'post_insert_'.$to_node_name;
		if (method_exists($this, $method_name))
		{
			return $this->$method_name($new_token_id_list, $id_list, $from_workflow_name, $from_node_name);
		}

		$this->processLinkPadlock(array_keys($id_list), $from_node_id, $to_node_id);

		return TRUE;
	}

	/**
	 *  Charge et vérifie l'existence des liens.
	 */
	protected function checkLinksBetweenNodes($from_node_id, $to_node_id)
	{
		/**
		 * Vérification des droits sur le déplacement et existence des liens.
		 */
		if($this->_node_links != NULL)
		{
			return TRUE;
		}

		$this->_node_links = array();
		$num_node_link		 = 0;
		if (defined('USE_LINK_RIGHTS') && isset($_SESSION['_USER']))
		{
			$link_right_list = array();
			ORM::getORMInstance('linkrights')->browse($link_right_list, $num_rows, array('link_rights_id'), array(
				array('output_node' , '=' , $to_node_id),
				array('input_node' , '=' , $from_node_id),
				array('killi_profil_id', 'IN', $_SESSION['_USER']['profil_id']['value']),
				array('move', '=', 1)
			));
			foreach ($link_right_list as $link_right)
			{
				$this->_node_links[] = $link_right['link_id']['value'];
			}
		}
		else
		{
			ORM::getORMInstance('nodelink')->search($this->_node_links , $num_node_link , array(
				array('output_node' , '=' , $to_node_id),
				array('input_node' , '=' , $from_node_id)
			));
		}

		if (count($this->_node_links)==0)
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 *  Vérifie les contraintes de BDD pour les id concernés.
	 */
	protected function checkLinksConstraints($id_list, $from_node_id, $to_node_id)
	{
		if($this->_node_links === NULL)
		{
			return FALSE;
		}

		/**
		 *  Vérification des contraintes BDD de déplacements.
		 */
		if (isset(ORM::getObjectInstance('nodelink')->constraints))
		{
			$node_link_list = array();
			$origin_node = array();
			ORM::getORMInstance('node')->read($from_node_id, $origin_node, array('object'));
			ORM::getORMInstance('nodelink')->read($this->_node_links, $node_link_list, array('constraints', 'constraints_qualification'));
			foreach ($node_link_list as $link)
			{
				if ((isset($link['constraints']) && !empty($link['constraints']['value'])) ||
					(isset($link['constraints_qualification']) && $link['constraints_qualification']['value'] == true))
				{
					$final_id_list = array();
					foreach ($id_list as $from_id => $to)
					{
						$final_id_list[$to['id']] = TRUE;
					}

					if (!WorkflowMethod::checkConstraints(
							$origin_node['object']['value'], 
							array_keys($final_id_list), 
							$link['constraints']['value'], 
							($link['constraints_qualification']['value'] == true), 
							FALSE
						)
					)
					{
						return FALSE;
					}
				}
			}
		}

		return TRUE;
	}

	/**
	 * Verrouille les types de documents selon les paramètres du link.
	 */
	protected function processLinkPadlock($id_list, $from_node_id, $to_node_id)
	{
		// On vérifie l'existence de l'attribut
		if (isset(ORM::getObjectInstance('nodelink')->constraints_padlock))
		{
			$padlocked_doc_id_list = array();
			$origin_node = array();
			ORM::getORMInstance('node')->read($from_node_id, $origin_node, array('object'));
			$object_from = $origin_node['object']['value'];

			// On read tous les links possibles dans le même sens entre les deux nodes.
			$group_doctype_id_list = array();
			ORM::getORMInstance('nodelink')->read($this->_node_links, $node_link_list, array('constraints_padlock'));
			foreach ($node_link_list as $link)
			{
				if (!empty($link['constraints_padlock']['value']))
				{
					$padlock_list = explode(',', $link['constraints_padlock']['value']);
					foreach ($padlock_list as $padlock_item)
					{
						list($object_info, $const_name) = explode(':', $padlock_item);
						$group_doctype_id_list[$object_info][constant($const_name)] = TRUE;
					}
				}
			}

			if (count($group_doctype_id_list) > 0)
			{
				foreach ($group_doctype_id_list as $object_info => $doctype_id_list)
				{
					// Si on doit récupérer l'ID de l'objet lié au document sur un attribut
					// spécifique de l'objet manipulé.
					if (strpos($object_info, '/'))
					{
						list ($field, $object_name) = explode('/', $object_info);
						$object_from_list = array();
						// On lit sur l'objet manipulé les ID de référence
						ORM::getORMInstance($object_from)->read($id_list, $object_from_list, array($field));
						$final_id_list = array();
						foreach ($object_from_list as $object_id => $object_item)
						{
							$final_id_list[$object_item[$field]['value']] = TRUE;
						}
					}
					else
					{
						$object_name = $object_info;
						$final_id_list = $id_list;
					}

					// On récupère les documents pour la liste d'ID, on les groupe par type pour
					// en retirer le plus récent de chaque.
					$document_by_type_list = array();
					$document_list = array();
					ORM::getORMInstance('document')->browse(
						$document_list, 
						$num, 
						array('object', 'object_id', 'document_type_id', 'date_creation'),
						array(
							array('object', '=', $object_name),
							array('used_as_padlock', '=', FALSE),
							array('object_id', 'in', $final_id_list),
							array('document_type_id', 'in', array_keys($doctype_id_list))
						)
					);

					foreach ($document_list as $document_id => $document_item)
					{
						$doctype_id = $document_item['document_type_id']['value'];
						$doc_ts     = intval($document_item['date_creation']['timestamp']);
						$document_by_type_list[$doctype_id][$doc_ts] = $document_id;
					}

					foreach ($document_by_type_list as $doctype_id => &$doc_list)
					{
						ksort($doc_list);
						$document_id = end($doc_list);
						ORM::getORMInstance('document')->write($document_id, array(
							'used_as_padlock' => TRUE
						));
						$padlocked_doc_id_list[] = $document_id;
					}
				}
			}

			if (count($padlocked_doc_id_list) > 0)
			{
				$_SESSION['_MESSAGE_LIST']['Verrouillage documents'] = count($padlocked_doc_id_list).' document(s) verrouillé(s).';
			}
		}

		TRUE;
	}

	/**
	 * Pre-Insert parent.
	 */
	protected function parent_pre_insert(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object)
	{
		if($from_object == $to_object)
		{
			return TRUE;
		}

		$conversion_list = array();
		foreach ($id_list as $key => $table)
		{
			if (!isset($table['conv_ok']) OR $table['conv_ok'] != true)
			{
				$conversion_list[$key] = $table;
			}
		}

		if (!empty($conversion_list))
		{
			/**
			 *  Conversion d'objet.
			 */
			$conv_method = strtolower($from_object.'_to_'.$to_object);

			//---On verifie que la method existe
			if (!method_exists('WorkflowObjectConversionMethod',$conv_method))
			{
				throw new Exception('WorkflowObjectConversionMethod:'.$conv_method.' is not implementd !');
			}

			$new_id_list=array();

			$hWorkflowObjectConversionMethod = new WorkflowObjectConversionMethod();
			$hWorkflowObjectConversionMethod->$conv_method($conversion_list, $new_id_list);

			foreach ($new_id_list as $key => $table)
			{
				$id_list[$key] = $table;
			}
		}
		return TRUE;
	}
	//.....................................................................
	protected function getNodeNameById($node_id, &$node_name)
	{
		global $hDB;
		$sql = 'Select node_name From killi_workflow_node Where workflow_node_id = '.$node_id.'';
		$hDB->db_select($sql, $result, $num);
		if ($num == 0) {
			throw new Exception("Impossible de trouver le noeud correspondant à l'ID '".$node_id."'");
			return TRUE;
		}
		$row = $result->fetch_assoc();
		$node_name = $row['node_name'];
		return TRUE;
	}
	//.....................................................................
	public function padlock()
	{
		$arg_list = func_get_args();
		if (count($arg_list) < 3)
		{
			throw new Exception('padlock() must have at least 3 arguments.');
			exit();
		}
		if (is_array($arg_list[2]))
		{
			$document_type_list = $arg_list[2];
		}
		else
		{
			$document_type_list = array();
			for ($i = 2 ; $i < count($arg_list) ; $i++)
			{
				$document_type_list[] = $arg_list[$i];
			}
		}

		$hORMDOC = ORM::getORMInstance('document');
		$document_list = array();
		$total_record = 0;
		$hORMDOC->browse(
			$document_list,
			$total_record,
			array('document_id'),
			array(
				array('object_id', 'in', $arg_list[0]),
				array('object', '=', 'adresse'),
				array('document_type_id', 'in', $document_type_list),
				array('used_as_padlock', '=', '0')
			),
			array('object_id','document_type_id')
		);
		$document_id_list = array();
		foreach ($document_list as $document)
		{
			$document_id_list[] = $document['document_id']['value'];
		}
		if (count($document_id_list) > 0)
		{
			$affected_rows = 0;
			$query =	'Update `document` '.
						'Set `used_as_padlock` = 1 '.
						'Where `document_id` In ('.implode(', ', $document_id_list).')';
			$this->_hDB->db_execute($query, $affected_rows);
		}
	}
	//.....................................................................
	protected function restrictTo()
	{
		$isOk = false;
		$profil_id = func_get_args();

		if (count($profil_id) > 1)
		{
			foreach ($profil_id as $pid)
			{
				if (in_array($pid, $_SESSION['_USER']['profil_id']['value']))
				{
					$isOk = true;
					break;
				}
			}
		}
		else
		{
			$profil_id = reset($profil_id);
			if (in_array($profil_id, $_SESSION['_USER']['profil_id']['value']))
			{
				$isOk = true;
			}
		}
		if (!$isOk)
		{
			$_SESSION['_ERROR_LIST']['Droits'] = 'Vous ne disposez pas des autorisations requises pour effectuer cette action.';

			UI::goBackWithoutBrutalExit();
		}
	}
}
