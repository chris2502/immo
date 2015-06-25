<?php

/**
 *  @class ImageRenderFieldDefinition
 *  @Revision $Revision: 4557 $
 *
 */

class ImageRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		$filename	= $value['value'];
		$width		= $this->node->getNodeAttribute('width', NULL);
		$preview	= $this->node->getNodeAttribute('nopreview', '0') == '0';
		if ($preview==TRUE)
		{
			?>
			<a href="<?= $filename ?>" class="preview" title="<?= $filename ?>"><img border="0" <?= $width ?> src="<?= $filename ?>"/></a>
			<?php
		}
		else
		{
			?>
			<img border="0" <?= $width ?> src="<?= $filename ?>"/>
			<?php
		}
	}
}
