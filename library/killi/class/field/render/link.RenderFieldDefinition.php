<?php

/**
 *  @class LinkRenderFieldDefinition
 *  @Revision $Revision: 3847 $
 *
 */
class LinkRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		$display_keys = $this->node->getNodeAttribute('display_keys', '1');

		if (empty($value['value']))
		{
			?><div></div><?php
			
			return TRUE;
		}

		?><a href="<?= $value['value'] ?>">Lien</a><?php

		return TRUE;
	}
}
