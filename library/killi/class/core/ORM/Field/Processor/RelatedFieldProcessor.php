<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs related.
 *
 *  @package killi
 *  @class RelatedFieldProcessor
 *  @Revision $Revision: 4558 $
 */
use \FieldDefinition;

use \Killi\Core\ORM\Exception\InternalErrorException;

class RelatedFieldProcessor extends AbstractFieldProcessor
{
	use \Killi\Core\ORM\Debug\Performance;

	protected $_related_list = array();
	private static $func_call_stack = array();

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

		if($field->type == 'related')
		{
			$join_attr = $field->object_relation;
			$this->_related_list[$field->object_relation][$field->attribute_name] = $field;
		}
		return TRUE;
	}

	protected function read_process_functions(array $field_list, array &$object_list)
	{
		//---Processing function fields
		foreach($field_list as $field)
		{
			if(is_string($this->_orm->_object->$field->function))
			{
				$raw = explode('::',$this->_orm->_object->$field->function);

				if(!isset($raw[1]))
				{
					throw new Exception('Impossible de determiner le nom de la methode dans la chaine ' . $this->_orm->_object->$field->function);
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

	public function read(&$object_list)
	{
		//---On traite les related
		foreach($this->_related_list AS $join_field => $fields)
		{
			/* On vérifie si le champs doit être récupéré et on effectue le mapping. */
			$mapping = array();
			foreach($fields AS $field_name => $field_object)
			{
				$mapping[$field_name] = $field_object->related_relation;

				foreach(array_keys($object_list) AS $id)
				{
					$object_list[$id][$field_name]['value'] = NULL;
				}
			}

			if(count($mapping) == 0)
			{
				continue;
			}

			/* Vérification du champ de jointure dans l'objet. */
			if(!property_exists($this->_orm->_object, $join_field))
			{
				throw new Exception('L\'attribut '.$join_field.' n\'existe pas dans la classe '.$this->_orm->getObjectName());
			}

			/* Vérification du type du champ de jointure. */
			$join_field_obj = $this->_orm->_object->$join_field;
			if(!($join_field_obj instanceof FieldDefinition))
			{
				throw new Exception('L\'attribut '.$join_field.' n\'est pas un FieldDefinition dans la classe ' . $this->_orm->getObjectName());
			}

			/* Si le champ de jointure est un related, on va chercher le parent. */
			if($join_field_obj->type === 'related')
			{
				throw new InternalErrorException('Le related via un related est en cours de réalisation...');
			}

			/* Un relation related ne peut être récupérée que par le biais d'un attribut de type many2one ou one2one. */
			if ($join_field_obj->type !== 'many2one' && $join_field_obj->type !== 'one2one')
			{
				throw new Exception('Related type only applicable on many2one or one2one relation !');
			}

			/* Vérification pour savoir si le champ de jointure est calculé. */
			if($join_field_obj->objectName == $this->_orm->getObjectName() && $join_field_obj->isFunction())
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
				$this->read_process_functions(array($join_field), $object_list);
				self::$func_call_stack[$join_field_obj->function]--;
			}

			/* Objet sur lequel est effectué la jointure. */
			$related_object	= $join_field_obj->object_relation;
			$hInstance		= ORM::getORMInstance($related_object);
			$related_object_list = array();

			/**
			 * Related via one2one.
			 */
			if($join_field_obj->type === 'one2one')
			{
				if(empty($join_field_obj->related_relation))
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
				$hInstance->browse($reldata_list, $total, array($rem_field), array(array($rem_field, 'in', array_keys($object_list))));
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
					throw new InternalErrorException('L\'attribut de jointure \'' . $join_field . '\' n\'est pas présent dans ' . $this->_orm->getObjectName() . ' !');
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

			$this->outcast($fields, $object_list);
		}

		return TRUE;
	}
}
