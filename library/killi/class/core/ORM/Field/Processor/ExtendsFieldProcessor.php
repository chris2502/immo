<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs étendus.
 *
 *  @package killi
 *  @class ExtendsFieldProcessor
 *  @Revision $Revision: 4671 $
 */
use \FieldDefinition;

class ExtendsFieldProcessor extends AbstractFieldProcessor
{
	protected $_extends_list = array();
	protected $_extended_attributes = array();

	public function select(FieldDefinition $field)
	{
		/* Les champs virtuels ne sont pas traités. */
		if($field->isVirtual())
		{
			return TRUE;
		}

		if(!isset($field->attribute_name))
		{
			throw new Exception('L\'attribute_name du champs ('.$field->objectName.'->'.$field->name.') n\'a pas été défini en amont dans le FieldDefinition. Normalement ce nom est défini lors de l\'instanciation de l\'objet. ' . print_r($field, true));
		}

		/* Champs récupéré sur l'objet parent. */
		if($field->objectName != $this->_orm->getObjectName())
		{
			$this->_extended_attributes[$field->attribute_name] = $field;
			return TRUE;
		}

		/* Alias SQL */
		if($field->isSQLAlias())
		{
			return TRUE;
		}

		if($field->type == 'extends')
		{
			$this->_extends_list[$field->attribute_name] = $field;
		}
		else
		if($field->type == 'related')
		{
			$join_attr = $field->object_relation;

			/* L'attribut de jointure se trouve sur le parent. */
			if($this->_orm->_object->$join_attr->objectName !== $this->_orm->getObjectName())
			{
				$this->_extended_attributes[$field->attribute_name] = $field;
			}
		}

		return TRUE;
	}

	public function read(&$object_list)
	{
		foreach($this->_extends_list AS $field_name => $field)
		{
			$idToRead		 = array() ;
			$extends_data	 = array() ;

			$hORM			= ORM::getORMInstance( $field->object_relation );
			$hInstance		= ORM::getObjectInstance( $field->object_relation );

			$parent_primary_key = empty($field->related_relation) ? $field_name : $field->related_relation;

			foreach( $object_list as $k2=>$v2 )
			{
				if(array_key_exists($parent_primary_key, $object_list[$k2]))
				{
					$idToRead[$object_list[ $k2 ][ $parent_primary_key ]['value']][] = $k2;
				}
				else
				if(!TOLERATE_MISMATCH)
				{
					$message =  'Erreur de coherence de la db ! ';
					$message .= 'L\'attribut ' . $parent_primary_key. ' n\'a pas été trouvé dans l\'index ' . $k2 . ' du tableau ' . print_r($object_list[$k2], true);
					throw new Exception($message);
				}
			}

			$f = array();
			foreach($this->_extended_attributes AS $attr_name => $attr)
			{
				if(isset($hInstance->$attr_name))
				{
					$f[] = $attr_name;
					foreach(array_keys($object_list) AS $id)
					{
						$object_list[$id][$attr_name]['value'] = NULL;
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
							if (($k3!=$this->_orm->_object_key_name) && (isset($object_list[ $ckey ][ $k3 ]['value'])))
							{
								continue;
								//throw new Exception( 'Try to duplicate attribut: ' . $k3  );
							}

							if(!array_key_exists('value', $v2[$k3]))
							{
								throw new Exception('La valeur du champ \'' . $k3 . '\' est manquante.');
							}

							$object_list[$ckey][$k3] = $v2[$k3] ;
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

		$this->outcast($this->_extends_list, $object_list);
		$this->outcast($this->_extended_attributes, $object_list);

	}
}
