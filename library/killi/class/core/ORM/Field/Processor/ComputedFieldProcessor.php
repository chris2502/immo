<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs calculés PHP.
 *
 *  @package killi
 *  @class ComputedFieldProcessor
 *  @Revision $Revision: 4558 $
 */
use \FieldDefinition;

class ComputedFieldProcessor extends AbstractFieldProcessor
{
	use \Killi\Core\ORM\Debug\Performance;

	protected $_computed_field_list = array();

	public function select(FieldDefinition $field)
	{
		/* Les champs virtuels ne sont pas traités. */
		if($field->isVirtual())
		{
			return TRUE;
		}

		/* Champs récupéré sur l'objet parent. */
		if($field->objectName != $this->_orm->getObjectName())
		{
			return TRUE;
		}

		/* Alias SQL */
		if($field->isSQLAlias())
		{
			return TRUE;
		}

		/* Champs calculés PHP */
		if($field->isFunction())
		{
			$this->_computed_field_list[$field->function][$field->attribute_name] = $field;
			return TRUE;
		}

		/* Cas des related sur champs calculés. */
		if($field->type == 'related')
		{
			$join_attr = $field->object_relation;
			$join_attr_object = $this->_orm->_object->$join_attr;
			if($join_attr_object->isFunction())
			{
				$this->_computed_field_list[$this->_orm->_object->$join_attr->function][$join_attr] = $join_attr_object;
			}
		}
		return TRUE;
	}

	public function read(&$object_list)
	{
		//---Processing function fields
		foreach($this->_computed_field_list as $function => $field_list)
		{
			if(!is_string($function))
			{
				continue;
			}

			$raw = explode('::',$function);

			if(!isset($raw[1]))
			{
				throw new Exception('Impossible de determiner le nom de la methode dans la chaine ' . $function);
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

			/**
			 * Exécution du outCast
			 */
			$this->outcast($field_list, $object_list);
		}
		return TRUE;
	}
}
