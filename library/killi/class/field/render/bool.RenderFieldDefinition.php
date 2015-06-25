<?php

/**
 *  @class CheckboxRenderFieldDefinition
 *  @Revision $Revision: 3378 $
 *
 */

class BoolRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderFilter($name, $selected_value)
	{
		?><input type='hidden' name="<?= $name.'/op' ?>" value='=' id="op_bool_<?= $this->node->id ?>" /><?php
		?><select id="search_<?= $this->node->id ?>" class="search_input" name="<?= $name ?>">
			<option></option>
			<option <?= $selected_value == '1' ? 'selected' : ''; ?> value="1">OUI</option>
			<option <?= $selected_value == '0' ? 'selected' : ''; ?> value="0">NON</option>
			<?php
				if(!$this->field->required)
				{
					?><option <?= $selected_value == 'NULL' ? 'selected' : ''; ?> value="NULL">(Vide)</option><?php
				}
			?>
		</select>
		<script>
			$('#search_<?= $this->node->id ?>').change(function()
			{
				if($('#search_<?= $this->node->id ?>').val() == 'NULL')
				{
					$('#op_bool_<?= $this->node->id ?>').val('NULL');
				}
				else
				{
					$('#op_bool_<?= $this->node->id ?>').val('=');
				}
				trigger_search($('#search_<?= $this->node->id ?>'));
			});
		</script>
		<?php

		return TRUE;
	}
	//--------------------------------------------------------------------
	public function renderValue($value, $input_name, $field_attributes)
	{
		$inverted = $this->node->getNodeAttribute('inverted', ($this->field->object_relation == 'inverted'));

		$yes = 1;
		$no = 0;
		if($inverted)
		{
			$yes = 0;
			$no = 1;
		}

		if($value['value'] === null)
		{
			?><div></div><?php

			return TRUE;
		}

		if ($value['value'] == $yes)
		{
			?><div <?= $this->node->style() ?> <?= $this->node->css_class(array('check-true')) ?>><span style="display:none;">1</span><img src="./library/killi/images/true.png"></div><?php
		}
		else
		{
			?><div <?= $this->node->style() ?> <?= $this->node->css_class(array('check-false')) ?>><span style="display:none;">0</span><img src="./library/killi/images/false.png"></div><?php
		}

		return TRUE;
	}
	//--------------------------------------------------------------------
	public function renderInput($value, $input_name, $field_attributes)
	{
		$inverted = $this->node->getNodeAttribute('inverted', ($this->field->object_relation == 'inverted'));

		$yes = 1;
		$no = 0;
		if($inverted)
		{
			$yes = 0;
			$no = 1;
		}

		$checked = ($value['value'] == $yes);

		$events = '';
		foreach($field_attributes AS $event => $foncs)
		{
			$events .= $event . '="';
			foreach($foncs AS $key => $fonc)
			{
				$events .= $fonc . '|';
			}
			$events .= '"';
		}

		?><div class="checkbox_input <?= $checked ? 'checkbox_oui' : 'checkbox_non' ?>" id="<?= $this->node->id ?>" <?= $events ?>></div><?php

		?><input style='display:none' type="radio" id="<?= $this->node->id; ?>_yes" name="<?= $input_name ?>" <?= $checked?'checked="checked"':'' ?> value="<?= $yes ?>" /><?php
		?><input style='display:none' type="radio" id="<?= $this->node->id; ?>_no" name="<?= $input_name ?>" <?= !$checked?'checked="checked"':'' ?> value="<?= $no ?>" /><?php

		return TRUE;
	}
}
