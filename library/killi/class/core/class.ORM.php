<?php

/**
 *  Classe de l'ORM
 *
 *  @package killi
 *  @class ORM
 *  @Revision $Revision: 4642 $
 */

class ORM
{
	private $_object			= NULL;
	private $_extended_object	= NULL;
	private $_object_name		= NULL;
	private $_object_table	 	= NULL;
	private $_object_database	= NULL;
	private $_object_key_name	= NULL;
	private $_xmlrpc_object		= NULL;
	private $_count_total		= FALSE;
	private $_domain_with_join	= array();

	private static $_instances		= array();
	private static $_ctrl_instances = array();
	public static $_objects			= array();

	private static $desc_table		= array();
	private static $no_fields		= array();
	private static $func_call_stack = array();

	public static $_cumulate_process_time = 0;
	private static $_start_time = 0;

	//---------------------------------------------------------------------
	/**
	* Déclare un objet dans l'ORM.
	*
	* @param string $class_name Nom de la classe de l'objet
	* @param boolean $rights Activer la gestion des droits sur l'objet
	*/
	static function declareObject($class_name=NULL, $rights = true, $path = '')
	{
		$lower = strtolower($class_name);

		if(isset(self::$_objects[$lower]))
		{
			self::$_objects[$lower]['rights'] = $rights;
			return FALSE;
		}

		self::$_objects[$lower] = array('className' => $class_name, 'class' => NULL, 'rights' => $rights); //, 'url' => $url, 'urlMethod' => $url_method, 'path' => $path);
		return TRUE;
	}

	public static function getClassPath($object_name)
	{
		return Autoload::getClassPath($object_name);
	}

	//---------------------------------------------------------------------
	/**
	* Obtenir une instance d'un objet
	*
	* @param string $object_name Nom de l'objet
	* @throws Exception
	* @return Objet
	*/
	static function getObjectInstance($object_name, $with_domain = TRUE)
	{
		$lower = strtolower($object_name);

		if(!isset(self::$_objects[$lower]))
		{
			throw new UndeclaredObjectException('L\'objet \'' . $object_name . '\' n\'est pas déclaré dans l\'ORM !');
		}

		$obj = self::$_objects[$lower]['className'];

		/* Si l'objet est instancié, on le retourne directement. */
		if(self::$_objects[$lower]['class'] !== NULL && $with_domain)
		{
			return self::$_objects[$lower]['class'];
		}

		Debug::log('Instanciate new object '.$lower);

		$className = self::$_objects[$lower]['className'];

		if(!class_exists($className))
		{
			throw new Exception('La classe ' . $className . ' n\'existe pas');
		}

		/* Création de l'object et définition des autorisations de base. */
		$obj = new $className();
		$class_name = get_class($obj);

		/* Empecher de boucler lorsque le domaine utilise l'objet. */
		if($with_domain)
		{
			self::$_objects[$lower]['class'] = $obj;
		}

		/* Définition des droits utilisateurs sur l'objet. */
		Rights::getCreateDeleteViewStatus($className, $create, $delete, $view);

		// on écrase pas les FieldDefinition qui portent le nom d'un droit
		$obj->create = (isset($obj->create) && $obj->create instanceof FieldDefinition) ? $obj->create : $create;
		$obj->delete = (isset($obj->delete) && $obj->delete instanceof FieldDefinition) ? $obj->delete : $delete;
		$obj->view   = (isset($obj->view)   && $obj->view   instanceof FieldDefinition) ? $obj->view   : $view;

		/* Définition des domaines. */
		if (method_exists($obj, 'setDomain') && $with_domain)
		{
			$obj->setDomain();
		}

		foreach($obj as $attribute_name=>&$attribute)
		{
			if($attribute instanceof FieldDefinition)
			{
				if(!isset($attribute->objectName))
				{
					$attribute->objectName = $class_name;
				}

				$attribute->attribute_name = $attribute_name;
			}
		}

		if(isset($obj->primary_key))
		{
			$raw = explode(',', $obj->primary_key);
			if(count($raw) > 1)
			{
				$obj->{$obj->primary_key} = PrimaryFieldDefinition::create()->setVirtual(TRUE);
			}
		}
		/* Parcours de l'objet pour extension. */
		foreach($obj AS $value)
		{
			//---On ne conserve que les attributs du mapping
			if ($value instanceof FieldDefinition && $value->type_relation === 'extends')
			{
				$hExtender = self::getObjectInstance($value->object_relation, $with_domain);
				foreach($hExtender as $key2 => $value2)
				{
					//---On ne conserve que les attributs du mapping
					//---Si attribut redefini sur l'objet, on n'ecrase pas
					if ($value2 instanceof FieldDefinition && !isset($obj->$key2))
					{
						$obj->$key2 = $value2;
					}
				}
			}
		}
		if($with_domain)
		{
			return self::$_objects[$lower]['class'];
		}

		return $obj;
	}

	/**
	 * Efface les instances d'objets afin de forcer la reconstruction de tous les domaines.
	 */
	static public function clearObjectInstances()
	{
		foreach (self::$_objects as $name => $content)
		{
			if (!empty($content['class']))
			{
				self::$_objects[$name]['class'] = null;
			}
		}
		return TRUE;
	}

	/**
	 * Trouve l'objet de liaison Many2Many de deux objets, NULL si introuvable
	 * @param string $object_a Premier objet
	 * @param string $object_b Deuxieme objet
	 * @return NULL|string
	 */
	static public function getM2MObject($object_a, $object_b)
	{
		$fieldTest = array(
			$object_b => ORM::getObjectInstance($object_a),
			$object_a => ORM::getObjectInstance($object_b)
		);

		foreach ($fieldTest as $relation => $hObj)
		{
			foreach ($hObj as $property)
			{
				if (is_a($property, 'FieldDefinition') AND $property->type == 'many2many' AND $property->object_relation == $relation AND isset($property->object_liaison))
				{
					return $property->object_liaison;
				}
			}
		}

		try
		{
			$h = ORM::getObjectInstance($object_a.$object_b);
		}
		catch (Exception $e)
		{
			try
			{
				$h = ORM::getObjectInstance($object_b.$object_a);
			}
			catch (Exception $e)
			{

				$namespacePath = Autoload::getClassNamespacePath($object_a);

				$_object_name=$object_a;
				if($namespacePath != null)
				{
					$_object_name = substr($object_a, strlen($namespacePath)-1);
				}

				try
				{
					$h = ORM::getObjectInstance($object_b.$_object_name);
				}
				catch (Exception $e)
				{
					try
					{
						$h = ORM::getObjectInstance($_object_name.$object_b);
					}
					catch (Exception $e)
					{
						$namespacePath = Autoload::getClassNamespacePath($object_b);

						$_object_name = $object_b;
						if($namespacePath != null)
						{
							$_object_name = substr($object_b, strlen($namespacePath)-1);
						}

						try
						{
							$h = ORM::getObjectInstance($object_a.$_object_name);
						}
						catch (Exception $e)
						{
							try
							{
								$h = ORM::getObjectInstance($_object_name.$object_a);
							}
							catch (Exception $e)
							{
								$found = false;
								do
								{
									/* On skip les classes par défaut. */
									if($object_b == 'stdClass' || $object_b == 'ObjectDefinition')
									{
										continue;
									}

									/* Tentative de liaison dans un sens. */
									$rel_object	= $object_b . $object_a;
									if(self::isObjectExists($rel_object) || class_exists($rel_object))
									{
										$found = true;
										break;
									}

									/* Tentative de liaison dans l'autre sens. */
									$rel_object = $object_a . $object_b;
									if(self::isObjectExists($rel_object) || class_exists($rel_object))
									{
										$found = true;
										break;
									}
								}
								while(($object_b = get_parent_class($object_b)) != FALSE);

								if(!$found)
								{
									return NULL;
								}

								$h = ORM::getObjectInstance($rel_object);
							}
						}
					}
				}
			}
		}

		return get_class($h);
	}

	//---------------------------------------------------------------------
	/**
	* Retourne une instance du controleur d'un objet (objectMethod) en chargeant le
	* controleur à la demande.
	*
	* @param string $object_name Nom de l'objet dont on veut le controleur.
	* @throws Exception
	* @return Object objectMethod
	*/
	static function getControllerInstance($object_name)
	{
		if (is_object($object_name))
		{
			$object_name = get_class($object_name);
		}

		if(isset(self::$_ctrl_instances[$object_name]))
		{
			return self::$_ctrl_instances[$object_name];
		}

		Debug::log('Instanciate new controler '.$object_name. 'Method');

		$controller = $object_name . 'Method';

		if(!class_exists($controller))
		{
			if(Autoload::loadClass($controller) == FALSE)
			{
				throw new UndeclaredObjectException('Controller for object ' . $object_name . ' not found !');
			}
		}

		return self::$_ctrl_instances[$object_name] = new $controller;
	}

	//---------------------------------------------------------------------
	/**
	* Obtenir une instance de l'objet ORM
	*
	* @param $object_name Nom de l'objet en lowercase
	* @param $count_total Effectuer un décompte du résultat des requêtes
	* @return ORM
	*/
	static function getORMInstance($object_name=NULL, $count_total=FALSE, $with_domain = TRUE)
	{
		$object_name = trim($object_name);

		if(!is_string($object_name) || empty($object_name))
		{
			throw new Exception("object_name must be a non-empty string");
		}

		if(!$with_domain)
		{
			return new ORM(self::getObjectInstance($object_name), $count_total, $with_domain);
		}

		if(!isset(self::$_instances[$object_name][$count_total]))
		{
			self::$_instances[$object_name][$count_total] = new ORM(self::getObjectInstance($object_name), $count_total, $with_domain);
		}
		return self::$_instances[$object_name][$count_total];
	}

	//---------------------------------------------------------------------
	/**
	* Méthode utilisée par l'index pour affecter les attributs de workflow à un objet.
	*
	* @param unknown_type $object_name
	*/
	static function setWorkflowAttributes($object_name)
	{
		$hInstance	= self::getObjectInstance($object_name);

		$hInstance->qualification_id = TextFieldDefinition::create()
				->setLabel('Qualification')
				->setFunction($object_name, 'setQualification')
				->setEditable(FALSE);

		$hInstance->commentaire = TextFieldDefinition::create()
				->setLabel('Commentaire')
				->setFunction($object_name, 'setCommentaire')
				->setEditable(FALSE);
	}
	//---------------------------------------------------------------------
	/**
	* Retourne l'ensemble des objets déclarés.
	*
	* @throws Exception
	* @return Tableau d'objet
	*/
	static function getAllDeclaredObjects()
	{
		$object_list = array();
		foreach(self::$_objects AS $object_name => &$object) // Laisser le & sinon bug (cf Timothé pour explication) !
		{
			if(!isset($object['rights']))
			{
				throw new Exception('L\'objet ' . $object_name . ' n\'a pas été déclaré correctement !');
			}

			$obj = self::getObjectInstance($object_name);
			if($object['rights'] == true)
			{
				$object_list[] = $obj;
			}
		}
		return $object_list;
	}
	//---------------------------------------------------------------------
	/**
	* Retourne la liste des objets déclarés.
	*
	* @throws Exception
	* @return Tableau de nom d'objet
	*/
	static function getDeclaredObjectsList()
	{
		return array_keys(self::$_objects);
	}

	//---------------------------------------------------------------------
	/**
	* Test l'existance d'un objet.
	*
	* @param string $object_name Nom de l'objet
	*/
	static function isObjectExists($object_name)
	{
		return isset(self::$_objects[strtolower($object_name)]);
	}
	//---------------------------------------------------------------------
	/**
	* Reinitialise les objets de l'ORM (supprime les instances et les propriétés).
	* Utilisé principalement pour les tests unitaires.
	*/
	public static function resetAllInstances()
	{
		/* Suppression des instances d'ORM. */
		foreach(self::$_instances AS $object_name => $instances)
		{
			foreach(array_keys($instances) AS $count)
			{
				unset(self::$_instances[$object_name][$count]);
			}
			unset(self::$_instances[$object_name]);
		}

		/* Suppression des instances d'objets. */
		foreach(array_keys(self::$_objects) AS $object_name)
		{
			unset(self::$_objects[$object_name]['class']);
			self::$_objects[$object_name]['class'] = NULL;
		}
	}

	//---------------------------------------------------------------------
	private function __construct($object, $count_total=FALSE, $with_domain = FALSE)
	{
		if($object == NULL)
		{
			throw new Exception('L\'objet passé en paramètre est NULL !');
		}
		$class_name = get_class($object);

		foreach($object as $attribute_name=>&$attribute)
		{
			if($attribute instanceof FieldDefinition)
			{
				if(!isset($attribute->objectName))
				{
					$attribute->objectName = $class_name;
				}

				$attribute->attribute_name = $attribute_name;
			}
		}

		if ($class_name===FALSE)
		{
			throw new Exception("ORM Constructor arg1 is not instance of a class !");
		}

		$this->_object_name = $class_name;

		if (!isset($object->primary_key))
		{
			throw new Exception("Object primary key is not defined in ".$this->_object_name);
		}

		ORM::init();

		global $hDB; //---On la recup depuis l'index ;-)

		$this->_hDB				= $hDB;
		$this->_count_total		= $count_total;
		$this->_object			= self::getObjectInstance($class_name, $with_domain);
		$primary_key = $object->primary_key;
		$raw = explode(',', $primary_key);
		if(count($raw) == 1)
		{
			$this->_object_key_name	= $primary_key;
		}
		else
		{
			$this->_object_key_name = $raw;
		}
		$this->_extended_object	= self::getObjectInstance($class_name, $with_domain);

		if (isset($object->xmlrpc_object))
		{
			$this->_xmlrpc_object = $object->xmlrpc_object;
		}
		elseif  ($this->_xmlrpc_object===NULL)
		{
			if (isset($object->json))
			{
				if (!isset($object->json['path']) || empty($object->json['path']))
				{
					throw new Exception("Unset or empty json['path'] parameter in ".$this->_object_name.' object !');
				}
				$object->json['ssl'] = (substr($object->json['path'], 0, 5)=='https'); // bool

				if ($object->json['ssl'])
				{
					if (!isset($object->json['cert']) || empty($object->json['cert']))
					{
						throw new Exception("Unset or empty json['cert'] parameter in ".$this->_object_name.' object !');
					}

					if (!isset($object->json['cert_pwd']) || empty($object->json['cert_pwd']))
					{
						throw new Exception("Unset or empty json['cert_pwd'] parameter in ".$this->_object_name.' object !');
					}
				}
			}
			else {
				if (!isset($object->table))
				{
					throw new Exception("Object table is not defined in ".$this->_object_name);
				}

				if (!isset($object->database))
				{
					throw new Exception("Object database is not defined in ".$this->_object_name);
				}

				$this->_object_table	= $object->table;
				$this->_object_database = $object->database;
			}
		}
	}
	//---------------------------------------------------------------------
	/**
	 * Intialise l'ORM en créant le connecteur SQL si besoin
	 */
	public static function init()
	{
		global $hDB; //---On la recup depuis l'index ;-)

		if(!isset($hDB) || !($hDB instanceof DbLayer))
		{
			global $dbconfig;
			$hDB = new DbLayer($dbconfig);
			$hDB->db_start();
		}
	}
	//---------------------------------------------------------------------
	// Native json browsing
	private function jsonProcessor($action, &$result, $fields=null, $args=null, $tri=null, $offset=null, $limit=null)
	{
		if($action != 'write' && $action != 'create' && $action != 'unlink')
		{
			if($fields === NULL)
			{
				/* Si les champs n\'ont pas été spécifiés, on liste les champs locaux non calculés. */
				$fields = array();
				foreach($this->_object as $key => $value)
				{
					 if (is_a($value, 'FieldDefinition') && $value->function === NULL)
					 {
						$fields[] = $key;
					 }
				 }
			}
			else
			{
				/* Si les champs ont été spécifiés, on retire les champs calculés locaux. */
				foreach($fields AS $position => $field)
				{
					if($this->_object->$field->function !== NULL)
					{
						unset($fields[$position]);
					}
				}
			}
		}

		if (isset($this->_object->json['object']))
		{
			$obj = $this->_object->json['object'];
		}
		else
		{
			$obj = strtolower($this->_object_name);
		}

		$curl = new KilliCurl($this->_object->json['path'].'index.php?action=json.'.$action);

		$curl->object = $obj;
		$curl->fields = $fields;
		$curl->offset = $offset;
		$curl->limit = $limit;
		$curl->order = $tri;

		if (isset($this->_object->json['login']))
		{
			$curl->setUser($this->_object->json['login'], $this->_object->json['password']);
		}
		else
		{
			$curl->setUser($_SESSION['_USER']['login']['value'], $_SESSION['_USER']['password']['value']);
		}

		if (isset($this->_object->json['ssl']) && !empty($this->_object->json['ssl']))
		{
			$curl->setSSL($this->_object->json['cert'], $this->_object->json['cert_pwd']);
		}

		if($action === 'read')
		{
			$curl->keys = is_array($args) ? $args : array($args);
		}
		elseif($action === 'browse' || $action === 'search' || $action === 'count')
		{
			$curl->filter = is_array($args) ? $args : array();
		}
		elseif($action === 'write')
		{
			$curl->key = $args;
			$curl->data = $fields;
		}
		elseif($action === 'create')
		{
			$curl->data = $fields;
		}
		elseif($action === 'unlink')
		{
			$curl->key = $fields;
		}

		try
		{
			$result = $curl->request();
		}
		catch (Exception $e)
		{
			$ex = new NativeJSONBrowsingException($e->getMessage());

			if(property_exists($e, 'curl'))
			{
				$ex->curl = $e->curl;
			}

			throw $ex;
		}

		return TRUE;
	}

	public function getCachedTotalCount()
	{
		if (!Cache::$enabled)
		{
			if (DISPLAY_ERRORS)
			{
				$count = 0;
				$this->count($count);
				return $count;
			}
			throw new Exception('Please check if APC is enabled.');
		}

		$object_domain	= 'NULL';
		$domain_with_join = 'NULL';
		$hInstance = ORM::getObjectInstance($this->_object_name);
		if (isset($hInstance->object_domain) && !empty($hInstance->object_domain))
		{
			$object_domain = md5(serialize($hInstance->object_domain));
		}
		if (isset($hInstance->domain_with_join) && !empty($hInstance->domain_with_join))
		{
			$domain_with_join = md5(serialize($hInstance->domain_with_join));
		}

		// ---------------------------------------
		// Clé APC = objet/domain/domain_with_join
		// ---------------------------------------
		$apc_key =	strtolower($this->_object_name).'/'.$object_domain.'/'.$domain_with_join;

		if (!Cache::get($apc_key, $count))
		{
			$count = 0;
			$this->count($count);

			// Plus le total count est élevé, plus l'intervalle
			// d'actualisation du cache SQL_CALC_FOUND_ROWS sera grand.
			// Intervalle minimum : 1 seconde.
			$factor = 200;
			$cur_total_count = intval($count);
			$update_interval = abs(ceil($cur_total_count / $factor));

			Cache::set($apc_key, $count, $update_interval);
		}

		return $count;
	}

	protected function read_process_related_list(array $related_list, $original_fields, array &$object_list, array &$computed_field_list)
	{
		//---On traite les related
		foreach($related_list AS $join_field => $fields)
		{
			/* On vérifie si le champs doit être récupéré et on effectue le mapping. */
			$mapping = array();
			foreach($fields AS $field_name => $field_object)
			{
				if ($original_fields != NULL && !in_array($field_name, $original_fields))
				{
					continue;
				}

				$mapping[$field_name] = $field_object->related_relation;

				foreach(array_keys($object_list) AS $id)
				{
					$object_list[$id][$field_name]['value'] = NULL;
					$object_list[$id][$field_name]['editable'] = $field_object->editable;
				}
			}

			if(count($mapping) == 0)
			{
				continue;
			}

			/* Vérification du champ de jointure dans l'objet. */
			if(!property_exists($this->_object, $join_field))
			{
				throw new Exception('L\'attribut '.$join_field.' n\'existe pas dans la classe '.$this->_object_name);
			}

			/* Vérification du type du champ de jointure. */
			$join_field_obj = $this->_object->$join_field;
			if(!($join_field_obj instanceof FieldDefinition))
			{
				throw new Exception('L\'attribut '.$join_field.' n\'est pas un FieldDefinition dans la classe ' . $this->_object_name);
			}

			/* Si le champ de jointure est un related, on va chercher le parent. */
			if($join_field_obj->type_relation === 'related')
			{
				throw new ORMInternalError('Le related via un related est en cours de réalisation...');
			}

			/* Un relation related ne peut être récupérée que par le biais d'un attribut de type many2one ou one2one. */
			if ($join_field_obj->type_relation !== 'many2one' && $join_field_obj->type_relation !== 'one2one')
			{
				throw new Exception('Related type only applicable on many2one or one2one relation !');
			}

			/* Vérification pour savoir si le champ de jointure est calculé. */
			if($join_field_obj->objectName == $this->_object_name && $join_field_obj->function !== NULL)
			{
				if(!isset(self::$func_call_stack[$join_field_obj->function]))
				{
					self::$func_call_stack[$join_field_obj->function] = 0;
				}

				if(self::$func_call_stack[$join_field_obj->function] == 3)
				{
					throw new Exception('LOOP LIMIT REACHED ! With function : ' . $join_field_obj->function);
				}

				self::$func_call_stack[$join_field_obj->function]++;
				$this->process_functions(array($join_field), $object_list);
				self::$func_call_stack[$join_field_obj->function]--;
			}

			/* Objet sur lequel est effectué la jointure. */
			$related_object	= $join_field_obj->object_relation;
			$hInstance		= self::getORMInstance($related_object);
			$related_object_list = array();

			/**
			 * Related via one2one.
			 */
			if($join_field_obj->type_relation === 'one2one')
			{
				if($join_field_obj->related_relation == NULL)
				{
					$rem_field = $hInstance->_object->primary_key;
				}
				else
				{
					$rem_field = $join_field_obj->related_relation;
				}

				foreach($object_list AS $k => $v)
				{
					if(!isset($object_list[$k][$join_field]['value']))
					{
						$object_list[$k][$join_field]['value'] = NULL;
					}
				}

				$reldata_list = array();
				$hInstance->browse($reldata_list, $total, array($join_field_obj->related_relation), array(array($rem_field, 'in', array_keys($object_list))));
				foreach($reldata_list AS $a_id => $d)
				{
					$obj_id = $d[$rem_field]['value'];
					if(isset($object_list[$obj_id]))
					{
						$object_list[$obj_id][$join_field]['value'] = $a_id;
					}
				}
				unset($reldata_list);
			}

			$related_id_list = array();
			//---Construction des id list
			foreach ($object_list as $object_value)
			{
				if(is_array($object_value) && array_key_exists($join_field, $object_value))
				{
					$related_id_list[$object_value[$join_field]['value']] = TRUE;
				}
			}
			//---Dedoublonne
			$related_id_list = array_keys($related_id_list);

			$hInstance->read($related_id_list, $related_object_list, array_values($mapping));
			unset($related_id_list);

			foreach($object_list as $k => $v)
			{
				if(!(isset($object_list[$k][$join_field]) || TOLERATE_MISMATCH))
				{
					throw new ORMInternalError('L\'attribut de jointure \'' . $join_field . '\' n\'est pas présent dans ' . get_class($this->_object) . ' !');
				}

				foreach($fields AS $field_name => $field_object)
				{
					if(!isset($mapping[$field_name]))
					{
						continue;
					}

					if(!array_key_exists($join_field, $object_list[$k]))
					{
						Debug::Log(sprintf("Impossible de trouver %s", $join_field));
						Debug::Log("dans");
						Debug::Log($object_list[$k]);
						throw new Exception(sprintf("Impossible de trouver %s", $join_field));
					}

					$object_list[$k][$field_name]['value'] = NULL;
					$object_list[$k][$field_name]['reference'] = NULL;

					$field_related = $mapping[$field_name];

					if(isset($related_object_list[$object_list[$k][$join_field]['value']][$field_related]))
					{
						foreach($related_object_list[$object_list[$k][$join_field]['value']][$field_related] AS $attribute => $value)
						{
							$object_list[$k][$field_name][$attribute] = $value;
						}
					}
				}
			}

			unset($related_object_list);
			unset($mapping);
		}
	}

	protected function read_process_extends(array $extends_list, array $extended_attributes, array &$object_list)
	{
		foreach($extends_list as $k1 => $v1 )
		{
			$idToRead		 = array() ;
			$extends_data	 = array() ;

			$hORM			= self::getORMInstance( $v1->object_relation );
			$hInstance		= self::getObjectInstance( $v1->object_relation );
			if(empty($v1->related_relation))
			{
				$key_object1 = $k1;
			}
			else
			{
				$key_object1 = $v1->related_relation;
			}
			unset($v1);

			foreach( $object_list as $k2=>$v2 )
			{
				if(array_key_exists($key_object1, $object_list[$k2]))
				{
					$idToRead[$object_list[ $k2 ][ $key_object1 ]['value']][] = $k2;
				}
				else
				if(!TOLERATE_MISMATCH)
				{
					$message =  'Erreur de coherence de la db ! ';
					$message .= 'L\'attribut ' . $key_object1. ' n\'a pas été trouvé dans l\'index ' . $k2 . ' du tableau ' . print_r($object_list[$k2], true);
					throw new Exception($message);
				}
			}

			$f = array();
			foreach($extended_attributes AS $attr)
			{
				if(isset($hInstance->$attr))
				{
					$f[] = $attr;
					foreach(array_keys($object_list) AS $id)
					{
						$object_list[$id][$attr]['value'] = NULL;
						$object_list[$id][$attr]['editable'] = FALSE;
					}
				}
			}

			if($f !== NULL && empty($f))
			{
				continue;
			}

			$hORM->read(array_keys($idToRead), $extends_data, $f);

			foreach($extends_data as $k2 => $v2 )
			{
				if(is_array($v2))
				{
					$child_keys = $idToRead[$k2];
					foreach($child_keys AS $ckey)
					{
						foreach( array_keys($v2) as  $k3)
						{
							if (($k3!=$this->_object_key_name) && (isset($object_list[ $ckey ][ $k3 ]['value'])))
							{
								continue;
								//throw new Exception( 'Try to duplicate attribut: ' . $k3  );
							}

							if(!array_key_exists('value', $v2[$k3]))
							{
								throw new Exception('La valeur du champ \'' . $k3 . '\' est manquante.');
							}

							$object_list[$ckey][$k3] = $v2[$k3] ;
							/*
							if( isset( $v2[ $k3 ][ 'editable' ] ) === true )
							{
								$object_list[ $ckey ][ $k3 ][ 'editable' ] = $v2[ $k3 ][ 'editable' ];
							}

							if (isset($v2[ $k3 ][ 'reference' ]))
							{
								$object_list[ $ckey ][ $k3 ][ 'reference' ] =  $v2[ $k3 ][ 'reference' ];
							}*/
						}
					}
				}
				else
				{
					if(!TOLERATE_MISMATCH)
					{
						throw new Exception($v2  . 'is not an array');
					}
				}
			}
			unset($idToRead);
		}
	}

	protected function read_process_many2many(array $many2many_list, $original_fields, array &$object_list)
	{
		foreach($many2many_list as $key => $value)
		{
			if ($this->_extended_object->$key->function!==NULL)
			{
				continue;
			}

			if ($original_fields != NULL && !in_array($key,$original_fields))
			{
				continue;
			}

			$hInstance		= self::getObjectInstance($value->object_relation);
			$instance_pk	= $hInstance->primary_key;
			$object_pk		= $this->_object->primary_key;

			$rel_object = self::getM2MObject($this->_object->$key->object_relation, $this->_object->$key->objectName);

			if($rel_object === NULL)
			{
				throw new Exception('Impossible de determiner l\'objet de laison '.$this->_object->$key->object_relation.'/'. $this->_object->$key->objectName);
			}

			$hRel = self::getObjectInstance($rel_object);

			if (isset($value->related_relation))
			{
				$instance_pk = $value->related_relation;
			}

			if (isset($value->pk_relation))
			{
				$object_pk = $value->pk_relation;
			}

			foreach(array_keys($object_list) as $k1)
			{
				// On ne réinitialise pas la valeur si la donnée à déjà été récupérée en JsonBrowse
				if (!isset($object_list[$k1][$key]))
				{
					$object_list[$k1][$key]['value'] = array();
				}

				if(!is_array($object_list[$k1][$key]['value']))
				{
					$object_list[$k1][$key]['value'] = array();
				}
			}

			if (count($object_list)>0 && isset($this->_object->table))
			{
				$id_list = array_keys($object_list);

				$hRelField = empty($this->_object->$key->related_relation) ? $this->_object->primary_key : $this->_object->$key->related_relation;

				$query  = 'SELECT '.$this->_object->table.'.'.$this->_object->primary_key.' as pkey, '.$hRel->table.'.'.$hInstance->primary_key.' as rel_id'
						. ' FROM '.$this->_object->database.'.'.$this->_object->table
						. ' INNER JOIN '.$hRel->table .' ON '.$this->_object->table.'.'.$this->_object->primary_key.'='.$hRel->table.'.'.$hRelField
						. ' WHERE '.$this->_object->table.'.'.$this->_object->primary_key.' IN ('.join(',',$id_list).')'
						. ' LIMIT 50000';

				$this->_hDB->db_select($query,$rresult,$rnumrows);

				for($i = 0; $i < $rnumrows; $i++)
				{
					$row = $rresult->fetch_assoc();
					$object_list[$row['pkey']][$key]['value'][] = $row['rel_id'];
				}
				$rresult->free();
			}
		}
		return TRUE;
	}


	/**
	 *  Fonction interne de l'ORM qui permet d'effectuer la récupération des champs
	 *  calculés passés en paramètres.
	 *  Attention : Aucune vérification n'est effectuée concernant les champs
	 *			  passés en paramètres. Ils doivent appartenir à l'objet et
	 *			  être des instances de FieldDefinition.
	 *
	 *  @param $field_list Tableau des champs calculés.
	 *  @param $object_list Contenu de l'objet.
	 */
	protected function process_functions(array $field_list, array &$object_list)
	{
		//---Processing function fields
		foreach($field_list as $field)
		{
			if(is_string($this->_object->$field->function))
			{
				$raw = explode('::',$this->_object->$field->function);

				if(!isset($raw[1]))
				{
					throw new Exception('Impossible de determiner le nom de la methode dans la chaine ' . $this->_object->$field->function);
				}

				$object = $raw[0];
				$method = $raw[1];
				$controller = $object . 'Method';
				unset($raw);

				//---Si pas attributs optionnels
				if (strpos($method,'(')===FALSE)
				{
					if(method_exists($controller, $method))
					{
						$begin = count($object_list);
						self::stop_counter();
						$controller::$method($object_list);
						self::start_counter();
						$end = count($object_list);
						if($begin != $end && !TOLERATE_MISMATCH)
						{
							throw new Exception('La fonction ' . $controller . '::' . $method . ' retourne un nombre d\'éléments différents !');
						}
					}
					else
					{
						throw new Exception($controller.'::'.$method.' doesn\'t exists');
					}
				}
				else
				{
					/* TODO: Utiliser call_user_func */
					eval($controller.'::'.str_replace('(','($object_list,',$method).';');
				}
				unset($controller);
				unset($method);
				unset($object);
			}
		}
	}

	public function readOne($id, &$object, array $original_fields=NULL)
	{
		$object_list = array();
		$this->read(array($id), $object_list, $original_fields);
		$object = $object_list[$id];
		unset($object_list);
		return TRUE;
	}

	public function readAndReturn($u_original_id_list, array $original_fields=NULL)
	{
		$object_list = array();
		$this->read($u_original_id_list, $object_list, $original_fields);
		return $object_list;
	}

	/**
	 * @param array $original_id_list	tableau des id à lire
	 * @param array &$object_list		tableau des resultats
	 * @param array $original_fields	champs necessaires
	 */
	function read($u_original_id_list, &$object_list, array $original_fields=NULL)
	{
		self::start_counter();

		//---Call Pre-read
		$method				= $this->_object_name.'Method';
		$object_list		= array();
		$related_list		= array();
		$extends_list		= array() ;
		$many2many_list		= array();
		$workflow_status_list = array();
		$fields_to_read		= array();

		if(DISPLAY_ERRORS)
		{
			if(empty($original_fields))
			{
				//backtrace
				$bt=array();
				foreach(debug_backtrace(false) as $sub_bt)
				{
					//if($i==0 && substr($sub_bt['file'], -10)=='Common.php') break;
					//if($i==0 && substr($sub_bt['file'], -17)=='MappingObject.php') break;
					$bt[]=(isset($sub_bt['file']) ? $sub_bt['file'] : 'unknown_file').':'.(isset($sub_bt['line']) ? $sub_bt['line'] : 'unknown_line');
				}

				if(!empty($bt))
				{
					self::$no_fields[]=$bt;
				}
			}
		}

		/* On transforme le paramètre id_list en tableau s'il correspond à une seule entrée. */
		$original_id_list = is_array($u_original_id_list) ? $u_original_id_list : array($u_original_id_list);

		$id_list = array();
		$fields = array();

		/* Exécution du Hook */
		if (class_exists($method))
		{
			$original_fields = $this->_addDependencies($original_fields);

			$hInstance = new $method();
			$hInstance->preRead($original_id_list, $id_list, $original_fields, $fields);
			unset($hInstance);
			$original_fields = $fields;
		}
		else
		{
			$id_list = $original_id_list;
			$fields  = $original_fields;
		}

		unset($method);
		unset($original_id_list);

		/* On élimine les doublons d'id */
		$id_list	 = array_unique($id_list);

		/* On retire les valeurs NULL */
		foreach($id_list AS $position => $id)
		{
			if($id === NULL || $id === '')
			{
				unset($id_list[$position]);
			}
		}

		$number_of_ids = count($id_list);

		/* S'il n'y a pas d'id à récupérer, on quitte. */
		if ($number_of_ids === 0)
		{
			return TRUE;
		}

		if($number_of_ids > 10000)
		{
			//new NonBlockingException('Non mais ALLO QUOI ! un read sur '.$number_of_ids.' id ?');
		}

		//---Création du tableau de résultat de base afin de maintenir le tri.
		foreach($id_list AS $id)
		{
			$object_list[$id] = array();
		}

		/* Traitement des id de clé primaires multiple. */
		$id_list_concatened = $id_list;
		if(is_array($this->_object_key_name))
		{
			$multiple_id_list = array();
			$combination_id_list = array();
			foreach($id_list AS $id)
			{
				$raw = explode(',', $id);
				$idx = 0;
				foreach($this->_object_key_name AS $k)
				{
					if(isset($raw[$idx]) && !empty($raw[$idx]))
					{
						$multiple_id_list[$k][] = $raw[$idx];
						$combination_id_list[$id][$k] = $raw[$idx];
					}
					else
					{
						$combination_id_list[$id][$k] = NULL;
					}
					$idx++;
				}
			}
			$id_list = $multiple_id_list;
		}

		/* On place le (ou les) champs primary_key dans la liste des champs à récupérer. */
		$primary_keys = explode(',', $this->_object->primary_key);
		foreach($primary_keys AS $pk)
		{
			$pk = trim($pk);
			$fields_to_read[$pk] = $pk;
		}

		/* Analyse des attributs de l'objet. */
		$extended_attributes = array();
		foreach($this->_object AS $field_name => $field_object)
		{
			if($field_object instanceof FieldDefinition)
			{
				if($field_object->objectName == $this->_object_name)
				{
					switch($field_object->type_relation)
					{
						case 'primary key':
						case 'one2one':
						case 'many2one':
						case 'selection':
							$fields_to_read[$field_name] = $field_name ;
							break ;
						case 'many2many':
							if($this->_xmlrpc_object==NULL)
							{
								$many2many_list[ $field_name ] = $field_object;
							}
							$fields_to_read[$field_name] = $field_name;
							break;
						case 'extends':
							$extends_list[ $field_name ] = $field_object;
							$fields_to_read[$field_name] = $field_name ;
							break ;
						case 'workflow_status':
							if ($fields === NULL || in_array($field_name, $fields))
							{
								$workflow_status_list[] = $field_name;
								$fields_to_read[$field_name] = $field_name ;
							}
							break;
						case 'related':
							$related_list[$field_object->object_relation][$field_name] = $field_object;
							$fields_to_read[$field_object->object_relation] = $field_object->object_relation;
							if($fields === NULL)
							{
								$fields_to_read[$field_name] = $field_name ;
							}
							break ;
						default:
							if($fields === NULL)
							{
								$fields_to_read[$field_name] = $field_name ;
							}
					}
				}
				else
				{
					$extended_attributes[] = $field_name;
				}
			}
		}

		/*  Requested fields */
		if($fields !== NULL)
		{
			foreach($fields AS $f)
			{
				$fields_to_read[$f] = $f;
			}
		}

		if(STRICT_MODE === TRUE)
		{
			$db_fields = array();
			foreach($fields_to_read as $field_to_read)
			{
				if(
						property_exists($this->_object, $field_to_read)
						&& $this->_object->$field_to_read->type_relation!=='one2many'
						&& $this->_object->$field_to_read->type_relation!=='workflow_status'
						&& $this->_object->$field_to_read->isDbColumn()
						&& $this->_object->$field_to_read->type_relation!='related'
						&& $this->_object->$field_to_read->type_relation!='many2many'
						&& $this->_object->$field_to_read->objectName == $this->_object_name
				){
					$db_fields[] = $field_to_read;
				}
			}
		}
		else
		{
			/* Récupération des champs qui correspondent à des colonnes de la table. */
			$db_fields = array();
			if(!isset($this->_object->json))
			{
				//  @todo éviter de faire un desc pour gagner en requete
				//
				if(!isset(self::$desc_table[$this->_object_table]))
				{
					$this->_hDB->db_select('desc `'.$this->_object_database.'`.`'.$this->_object_table . '`;', $result, $numrows);

					while($row = $result->fetch_assoc())
					{
						$db_fields[]=$row['Field']; // liste des colonnes
					}
					$result->free();

					// sauvegarde
					self::$desc_table[$this->_object_table]=$db_fields;
				}
				else
				{
					$db_fields = self::$desc_table[$this->_object_table];
				}
			}
		}

		$computed_field_list = array();
		$many2one_list = array();
		$one2one_list = array();
		$db_fields_select = array();
		$many2one_reference = array();
		$reference_mapping = array();
		$query_joins = '';
		$tmp_id = 0;

		/* Parcours des attributs à récupérer et vérification de type ! */
		foreach($fields_to_read as $field_to_read)
		{
			/* Vérification de l'existance de l'attribut. */
			if(!isset($this->_object->$field_to_read))
			{
				if(DISPLAY_ERRORS)
				{
					#throw new ObjectException('L\'attribut ' . $field_to_read . ' n\'appartient pas à l\'objet ' .$this->_object_name. ' !');
				}
				continue;
			}

			$attribute = $this->_object->$field_to_read;

			/* Vérification du type de l'attribut. */
			if(!($attribute instanceof FieldDefinition))
			{
				throw new ObjectException('L\'attribut ' . $field_to_read . ' n\'est pas de type FieldDefinition !');
			}

			/* Si l'attribut correspond à une colonne de la table, on le selectionne. */
			if( in_array( $field_to_read, $db_fields ) ) // si l'attribut demandé est une colonne de la table
			{
				$db_fields_select[] = 'orm_main.' . '`'.$field_to_read.'`'; // on select
			}

			/* Si l'attribut a été demandé */
			if($original_fields == NULL || in_array($field_to_read, $original_fields) || $attribute->isSQLAlias())
			{
				/* Si l'attribut est calculé sur l'objet courant. */
				if($attribute->isFunction() && $attribute->objectName == $this->_object_name)
				{
					$computed_field_list[$attribute->function] = $field_to_read;
				}

				/* Si l'attribut est calculé par le SGBD */
				if($attribute->isSQLAlias() && $attribute->objectName == $this->_object_name)
				{
					$calc = $attribute->sql_alias;
					$vars = array();
					preg_match_all('(%[a-zA-Z0-9_]+%)', $calc, $vars);
					foreach($vars AS $var)
					{
						foreach($var AS $v)
						{
							$v2 = 'orm_main.' . '`'.substr($v, 1, -1).'`';
							$calc = str_replace($v, $v2, $calc);
						}
					}

					$db_fields_select[] = $calc . ' AS ' . $field_to_read;
				}

				/* Si l'attribut est un one2one, non calculé. */
				if (!is_array($attribute->type) && !$attribute->isFunction() && !$attribute->isSQLAlias() && ($attribute->type_relation==='one2one' || $attribute->type_relation==='parent'))
				{
					$one2one_list[] = $field_to_read;
				}

				/* Si l'attribut est un many2one, non calculé. */
				if (!is_array($attribute->type) && $attribute->isDbColumn() && ($attribute->type_relation==='many2one' || $attribute->type_relation==='parent'))
				{
					$many2one_list[] = $field_to_read;

					if(empty($attribute->object_relation))
					{
						throw new Exception('Pas d\'object_relation sur le champ : ' . $attribute->name);
					}
					$lnk_obj = self::getObjectInstance($attribute->object_relation);
					if(isset($lnk_obj->database) && isset($this->_object->database) && $lnk_obj->database == $this->_object->database && isset($lnk_obj->reference) && $attribute->objectName == $this->_object_name)
					{
						$ref_field = $lnk_obj->reference;
						if(!($ref_field instanceof FieldDefinition) && $lnk_obj->$ref_field->objectName == get_class($lnk_obj) && $lnk_obj->$ref_field instanceof FieldDefinition && is_string($ref_field) && !$lnk_obj->$ref_field->isFunction())
						{
							$tmp_id++;
							$m2o_table_alias = 'orm_m2o_' . $tmp_id;
							$field_alias = 'orm_' . $ref_field . '_rf_' . $tmp_id;
							$query_joins .= 'LEFT JOIN ' . $lnk_obj->database . '.' . $lnk_obj->table . ' ' . $m2o_table_alias . ' ON ' . $m2o_table_alias . '.' . $lnk_obj->primary_key . '=orm_main.' . $field_to_read . ' ';
							if($lnk_obj->$ref_field->isSQLAlias())
							{
								$calc = $lnk_obj->$ref_field->sql_alias;
								$vars = array();
								preg_match_all('(%[a-zA-Z0-9_]+%)', $calc, $vars);
								foreach($vars AS $var)
								{
									foreach($var AS $v)
									{
										$v2 = $m2o_table_alias.'.' . '`'.substr($v, 1, -1).'`';
										$calc = str_replace($v, $v2, $calc);
									}
								}
								$many2one_reference[] = array(	'table_alias' => '',
																'field_alias' => $field_alias,
																'field' => $calc);
							}
							else
							{
								if($lnk_obj->$ref_field->type_relation == 'related')
								{
									// TODO: Optim on related.
									continue;
								}
								$many2one_reference[] = array(	'table_alias' => $m2o_table_alias,
																'field_alias' => $field_alias,
																'field' => $ref_field);
							}
							$reference_mapping[$field_alias] = $field_to_read;
						}
					}
				}
			}
		}

		/* On ajoute l'attribut de référence s'il est présent et qu'il correspond à un champ de la table. */
		if(isset($this->_object->reference) && in_array( $this->_object->reference, $db_fields ))
		{
			$reference_mapping['orm_main_ref'] = $this->_object->primary_key;
			$db_fields_select[] = 'orm_main.' . '`'.$this->_object->reference.'` AS orm_main_ref';
		}

		/* Récupération via JSON Browse */
		if(isset($this->_object->json))
		{
			$this->jsonProcessor('read', $object_list, $original_fields, $u_original_id_list);
			if(isset($this->_object->table))
			{
				$obj = reset($object_list);

			}
		}
		/* Récupération via BDD */
		else
		{
			// ne devrait pas arriver !
			if(count($db_fields_select)==0)
			{
				$db_fields_select=array('*');
			}

			$query	= 'SELECT DISTINCT '.implode(',', $db_fields_select) . ' ';

			foreach($many2one_reference AS $ref)
			{
				if(!empty($ref['table_alias']))
				{
					$query .= ', ' . $ref['table_alias'] . '.' . $ref['field'] . ' AS ' . $ref['field_alias'] . ' ';
				}
				else
				{
					$query .= ', ' . $ref['field'] . ' AS ' . $ref['field_alias'] . ' ';
				}

			}
			$query .= 'FROM ' . $this->_object_database . '.' . $this->_object_table .  ' orm_main '
					. $query_joins
					. 'WHERE ';
			if(is_array($this->_object_key_name))
			{
				$or = array();
				$qs = array();
				foreach($combination_id_list AS $id)
				{
					$combination = array();
					foreach($this->_object_key_name AS $k)
					{
						if($id[$k] !== NULL)
						{
							$combination[] = 'orm_main.`'. $k .'`=\''.$this->_hDB->db_escape_string($id[$k]).'\'';
						}
						else
						{
							$combination[] = 'orm_main.`'. $k .'` IS NULL';
						}
					}

					$f = '(' . join(' AND ', $combination) . ')';
					$qs[] = $f;
				}
				foreach($this->_object_key_name AS $k)
				{
					$or[] = 'orm_main.' . $k;
				}
				/*
				foreach($this->_object_key_name AS $k)
				{
					if(isset($id_list[$k]))
					{
						$ids = $id_list[$k];
						$q = 'orm_main.' . $k . ' IN ('.((!is_numeric(current($ids))) ? '\''.implode('\',\'', $ids).'\'' : implode(',', $ids)).') ';
						$q = '(' . $q . ' OR orm_main.' . $k . ' IS NULL)';
						$qs[] = $q;
						$or[] = 'orm_main.' . $k;
					}
					else
					{
						$qs[] = '(orm_main.' . $k . ' IS NULL)';
					}

				}
				*/
				$query .= '('.join(' OR ', $qs).')'
						.' ORDER BY ' . join(',', $or) . ' '
						. 'LIMIT ' . $number_of_ids;
			}
			else
			{
				$okn =$this->_object_key_name;
				$query .= 'orm_main.' . $this->_object_key_name . ' IN ('.($this->_object->$okn instanceof TextFieldDefinition ? '\''.implode('\',\'', $id_list).'\'' : implode(',', $id_list)).') '
						.' ORDER BY orm_main.' . $this->_object_key_name . ' '
						. 'LIMIT ' . $number_of_ids;
			}

			//---Process query
			$this->_hDB->db_select($query, $result, $numrows);

			if((TOLERATE_MISMATCH == FALSE) && ($number_of_ids > $numrows))
			{
				throw new MismatchObjectException('Number result for "'.$this->_object_name.'" does not match number ID ! (Number=' . $number_of_ids . ', Result=' . $numrows . ')');
			}

			//---On deroule les resultats
			while(($row = $result->fetch_assoc()) != NULL)
			{
				foreach($row AS $key => $value)
				{
					if(is_array($this->_object_key_name))
					{
						$v = array();
						foreach($this->_object_key_name AS $k)
						{
							$v[] = $row[$k];
						}
						$object_id = join(',', $v);

						/**
						 * Dans le cas où la primary key n'est pas présente dans le jeu de résultats (cas des clés primaires multiples), on ajoute l'object_id généré au tableau de résultats.
						 */
						$object_list[$object_id][$this->_object->primary_key]['value'] = $object_id;
					}
					else
					{
						$object_id = $row[$this->_object_key_name];
					}
					if(isset($reference_mapping[$key]) && !empty($reference_mapping[$key]))
					{
						$f = $reference_mapping[$key];
						$object_list[$object_id][$f]['reference'] = $value;
					}
					else
					if(property_exists($this->_object, $key))
					{
						$object_list[$object_id][$key]['value'] = $value;

						if(($this->_extended_object->$key instanceof FieldDefinition) && !($this->_extended_object->$key instanceof ExtendedFieldDefinition))
						{
							switch($this->_object->$key->type)
							{
								case 'date':

									if($value == "0000-00-00 00:00:00" || $value == "0000-00-00")
									{
										$value = null;
									}

									$object_list[$object_id][$key]['value'] = strtotime($value);
									$object_list[$object_id][$key]['timestamp'] = strtotime($value);
									$object_list[$object_id][$key]['date_str'] = $value;
									break;
								case 'time':
									$object_list[$object_id][$key]['value']		= $value;
									$object_list[$object_id][$key]['timestamp']	= strtotime($value, 0);
									break;
								case 'price':
									$object_list[$object_id][$key]['value']		= $value;
									if ($value && is_numeric($value))
									{
										$object_list[$object_id][$key]['html']	= number_format($value, 2, ',', ' ').' €';
									}
								break;
							}
						}

						unset($value);
						$object_list[$object_id][$key]['editable'] =  $this->_object->$key->editable;
					}
				}
			}

			if((TOLERATE_MISMATCH == FALSE) && ($number_of_ids != count($object_list)))
			{
				throw new MismatchObjectException('Number result for "'.$this->_object_name.'" does not match number ID ! (Number=' . $number_of_ids . ', Result=' . count($object_list) . ')');
			}

			if(!TOLERATE_MISMATCH)
			{
				foreach($object_list AS $o_id => $o_data)
				{
					if(count($o_data) == 0)
					{
						$message = 'L\'ORM n\'a pas su récupérer l\'élément ' . $o_id . ' avec la requête ! ';
						$message .= 'Requête : ' . $query;
						throw new ORMInternalError($message);
					}
				}
			}

			$result->free();
			unset($query);

			/* Vérification des champs à récupérer en extends. */
			$extended_attributes_read = array();
			foreach($extended_attributes AS $a)
			{
				if($original_fields == NULL || in_array($a, $original_fields) || isset($fields_to_read[$a]))
				{
					$extended_attributes_read[] = $a;
				}
			}
			$this->read_process_extends($extends_list, $extended_attributes_read, $object_list);
		}

		$this->read_process_related_list($related_list, $original_fields, $object_list, $computed_field_list);
		$this->read_process_many2many($many2many_list, $original_fields, $object_list);

		unset($extends_list);
		unset($related_list);
		unset($many2many_list);

		if(!is_array($object_list))
		{
			$object_list=array();
		}

		/* On élimine les objets vide : Dans le cas de tolerate_mismatch les objets peuvent ne pas exister ! */
		foreach($id_list_concatened AS $id)
		{
			if(isset($object_list[$id]) && count($object_list[$id]) == 0)
			{
				unset($object_list[$id]);
			}
		}

		/* Si la base est incohérente, on passe les fonctions de récupérations des m2m, o2m, fonctions et status de workflow. */
		if(!(TOLERATE_MISMATCH && empty($object_list)))
		{
			//---On recup les one2many
			foreach( $this->_object AS $key=>$value )
			{
				if ($original_fields!=NULL && (!in_array($key,$original_fields)))
				{
					continue;
				}

				/* Pas de récupération sur des objets distants (c'est le site distant qui s'en charge). */
				if(isset($this->_object->json))
				{
					continue;
				}

				/* Si le champ n'est pas demandé, on ne le récupère pas. */
				if($fields !== NULL && !in_array($key, $fields))
				{
					continue;
				}

				/* Si le champ n'est pas un fieldDefinition, on l'ignore. */
				if(!($this->_object->$key instanceof FieldDefinition))
				{
					continue;
				}

				/* Si le champ est calculé par fonction PHP, on l'ignore. */
				if($this->_object->$key->isFunction())
				{
					continue;
				}

				/* Si le champ n'est pas un one2many, on l'ignore. */
				if($this->_object->$key->type_relation !== 'one2many')
				{
					continue;
				}

				/* Si le champ n'appartient pas à l'objet courant, on l'ignore. */
				if(get_class($this->_object) != $this->_object->$key->objectName)
				{
					continue;
				}

				/**
				 * Définition de la valeur par défaut.
				 */
				foreach($object_list as $object_id=>$object)
				{
					$object_list[$object_id][$key]['value']	= array();
					$object_list[$object_id][$key]['editable'] = FALSE;
				}

				if(!isset($this->_object->$key->related_relation))
				{
					throw new Exception('one2many need 2 arguments in class '.$this->_object_name);
				}

				$o2m_id_list		= array();
				$objectInstance		= self::getObjectInstance($this->_object->$key->object_relation);
				$var1_table_object_key = $objectInstance->table.'.'.$this->_object->$key->related_relation;

				$ids = array();
				if(is_array($this->_object_key_name))
				{
					$ids = $id_list[$this->_object->$key->related_relation];
				}
				else
				{
					$ids = $id_list;
				}

				$order = isset($this->_object->$key->_order_relation) ? $this->_object->$key->_order_relation : NULL;
				self::getORMInstance($this->_object->$key->object_relation)->search($o2m_id_list,$num,array(array($this->_object->$key->related_relation,'in',$ids)),$order,0,50000,array($var1_table_object_key));
				unset($order);

				if ($o2m_id_list!=NULL)
				{
					foreach($o2m_id_list as $o2m)
					{
						$object_list[$o2m[$var1_table_object_key]][$key]['value'][]  = $o2m[$objectInstance->primary_key];
						$object_list[$o2m[$var1_table_object_key]][$key]['editable'] = FALSE;
					}
				}
				unset($o2m_id_list);
				unset($var1_table_object_key);
			}

			/* On prend le premier élément de la liste. */
			$l1 = reset($object_list);

			//---Process one2one one by one
			foreach($one2one_list AS $one2one)
			{
				/* Déja défini par un related. */
				if(isset($l1[$one2one]['value']))
				{
					continue;
				}

				$join_field_obj = $this->_object->$one2one;
				$hORM = ORM::getORMInstance($join_field_obj->object_relation);
				if($join_field_obj->related_relation == NULL)
				{
					$rem_field = $hORM->_object->primary_key;
				}
				else
				{
					$rem_field = $join_field_obj->related_relation;
				}

				foreach($object_list AS $k => $v)
				{
					if(!isset($object_list[$k][$one2one]['value']))
					{
						$object_list[$k][$one2one]['value'] = NULL;
					}
				}

				$reldata_list = array();
				$hORM->browse($reldata_list, $total, array($join_field_obj->related_relation), array(array($rem_field, 'in', array_keys($object_list))));
				foreach($reldata_list AS $a_id => $d)
				{
					$obj_id = $d[$rem_field]['value'];
					if(isset($object_list[$obj_id]))
					{
						$object_list[$obj_id][$one2one]['value'] = $a_id;
					}
				}
				unset($reldata_list);
			}

			//---Process many2one one by one
			foreach($many2one_list as $many2one)
			{
				//---Si on a dejà une reference (fromxmlrpc par exemple) .... on ne la calcule pas
				// Idem si le champs à l'attribut no_reference à TRUE.
				if(!isset($l1[$many2one]))
				{
					throw new ObjectException('Le champ ' . $many2one . ' n\'existe pas dans ' . $this->_object_name . ' (sans doute non présent dans la table de cet objet) !');
				}

				if (!array_key_exists('reference', $l1[$many2one])
					AND !(property_exists($this->_object, $many2one)
					AND property_exists($this->_object->$many2one, 'no_reference')
					AND $this->_object->$many2one->no_reference))
				{
					$m2o_id_list	 = array();
					$hMethodInstance = self::getControllerInstance($this->_object->$many2one->object_relation);
					$ref_list		 = array();

					foreach($object_list as $object_id=>$object)
					{
						if($object != NULL && isset($object[$many2one]))
						{
							if(is_numeric($object[$many2one]['value']))
							{
								$m2o_id_list[$object[$many2one]['value']] = TRUE;
							}
						}
						else
						if(!TOLERATE_MISMATCH)
						{
							throw new MismatchObjectException("Impossible de trouver le tuple ".$object_id." dans les objets ".$this->_object_name. ' pour le champ ' . $many2one);
						}
					}
					//---Dédoublonne.
					$m2o_id_list = array_keys($m2o_id_list);
					//---Instance de Method
					if(!method_exists($hMethodInstance, 'getReferenceString'))
					{
						throw new Exception('Vous n\'avez pas defini la methode getReferenceString dans la classe '.get_class($hMethodInstance));
					}

					if(count($m2o_id_list) > 0)
					{
						self::stop_counter();
						$hMethodInstance->getReferenceString($m2o_id_list,$ref_list);
						self::start_counter();
						if(DISPLAY_ERRORS && count($m2o_id_list) != count($ref_list) && !TOLERATE_MISMATCH)
						{
							$message = 'La methode getReferenceString dans ' . get_class($hMethodInstance) . ' ne renvoi pas le même nombre d\'élements.' . PHP_EOL;
							$message .= 'Nombre d\'id passés : ' . count($m2o_id_list) . ', Nombre de références retournées : ' . count($ref_list);
							throw new Exception($message);
						}

						foreach($object_list as $key=>$object)
						{
							if(array_key_exists($many2one, $object_list[$key]))
							{
								if(isset($ref_list[$object_list[$key][$many2one]['value']]))
								{
									$object_list[$key][$many2one]['reference'] = (!is_array($ref_list[$object_list[$key][$many2one]['value']]))?$ref_list[$object_list[$key][$many2one]['value']]:$ref_list[$object_list[$key][$many2one]['value']]['value'];
								}
							}
							else
							{
								Debug::Log("impossible de trouver ".$many2one);
							}

						}
					}

					unset($hMethodInstance);
				}
			}
			unset($l1);

			//---Process workflow status
			foreach($workflow_status_list AS $workflow_status)
			{
				if(get_class($this->_object) != $this->_object->$workflow_status->objectName)
				{
					continue;
				}

				if (!isset($fields_to_read[$workflow_status]))
				{
					continue;
				}

				$workflow_name	= $this->_object->$workflow_status->object_relation;
				$object_id		=  $this->_object->$workflow_status->related_relation;


				$object_id_list = array();

				//---Id list
				foreach($object_list as $id=>$object)
				{
					if(is_array($this->_object_key_name))
					{
						if(!isset($object[$object_id]))
						{
							throw new Exception("Process workflow status failed");
						}
						$object_id_list[$object[$object_id]['value']][] = $id;
					}
					else
					{
						$object_id_list[$id][] = $id;
					}
					$object_list[$id][$workflow_status]['value']	= NULL;
					$object_list[$id][$workflow_status]['editable'] = FALSE;
					$object_list[$id][$workflow_status]['url']		= NULL;
					$object_list[$id][$workflow_status]['workflow_node_id'] = array();
					$object_list[$id][$workflow_status]['workflow_node_name'] = array();
				}

				//---Nom des objects enfants
				$object_name_list = array('"'.strtolower($this->_object_name).'"');

				foreach(self::$_objects as $obj)
				{
					$instance = $obj['class'];
					if(isset($instance->primary_key) && !is_array($instance->primary_key))
					{
						$pk = $instance->primary_key;
						if (isset($instance->$pk) && (strtolower($instance->$pk->type)==strtolower('extends:'.$this->_object_name)))
						{
							$object_name_list[] = '"'.strtolower(get_class($instance)).'"';
						}
						unset($pk);
					}
					unset($instance);
				}

				$cp = ($object_id=='id')?" and (killi_workflow_node.object in (".join(',',$object_name_list).")) ":'';
				$query = "select killi_workflow_token.$object_id,killi_workflow_node.etat,killi_workflow_node.workflow_node_id,killi_workflow_node.node_name,killi_workflow_node.`object`, UNIX_TIMESTAMP(killi_workflow_token.date) as token_date, DATEDIFF( NOW(), killi_workflow_token.date ) AS dday
				from killi_workflow_token,killi_workflow_node,killi_workflow
				where killi_workflow_token.$object_id in (".join(',',array_keys($object_id_list)).")
				and (killi_workflow_token.node_id=killi_workflow_node.workflow_node_id)
				and (killi_workflow_node.workflow_id=killi_workflow.workflow_id)
				$cp
				and (killi_workflow.workflow_name=\"".$workflow_name."\")
						order by killi_workflow_token.date ASC";

				unset($cp);

				$this->_hDB->db_select($query,$rresult,$rnumrows);

				unset($query);
				unset($rnumrows);

				while(($row = $rresult->fetch_assoc())!= NULL)
				{
					$o_id_list = $object_id_list[$row[$object_id]];
					foreach($o_id_list AS $o_id)
					{
						$object_list[$o_id][$workflow_status]['url']	   = NULL;
						if ((isset($object_list[$o_id][$workflow_status]['value'])))
						{
							$object_list[$o_id][$workflow_status]['value']	.= ' & '.$row['etat'].' ('.date('d/m/Y',$row['token_date']).' j+' . $row['dday'] . ')';
							$object_list[$o_id][$workflow_status]['workflow_node_id'][]	= $row['workflow_node_id'];
							$object_list[$o_id][$workflow_status]['workflow_node_name'][]	= $row['node_name'];
						}
						else
						{
							Security::crypt($row['workflow_node_id'], $crypt_node_id);
							$object_list[$o_id][$workflow_status]['value']	= $row['etat'].' ('.date('d/m/Y',$row['token_date']).' j+' . $row['dday'] . ')';
							$object_list[$o_id][$workflow_status]['workflow_node_id'][]	= $row['workflow_node_id'];
							$object_list[$o_id][$workflow_status]['workflow_node_name'][]	= $row['node_name'];

							if(!empty($row['object']))
							{
								$hInstance = ORM::getObjectInstance($row['object']);
								if ($hInstance->view)
								{
									$object_list[$o_id][$workflow_status]['url']	  = './index.php?action='.$row['object'].'.edit&crypt/workflow_node_id='.$crypt_node_id;
								}
							}

						}
					}
					unset($row);
				}
				$rresult->free();
			}

			// outCast
			foreach($object_list as $object_id=>$object)
			{
				foreach($this->_object as $attribute_name=>$attribute)
				{
					if($attribute instanceof ExtendedFieldDefinition && isset($object_list[$object_id][$attribute_name]) && !in_array($attribute_name, $computed_field_list))
					{
						$casted_value = $object_list[$object_id][$attribute_name]['value'];

						$attribute->outCast($casted_value);

						// on ajoute/remplace les valeurs de l'outCast dans le tableau du field
						foreach($casted_value as $casted_value_name=>$casted_value_content)
						{
							$object_list[$object_id][$attribute_name][$casted_value_name] = $casted_value_content;
						}
					}
				}
			}

			$this->process_functions($computed_field_list, $object_list);

			// outCast des champs calculés par fonction
			foreach($object_list as $object_id=>$object)
			{
				foreach($this->_object as $attribute_name=>$attribute)
				{
					if($attribute instanceof ExtendedFieldDefinition && isset($object_list[$object_id][$attribute_name]) && in_array($attribute_name, $computed_field_list))
					{
						$casted_value = $object_list[$object_id][$attribute_name]['value'];

						$attribute->outCast($casted_value);

						// on ajoute/remplace les valeurs de l'outCast dans le tableau du field
						foreach($casted_value as $casted_value_name=>$casted_value_content)
						{
							$object_list[$object_id][$attribute_name][$casted_value_name] = $casted_value_content;
						}
					}
				}
			}

			if(is_array($this->_object_key_name))
			{
				foreach($object_list AS $id => &$data)
				{
					$data[$this->_object->primary_key]['value'] = $id;
					$data[$this->_object->primary_key]['editable'] = FALSE;
				}
			}
		}
		unset($workflow_status_list);
		unset($many2one_list);
		unset($fields);
		unset($original_fields);
		unset($computed_field_list);
		unset($fields_to_read);

		//---Call Post-read
		$method = $this->_object_name.'Method';
		if (class_exists($method))
		{
			$hInstance = new $method();
			self::stop_counter();
			$hInstance->postRead($object_list,$object_list);
			self::start_counter();
			unset($hInstance);
		}
		unset($method);

		if( isset( $object_list ) === TRUE && is_array( $object_list ) === TRUE )
		{
			reset( $object_list ) ;
		}

		if (!is_array($u_original_id_list))
		{
			$object_list = $object_list[$u_original_id_list];
		}

		if( isset( $object_list ) === TRUE && is_array( $object_list ) === TRUE )
		{
			reset( $object_list ) ;
		}

		self::stop_counter();

		return True;
	}
	//-------------------------------------------------------------------------
	private function _findExtendedObject( $object_name, &$object_list )
	{
		$object_list[] = self::getObjectInstance($object_name);
		$i = count( $object_list ) ;
		$i-- ;

		foreach( $object_list[ $i ] as $k => $v )
		{
			if (is_object($object_list[ $i ]->$k) && is_object($v) && $v instanceof FieldDefinition
					&& !is_array($object_list[ $i ]->$k->type) && $object_list[ $i ]->$k->type_relation === 'extends')
			{
				$tmp = $object_list[ $i ]->$k->object_relation;
				if(isset( $tmp ) && $tmp != '')
				{
					$this->_findExtendedObject($tmp, $object_list);
				}
			}
		}
	}
	/**
	 * équivalent au insert
	 * exemple create:(array('id'=>'1');
	 * @param $object_data: tableau associatif des champs de la table et valeur à insérer
	 * 
	 */
	function create( array $object_data, &$object_id=null, $ignore_duplicate=false, $on_duplicate_key = FALSE )
	{
		if(isset($this->_object->json))
		{
			// tmp
			if(isset($this->_object->json['enable_create']) && $this->_object->json['enable_create'] === TRUE)
			{
				$this->jsonProcessor('create', $object_id, $object_data);
			}

			return TRUE;
		}

		$OL = array() ;
		if( is_object( $this->_object ) === true )
		{
			$this->_findExtendedObject( $this->_object_name, $OL ) ;
			$OL = array_reverse( $OL ) ;
		}

		$query  = NULL ;
		$total = count($OL);

		for( $i = 0 ; $i < $total; $i++ )
		{
			//---Call Pre-read
			$objectName = get_class($OL[$i]);
			$method = $objectName . 'Method';
			if(class_exists($method))
			{
				$hInstance = new $method();
				$hInstance->preCreate($object_data,$object_data);
			}

			$emptySet = true;
			foreach( $OL[ $i ] as $field_name => $field )
			{
				/* On ne prend que les FieldDefinition déclarés sur l'objet. */
				if(!($field instanceof FieldDefinition))
				{
					continue;
				}

				/* On évite les champs qui ne sont pas sur l'objet courant (càd récupéré du parent). */
				if($field->objectName != $objectName)
				{
					continue;
				}

				/* On retire les champs non présent en base */
				if(!$field->isDbColumn())
				{
					continue;
				}

				/* Si le champ n'a pas de valeur définis. */
				if(!isset($object_data[$field_name]))
				{
					continue;
				}

				/* On évite les many2one non renseigné. */
				if($field->type_relation == 'many2one' && $object_data[$field_name] == 0)
				{
					continue;
				}

				/* On n'enregistre pas les champs vide. */
				if($object_data[$field_name] === '' && $object_data[$field_name] !== FALSE)
				{
					continue;
				}

				if(!is_array($field->type) && preg_match( '/^primary/i', $field->type) && $total > 1)
				{
					continue;
				}

				// ExtendedFieldDefinition
				if($field instanceof ExtendedFieldDefinition)
				{
					$field->inCast($object_data[ $field_name ]);
				}
				else
				{
					/* Vérification du format de date : Si c'est un timestamp alors on convertit en datetime. */
					if($field->type == 'date' && is_numeric($object_data[ $field_name ]))
					{
						$object_data[ $field_name ] = date('Y-m-d', $object_data[ $field_name ]);
					}

					//---On considere le type checkbox (boolean)
					if ($field->type_relation==='checkbox')
					{
						$object_data[ $field_name ] = ($object_data[ $field_name ]==TRUE || $object_data[ $field_name ] == 1 || $object_data[ $field_name ] == '1') ? '1' : '0';
					}
				}

				$emptySet = false;

				if($query == '' )
				{
					if( $ignore_duplicate === false )
					{
						$query = 'INSERT INTO ' . $OL[ $i ]->{'database'} . '.' . $OL[ $i ]->{'table'} . ' SET ' ;
					}
					else
					{
						$query = 'INSERT IGNORE INTO ' . $OL[ $i ]->{'database'} . '.' . $OL[ $i ]->{'table'} . ' SET ' ;
					}
					$query .= '`'.$field_name.'`' . '=\'' . $this->_hDB->db_escape_string( $object_data[ $field_name ] ) . '\'';
				}
				else
				{
					$query .= ', ' . '`'.$field_name.'`' . '=\'' . $this->_hDB->db_escape_string( $object_data[ $field_name ] ) . '\'' ;
				}
			}

			/* Cas de la création d'un objet parent d'un extended sans définition des attributs du père. */
			if($emptySet)
			{
				if(!isset($object_data[$OL[$i]->primary_key]))
				{
					$query = 'INSERT INTO ' . $OL[ $i ]->database . '.' . $OL[ $i ]->table . ' (' . $OL[$i]->primary_key . ') VALUES (NULL);';
				}
			}

			if( $query != '' )
			{
				$nbrow = 0 ;
				$id = 0;
				if(is_array($on_duplicate_key))
				{
					$query .= " ON DUPLICATE KEY ".$this->get_write_sql(NULL, $on_duplicate_key, FALSE, FALSE, FALSE, FALSE);
				}
				$this->_hDB->db_execute( $query, $nbrow ) ;
				$this->_hDB->db_insert_id($id);

				if(empty($object_data[  $OL[ $i ]->primary_key ]))
				{
					if($id == 0 && $on_duplicate_key === FALSE)
					{
						/* Si l'objet existe déjà en base, on ignore la suite de la création. */
						if($ignore_duplicate === true)
						{
							return TRUE;
						}

						throw new InsertErrorException('i 1 Unable to insert element : ' . $this->_object_name);
					}
					$object_data[  $OL[ $i ]->primary_key ] = $id;
				}

				$query = '' ;
			}
		}

		$object_id = $object_data[$this->_object->primary_key];

		if($object_id == 0 and !$on_duplicate_key)
		{
			throw new InsertErrorException('Unable to insert element : ' . $this->_object_name);
		}

		//---Call Post Create
		$method = $this->_object_name . 'Method';
		if(class_exists($method))
		{
			$hInstance = new $method();
			$hInstance->postCreate($object_id,$object_id);
		}

		return true;
	}
	//-------------------------------------------------------------------------
	private function get_write_sql(
		$object_id			  ,
		array $object		   ,
		$ignore_duplicate=FALSE ,
		$with_where_clause=TRUE ,
		$with_set = TRUE		,
		$with_table_name=TRUE )
	{
		$OL = array() ;
		$this->_findExtendedObject( $this->_object_name, $OL ) ;

		//---On cree le tableau des objets
		$object_list = array();

		foreach( $this->_extended_object as $key=>$value)
		{
			if( $this->_extended_object->$key instanceof FieldDefinition )
			{
				$object_list[] = $this->_extended_object->$key->objectName;
			}
		}

		//---On dedoublonne
		$object_list = array_unique($object_list);

		//---On cree le bloc data pour chaque object
		$object_data = array();
		foreach( $object_list as $object_name)
		{
			$method	= $object_name . 'Method' ;
			if(class_exists($method))
			{
				$hInstance = new $method() ;
				$hInstance->preWrite( $object_id, $object, $object ) ;
			}

			foreach($object as $key=>$value)
			{
				if (isset($this->_extended_object->$key) && $this->_extended_object->$key instanceof FieldDefinition)
				{
					if ($this->_extended_object->$key->objectName===$object_name)
					{
						$object_data[$object_name][$key] = $value;
					}
				}
			}
		}

		//---Si plusieurs tables dans $object_data ---> on traite tout ce qui n'est pas object courant
		$extends_data		 = array();
		$current_object_data = array();

		if (count($OL)>1)
		{
			foreach($object_data as $key=>$value)
			{
				if ($key!=$this->_object_name)
				{
					foreach($value as $k=>$v)
					{
						$extends_data[$k] = $v;
					}
				}
				else
				{
					foreach($value as $k=>$v)
					{
						$current_object_data[$k] = $v;
					}
				}
			}

			//---On recherche les extends de l'object courant
			$extends_list = array();
			foreach($this->_object as $key=>$value)
			{
				if (  $this->_object->$key instanceof FieldDefinition )
				{
					if ($this->_object->$key->type_relation === 'extends')
					{
						$extends_list[$this->_object->$key->object_relation] = $key;
					}
				}
			}

			//---On fait les write des extends
			foreach(array_keys($extends_list) as $extends_object_name)
			{
				//--- !!! Recursivité !!!
				$hORM = self::getORMInstance($extends_object_name);
				$hORM->write($object_id,$extends_data);
			}
			
					}
		else
		{
			$current_object_data = $object;
		}

		if($with_table_name)
		{
			$table_reference = $this->_object_database.'.'.$this->_object_table;
		}
		else
		{
			$table_reference = '';
		}


		if( $ignore_duplicate === false )
		{
			$query = 'update '.$table_reference;
		}
		else
		{
			$query = 'update IGNORE '.$table_reference;
		}

		$query = 'update '.$table_reference;

		//---On deroule les champs
		foreach($current_object_data as $key=>$value)
		{
			if (!isset($this->_extended_object->$key))
			{
				continue;
			}
			// traitement du cryptage des mots de passe
			if($this->_extended_object->$key instanceof PasswordFieldDefinition && !empty($this->_extended_object->$key->crypt_method) && function_exists($this->_extended_object->$key->crypt_method))
			{// si c'est un champ de type mot de passe dont la propriété crypt_method correspond a une fonction qui existe
				if(empty($value))
				{// si il est vide on ne met rien a jour
					continue;
				}
				else
				{// sinon on crypt avec la méthode parametré via setCryptMethod(md5|sah1|...)
					$value=call_user_func($this->_extended_object->$key->crypt_method,$value);
				}
			}
			// ExtendedFieldDefinition
			if($this->_extended_object->$key instanceof ExtendedFieldDefinition)
			{
				 $this->_extended_object->$key->inCast($value);
			}
			else
			{
				//---On considere le type checkbox (boolean)
				if ($this->_extended_object->$key->type_relation==='checkbox')
				{
					$value = ($value == TRUE || $value == 1 || $value == '1') ? '1' : '0';
				}
			}

			//---Si PK on ignore
			if ($this->_extended_object->$key->type==='primary key')
			{
				continue;
			}

			//---Si fct ou virtuel on ignore
			if (!$this->_extended_object->$key->isDbColumn())
			{
				continue;
			}

			//---Gestion du NULL
			if(  $value  !== NULL )
			{
				$value = '"'.$this->_hDB->db_escape_string($value).'"' ;
			}
			else
			{
				$value = 'NULL' ;
			}

			if( !isset( $fields))
			{
				if($with_set)
				{
					$fields =' SET `'.$key.'`='.$value ;
					continue ;
				}
				else
				{
					$fields =' `'.$key.'`='.$value ;
					continue;
				}
			}

			$fields .= ', `'.$key.'`='.$value ;
		}

		if (!isset($fields))
		{
			return NULL;
		}

		$query .= $fields;

		if($with_where_clause)
		{
		//---Add condition
			$query.=' where `'.$this->_object_key_name.'`="'.$object_id.'"';
		}

		return $query;
	}

	function write( $object_id, array $object, $ignore_duplicate=FALSE, &$affected=NULL)
	{
		if(isset($this->_object->json))
		{
			// tmp
			if(isset($this->_object->json['enable_write']) && $this->_object->json['enable_write'] === TRUE)
			{
				$this->jsonProcessor('write', $result, $object, $object_id);

				$affected = $result['affected'];
			}

			return TRUE;
		}

		//---On soumet la requete
		$query = $this->get_write_sql($object_id, $object, $ignore_duplicate);
		if($query == NULL)
		{
			return TRUE;
		}

		$this->_hDB->db_execute($query, $affected);

		if ($affected>1)
		{
			throw new Exception( 'More than 1 records affected by update. Transaction canceled !!!' ) ;
		}
		
		$method	= $this->_object_name . 'Method' ;
		if(class_exists($method))
		{
			$hInstance = new $method() ;
			$hInstance->postWrite( $object_id, $affected );
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	/**
	 * Equivalent de l'instruction select du sql
	 * @param $objet_list: contenu(résultat) de la table
	 * @total: nombre de ligne retourné
	 * @param $fields: liste des champs de la table voulus
	 * @param $args: critère de séléction. C'est un tableau de tableau dont le 1er param du 2èmetableau est
	 * 				 est un champs (opérande)de la table, et le 2ème la condition(opérantion d'égalité et 
	 * 				la 3ème une operande
	 * @param  tri: équivalent au order by en sql
	 * @param limit: équivalent du limit 
	 */
	function browse(array &$object_list=NULL,
			&$total_record,
			array $fields=NULL,
			array $args=NULL,
			array $tri=NULL,
			$offset=0,
			$limit=NULL)
	{
		if($object_list == NULL)
		{
			$object_list = array();
		}
		$id_list = array();
		$this->search($id_list,$total_record,$args,$tri,$offset,$limit);

		//---Si pas de resultat ---> pas de read
		if (count($id_list)===0)
		{
			return TRUE;
		}
		else
		{
			//---Process NULL id
			$no_null_list = array();

			foreach ($id_list as $id => $value)
			{
				if ($value==NULL)
				{
					$object_list[$id] = NULL;
				}
				else
				{
					$no_null_list[$id] = $value;
				}
			}

			unset($id_list);
			$this->read($no_null_list,$object_list,$fields);

			return TRUE;
		}
	}
	//-------------------------------------------------------------------------
	function search(array &$object_id_list,&$total_record,array $args=NULL,array $order=NULL,$offset=0,$limit=NULL,array $extended_result=array())
	{
		if(isset($this->_object->json))
		{
			if(isset($this->_object->object_domain) && count($this->_object->object_domain) > 0)
			{
				if($args == NULL)
				{
					$args = array();
				}

				foreach($this->_object->object_domain AS $domain_rule)
				{
					if (count($domain_rule)>0)
					{
						$args[] = $domain_rule;
					}
				}

				if ($order==NULL && isset($this->_object->order))
				{
					$order = $this->_object->order;
				}
			}

			$this->jsonProcessor('search',$object_id_list,null,$args,$order,$offset,$limit,true);

			if ($this->_count_total===TRUE)
			{
				$this->jsonProcessor('count',$total_record,null,$args);
			}

			return true;
		}


		if($object_id_list == NULL)
		{
			$object_id_list = array();
		}

		//$hQB = new QueryBuilder($this->_object, $this->_count_total);

		//---Toujours Utiliser la version extended
		//$this->_object = self::getObjectInstance($this->_object_name);

		//---Order
		if (($order==NULL) && (isset($this->_object->order)))
		{
			$order = $this->_object->order;
		}

		//---Add domain rules to args
		if(isset($this->_object->object_domain) && count($this->_object->object_domain) > 0)
		{
			if($args == NULL)
			{
				$args = array();
			}

			foreach($this->_object->object_domain AS $domain_rule)
			{
				if (count($domain_rule)>0)
				{
					$args[] = $domain_rule;
				}
			}
		}

		//---On cree la jointure extends
		$object_list = array();
		$table_list  = array();
		$join_list   = array();

		$this->_findExtendedObject($this->_object_name, $object_list);

		foreach($object_list as $object)
		{
			$table = $object->database.'.'.$object->table;

			if(!in_array($table, $table_list))
			{
				$table_list[] = $table;
			}

			/**
			 * On parcours les champs à le recherche des workflow_status --> creation des jointures
			 */
			$count_workflow_status = 0;
			$workflow_status_where_list = array();

			if($args != NULL)
			{
				/* Parcours des champs. */
				foreach($object as $key=>$value)
				{
					if(!$object->$key instanceof FieldDefinition || is_array($object->$key->type))
					{
						continue;
					}

					/* Si le champ est related, on recherche s'il correspond à un workflow_status. */
					$obj = $object;
					if($obj->$key->type_relation === 'related')
					{
						$loc_attribute = $obj->$key->object_relation;
						$rel_attribute = $obj->$key->related_relation;

						if (empty($loc_attribute))
						{
							throw new Exception('Le champ '.$key.' est Related mais sans objet lié défini');
						}
						
						if (empty($rel_attribute))
						{
							throw new Exception('Le champ '.$key.' est Related mais sans champ lié défini');
						}
						
						if($obj->$loc_attribute->type_relation !== 'one2one' && $obj->$loc_attribute->type_relation !== 'many2one' && $obj->$loc_attribute->type_relation !== 'related' && $obj->$loc_attribute->type_relation !== 'extends')
						{
							throw new Exception('Attribut related utilisant un attribut non many2one ! (' . $obj->$loc_attribute->type . ')');
						}

						if($obj->$loc_attribute->type_relation === 'related')
						{
							continue;
						}

						$object_rel = $obj->$loc_attribute->object_relation;

						$hI = ORM::getObjectInstance($object_rel, FALSE); // Trouver une autre solution...
						if(!isset($hI->$rel_attribute) || !($hI->$rel_attribute instanceof FieldDefinition))
						{
							throw new Exception('L\'attribut "' . $rel_attribute . '" n\'existe pas dans l\'objet "' . $object_rel . '" appelé lors d\'un search depuis l\'objet "' . $this->_object_name . '" !');
						}

						if($hI->$rel_attribute->type_relation !== 'workflow_status')
						{
							continue;
						}

						$obj = $hI;
						$key = $rel_attribute;
					}

					if ($obj->$key->type_relation !== 'workflow_status')
					{
						continue;
					}

					/* Parcours des arguments de recherche. */
					foreach($args as $ka=>$arg)
					{
						if ($arg[0] != $key)
						{
							continue;
						}

						$hORM = self::getORMInstance('node');
						$node_list = array();

						$count_workflow_status++;

						$op = $arg[1];
						$node_id = NULL;
						if(is_array($arg[2]))
						{
							$first = reset($arg[2]);
							if(is_numeric($first))
							{
								$node_id = $first;
							}
						}
						else
						{
							if(is_numeric($arg[2]))
							{
								$node_id=$arg[2];
							}
						}

						if($node_id != NULL)
						{
							//---On recup le workflow node
							$hORM->read(array($node_id),$node_list,array('object'));
						}

						$keywt = $obj->$key->related_relation;
						$keyname = $obj->primary_key;
						if($keywt === NULL || $keywt == 'id')
						{
							if(is_array($this->_object_key_name))
							{
								throw new ObjectException('Le champ \''.$key.'\' de l\'objet \''.get_class($obj).'\' doit prendre 3 paramètres : workflow_status:workflow_name:key_name !');
							}
							$keywt = 'id';
							$keyname = $obj->primary_key;
						}

						if(isset($obj->$key->pk_relation) && $obj->$key->pk_relation !== NULL)
						{
							$keyname = $obj->$key->pk_relation;
						}

						$node_name = NULL;
						if(!is_array($arg[2]))
						{
							if(is_numeric($arg[2]))
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.workflow_node_id'.$op.$arg[2];
							}
							else
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.node_name'.$op.'\''.$arg[2].'\'';
								$node_name = $arg[2];
							}
						}
						else
						{
							$first = reset($arg[2]);
							if(is_numeric($first))
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.workflow_node_id '.$op.' ('.implode(', ',$arg[2]).')';
							}
							else
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.node_name '.$op.' (\''.implode('\', \'',$arg[2]).'\')';
								$node_name = $arg[2];
							}
						}

						//---Si object != ordre_travail
						// TODO: Support du filtrage sur d'autres types d'objets. */
						if ($node_name != NULL || ($node_id != NULL && $node_list[$node_id]['object']['value'] != 'ordretravail'))
						{
							$table_list[] = 'killi_workflow_token as kwt'.$count_workflow_status;
							$join_list[]  = $table.'.'.$keyname.'=kwt'.$count_workflow_status.'.'.$keywt;

							$table_list[] = 'killi_workflow_node as kwn'.$count_workflow_status;
							$join_list[]  = 'kwn'.$count_workflow_status.'.workflow_node_id=kwt'.$count_workflow_status.'.node_id';
						}
						else //--- Si ODT
						{
							$table_list[] = 'killi_ordre_travail as kodt'.$count_workflow_status;
							$join_list[]  = $table.'.'.$keyname.'=kodt'.$count_workflow_status.'.id';

							$table_list[] = 'killi_workflow_token as kwt'.$count_workflow_status;
							$join_list[]  = 'kwt'.$count_workflow_status.'.id=kodt'.$count_workflow_status.'.ordre_travail_id';

							$table_list[] = 'killi_workflow_node as kwn'.$count_workflow_status;
							$join_list[]  = 'kwn'.$count_workflow_status.'.workflow_node_id=kwt'.$count_workflow_status.'.node_id';
						}
						//array_splice($args,$ka,1);
						unset($args[$ka]);
					}
				}
			}

			/**
			 *  On parcours les champs à le recherche des extends/related/many2many/one2one --> creation des jointures
			 */
			$related_field_list = array();
			$one2one_field_list = array();
			$many2many_field_list = array();
			foreach($object as $key=>$value)
			{
				/* On vérifie que l'attribut est bien un field de l'objet. */
				if (!($object->$key instanceof FieldDefinition) || is_array($object->$key->type))
				{
					continue;
				}

				/* On retire les champs virtuels */
				if($object->$key->isVirtual())
				{
					continue;
				}

				/* On effectue la jointure de l'objet avec son parent via l'attribut en extends. */
				switch($object->$key->type_relation)
				{
					case 'extends':
						//---On instancie l'object de ref
						$hInstance = self::getObjectInstance($object->$key->object_relation);

						if(is_null($object->$key->related_relation))
						{
							$related_relation = $key;
						}
						else
						{
							$related_relation = $object->$key->related_relation;
						}

						if(is_null($object->$key->pk_relation))
						{
							$pk_relation = $key;
						}
						else
						{
							$pk_relation = $object->$key->pk_relation;
						}

						$join_list[] = $hInstance->table.'.'.$related_relation.'='.$object->table.'.'.$pk_relation;
						$table = $hInstance->database.'.'.$hInstance->table;

						// $hQB->addLeftJoin($hInstance->database, $hInstance->table, $hInstance->primary_key, $key);

						if(!in_array($table, $table_list))
						{
							$table_list[] = $table;
						}
						break;
					case 'related':
						$related_field_list[$key] = $key;
						break;
					case 'one2one':
						$one2one_field_list[$key] = $key;
						break;
					case 'many2many':
						$many2many_field_list[$key] = $key;
						break;
				}
			}

			if($args !== NULL)
			{
				foreach($related_field_list AS $key)
				{
					//---related
					foreach($args as $ka=>$arg)
					{
						if($arg[0] != $key)
						{
							continue;
						}

						/* On construit la jointure de related sur l'objet qui contient cet attribut. */
						$fieldOwner = self::getObjectInstance($object->$key->objectName);

						/* TODO: Remplacer cette construction par un do..while qui sera plus approprié. */
						/* Contient l'attribut cible de l'objet qui pointe en many2one. */
						$many2one_attribute = $object->$key->object_relation;

						// $hQB->join($many2one_attribute);
						/* On retire les champs calculés PHP. */
						if($fieldOwner->$many2one_attribute->isFunction())
						{
							continue;
						}

						/* On vérifie que l'attribut cible est bien de type many2one/one2one. */
						if($fieldOwner->$many2one_attribute->type_relation == 'many2one' ||
						   $fieldOwner->$many2one_attribute->type_relation == 'one2one')
						{
							$many2one_object = $fieldOwner->$many2one_attribute->object_relation;
							$object_attribute = $fieldOwner->$key->related_relation;

							/* On récupère l'objet qui pointe en many2one. */
							$hInstance = self::getObjectInstance($many2one_object);

							/**
							 * Si l'élément de jointure est un objet jsonBrowse, on ne peut pas créer de jointure.
							 */
							if(!isset($hInstance->database))
							{
								throw new Exception('Impossible de créer une jointure de l\'objet ' . $object->$key->objectName . ' avec l\'objet jsonBrowse : ' . $many2one_object . ' !');
							}

							/* On construit la jointure pour la récupération des champs. */
							$table = $hInstance->database.'.'.$hInstance->table;
							$alias = $table;
							if($fieldOwner->database.'.'.$fieldOwner->table == $table)
							{
								$alias = 'orm_' . $fieldOwner->$key->object_relation;
								$table = $table . ' ' . $alias;
							}

							$first = $alias.'.'.$hInstance->primary_key;
							if($fieldOwner->$many2one_attribute->type_relation == 'one2one')
							{
								if($fieldOwner->$many2one_attribute->related_relation != NULL)
								{
									$first = $alias.'.'.$fieldOwner->$many2one_attribute->related_relation;
								}
							}

							if($fieldOwner->$many2one_attribute->isSQLAlias())
							{
								$vars = array();
								$calc = $fieldOwner->$many2one_attribute->sql_alias;
								preg_match_all('(%[a-zA-Z0-9_]+%)', $calc, $vars);
								foreach($vars AS $var)
								{
									foreach($var AS $v)
									{
										$v2 = $fieldOwner->database.'.'.$fieldOwner->table.'.`'.substr($v, 1, -1).'`';
										$calc = str_replace($v, $v2, $calc);
									}
								}
								$second = $calc;
							}
							else
							{
								if($fieldOwner->$many2one_attribute->type_relation == 'one2one')
								{
									$rem_field = $fieldOwner->primary_key;
								}
								else
								{
									$rem_field = $fieldOwner->$key->object_relation;
								}
								$second = $fieldOwner->database.'.'.$fieldOwner->table.'.'.$rem_field;
							}
							$join = $first . '=' . $second;

							if($first == $second)
							{
								throw new JoinConflictException('Erreur de jointure sur le champ related \''. $key .'\' : ' . $join);
							}

							if(!in_array($join, $join_list))
							{
								$join_list[] = $join;
							}

							if(!in_array($table, $table_list))
							{
								$table_list[] = $table;
							}

							if($hInstance->$object_attribute->type_relation == 'many2many')
							{
								$rel_object = self::getM2MObject($hInstance->$object_attribute->object_relation, $hInstance->$key->objectName);

								if($rel_object === NULL)
								{
									throw new Exception('Impossible de determiner l\'objet de laison '.$object->$key->object_relation.'/'.$object->$key->objectName);
								}

								$hRel = self::getObjectInstance($rel_object);


								$table_hRel		= $hRel->database.'.'.$hRel->table;
								$first			= $hInstance->database.'.'.$hInstance->table.'.'.$hInstance->primary_key;
								$second			= $table_hRel.'.'.$hInstance->primary_key;
								$join_object	= $first . ' = ' . $second;

								if($first == $second)
								{
									throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_object);
								}

								$hInstance2 = self::getObjectInstance($hInstance->$key->object_relation);
								$table_hInstance	= $hInstance2->database.'.'.$hInstance2->table;
								$first				= $table_hInstance.'.'.$hInstance2->primary_key;
								$second				= $table_hRel.'.'.$hInstance2->primary_key;
								$join_hInstance		= $first . ' = ' . $second;

								if($first == $second)
								{
									throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_hInstance);
								}

								if(!in_array($join_object, $join_list))
								{
									$join_list[] = $join_object;
								}

								if(!in_array($join_hInstance, $join_list))
								{
									$join_list[] = $join_hInstance;
								}

								if(!in_array($table_hRel, $table_list))
								{
									$table_list[] = $table_hRel;
								}

								if(!in_array($table_hInstance, $table_list))
								{
									$table_list[] = $table_hInstance;
								}
							}

							/* Si l'attribut de destination est un related, on ajoute la jointure et on reboucle. */
							while(!empty($object_attribute)
								&& isset($hInstance->$object_attribute->type_relation)
								&& ($hInstance->$object_attribute->type_relation == 'related'))
							{
								$obj = $hInstance;
								$m2o_attr = $obj->$object_attribute->object_relation;
								$lnk_obj = $obj->$object_attribute->related_relation;

								if($obj->$m2o_attr->type_relation != 'many2one' && $obj->$m2o_attr->type_relation != 'one2one' && $obj->$m2o_attr->type_relation != 'related')
								{
									throw new Exception('Erreur de related avec le champ : \'' . $object_attribute . '\' dans l\'objet ' . get_class($obj) . '. Le champ \'' . $m2o_attr . '\' n\'est pas un champ many2one/one2one !');
								}

								if($obj->$m2o_attr->function !== NULL)
								{
									break; // Pas de jointure sur un champ calculé.
								}

								$m2o_object = $obj->$m2o_attr->object_relation;

								$hInstance = self::getObjectInstance($m2o_object);

								$table = $hInstance->database.'.'.$hInstance->table;

								$first = $table.'.'.$hInstance->primary_key;
								$second = $obj->database.'.'.$obj->table.'.'.$obj->$object_attribute->object_relation;
								if($obj->$m2o_attr->type_relation == 'one2one')
								{
									if($obj->$m2o_attr->related_relation != NULL)
									{
										$first = $table.'.'.$obj->$m2o_attr->related_relation;
										$second = $obj->database.'.'.$obj->table.'.'.$obj->primary_key;
									}
								}
								$join = $first . '=' . $second;
								if($first == $second)
								{
									throw new JoinConflictException('Erreur de jointure sur le champ related \''. $key .'\' : ' . $join);
								}

								if(!in_array($join, $join_list))
								{
									$join_list[] = $join;
								}

								if(!in_array($table, $table_list))
								{
									$table_list[] = $table;
								}
								$object_attribute = $lnk_obj;
							}
						}
					}
				}

				//---Many2many
				foreach($many2many_field_list AS $key)
				{
// 					if(!$object->$key->isDbColumn())
// 					{
// 						continue;
// 					}

					foreach($args as $ka=>$arg)
					{
						if($arg[0] != $key)
						{
							continue;
						}

						$rel_object = self::getM2MObject($object->$key->object_relation, $object->$key->objectName);

						if($rel_object === NULL)
						{
							throw new Exception('Impossible de determiner l\'objet de laison '.$object->$key->object_relation.'/'.$object->$key->objectName);
						}

						$hRel = self::getObjectInstance($rel_object);
						$hInstance = self::getObjectInstance($object->$key->object_relation);

						$table_hRel		= $hRel->database.'.'.$hRel->table;
						$first			= $object->database.'.'.$object->table.'.'.$object->primary_key;
						$second			= $table_hRel.'.'.$object->primary_key;
						$join_object	= $first . ' = ' . $second;

						if($first == $second)
						{
							throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_object);
						}

						$table_hInstance	= $hInstance->database.'.'.$hInstance->table;
						$first				= $table_hInstance.'.'.$hInstance->primary_key;
						$second				= $table_hRel.'.'.$hInstance->primary_key;
						$join_hInstance		= $first . ' = ' . $second;

						if($first == $second)
						{
							throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_hInstance);
						}

						if(!in_array($join_object, $join_list))
						{
							$join_list[] = $join_object;
						}

						if(!in_array($join_hInstance, $join_list))
						{
							$join_list[] = $join_hInstance;
						}

						if(!in_array($table_hRel, $table_list))
						{
							$table_list[] = $table_hRel;
						}

						if(!in_array($table_hInstance, $table_list))
						{
							$table_list[] = $table_hInstance;
						}
					}
				}
			}

			unset($related_field_list);
			unset($one2one_field_list);
			unset($many2many_field_list);

			$sql_computed_field = array();

			/**
			 *  Build order
			 */
			$order_by = ' ';
			if ($order != NULL)
			{
				/* Génération de l'order by. */
				$ordering = array();
				foreach($order as $o)
				{
					/* Définition explicite. */
					$raw = explode('.', $o);
					if(count($raw) > 1)
					{
						// $hQB->addOrderBy($o);
						$ordering[] = $o;
						continue;
					}

					/* Définition implicite. */
					$t = explode(' ', $o);
					$field = $t[0];
					$order = isset($t[1]) ? $t[1] : 'ASC';
					unset($t);

					if(!(isset($this->_object->$field) && $this->_object->$field instanceof FieldDefinition))
					{
						continue;
					}
					$table = $this->_object->table;

					if($this->_object->$field->isSQLAlias())
					{
						$calc = $this->_object->$field->sql_alias;
						$vars = array();
						preg_match_all('(%[a-zA-Z0-9_]+%)', $calc, $vars);
						foreach($vars AS $var)
						{
							foreach($var AS $v)
							{
								$v2 = $this->_object->database.'.'.$table.'.`'.substr($v, 1, -1).'`';
								$calc = str_replace($v, $v2, $calc);
							}
						}

						$sql_computed_field['computed_alias_'.$table.'_'.$field] = $calc;
						$ordering[] = 'computed_alias_'.$table.'_' . $field . ' ' . $order;
						$computed = true;
						continue;
					}

					if($this->_object->$field->type_relation == 'workflow_status')
					{
						$obj = ORM::getObjectInstance($this->_object->$field->objectName); // Gestion pour l'extends

						$table_list[] = 'killi_workflow_token AS kwto';
						$join_list[]  = $obj->table.'.'.$obj->primary_key.'=kwto.id';

						$table_list[] = 'killi_workflow_node AS kwno';
						$join_list[]  = 'kwno.workflow_node_id=kwto.node_id';
						$args[] = array('kwno.object', '=\''.strtolower($this->_object->$field->objectName).'\'');

						$ordering[] = 'kwto.date ' . $order;
						continue;
					}

					if($this->_object->$field->type_relation == 'related')
					{
						$relation_name = $this->_object->$field->object_relation;
						$hInstance = ORM::getObjectInstance($this->_object->$relation_name->object_relation);
						$table = $hInstance->table;
						$key = $hInstance->primary_key;
						// $hQB->join($relation_name);

						$fieldN = $this->_object->$field->related_relation;
						$hField = $hInstance->$fieldN;
						$o = $fieldN;
						$hInstance = ORM::getObjectInstance($hField->objectName);
						$table = $hInstance->table;

						$table_list[] = $hInstance->database . '.' . $table;
						$join_list[] = $table . '.' . $key . '=' . $this->_object->table . '.' . $relation_name;

						if($hField->isSQLAlias())
						{
							$calc = $hField->sql_alias;
							$vars = array();
							preg_match_all('(%[a-zA-Z0-9_]+%)', $calc, $vars);
							foreach($vars AS $var)
							{
								foreach($var AS $v)
								{
									$v2 = $hInstance->database.'.'.$table.'.`'.substr($v, 1, -1).'`';
									$calc = str_replace($v, $v2, $calc);
								}
							}

							$sql_computed_field['computed_alias_'.$table.'_'.$field] = $calc;
							$ordering[] = 'computed_alias_'.$table.'_' . $field . ' ' . $order;
							$computed = true;
							continue;
						}
					}

					// $hQB->addOrderBy($table, $field, $order);
					$ordering[] = $table . '.' . $o;
				}

				if(count($ordering) > 0)
				{
					$order_by = 'ORDER BY ' . join(', ', $ordering);
				}
			}
			else
			{
				/* Order by par défaut. */
				if(is_array($this->_object_key_name))
				{
					$order = array();
					foreach($this->_object_key_name AS $pk)
					{
						$order[] = $this->_object->table.'.'.$pk;
					}
					if(count($order) > 0)
					{
						$order_by = 'ORDER BY ' . join(',', $order);
					}
				}
				else
				{
					// $hQB->addOrderBy($this->_object->table, $this->_object_key_name, 'ASC');
					$order_by = 'ORDER BY '.$this->_object->table.'.'.$this->_object_key_name;
				}
			}

			/**
			 * Construction des jointures
			 */
			$table_list =  array_unique( $table_list ) ;

			//---If join domain is set
			if (isset($this->_object->domain_with_join))
			{
				foreach($this->_object->domain_with_join['table'] as $table)
				{
					$table_list[] = $table;
				}

				foreach($this->_object->domain_with_join['join'] as $join)
				{
					$join_list[] = $join;
				}

				foreach($this->_object->domain_with_join['filter'] as $filter)
				{
					$args[] = isset($filter[2]) ? array($filter[0], $filter[1], $filter[2]) : array($filter[0], $filter[1]);
				}
			}

			//---On dedoublonne les jointures
			for ($i=0;$i<count($table_list);$i++)
			{
				for ($j=$i+1; $j<count($table_list);$j++)
				{
					if ($table_list[$i]==$table_list[$j])
					{
						array_splice($table_list,$j,1);
						$merge_on = $join_list[$i-1].' and '.$join_list[$j-1];
						array_splice($join_list,$j-1,1);
						$join_list[$i-1] = $merge_on;
					}
				}
			}

			/**
			 *  Build where list
			 */
			$where = ' ';
			$having = ' ';
			if (($args!=NULL) || (count($workflow_status_where_list)>0))
			{
				$where = ' WHERE ';
				$having = ' HAVING ';
				foreach($args as $arg)
				{
					//---Need 3 key
					if (count($arg)<2)
					{
						throw new Exception('search conditions need 2 or 3 attributes !');
					}

					$computed	= false;
					$field		= $arg[0];
					$is_as		= false;
					$operator	= strtolower($arg[1]);
					$fullqual	= (strpos($field, '.') !== False);
					$field_object = NULL;
					if(!$fullqual)
					{
						$as_explode=explode(' as ',$field);
						if(count($as_explode) == 2)
						{
							$field = $as_explode[1];
							$is_as = true;
						}

						if(property_exists($this->_object, $field))
						{
							$field_object = $this->_object->$field;
							if($field_object->isFunction() || $field_object->isVirtual())
							{
								continue; // pas de where sur les champs calculés PHP ou Virtuel.
							}

							if($field_object->search_disabled === TRUE)
							{
								continue; // filtrage interdit
							}

							if(is_array($field_object->type))
							{
								$hInstance = self::getObjectInstance($this->_object->$field->objectName);
								$field = $hInstance->database.'.'.$hInstance->table.'.'.$field;
							}
							else
							if($field_object->isSQLAlias())
							{
								$calc = $field_object->sql_alias;

								$field_objectName = $field_object->objectName;
								$hInstance = ORM::getObjectInstance($field_objectName);
								$field_db = $hInstance->database;
								$field_table = $hInstance->table;

								$vars = array();
								preg_match_all('(%[a-zA-Z0-9_]+%)', $calc, $vars);
								foreach($vars AS $var)
								{
									foreach($var AS $v)
									{
										$v2 = $field_db.'.'.$field_table.'.`'.substr($v, 1, -1).'`';
										$calc = str_replace($v, $v2, $calc);
									}
								}
								$sql_computed_field[$field] = $calc;
								$computed = true;
							}
							else
							if($field_object->type_relation == 'related')
							{

								/* TODO: Remplacer cette construction par un do..while qui sera plus approprié. */
								/* Attribut many2one ou one2one de liaison */
								$attribute_relation = $field_object->object_relation;
								if($this->_object->$attribute_relation->isFunction() || $this->_object->$attribute_relation->isVirtual())
								{
									continue;
								}

								if($this->_object->$attribute_relation->type_relation == 'many2one')
								{
									/* On récupère l'attribut de l'objet que l'on veut récupérer. */
									$attr	 = $field_object->related_relation;

									/* On récupère l'objet sur lequel on fait le lien. */
									$hInstance = self::getObjectInstance($this->_object->$attribute_relation->object_relation);

									if($hInstance->$attr->isFunction() || $hInstance->$attr->isVirtual())
									{
										continue;
									}

									$is_SQLComputed = FALSE;
									if($hInstance->$attr->isSQLAlias())
									{
										$is_SQLComputed = TRUE;
									}

									$computedField = FALSE;

									/* Si l'attribut que l'on veut récupérer est un related, on remonte à son parent. */
									while($hInstance->$attr->type_relation == 'related')
									{
										/* Attribut many2one de liaison */
										$attribute_relation = $hInstance->$attr->object_relation;

										/* On récupère l'attribut de l'objet que l'on veut récupérer. */
										$attr = $hInstance->$attr->related_relation;

										/* On récupère l'objet sur lequel on fait le lien. */
										$hInstance = self::getObjectInstance($hInstance->$attribute_relation->object_relation);

										if($hInstance->$attr->isSQLAlias())
										{
											$is_SQLComputed = TRUE;
											break;
										}

										if($hInstance->$attr->isFunction() || $hInstance->$attr->isVirtual())
										{
											$computedField = TRUE;
											break;
										}
									}

									if($computedField)
									{
										continue;
									}

									if($is_SQLComputed)
									{
										$calc = $hInstance->$attr->sql_alias;

										$vars = array();
										preg_match_all('(%[a-zA-Z0-9_]+%)', $calc, $vars);
										foreach($vars AS $var)
										{
											foreach($var AS $v)
											{
												$v2 = $hInstance->database.'.'.$hInstance->table.'.`'.substr($v, 1, -1).'`';
												$calc = str_replace($v, $v2, $calc);
											}
										}
										$field = $calc;
									}
									else
									if($hInstance->$attr->type_relation == 'many2many')
									{
										$hInstance = self::getObjectInstance($hInstance->$attr->object_relation);
										$attr = $hInstance->primary_key;
										$field = $hInstance->database.'.'.$hInstance->table.'.'.$attr;
									}
									else
									{
										$field = $hInstance->database.'.'.$hInstance->table.'.'.$attr;
									}
								}
								else
								if($this->_object->$attribute_relation->type_relation == 'one2one')
								{
									$hInstance = self::getObjectInstance($this->_object->$attribute_relation->object_relation);
									$attr = $field_object->related_relation;
									$field = $hInstance->database.'.'.$hInstance->table.'.'.$attr;
								}
							}
							else
							if($field_object->type_relation=='many2many')
							{
								$hInstance = self::getObjectInstance($field_object->object_relation);
								$field = $hInstance->database.'.'.$hInstance->table.'.'.$hInstance->primary_key;
							}
							else
							{
								if($this->_object_name != $field_object->objectName)
								{
									$hInstance = self::getObjectInstance($this->_object->$field->objectName);
									if(!$hInstance->$field->isDbColumn())
									{
										continue;
									}
									$field = $hInstance->database.'.'.$hInstance->table.'.'.$field;
								}
								else
								{
									if($is_as)
									{
										$field = preg_replace('/%field%/', $this->_object->database.'.'.$this->_object->table.'.'.$field, $as_explode[0]);
									}
									else
									{
										$field = $this->_object->database.'.'.$this->_object->table.'.'.$field;
									}
								}
							}
						}
						else
						{
							Debug::log('la propriete \'' . $field . '\' n\'existe pas dans l\'objet de type ' . $this->_object_name);
						}
					}

					//---Si relation in
					if ($operator === 'in' || $operator === 'not in')
					{
						//---arg 2 doit etre un array
						if (!is_array($arg[2]))
						{
							throw new Exception('3 args of domain using IN relation must be array !');
						}

						//---Si array vide
						if (count($arg[2])==0)
						{
							$object_id_list = array();
							return TRUE;
						}

						$formated = '(';
						$pos=0;
						foreach($arg[2] as $value)
						{
							if(is_array($value))
							{
								$f = array();
								foreach($value AS $v)
								{
									$f[] = '\''.$this->_hDB->db_escape_string($v[$arg[0]]).'\'';
								}
								$formated .= join(',', $f);
							}
							else
							{
								$formated.='\''.$this->_hDB->db_escape_string($value).'\'';
							}

							if (($pos+1)<count($arg[2]))
							{
								$formated.=',';
							}

							$pos++;
						}

						$arg[2] = $formated.')';
					}
					else
					if ($operator === 'between')
					{
						if (count($arg) < 4)
						{
							throw new Exception('4 args are expected using BETWEEN condition');
						}

						// Timestamp cases
						for ($idx = 2 ; $idx <=3 ; $idx++)
						{
							if (is_numeric($arg[$idx]))
							{
								$arg[$idx] = date('Y-m-d H:i:s', $arg[$idx]);
							}
						}

						$arg[2] = '"'.$arg[2].'" AND "'.$arg[3].'"';
					}
					else
					if (isset($arg[2]))
					{
						// cast
						if($field_object instanceof ExtendedFieldDefinition)
						{
							$field_object->inCast($arg[2]);
						}

						$arg[2] = '\'' .$this->_hDB->db_escape_string($arg[2]) . '\'';
					}

					if($field_object)
					{
						// cast de la colonne en date
						if($field_object instanceof DateFieldDefinition || $field_object->type === 'date')
						{
							$field = 'DATE('.$field.')';
						}

						// cast de la colonne en date si recherche une date
						if($field_object instanceof DatetimeFieldDefinition && (!isset($arg[2]) || preg_match('/^\'[0-9]{4}-[0-9]{2}-[0-9]{2}\'/', $arg[2])))
						{
							$field = 'DATE('.$field.')';
						}

						// cast de la colonne en heure si recherche une heure
						if(($field_object instanceof TimeFieldDefinition || $field_object instanceof DatetimeFieldDefinition || $field_object->type === 'time') && (!isset($arg[2]) || preg_match('/^\'[0-9]{2}:[0-9]{2}:[0-9]{2}\'$/', $arg[2])))
						{
							$field = 'TIME('.$field.')';
						}
					}

					if($computed)
					{
						$having .= (isset($arg[2]))?'('.$field.' '.$arg[1].' '.$arg[2].') and ':'('.$field.' '.$arg[1].') and ';
					}
					else
					{
						//$hQB->addWhere($arg[0], $arg[1], $arg[2]);
						$where .= (isset($arg[2]))?'('.$field.' '.$arg[1].' '.$arg[2].') and ':'('.$field.' '.$arg[1].') and ';
					}
				} // End foreach..

				//---Process workflow_status
				foreach($workflow_status_where_list as $k=>$workflow_status_where)
				{
					$where .=   '('.$workflow_status_where.') and ';
				}

				//---Remove last and
				if($where==' WHERE ')
				{
					$where='';
				}
				else
				{
					$where = substr($where,0,-5);
				}

				if($having == ' HAVING ')
				{
					$having = '';
				}
				else
				{
					$having = substr($having,0,-5);
				}

			}

			/**
			 *  Construction de la requête.
			 */
			if (count($table_list)===1)
			{
				if (count($extended_result)==0)
				{
					$query = 'SELECT ';

					if($this->_count_total===TRUE)
					{
						$query .= 'SQL_CALC_FOUND_ROWS ';
					}

					if(is_array($this->_object_key_name))
					{
						$pks = array();
						foreach($this->_object_key_name AS $k)
						{
							$pks[] = $this->_object_table.'.'.$k;
						}

						$query .= 'DISTINCT '.join(',', $pks);
					}
					else
					{
						$query .= 'DISTINCT('.$this->_object_key_name.')';
					}
				}
				else
				{
					$as_extended_result = array();

					foreach($extended_result as $k=>$v)
					{
						$as_extended_result[$k] = $v.' AS k'.$k;
					}

					$query = 'SELECT '.join(',',$as_extended_result).','.$this->_object_key_name;
				}
				foreach($sql_computed_field AS $field => $field_calc)
				{
					$query .= ', ' . $field_calc . ' AS ' . $field;
				}
				$query .= ' FROM '.$this->_object_database.'.'.$this->_object_table.' '.$where.' '.$having.' '.$order_by;
			}
			else
			{
				$extends_from	= $table_list[0];
				$nt				= count($table_list);

				for( $ti = 1; $ti < $nt; $ti ++)
				{
					$extends_from .= ' LEFT JOIN '.$table_list[$ti].' ON ('.$join_list[$ti-1].') ';
				}
				$extends_from .= $where.' '.$having;

				if (count($extended_result)==0)
				{
					$query = 'SELECT ';

					if($this->_count_total===TRUE)
					{
						$query .= 'SQL_CALC_FOUND_ROWS ';
					}
					if(is_array($this->_object_key_name))
					{
						$pks = array();
						foreach($this->_object_key_name AS $k)
						{
							$pks[] = $this->_object_table.'.'.$k;
						}

						$query .= 'DISTINCT '.join(',', $pks);
					}
					else
					{
						$query .= 'DISTINCT('.$this->_object_table.'.'.$this->_object_key_name.')';
					}
				}
				else
				{
					$as_extended_result = array();
					foreach($extended_result as $k=>$v)
					{
						$as_extended_result[$k] = $v.' AS k'.$k;
					}

					$query = 'SELECT '.join(',',$as_extended_result).','.$this->_object_table.'.'.$this->_object_key_name;
				}
				foreach($sql_computed_field AS $field => $field_calc)
				{
					$query .= ', ' . $field_calc . ' AS ' . $field;
				}
				$query .= ' FROM '.$extends_from.' '.$order_by;
			}

			/**
			 *  Process limit
			 */
			if ($limit!=NULL)
			{
				//$hQB->setOffset($offset*$limit)->setLimit($limit);
				$query.=' LIMIT '.$offset*$limit.','.$limit;
			}

			/**
			 *  Exécution de la requête.
			 */
			//$q2 = $hQB->build();
			//echo 'Query Builder :', PHP_EOL, $q2, PHP_EOL;
			//echo $query, PHP_EOL;
			//Debug::printAtEnd($query);
			//---Process query
			$this->_hDB->db_select($query, $result, $numrows);

			if($this->_count_total === TRUE)
			{
				$query = 'SELECT FOUND_ROWS() AS total_record;';
				$this->_hDB->db_select($query, $counter, $num);
				$row = $counter->fetch_assoc();
				$total_record = $row['total_record'];
				$counter->free();
			}
			else
			{
				$total_record = NULL;
			}

			/**
			 *  Récupération du résultat.
			 */
			while ($row = $result->fetch_assoc())
			{
				if (count($extended_result)==0)
				{
					if(is_array($this->_object_key_name))
					{
						$r = array();
						foreach($this->_object_key_name AS $k)
						{
							if(!isset($row[$k]))
							{
								$r[] = NULL;
							}
							else
							{
								$r[] = $row[$k];
							}
						}
						$object_id_list[] = join(',', $r);
					}
					else
					{
						$object_id_list[] = $row[$this->_object_key_name];
					}
				}
				else
				{
					$resultat							= array();
					$resultat[$this->_object_key_name]	= $row[$this->_object_key_name];

					foreach($extended_result as $k=>$v)
					{
						$resultat[$v] =  $row['k'.$k];
					}

					$object_id_list[] = $resultat;
				}
			}

			$result->free();

			return TRUE;
		}
	}
	//-------------------------------------------------------------------------
	function count(&$total_record, $args=NULL )
	{
		if(isset($this->_object->json))
		{
			return $this->jsonProcessor('count',$total_record,null,$args);
		}

		$old_count = $this->_count_total;
		$this->_count_total = true;

		$object_list = array();
		$state = $this->search($object_list ,$total_record, $args, null, 0, 1);

		$this->_count_total = $old_count;

		return $state;
	}
	//-------------------------------------------------------------------------
	function unlink($object_id)
	{
		if(isset($this->_object->json))
		{
			// tmp
			if(isset($this->_object->json['enable_unlink']) && $this->_object->json['enable_unlink'] === TRUE)
			{
				$this->jsonProcessor('unlink', $foo, $object_id);
			}

			return TRUE;
		}

		$OL = array();
		$this->_findExtendedObject( $this->_object_name, $OL);

		$nbOL = count($OL);
		$hInstance_list = array();
		for($i = 0; $i < $nbOL; $i ++)
		{
			//---Call Pre-unlink
			$hInstance = null;
			$method = get_class( $OL[ $i ] ).'Method';
			if(class_exists($method))
			{
				$hInstance = new $method();

				if ($hInstance->preUnlink($object_id,$object_id)==FALSE)
				{
					throw new CantDeleteException('Cannot preUnlink object '.get_class($OL[$i]). ' with object_id '.$object_id);
				}
			}
			$hInstance_list[$i] = $hInstance;
		}

		//---On supprime les token lies a cet objet sauf si c'est un ordre de travail
		if(strtolower($this->_object_name) != 'ordretravail')
		{
			$query = sprintf(
					'DELETE FROM '.RIGHTS_DATABASE.'.killi_workflow_token
					USING '.RIGHTS_DATABASE.'.killi_workflow_token,'.RIGHTS_DATABASE.'.killi_workflow_node
					WHERE (killi_workflow_token.node_id=killi_workflow_node.workflow_node_id)
					AND (killi_workflow_node.object="%s")
					AND (killi_workflow_token.id="%s")', strtolower($this->_object_name), $object_id);

			$this->_hDB->db_execute($query, $rows);
		}
		for($i = 0; $i < $nbOL; $i ++)
		{
			$rows = 0;

			$query = sprintf('DELETE FROM `%s`.`%s`  WHERE  `%s`= "%s";'
					, $OL[$i]->database
					, $OL[$i]->table
					, $OL[$i]->primary_key
					, $object_id);
			$tmp = $this->_hDB->db_execute($query, $rows);

			if($rows == 0 && $tmp === TRUE)
			{
				throw new CantDeleteException('Cannot delete object '.get_class($OL[$i]). ' with object_id '.$object_id. '(row not found)' );
			}

			if(isset($hInstance_list[$i]) && $hInstance_list[$i]->postUnlink($object_id)==FALSE)
			{
				throw new CantDeleteException('Cannot postUnlink object '.get_class($OL[$i]). ' with object_id '.$object_id);
			}
		}
		return TRUE;
	}
	//-------------------------------------------------------------------------
	function __get($key)
	{
		return $this->$key;
	}
	//-------------------------------------------------------------------------
	function generateSQLcreateObject($is_temporary = FALSE)
	{
		$temporary = '';
		if($is_temporary)
		{
			$temporary = 'TEMPORARY';
		}
		$query  = 'CREATE ' . $temporary . ' TABLE IF NOT EXISTS ' . $this->_object->database . '.' . $this->_object->table . ' ( ' . PHP_EOL;
		$query .= "\t" . '`' . $this->_object->primary_key . '` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,' . PHP_EOL;

		foreach($this->_object AS $fieldname=>$field)
		{

			if(!($this->_object->$fieldname instanceof FieldDefinition))
			{
				continue;
			}

			if($field->type == 'primary key')
			{
				continue;
			}

			if($field->objectName != get_class($this->_object))
			{
				continue;
			}

			if(!$field->isDbColumn())
			{
				continue;
			}

			if((!isset($field->type_relation) || $field->type_relation != 'extends'))
			{
				$default_value = $field->default_value;
				$skip = false;
				switch($field->type)
				{
					case 'text':
						$length = empty($field->max_length) ? 255 : $field->max_length;
						$fieldtype = 'varchar('.$length.') CHARACTER SET utf8 COLLATE utf8_general_ci';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT \''.$default_value.'\'';
						break;
					case 'csscolor':
						$fieldtype = 'varchar(7) CHARACTER SET utf8 COLLATE utf8_general_ci';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT \''.$default_value.'\'';
						break;
					case 'int':
						$fieldtype = 'INT(10)';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'time':
						$fieldtype = 'time';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'date':
						$fieldtype = 'date';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'datetime':
						$fieldtype = 'datetime';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'checkbox':
						$fieldtype = 'tinyint(1) unsigned';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . ($default_value === FALSE ? '0' : '1') . '\'');
						break;
					case 'textarea' :
					case 'serialized':
					case 'json':
						$fieldtype = 'LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL';
						break;
					default:
						switch($field->type_relation)
						{
							case 'many2many':
								$fieldtype = 'INT(10) UNSIGNED';
								$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT NULL';
								break;
							case 'many2one':
								$fieldtype = 'INT(10) UNSIGNED';
								$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT NULL';
								break;
							default:
								$skip = true;
						}
				}
				if(!$skip)
				{
					$query .=  "\t" . '`' . $fieldname . '` ' . $fieldtype . ',' . PHP_EOL;
				}
			}
		}

		$query .= 'PRIMARY KEY (`' . $this->_object->primary_key . '`)' . PHP_EOL;
		$query .= ') ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
		return $query;
	}
	//-------------------------------------------------------------------------
	function createObjectInDatabase()
	{
		if(!isset($this->_hDB))
		{
			throw new Exception('Database instance not found !');
		}

		$this->_hDB->db_rollback();

		$query = $this->generateSQLcreateObject($this->_object_database != TESTS_DATABASE);

		$this->_hDB->db_execute($query, $rows);
	}
	//-------------------------------------------------------------------------
	function deleteObjectInDatabase()
	{
		if(!isset($this->_hDB))
		{
			throw new Exception('Database instance not found !');
		}

		$this->_hDB->db_rollback();
		$this->_hDB->db_execute(
			'DROP TABLE '.TESTS_DATABASE.'.' . $this->_object->table . ';',
			$rows
		);
	}

	/*
	 *
	* DEBUG TRACE && DEV TOOLS
	*
	*
	*/
	private static function start_counter()
	{
		self::$_start_time = microtime(TRUE);
	}

	private static function stop_counter()
	{
		self::$_cumulate_process_time += (microtime(TRUE) - self::$_start_time);
	}

	// @codeCoverageIgnoreStart
	static function dump_bt($bts)
	{
		$return='';
		foreach($bts as $bt)
		{
			$return.=$bt.'<br/>';
		}
		return $return;
	}
	//---------------------------------------------------------------------
	static function trace_no_fields()
	{
		if(count(self::$no_fields)==0)
		{
			return true;
		}

		$table="<table class='table_list' style='table-layout: fixed'><tr><th style='text-align:left'>Backtrace</th></tr>";

		foreach(self::$no_fields as $i=>$field)
		{
			$table.="<tr".($i%2==0?' style="background-color:#eee;"':'')."><td style='text-align:left;word-wrap:break-word'>".self::dump_bt($field).'</td></tr>';
		}

		echo "<h3 style='margin:5px'>".count(self::$no_fields)." accès à l'ORM sans paramètre field spécifiés !</h3>".$table."</table>";
	}
	//---------------------------------------------------------------------
	static function traceObjectLoader()
	{
		$loaded = 0;
		$total = 0;
		$managed = 0;
		$loaded_str = '';
		foreach(self::$_objects AS $name => $object)
		{
			if($object['class'] !== NULL)
			{
				$loaded_str .= $name . '<br>';
				$loaded++;
			}

			if($object['rights'] == TRUE)
			{
				$managed++;
			}
			$total++;
		}
		echo '<h3>Chargement des objets dans l\'ORM :</h3>';
		echo 'Total : ', $total, ', Chargés : ', $loaded, ', Objets avec gestion des droits : ', $managed, '<br>';//, $loaded_str, '<br>';
	}

	// @codeCoverageIgnoreEnd

	/**
	 * Retourne un tableau de champs mis à jour avec la liste des dépendances.
	 *
	 * Récursif.
	 */
	protected function _addDependencies($fields)
	{
		if (!empty($fields))
		{
			$head = array();
			$news = array();
			$tail = array();
			foreach ($fields as $of)
			{
				if (property_exists($this->_object, $of) AND property_exists($this->_object->$of, 'dependencies') AND !empty($this->_object->$of->dependencies))
				{
					$news = array_merge($news, $this->_object->$of->dependencies);
					$tail[] = $of;
				}
				else
				{
					$head[] = $of;
				}
			}
			$news = array_diff($news, $head);
			$fields = array_unique(array_merge($head, $this->_addDependencies($news), $tail));
		}
		return $fields;
	}
}
