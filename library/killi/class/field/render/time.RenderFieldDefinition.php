<?php

/**
 *  @class TimeRenderFieldDefinition
 *  @Revision $Revision: 4512 $
 *
 */

class TimeRenderFieldDefinition extends DateRenderFieldDefinition
{
	public function renderFilter($name, $selected_value)
	{
		$selected_operator = (isset($_POST[$name.'/op']) ? htmlentities($_POST[$name.'/op']) : NULL);

		?><table class='ui-filter ui-filter-date'><?php
			?><tr><?php
				?><td><?php

					?><select name="<?= $name.'/op' ?>" onChange="if($('#search_<?= $this->node->id ?>').val() != ''){trigger_search($('#search_<?= $this->node->id ?>'));}"><?php
					foreach(self::$operators as $operator_key=>$operator)
					{
						?><option value="<?= $operator_key; ?>" <?= ($selected_operator == $operator_key ? ' selected' : ''); ?>><?= $operator; ?></option><?php
					}
					?></select><?php

					?><input onChange="trigger_search($('#search_<?= $this->node->id ?>'));" class="search_input" value="<?= htmlentities($selected_value,ENT_COMPAT,'UTF-8') ?>" name="<?= $name ?>" id="search_<?= $this->node->id ?>" placeholder='hh:mm:ss'><?php
				?></td><?php

			if($selected_value != '')
			{
				?><td class='ui-filter-clear'><?php
					?><img onclick="$('#search_<?= $this->node->id ?>').val('');trigger_search($('#search_<?= $this->node->id ?>'));" src='library/killi/images/delete.png' /><?php
				?></td><?php
			}

			?></tr><?php
		?></table><?php

		?><script>
			$(function() {
				$('#search_<?= $this->node->id ?>').timepicker({stepHour: 1, stepMinute: 1});
			});
		</script><?php


		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		$format = $this->node->getNodeAttribute('format', 'H:i:s');

		// fix POST/GET
		$this->field->inCast ( $value['value'] );
		$value = $value['value'];
		$this->field->outCast ( $value );
		
		if( $value['value'] === NULL)
		{
			?><div></div><?php

			return TRUE;
		}

		// to format
		echo $value['value']->format($format);

		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$this->node->_getClass($this->field, $class);

		// fix POST/GET
		$this->field->inCast ( $value['value'] );
		$value = $value['value'];
		$this->field->outCast ( $value );

		// to format
		$date_str = ($value['value'] === NULL ? '' : $value['value']->format('d/m/Y'));

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

		?>
		<input <?= $events ?> <?= $class ?> size="8" name="<?= $input_name ?>" id="<?= $this->node->id ?>" type="text" value="<?= $date_str ?>" placeholder='hh:mm:ss'/>

		<script>
			$(function() {
				 $('#<?= $this->node->id ?>').timepicker({stepHour: 1, stepMinute: 1});
			 });
		</script>
		<?php

		return TRUE;
	}
}
