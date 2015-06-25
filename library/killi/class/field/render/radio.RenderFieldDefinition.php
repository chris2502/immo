<?php

/**
 *  @class RadioRenderFieldDefinition
 *  @Revision $Revision: 3965 $
 *
 */

class RadioRenderFieldDefinition extends EnumRenderFieldDefinition
{
	public function renderInput($value, $input_name, $field_attributes)
	{
		$data_index = $this->node->getNodeAttribute('data', '');
		$this->node->_getClass($this->field, $class);
		$data_list = array();
		if(!empty($data_index))
		{
			$current_data = $this->node->getDataList();
			$data_list = $current_data[$data_index];
		}
		else
		if(is_array($this->field->type))
		{
			$data_list = $this->field->type;
		}

		$events = '';
		foreach($field_attributes AS $event => $foncs)
		{
			$events .= $event . '="';
			foreach($foncs AS $key => $fonc)
			{
				$events .= $fonc . '($(this));';
			}
			$events .= '"';
		}

		?><div <?= $this->node->css_class() ?> <?= $this->node->style()?>><ul class="field_radio"><?php
			$count = 0;
			foreach($data_list as $key => $array_value)
			{
				$id = $this->node->id . '_' . $count++;
				Security::crypt ( $key, $crypt_key );
				?><li><label><?php
				?><input <?= $class ?> <?= $events; ?> type="radio" name="<?= 'crypt/'.$input_name ?>" <?= ($value['value'] == $key ? 'checked="1"' : '') ?> value="<?= $crypt_key ?>"/><?php
				?><?= htmlentities($array_value, ENT_QUOTES, 'UTF-8'); ?></label><?php
				?></li><?php
			}
		?></ul></div><?php

		return TRUE;
	}
}
