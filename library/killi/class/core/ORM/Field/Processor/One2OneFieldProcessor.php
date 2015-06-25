<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs One2One.
 *
 *  @package killi
 *  @class One2OneFieldProcessor
 *  @Revision $Revision: 4558 $
 */
use \FieldDefinition;

class One2OneFieldProcessor extends AbstractFieldProcessor
{
	protected $_one2one_list = array();

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

		if($field->type == 'one2one')
		{
			$this->_one2one_list[$field->attribute_name] = $field;
		}
		return TRUE;
	}

	public function read(&$object_list)
	{
		/* On prend le premier élément de la liste. */
		$l1 = reset($object_list);

		//---Process one2one one by one
		foreach($this->_one2one_list AS $one2one => $join_field_obj)
		{
			/* Déja défini par un related. */
			if(isset($l1[$one2one]['value']))
			{
				continue;
			}

			$hORM = ORM::getORMInstance($join_field_obj->object_relation);
			if(empty($join_field_obj->related_relation))
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
			$hORM->browse($reldata_list, $total, array($rem_field), array(array($rem_field, 'in', array_keys($object_list))));
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
		$this->outcast($this->_one2one_list, $object_list);
		return TRUE;
	}
}
