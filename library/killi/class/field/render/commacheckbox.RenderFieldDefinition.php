<?php

class CommaCheckboxRenderFieldDefinition extends RenderFieldDefinition
{
	private $_images = true;

	public function renderValue($value, $input_name, $field_attributes)
	{
		$attr = $this->node->getNodeAttribute('attribute');

		if (isset($_GET[$attr]))
		{
			?><input type="hidden" name="<?= $input_name ?>" id="crypt_<?= $this->node->id ?>"  value="<?= $value['value'] ?>" <?= join(' ', $field_attributes); ?>/><?php
		}

		$exploded = explode(',', $value['value']);

		if (isset($_GET['view']) && $_GET['view'] == 'form')
		{
			?>

			<ul style="padding-left: 16px;">

			<?php
			foreach ($exploded as $v)
			{
			?>

				<li><?php echo $v; ?></li>

			<?php
			}
			?>

			</ul>

			<?php
		}
		else
		{
			echo implode(', ', $exploded);
		}

		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$object	= $this->node->getNodeAttribute('object');
		$attribute = $this->node->getNodeAttribute('attribute');

		$ro = ($this->node->getNodeAttribute('cbxonly', '0') == '1');

		$cbx_style = 'vertical-align:middle;margin-right:6px;';

		?>
		<script type="text/javascript">
		function evt_cbx_<?=$this->node->id?>()
		{
			$('input[cbxid="<?=$this->node->id?>"]').off('change');
			$('input[cbxid="<?=$this->node->id?>"]').change(function(e){
				var checked = ($(e.target).attr('checked') == 'checked');
				var value   = $(e.target).val();
				
				if ($('#<?=$this->node->id?>_real').val() == '')
				{
					var val = new Array();
				}
				else
				{
					var val = $('#<?=$this->node->id?>_real').val().split(',');
				}

				if (checked)
				{
					val.push(value);
				}
				else
				{
					var newVal = new Array();
					for (var i = 0 ; i < val.length ; i++)
					{
						if (val[i] != value)
						{
							newVal.push(val[i]);
						}
					}
					val = newVal;
				}

				var inputValue = (val.length > 0)? val.join(',') : '';

				$('#<?=$this->node->id?>_real').val(inputValue);
			});
		}

		$(document).ready(function(){
			$('#<?=$this->node->id?>_add').keypress(function(e){
				if (e.which == 13)
				{
					var v = $('#<?=$this->node->id?>_add').val();
					$('#<?=$this->node->id?>').append('<li><input cbxid="<?=$this->node->id?>" type="checkbox" checked="checked" value="' + v + '" style="<?=$cbx_style?>" />' + v + '</li>');
					$('#<?=$this->node->id?>_add').val('');

					var val = $('#<?=$this->node->id?>_real').val().split(',');
					val.push(v);
					$('#<?=$this->node->id?>_real').val(val.join(','));
					evt_cbx_<?=$this->node->id?>();
				}
			});
			evt_cbx_<?=$this->node->id?>();
		});
		</script>
		<?php

		$exploded  = array();
		if (!empty($value['value']))
		{
			$exploded = explode(',', $value['value']);
		}

		?>

		<input id="<?=$this->node->id?>_real" type="hidden" name="<?=$input_name?>" value="<?=$value['value']?>" />

		<ul style="list-style-type:none;" id="<?=$this->node->id?>">

		<?php
		foreach ($exploded as $v)
		{
		?>

			<li><input cbxid="<?=$this->node->id?>" type="checkbox" checked="checked" value="<?=$v?>" style="<?=$cbx_style?>" /><?php echo $v; ?></li>

		<?php
		}
		?>

		</ul>

		<?php
		if (!$ro)
		{
		?>

		<br />
		<input id="<?=$this->node->id?>_add" type="text" style="background-image:url('<?=KILLI_DIR?>images/add.png'); background-repeat: no-repeat; background-position: 100% 50%; padding-right: 18px;"/>

		<?php
		}

		return TRUE;
	}
}