<?php

/**
 *  @class SerializedRenderFieldDefinition
 *  @Revision $Revision: 3847 $
 *
 */

class SerializedRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		if($value['value'] === NULL)
		{
			?><div></div><?php

			return TRUE;
		}

		?><div id="<?= $this->node->id ?>" <?= $this->node->css_class() ?> <?= $this->node->style() ?>><pre><?php

		var_export($value['value']);

		?></pre></div><?php
	}
}
