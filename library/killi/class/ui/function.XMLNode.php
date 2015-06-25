<?php

/**
 *  @class FunctionXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class FunctionXMLNode extends XMLNode
{
	public function open()
	{
		$value		='12';
		$unit		='ms';
		$format		='%s';

		$label		= $this->getNodeAttribute('string');
		$object		= $this->getNodeAttribute('object');
		$method		= $this->getNodeAttribute('method');
		$refresh	= $this->getNodeAttribute('refresh', 300);
		$showlabel  = $this->getNodeAttribute('nolabel', '0') == '0';

		$hInstance 	= ORM::getObjectInstance($object);
		$primary_key = $this->getNodeAttribute('key', $hInstance->primary_key);

		$field_name	= 'ar_'.str_replace(' ','_',strtolower($label));

		$key = $this->_current_data[$primary_key]['value'];
		Security::crypt($key,$crypt_key);

		//---Si vue list
		if ($this->_view=='search')
		{
			$field_name=$field_name.'_'.$crypt_key;
		}

		//---Method must be prefix with ajax
		if (substr($method,0,4)!='ajax')
		{
			throw new Exception('Method used in \'function\' xml tag, must be prefix with -ajax- !');
			return FALSE;
		}

		$url = './index.php?action='.$object.'.'.$method.'&token='.$_SESSION['_TOKEN'].'&crypt/primary_key='.$crypt_key;

		if ($refresh!=0)
		{
			$label .= ' (refresh '.$refresh.' sec)';
		}

		if ($this->_view=='form')
		{
			?><table class="field" cellspacing="2" cellpadding="1"><tr><?php

				if ($showlabel)
				{
					?><td class="field_label"><?= $label ?> : </td><?php
				}
				?>
					<td class="field_td">
						<table class="field_cell" cellspacing="0" cellpadding="0" style="border: none;">
							<tr>
								<td<?= $this->style() ?> id="<?= $field_name ?>" name="<?= $field_name ?>">
									<img src='<?= KILLI_DIR ?>/images/waiting.gif'/>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<?php
		}
		else if ($this->_view=='search')
		{
			?><div id="<?= $field_name ?>" name="<?= $field_name ?>"></div><?php
		}

		?>
		<script type="text/javascript">
		var xr_<?= $field_name ?> = createXhrObject();

		$(document).ready(function(){
			setTimeout(refresh_<?=  $field_name ?>,<?= (1000) ?>);
		});

		function refresh_<?= $field_name ?>()
		{
			AjaxRequest.get(
			{
				'url':'<?= $url ?>',
				'async': false,
				'onSuccess':function(req)
				{
					document.getElementById('<?= $field_name ?>').innerHTML = req.responseText;
					setTimeout(refresh_<?=  $field_name ?>,<?= ($refresh*1000) ?>)
				},
				'onError': function(req)
				{
					document.getElementById('<?= $field_name ?>').innerHTML = "erreur";
					setTimeout(refresh_<?=  $field_name ?>,<?= ($refresh*1000) ?>)
				},
				'onTimeout': function()
				{
					document.getElementById('<?= $field_name ?>').innerHTML = "timeout";
					setTimeout(refresh_<?=  $field_name ?>,<?= ($refresh*1000) ?>)
				}
			});
		};
		</script>
		<?php

		return TRUE;
	}
}
