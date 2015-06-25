<?php

namespace Killi\Core\ORM;

/**
 *  Classe de chargement et d'initialisation des objets
 *
 *  @package killi
 *  @class ObjectManager
 *  @Revision $Revision: 4671 $
 */

use \Exception;
use \FieldDefinition;

use \UndeclaredObjectException; // Utilisation de l'exception globale plutôt que celle défini dans le namespace en attendant la migration complète.
use \TextFieldDefinition;

trait ObjectManager
{
	public static $_objects			= array();
	protected static $_dynamic_fields = array();

	//---------------------------------------------------------------------
	/**
	* Déclare un objet dans l'ORM.
	*
	* @param string $class_name Nom de la classe de l'objet
	* @param boolean $rights Activer la gestion des droits sur l'objet
	*/
	public static function declareObject($class_name=NULL, $rights = true, $path = '')
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
		if($object_name === NULL)
		{
			throw new Exception('Impossible de récupérer une instance d\'un nom d\'objet vide !');
		}
		$lower = strtolower($object_name);

		if(!isset(self::$_objects[$lower]))
		{
			throw new UndeclaredObjectException('L\'objet \'' . $object_name . '\' n\'est pas déclaré dans l\'ORM !');
		}

		/* Si l'objet est instancié, on le retourne directement. */
		if(self::$_objects[$lower]['class'] !== NULL && $with_domain)
		{
			return self::$_objects[$lower]['class'];
		}

		Debug::log('Instanciate new object '.$lower);

		$className = self::$_objects[$lower]['className'];

		if(!class_exists($className))
		{
			throw new UndeclaredObjectException('La classe ' . $className . ' n\'existe pas : ' . print_r(self::$_objects[$lower] , true));
		}

		/* Création de l'object et définition des autorisations de base. */
		$obj = new $className();
		$class_name = get_class($obj);

		self::setDynamicObjectField($obj);

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

		/**
		 * Primary Key.
		 */
		if(isset($obj->primary_key))
		{
			$raw = explode(',', $obj->primary_key);
			if(count($raw) > 1)
			{
				$obj->{$obj->primary_key} = PrimaryFieldDefinition::create()->setVirtual(TRUE);
			}
		}
		else
		{
			throw new Exception("Object primary key is not defined in ".$object_name);
		}

		if(!empty($_GET['action']))
		{
			$raw = explode('.', $_GET['action']);
			if($raw[0] == $object_name && $raw[1] == 'historic')
			{
				$obj->object_domain[]  = array($obj->primary_key, '=', $_GET['primary_key']);
				$obj->table		 	.= '_log';
				$obj->primary_key	= 'log_id';
				$obj->log_id			= PrimaryFieldDefinition::create();
				$obj->log_id->attribute_name = 'log_id';
			}
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

		/* Parcours de l'objet pour extension. */
		foreach($obj AS $value)
		{
			//---On ne conserve que les attributs du mapping
			if ($value instanceof FieldDefinition && $value->type === 'extends')
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

		/* Définition des domaines : Important que ce soit réalisé en dernier dans l'instanciation d'un objet afin d'éviter des problèmes de dépendances circulaires. */
		if (method_exists($obj, 'setDomain') && $with_domain)
		{
			$obj->setDomain();
		}

		if($with_domain)
		{
			return self::$_objects[$lower]['class'];
		}

		return $obj;
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
			$object_b => self::getObjectInstance($object_a),
			$object_a => self::getObjectInstance($object_b)
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
			$h = self::getObjectInstance($object_a.$object_b);
		}
		catch (Exception $e)
		{
			try
			{
				$h = self::getObjectInstance($object_b.$object_a);
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
					$h = self::getObjectInstance($object_b.$_object_name);
				}
				catch (Exception $e)
				{
					try
					{
						$h = self::getObjectInstance($_object_name.$object_b);
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
							$h = self::getObjectInstance($object_a.$_object_name);
						}
						catch (Exception $e)
						{
							try
							{
								$h = self::getObjectInstance($_object_name.$object_a);
							}
							catch (Exception $e)
							{
								$found = false;
								do
								{
									/* On skip les classes par défaut. */
									if($object_b == 'stdClass')
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

								$h = self::getObjectInstance($rel_object);
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
	* Méthode utilisée par l'index pour affecter les attributs de workflow à un objet.
	*
	* @param string $object_name
	*/
	public static function setWorkflowAttributes($object_name)
	{
		$object_name = strtolower($object_name);

		self::$_dynamic_fields[$object_name]['qualification_id'] = TextFieldDefinition::create()
				->setLabel('Qualification')
				->setFunction($object_name, 'setQualification')
				->setEditable(FALSE);

		self::$_dynamic_fields[$object_name]['commentaire'] = TextFieldDefinition::create()
				->setLabel('Commentaire')
				->setFunction($object_name, 'setCommentaire')
				->setEditable(FALSE);
	}

	/**
	 * Méthode utilisée par le common pour la génération de l'historique.
	 *
	 * @param string $object_name
	 */
	public static function setHistoric($object_name)
	{
		$object_name = strtolower($object_name);

		self::$_dynamic_fields[$object_name]['log_id'] = PrimaryFieldDefinition::create()
				->setLabel('Log ID');
	}

	protected static function setDynamicObjectField(&$objectInstance)
	{
		$object_name = strtolower(get_class($objectInstance));

		if(!isset(self::$_dynamic_fields[$object_name]))
		{
			return TRUE;
		}

		foreach(self::$_dynamic_fields[$object_name] AS $field_name => $field)
		{
			$objectInstance->$field_name = clone $field;
		}
		return TRUE;
	}
}
