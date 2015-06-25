<?php

/**
 *  @class ConditionRenderFieldDefinition
 *  @Revision $Revision: 4347 $
 *
 */

class ConditionRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderFilter($name, $selected_value)
	{
		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		if($value === NULL)
		{
			?><div></div><?php

			return TRUE;
		}

		$var_list	= $this->node->getNodeAttribute('var_list', '');
		$output			= $this->node->getNodeAttribute('output', '');

		$variables_list = empty($this->node->_data_list[$var_list]) ? array() : $this->node->_data_list[$var_list];
		$output_list = explode(',', $output);

		foreach($output_list AS $id => &$o)
		{
			$o = trim($o);
			if(empty($o))
			{
				unset($output_list[$id]);
			}
		}

		$value = empty($value['value']) ? '' : htmlentities(json_encode($value['value']));

		?><div <?= $this->node->css_class() ?> <?= $this->node->style()?>><?php
		?><input id="<?= $this->node->id ?>_input" type="hidden" name="<?= $input_name ?>" value="<?= $value ?>"/><?php
		?></div><?php

		?>
		<script>
		$(document).ready(function() {
			var variables_list = ['Test', 'Truc', 'Bidule'];
			$('#<?= $this->node->id ?>_input').moduleConditionEditor(
				{
					editable: false,
					output: <?= json_encode($output_list) ?>,
					variable: <?= json_encode($variables_list) ?>
				}
			);
		});
		</script><?php
		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$var_list	= $this->node->getNodeAttribute('var_list', '');
		$output			= $this->node->getNodeAttribute('output', '');

		$output_list	= explode(',', $output);
		$variables_list = empty($this->node->_data_list[$var_list]) ? array() : $this->node->_data_list[$var_list];

		foreach($output_list AS $id => &$o)
		{
			$o = trim($o);
			if(empty($o))
			{
				unset($output_list[$id]);
			}
		}

		$value = empty($value['value']) ? '' : htmlentities(json_encode($value['value']));

		?><div <?= $this->node->css_class() ?> <?= $this->node->style()?>><?php
		?><input id="<?= $this->node->id ?>_input" type="hidden" name="<?= $input_name ?>" value="<?= $value ?>"/><?php
		?></div><?php

		?>
		<script>
		$(document).ready(function() {
			var variables_list = ['Test', 'Truc', 'Bidule'];
			$('#<?= $this->node->id ?>_input').moduleConditionEditor(
				{
					output: <?= json_encode($output_list) ?>,
					variable: <?= json_encode($variables_list) ?>
				}
			);
		});
		</script><?php
		return TRUE;
	}
}
