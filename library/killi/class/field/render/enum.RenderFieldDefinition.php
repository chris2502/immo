<?php

/**
 *  @class EnumRenderFieldDefinition
 *  @Revision $Revision: 4515 $
 *
 */
class EnumRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderFilter($name, $selected_value)
	{
		?><input type='hidden' name="<?= $name.'/op' ?>" value='='/><?php
		?><select id="search_<?= $this->node->id ?>" class="search_input" onchange="return trigger_search($('#search_<?= $this->node->id ?>'));" name="crypt/<?= $name ?>"><?php
			?><option></option><?php

			foreach ( $this->field->type as $key => $array_value )
			{
				if(empty($key))
				{
					continue;
				}
				Security::crypt ( $key, $crypt_key );

				?><option <?= ($selected_value == $key ? 'selected' : '') ?> value="<?= $crypt_key ?>"><?= htmlentities($array_value, ENT_QUOTES, 'UTF-8') ?></option><?php
			}

		?></select><?php

		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		$data_index = $this->node->getNodeAttribute('data', '');
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

		if (empty($value ['value']) || !isset( $data_list[$value ['value']] ))
		{
			?><div></div><?php

			return TRUE;
		}

		?><?= htmlentities($data_list [$value ['value']], ENT_QUOTES, 'UTF-8'); ?><?php

		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$data_index = $this->node->getNodeAttribute('data', '');
		$noblankvalue = $this->node->getNodeAttribute ( 'noblankvalue', FALSE );
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
				$events .= $fonc . '(this);';
			}
			$events .= '"';
		}

		?><div <?= $this->node->css_class() ?> <?= $this->node->style()?>><?php
			?><select <?= $class ?> name="<?= 'crypt/'.$input_name ?>" id="<?= $this->node->id ?>" <?= $events; ?>><?php

			if (! ($noblankvalue || ($this->field->required && isset ( $_GET ['view'] ) && $_GET ['view'] == 'form')))
			{
				?><option></option><?php
			}

			foreach ( $data_list as $key => $array_value )
			{
				Security::crypt ( $key, $crypt_key );

				?><option <?= ($value['value'] == $key ? 'selected' : '') ?> value="<?= $crypt_key ?>"><?= htmlentities($array_value, ENT_QUOTES, 'UTF-8'); ?></option><?php
			}

			?></select><?php
		?></div><?php

		return TRUE;
	}
}
