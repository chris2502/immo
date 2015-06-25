<?php

/**
 *  @class One2oneRenderFieldDefinition
 *  @Revision $Revision: 3915 $
 *
 */

class One2oneRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		if($value['value'] == NULL)
		{
			?><div></div><?php

			return TRUE;
		}

		$object_relation = strtolower($this->field->object_relation);

		Rights::getCreateDeleteViewStatus($object_relation, $create, $delete, $view);

		if($view)
		{
			Security::crypt($value['value'], $crypted_value);

			?><a href="./index.php?action=<?= $object_relation ?>.edit&token=<?= $_SESSION['_TOKEN'] ?>&view=form&crypt/primary_key=<?= $crypted_value ?>"><?php
		}

		/* Récupération de la référence si non calculée. */
		if(!array_key_exists('reference', $value))
		{
			$reference_list = array();
			ORM::getControllerInstance($object_relation)->getReferenceString(array($value['value']), $reference_list);
			$value['reference'] = $reference_list[$value['value']];
		}

		echo htmlentities($value['reference'], ENT_QUOTES, 'UTF-8');

		if($view)
		{
			?></a><?php
		}
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$object_relation = strtolower($this->field->object_relation);

		Rights::getCreateDeleteViewStatus($object_relation, $create, $delete, $view);

		if($value['value'] === NULL)
		{
			if(!$create)
			{
				?><div></div><?php

				return TRUE;
			}

			Security::crypt($this->_current_data[ORM::getObjectInstance($this->field->objectName)->primary_key]['value'], $encrypted_pk);

			?><button id="bouton_create" style='width:75px' onclick="window.open('./index.php?action=<?= $object_relation ?>.edit&view=create&token=<?= $_SESSION['_TOKEN'] ?>&crypt/<?= ORM::getObjectInstance($object_relation)->primary_key; ?>=<?= $encrypted_pk; ?>','popup_<?= rand(1000000,9999999) ?>','height=600, width=800, toolbar=no, scrollbars=yes'); return false;"><div style="background-image: url('./library/killi/images/new.gif');background-repeat: no-repeat;background-position: 2px center;">Créer</div></button><?php
		}

		?><div <?= $this->node->css_class() ?> <?= $this->node->style()?>><?php

		/* Récupération de la référence si non calculée. */
		if(!array_key_exists('reference', $value))
		{
			$reference_list = array();
			ORM::getControllerInstance($object_relation)->getReferenceString(array($value['value']), $reference_list);
			$value['reference'] = $reference_list[$value['value']];
		}

		if($view)
		{
			Security::crypt($value['value'], $crypted_value);

			?><a href="./index.php?action=<?= $object_relation ?>.edit&token=<?= $_SESSION['_TOKEN'] ?>&view=form&crypt/primary_key=<?= $crypted_value ?>"><?php
		}

		echo htmlentities($value['reference'], ENT_QUOTES, 'UTF-8');

		if($view)
		{
			?></a><?php
		}

		?></div><?php
	}
}
