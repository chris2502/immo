<?php

/**
 *  @class DatetimeRenderFieldDefinition
 *  @Revision $Revision: 3676 $
 *
 */

class DatetimeRenderFieldDefinition extends DateRenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		$format = $this->node->getNodeAttribute('format', 'd/m/Y H:i:s');
		$delta = $this->node->getNodeAttribute('delta', '0') == '1';

		// fix POST/GET
		$this->field->inCast ( $value['value'] );
		$value = $value['value'];
		$this->field->outCast ( $value );
		
		if( $value['value'] === NULL )
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

		$title = str_replace(parent::$english_months, parent::$french_months, str_replace(parent::$english_days, parent::$french_days, $value['value']->format('l d F Y'.($value['timestamp'] % 60*60*24 ? ' à H\hi:s' : ''))));

		?><span title="<?= $title ?>" class='tooltip_link'><?= $date_str; ?></span><?php

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
		$date_str = ($value['value'] == NULL ? '' : $value['value']->format('d/m/Y H:i:s'));

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
		<input <?= $class ?> size="19" name="<?= $input_name ?>" id="<?= $this->node->id ?>" type="text" value="<?= $date_str ?>" placeholder='jj/mm/aaaa hh:mm:ss'/>

		<script>
			$(function() {
				 $('#<?= $this->node->id ?>').datetimepicker();
			 });
		</script>
		<?php

		return TRUE;
	}
}
