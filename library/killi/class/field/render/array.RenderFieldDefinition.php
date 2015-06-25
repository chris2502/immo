<?php

/**
 *  @class ArrayRenderFieldDefinition
 *  @Revision $Revision: 3847 $
 *
 */
class ArrayRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		$display_keys = $this->node->getNodeAttribute('display_keys', '1');

		if (empty($value['value']))
		{
			?><div></div><?php
			
			return TRUE;
		}
		
		?><ul class="arrayfield arrayfield-value" style="list-style:none;"><?php
		
		foreach ($value['value'] as $key => $value)
		{
			?><li><?php
			if ($display_keys)
			{
				echo $key . ' : ';
			}
			echo $value;
			?></li><?php
		}
		?></ul><?php
		return TRUE;
	}
}
