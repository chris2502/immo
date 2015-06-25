<?php

/**
 *  @class DateRenderFieldDefinition
 *  @Revision $Revision: 4534 $
 *
 */

class DateRenderFieldDefinition extends RenderFieldDefinition
{
	protected static $english_days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
	protected static $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
	protected static $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	protected static $french_months = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');

	protected static $operators = array('='=>'=', '&gt;'=>'&gt;', '&lt;'=>'&lt;', '&gt;='=>'&gt;=', '&lt;='=>'&lt;=', '!='=>'!=');

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

					?><input class="search_input" value="<?= $selected_value != NULL ? date('d/m/Y',strtotime($selected_value)) : NULL ?>" id="search_<?= $this->node->id ?>" placeholder='jj/mm/aaaa'><?php
					?><input type='hidden' value="<?= htmlentities($selected_value,ENT_COMPAT,'UTF-8') ?>" name="<?= $name ?>" id="hidden_search_<?= $this->node->id ?>"><?php
				?></td><?php

			if($selected_value != '')
			{
				?><td class='ui-filter-clear'><?php
					?><img onclick="$('#hidden_search_<?= $this->node->id ?>').val('').attr('');$('#search_<?= $this->node->id ?>').val('').attr('value', '').datepicker('setDate', null).datepicker('disable');trigger_search($('#search_<?= $this->node->id ?>'));" src='library/killi/images/delete.png' /><?php
				?></td><?php
			}

			?></tr><?php
		?></table><?php

		?><script>
			$(document).ready(function() {
				$('#search_<?= $this->node->id ?>').datepicker({
					onClose: function(dateText) {
						if($('#search_<?= $this->node->id ?>').val() != '')
						{
							//var date = $('#search_<?= $this->node->id ?>').datepicker('getDate');
							var date = $(this).datepicker('getDate');
							var date_ymd = $.datepicker.formatDate( "yy-mm-dd", date);
							$('#hidden_search_<?=$this->node->id ?>').val(date_ymd).attr('value', date_ymd);
						}
						else
						{
							$('#hidden_search_<?=$this->node->id ?>').val('').attr('value', '');
						}
						trigger_search($('#search_<?= $this->node->id ?>'));
					}
				});
			});
		</script><?php

		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		$format = $this->node->getNodeAttribute('format', 'd/m/Y');
		$delta = $this->node->getNodeAttribute('delta', '0') == '1';

		// fix POST/GET
		$this->field->inCast ( $value['value'] );
		$value = $value['value'];
		$this->field->outCast ( $value );
		
		if($value['value'] === NULL)
		{
			?><div></div><?php

			return TRUE;
		}

		// to format
		$date_str = $value['value']->format($format);

		if ($delta)
		{
			$delta_num = round(($value['timestamp']-time())/(60*60*24));

			if ($delta_num > 0)
			{
				$date_str .= ' (J-' . ($delta_num) . ')';
			}
			else
			{
				$date_str .= ' (J+' . (-$delta_num) . ')';
			}
		}

		$title = str_replace(self::$english_months, self::$french_months, str_replace(self::$english_days, self::$french_days, $value['value']->format('l d F Y')));

		?><span title="<?= $title ?>" class="tooltip_link"><?= $date_str; ?></span><?php

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
		$date_str = ($value['value'] === NULL ? '' :  $value['value']->format('d/m/Y'));

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
		<input <?= $events ?> <?= $class ?> size="10" name="<?= $input_name ?>" id="<?= $this->node->id ?>" type="text" value="<?= $date_str ?>" placeholder='jj/mm/aaaa'/>

		<script>
			$(function() {
				 $('#<?= $this->node->id ?>').datepicker({showButtonPanel:true, dateFormat: 'dd/mm/yy'});
			 });
		</script>
		<?php

		return TRUE;
	}
}
