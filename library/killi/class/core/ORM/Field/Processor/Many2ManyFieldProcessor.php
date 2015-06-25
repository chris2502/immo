<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs Many2Many.
 *
 *  @package killi
 *  @class Many2ManyFieldProcessor
 *  @Revision $Revision: 4558 $
 */
use \FieldDefinition;

class Many2ManyFieldProcessor extends AbstractFieldProcessor
{
	protected $_many2many_list = array();

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
		
		/* Calculé par fonction */
		if($field->isFunction())
		{
			return TRUE;
		}

		if($field->type == 'many2many')
		{
			$this->_many2many_list[ $field->attribute_name ] = $field;
		}
		return TRUE;
	}

	public function read(&$object_list)
	{
		global $hDB;
		foreach($this->_many2many_list as $field_name => $field)
		{
			$hInstance		= ORM::getObjectInstance($field->object_relation);
			$instance_pk	= $hInstance->primary_key;
			$object_pk		= $this->_orm->_object->primary_key;

			$rel_object = ORM::getM2MObject($field->object_relation, $field->objectName);

			if($rel_object === NULL)
			{
				throw new Exception('Impossible de determiner l\'objet de laison '.$field->object_relation.'/'. $field->objectName);
			}

			$hRel = ORM::getObjectInstance($rel_object);

			if (isset($field->related_relation))
			{
				$instance_pk = $field->related_relation;
			}

			if (isset($field->pk_relation))
			{
				$object_pk = $field->pk_relation;
			}

			foreach(array_keys($object_list) as $k1)
			{
				// On ne réinitialise pas la valeur si la donnée à déjà été récupérée en JsonBrowse
				if (!isset($object_list[$k1][$field_name]))
				{
					$object_list[$k1][$field_name]['value'] = array();
				}

				if(!is_array($object_list[$k1][$field_name]['value']))
				{
					$object_list[$k1][$field_name]['value'] = array();
				}
			}

			if (count($object_list)>0 && isset($this->_orm->_object->table))
			{
				$id_list = array_keys($object_list);

				$hRelField = empty($field->related_relation) ? $this->_orm->_object->primary_key : $field->related_relation;

				$query  = 'SELECT '.$this->_orm->_object->table.'.'.$this->_orm->_object->primary_key.' as pkey, '.$hRel->table.'.'.$hInstance->primary_key.' as rel_id'
						. ' FROM '.$this->_orm->_object->database.'.'.$this->_orm->_object->table
						. ' INNER JOIN '.$hRel->table .' ON '.$this->_orm->_object->table.'.'.$this->_orm->_object->primary_key.'='.$hRel->table.'.'.$hRelField
						. ' WHERE '.$this->_orm->_object->table.'.'.$this->_orm->_object->primary_key.' IN ('.join(',',$id_list).')'
						. ' LIMIT 50000';

				$hDB->db_select($query,$rresult,$rnumrows);

				for($i = 0; $i < $rnumrows; $i++)
				{
					$row = $rresult->fetch_assoc();
					$object_list[$row['pkey']][$field_name]['value'][] = $row['rel_id'];
				}
				$rresult->free();
			}
		}
		$this->outcast($this->_many2many_list, $object_list);
		return TRUE;
	}
}
