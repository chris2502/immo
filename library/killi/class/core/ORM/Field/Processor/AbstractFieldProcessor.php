<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des types de champs spécifiques.
 *
 *  @package killi
 *  @class AbstractFieldProcessor
 *  @Revision $Revision: 4474 $
 */
use Killi\Core\ORM\ORMService;

use \FieldDefinition;

abstract class AbstractFieldProcessor
{
	protected $_orm = NULL;

	public function __construct(ORMService $orm)
	{
		$this->_orm = $orm;
	}

	protected function outcast($fields_object_list, &$object_list)
	{
		foreach($fields_object_list as $field_name => $field_object)
		{
			foreach($object_list as $object_id=>$object)
			{
				if(!isset($object_list[$object_id][$field_name]))
				{
					continue;
				}

				$casted_value = $object_list[$object_id][$field_name]['value'];

				$field_object->outCast($casted_value);

				// on ajoute/remplace les valeurs de l'outCast dans le tableau du field
				foreach($casted_value as $casted_value_name=>$casted_value_content)
				{
					$object_list[$object_id][$field_name][$casted_value_name] = $casted_value_content;
				}

				Rights::getRightsByAttribute ( $field_object->objectName, $field_name, $read, $write );

				$object_list[$object_id][$field_name]['editable'] = ((isset($object_list[$object_id][$field_name]['editable']) ? $object_list[$object_id][$field_name]['editable'] : TRUE) && $write);
			}
		}
		return TRUE;
	}

	public abstract function select(FieldDefinition $field);

	public abstract function read(&$object_list);
}
