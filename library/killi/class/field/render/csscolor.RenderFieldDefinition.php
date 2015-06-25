<?php

/**
 *  @class CsscolorRenderFieldDefinition
 *  @Revision $Revision: 3847 $
 *
 */

class CsscolorRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		?><div <?= self::_attributesToClass() ?><?= $this->node->style(array('background-color'=>$value['value'])) ?>><?= $value['value']; ?></div><?php

		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$this->node->_getClass($this->field, $class);

		$events = '';
		foreach($field_attributes AS $event => $foncs)
		{
			$events .= $event . '="';
			foreach($foncs AS $key => $fonc)
			{
				$events .= $fonc . '(this);';
			}
			$events .= '"';
		}

		?><div <?= $this->node->css_class() ?> <?= $this->node->style()?>><?php
			?><input colorpicker type="text" name="<?= $input_name; ?>" id="<?php echo $this->node->id; ?>" <?php echo $class; ?> <?= $events; ?> value="<?php echo htmlentities($value['value'], ENT_COMPAT, 'UTF-8'); ?>" /><?php
		?></div><?php

		return TRUE;
	}
}
