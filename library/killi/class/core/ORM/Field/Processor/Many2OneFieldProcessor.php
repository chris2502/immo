<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs Many2One.
 *
 *  @package killi
 *  @class Many2OneFieldProcessor
 *  @Revision $Revision: 4558 $
 */
use \FieldDefinition;
use \Debug;

use \Killi\Core\ORM\Exception\ObjectDefinitionException;
use \Killi\Core\ORM\Exception\MismatchObjectException;

class Many2OneFieldProcessor extends AbstractFieldProcessor
{
	use \Killi\Core\ORM\Debug\Performance;

	protected $_many2one_list = array();

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

		if($field->type == 'many2one')
		{
			$this->_many2one_list[$field->attribute_name] = $field;
		}

		return TRUE;
	}

	public function read(&$object_list)
	{
		/* On prend le premier élément de la liste. */
		$l1 = reset($object_list);

		//---Process many2one one by one
		foreach($this->_many2one_list as $many2one => $field)
		{
			//---Si on a dejà une reference.... on ne la calcule pas
			// Idem si le champs à l'attribut no_reference à TRUE.
			if(!isset($l1[$many2one]))
			{
				throw new ObjectDefinitionException('Le champ ' . $many2one . ' n\'existe pas dans ' . $this->_orm->getObjectName() . ' (sans doute non présent dans la table de cet objet) !');
			}

			/* Référence déjà calculée. */
			if(isset($l1[$many2one]['reference']))
			{
				continue;
			}

			if(!empty($many2one->no_reference))
			{
				continue;
			}

			$m2o_id_list	 = array();
			$hMethodInstance = ORM::getControllerInstance($field->object_relation);
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
					throw new MismatchObjectException("Impossible de trouver le tuple ".$object_id." dans les objets ".$this->_orm->getObjectName(). ' pour le champ ' . $many2one);
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
						Debug::Log('impossible de trouver '.$many2one);
					}

				}
			}

			unset($hMethodInstance);
		}
		unset($l1);
		$this->outcast($this->_many2one_list, $object_list);
		return TRUE;
	}
}
