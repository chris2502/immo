<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs One2Many.
 *
 *  @package killi
 *  @class One2ManyFieldProcessor
 *  @Revision $Revision: 4558 $
 */
use \FieldDefinition;
use \Killi\Core\ORM\Exception\ObjectDefinitionException;

class One2ManyFieldProcessor extends AbstractFieldProcessor
{
	protected $_one2many_list = array();

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

		/* Champs calculé PHP */
		if($field->isFunction())
		{
			return TRUE;
		}

		if($field->type == 'one2many')
		{
			$this->_one2many_list[$field->attribute_name] = $field;
		}
		return TRUE;
	}

	public function read(&$object_list)
	{
		$id_list = array_keys($object_list);

		//---On recup les one2many
		foreach($this->_one2many_list AS $field_name => $field)
		{
			/* Pas de récupération sur des objets distants (c'est le site distant qui s'en charge). */
			if(isset($this->_orm->_object->json))
			{
				continue;
			}

			/**
			 * Définition de la valeur par défaut.
			 */
			foreach($object_list as $object_id=>$object)
			{
				$object_list[$object_id][$field_name]['value']	= array();
			}

			if(!isset($field->related_relation))
			{
				throw new ObjectDefinitionException('one2many need 2 arguments in class '.$this->_object_name);
			}

			$o2m_id_list		= array();
			$objectInstance		= ORM::getObjectInstance($field->object_relation);
			$var1_table_object_key = $objectInstance->table.'.'.$field->related_relation;

			$ids = $id_list;
			if(is_array($this->_orm->_object_key_name))
			{
				/* Cas particulier des clés primaires multiples. */
				$ids = array();
				foreach($object_list AS $object)
				{
					$id = $object[$field->related_relation]['value'];
					$ids[$id] = $id;
				}
			}

			$hORM = ORM::getORMInstance($field->object_relation);
			$hORM->search(	$o2m_id_list,
							$num,
							array(
								array($field->related_relation, 'in', $ids)
							),
							NULL,
							0,
							50000,
							array($var1_table_object_key));

			if ($o2m_id_list!=NULL)
			{
				foreach($o2m_id_list as $o2m)
				{
					$object_list[$o2m[$var1_table_object_key]][$field_name]['value'][]  = $o2m[$objectInstance->primary_key];
				}
			}
			unset($o2m_id_list);
			unset($var1_table_object_key);
		}

		$this->outcast($this->_one2many_list, $object_list);
		return TRUE;
	}
}
