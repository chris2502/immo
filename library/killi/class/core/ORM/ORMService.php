<?php

namespace Killi\Core\ORM;

/**
 *  Classe de l'ORM
 *
 *  @package killi
 *  @class ORMService
 *  @Revision $Revision: 4594 $
 */

use \FieldDefinition;

use Killi\Core\ORM\Exception\ObjectDefinitionException;
use Killi\Core\ORM\Exception\RequestException;

class ORMService implements Handler\HandlerInterface
{
	use ObjectManager;		// Module de gestion des objets
	use ControllerManager;	// Module de gestion des controleurs
	use Debug\Performance;
	use Debug\Trace;

	/**
	 * @field Instances de l'ORM en mémoire pour la durée de la requête.
	 */
	private static $_instances		= array();

	private $_object			= NULL;
	private $_handler			= NULL;

	//---------------------------------------------------------------------
	/**
	* Obtenir une instance de l'objet ORM
	*
	* @param $object_name Nom de l'objet en lowercase
	* @param $count_total Effectuer un décompte du résultat des requêtes
	* @return ORM
	*/
	public static function getORMInstance($object_name=NULL, $count_total=FALSE, $with_domain = TRUE)
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
		$this->_object_name = get_class($object);

		self::init();

		// Récupération d'une instance de l'objet (avec ou sans domaine).
		$this->_object = self::getObjectInstance($this->_object_name, $with_domain);

		/**
		 * Stockage de la clé primaire dans la classe courante.
		 */
		$primary_key = $this->_object->primary_key;
		$raw = explode(',', $primary_key);
		if(count($raw) == 1)
		{
			$this->_object_key_name	= $primary_key;
		}
		else
		{
			$this->_object_key_name = $raw;
		}

		/**
		 * Initialisation du traitant.
		 */
		if(isset($this->_object->json) && is_array($this->_object->json))
		{
			$this->_handler = new Handler\NJBHandler($this);
		}
		else
		if(isset($this->_object->collection) && is_string($this->_object->collection))
		{
			$this->_handler = new Handler\MongoHandler($this);
		}
		else
		{
			$this->_handler = new Handler\MySQLHandler($this);
		}

		$this->_handler->setObject($this->_object);
		$this->_handler->setWithDomain($with_domain);
		$this->_handler->setCountTotal($count_total);

		$this->_handler->boot();
	}

	//---------------------------------------------------------------------
	public function getObjectName()
	{
		return $this->_object_name;
	}
	//---------------------------------------------------------------------
	/**
	 * Intialise l'ORM en créant le connecteur SQL si besoin
	 */
	public static function init()
	{
		global $hDB; //---On la recup depuis l'index ;-)

		if(!isset($hDB) || !($hDB instanceof \DbLayer))
		{
			global $dbconfig;
			$hDB = new \DbLayer($dbconfig);
			$hDB->db_start();
		}
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
		$hInstance = self::getObjectInstance($this->_object_name);
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
				if(isset($this->_object->$of) && isset($this->_object->$of->dependencies) && !empty($this->_object->$of->dependencies))
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

	//-------------------------------------------------------------------------
	public function readOne($id, &$object, array $original_fields=NULL)
	{
		$object_list = array();
		$this->read(array($id), $object_list, $original_fields);
		$object = $object_list[$id];
		unset($object_list);
		return TRUE;
	}

	//-------------------------------------------------------------------------
	public function readAndReturn($u_original_id_list, array $original_fields=NULL)
	{
		$object_list = array();
		$this->read($u_original_id_list, $object_list, $original_fields);
		return $object_list;
	}

	//-------------------------------------------------------------------------
	function __get($key)
	{
		return $this->$key;
	}

	/**
	 *  Méthode qui permet de "nettoyer" une liste d'id et de générer le tableau
	 *  d'objet initial en conservant l'ordre des id.
	 */
	protected function instanciateObjectList(&$id_list)
	{
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
			return NULL;
		}

		if($number_of_ids > 10000)
		{
			//new NonBlockingException('Non mais ALLO QUOI ! un read sur '.$number_of_ids.' id ?');
		}

		$object_list = array();
		//---Création du tableau de résultat de base afin de maintenir le tri.
		foreach($id_list AS $id)
		{
			$object_list[$id] = array();
		}

		return $object_list;
	}

	protected function processRequiredFields(&$fields)
	{
		$field_list = array();

		// Remapping
		if($fields !== NULL)
		{
			$fs = array();
			foreach($fields AS $f)
			{
				$fs[$f] = $f;
			}
			$field_list = $fs;
			unset($fs);
		}

		/* On place le (ou les) champs primary_key dans la liste des champs à récupérer. */
		$primary_keys = explode(',', $this->_object->primary_key);
		foreach($primary_keys AS $pk)
		{
			$pk = trim($pk);
			$field_list[$pk] = $pk;
		}

		/* Ajout de tout les champs de l'objet. */
		if($fields == NULL)
		{
			foreach($this->_object AS $field_name => $field_object)
			{
				if($field_object instanceof FieldDefinition)
				{
					$field_list[$field_name] = $field_name;
				}
			}
		}

		/* Vérification des champs et ajout des dépendances. */
		foreach($field_list AS $field_name)
		{
			/* Vérification de l'existance de l'attribut. */
			if(!isset($this->_object->$field_name))
			{
				//throw new RequestException('L\'attribut ' . $field_name . ' demandé n\'appartient pas à l\'objet ' .$this->_object_name. ' !');

				// Retrait des champs demandées mais non présents.
				unset($field_list[$field_name]);
				continue;
			}

			$field_object = $this->_object->$field_name;

			/* Vérification du type de l'attribut. */
			if(!($field_object instanceof FieldDefinition))
			{
				throw new ObjectDefinitionException('L\'attribut ' . $field_name . ' n\'est pas de type FieldDefinition dans l\'objet '. $this->_object_name .' !');
			}

			/* Retrait des attributs one2many non demandé explicitement */
			if($field_object->type == 'one2many' && $fields !== NULL && !in_array($field_name, $fields))
			{
				unset($field_list[$field_name]);
			}

			/* Retrait des attributs many2many non demandé explicitement */
			if($field_object->type == 'many2many' && $fields !== NULL && !in_array($field_name, $fields))
			{
				unset($field_list[$field_name]);
			}
		}
		$fields = $field_list;

		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 */
	function read($original_id_list, &$object_list, array $original_fields=NULL)
	{
		self::start_counter(); // Performance Start

		$method = $this->_object_name.'Method';

		/**
		 * Vérification des appels de read avec la liste des fields à Null.
		 */
		if(DISPLAY_ERRORS)
		{
			self::check_trace_no_fields($original_fields);
		}

		/* On transforme le paramètre id_list en tableau s'il correspond à une seule entrée. */
		$return_single_record = FALSE;
		if(!is_array($original_id_list))
		{
			$return_single_record = TRUE;
			$original_id_list = array($original_id_list);
		}

		/* Gestion des dépendances. */
		$original_fields = $this->_addDependencies($original_fields);

		/**
		 *  Hook preRead
		 */
		$id_list = array();
		$fields = array();
		if(class_exists($method))
		{
			/* Exécution du Hook */
			$hInstance = new $method();
			self::stop_counter();
			$hInstance->preRead($original_id_list, $id_list, $original_fields, $fields);
			self::start_counter();
			unset($hInstance);
			$original_fields = $fields;
		}
		else
		{
			$id_list = $original_id_list;
			$fields  = $original_fields;
		}
		unset($original_id_list);

		/* Génération du tableau d'objet initial */
		$object_list = $this->instanciateObjectList($id_list);

		/* Pas d'id à récupérer, on quitte. */
		if($object_list === NULL)
		{
			$object_list = array();
			return TRUE;
		}

		/* On détermine les champs demandés et nécessaires pour la requête */
		$this->processRequiredFields($fields);

		/**
		 * Initialisation des processeurs de champs.
		 */
		$field_processor_list = array();

		// Champs calculés PHP
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\ExtendsFieldProcessor($this);
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\RelatedFieldProcessor($this);
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\Many2ManyFieldProcessor($this);
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\One2ManyFieldProcessor($this);
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\One2OneFieldProcessor($this);
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\Many2OneFieldProcessor($this);
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\WorkflowStatusFieldProcessor($this);
		$field_processor_list[] = new \Killi\Core\ORM\Field\Processor\ComputedFieldProcessor($this);

		foreach($fields AS $field_name)
		{
			$field_object = $this->_object->$field_name;
			foreach($field_processor_list AS $fp)
			{
				$fp->select($field_object);
			}
		}

		/**
		 *  Appel du read.
		 */
		$this->_handler->read($id_list, $object_list, $fields);

		/* Si la base est incohérente, on passe les fonctions de récupérations des m2m, o2m, fonctions et status de workflow. */
		if(!(TOLERATE_MISMATCH && empty($object_list)))
		{
			foreach($field_processor_list AS $fp)
			{
				$fp->read($object_list);
			}
		}

		/**
		 *  Génération d'un champ virtuel pour le stockage de la valeur de clés primaires multiple.
		 */
		if(is_array($this->_object_key_name))
		{
			foreach($object_list AS $id => &$data)
			{
				$data[$this->_object->primary_key]['value'] = $id;
			}
		}

		/**
		 *  Call Post-read
		 */
		if (class_exists($method))
		{
			$hInstance = new $method();
			self::stop_counter();
			$hInstance->postRead($object_list, $object_list);
			self::start_counter();
			unset($hInstance);
		}
		unset($method);

		if( isset( $object_list ) === TRUE && is_array( $object_list ) === TRUE )
		{
			reset( $object_list ) ;
		}

		if ($return_single_record)
		{
			$object_list = reset($object_list);
		}

		self::stop_counter(); // Performance Stop

		return TRUE;
	}
	//-------------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	function create( array $object_data, &$object_id=null, $ignore_duplicate=false, $on_duplicate_key = FALSE )
	{
		$this->_handler->create($object_data, $object_id, $ignore_duplicate, $on_duplicate_key);
		return TRUE;
	}
	//-------------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	function write( $object_id, array $object, $ignore_duplicate=FALSE, &$affected=NULL)
	{
		$this->_handler->write($object_id, $object, $ignore_duplicate, $affected);
		return TRUE;
	}
	//-------------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	function browse(array &$object_list = NULL, &$total_record = 0, array $fields=NULL, array $args=NULL, array $tri=NULL, $offset=0, $limit=NULL)
	{
		$this->_handler->browse($object_list, $total_record, $fields, $args, $tri, $offset, $limit);
		return TRUE;
	}
	//-------------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	function search(array &$object_id_list = NULL,&$total_record = 0,array $args=NULL,array $order=NULL,$offset=0,$limit=NULL,array $extended_result=array())
	{
		$this->_handler->search($object_id_list, $total_record, $args, $order, $offset, $limit, $extended_result);
		return TRUE;
	}
	//-------------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	function count(&$total_record, $args=NULL)
	{
		$this->_handler->count($total_record, $args);
		return TRUE;
	}
	//-------------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	function unlink($object_id)
	{
		$this->_handler->unlink($object_id);
		return TRUE;
	}
	//-------------------------------------------------------------------------

	function generateSQLcreateObject($is_temporary = FALSE)
	{
		if(method_exists($this->_handler, 'generateSQLcreateObject'))
		{
			$this->_handler->generateSQLcreateObject($is_temporary);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * {@inheritDoc}
	 */
	function createObjectInDatabase()
	{
		if(method_exists($this->_handler, 'createObjectInDatabase'))
		{
			$this->_handler->createObjectInDatabase();
			return TRUE;
		}
		return FALSE;
	}
	//-------------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	function deleteObjectInDatabase()
	{
		if(method_exists($this->_handler, 'deleteObjectInDatabase'))
		{
			$this->_handler->deleteObjectInDatabase();
			return TRUE;
		}
		return FALSE;
	}
	//-------------------------------------------------------------------------
}
