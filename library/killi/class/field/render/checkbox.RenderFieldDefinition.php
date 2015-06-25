<?php

/**
 *  @class CheckboxRenderFieldDefinition
 *  @Revision $Revision: 4082 $
 *
 */

class CheckboxRenderFieldDefinition extends BoolRenderFieldDefinition
{
	public function renderInput($value, $input_name, $field_attributes)
	{
		$selector = $this->node->getNodeAttribute('selector', '0');
		if(empty($selector))
		{
			parent::renderInput($value, $input_name, $field_attributes);
			return TRUE;
		}
		?><input type="checkbox" name="<?= $input_name ?>"/><?php
		return TRUE;
	}
}
