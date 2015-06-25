<?php

/**
 *  @class PageXMLNode
 *  @Revision $Revision: 4066 $
 *
 */

class Slider_RangeXMLNode extends XMLNode
{
	public function open()
	{
		$object		= $this->getNodeAttribute('object');
		$minimum	= $this->getNodeAttribute('min');
		$maximum	= $this->getNodeAttribute('max');
		$step		= $this->getNodeAttribute('step');
		$values		= $this->getNodeAttribute('default');
		$tvalues	= explode(',', $values);

		$this->_getDefaultValue($default_value);

		if( isset($default_value['value']) && !empty($default_value['value']))
		{
			$tvalues = explode('-', $default_value['value']);

			$values = trim(str_replace(":30", ".5", $tvalues[0])).','.trim(str_replace(":30", ".5", $tvalues[1]));
		}

		$name	= $this->getNodeAttribute('name');
		$champ	= $object.strtolower($name);

		?>
		<table style="width: 70%;">
			<tr>
				<td style="white-space: nowrap;width:70px;"><?= $name ?>&nbsp;&nbsp;</td>
				<td style="width:120px;"><div id="<?= strtolower($name) ?>"></div></td>
				<td style="white-space: nowrap;"><input READONLY value="<?= $tvalues[0]." - ".$tvalues[1]."" ?>" id="<?=$champ?>" type="text" name="<?=strtolower($name)?>" style="width: 80px;margin-left:10px">&nbsp;</td>
			</tr>
		</table>

		<script>
			$(document).ready(function(){
				$('#<?= strtolower($name) ?>').slider({ animate: false, range:true, max: <?= $maximum ?>,
											min: <?= $minimum ?>,step: <?= $step ?>, values: [<?= $values ?>], orientation:"horizontal"
											});
				//return <?= $onchange ?>(<?= $value ?>);
			});

			$('#<?=strtolower($name)?>').bind('slide', function(event, ui)
			{
				var from = ui.values[0]+"";
				if((from.length)>2)
					from = from.replace(".5",":30" );
				else from+="";
				var to = ui.values[1]+"";
				if((to.length)>2)
					to=to.replace(".5",":30" );
				else to+="";
		 		$( "#<?=$champ?>" ).val( from + " - " + to +"" );
			});

		</script>

		<?php

		return TRUE;
	}
}
