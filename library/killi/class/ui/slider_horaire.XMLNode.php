<?php

/**
 *  @class PageXMLNode
 *  @Revision $Revision: 4066 $
 *
 */

class Slider_HoraireXMLNode extends XMLNode
{
	public function open()
	{
		$attribute	= $this->attributes;

		$object		= $attribute['object'];

		$minimum_am	= '0';
		$maximum_am	= '14';

		$minimum_pm	= '12';
		$maximum_pm	= '24';

		$step		= $attribute['step'];
		$values		= $attribute['default'];

		$this->_getDefaultValue($default_value);

		$range_values = explode(':', $values);
		if(count($range_values) >1 )
		{
			$tvalues_am = explode(',', $range_values[0]);
			$tvalues_pm = explode(',', $range_values[1]);
		}
		else { }


		$this->_getDefaultValue($default_value);

		if( isset($default_value['value']) && !empty($default_value['value']))
		{
			$tvalues = explode('-', $default_value['value']);
			$values = trim(str_replace(":30", ".5", $tvalues[0])).','.trim(str_replace(":30", ".5", $tvalues[1]));
		}

		if(!isset($attribute['label'])) $attribute['label'] = $attribute['name'];

		if (isset($attribute['onchange']))
			$onchange = $attribute['onchange'];

		$name = $attribute['name'];
		$champ = $object.strtolower($attribute['name']);

		?>
		<table style="width: 80%;">
			<tr>
				<td style="white-space: nowrap;width:70px;"><?= $attribute['name'] ?>  &nbsp;&nbsp;</td>
				<td style="width:120px;">
					<div id="<?= strtolower($name) ?>_am" style="margin-bottom:5px"></div>
					<div id="<?= strtolower($name) ?>_pm"></div>
				</td>
				<td style="white-space: nowrap;">
					<input READONLY value="<?= $tvalues_am[0]." - ".$tvalues_am[1]."" ?>" id="<?=$champ?>_am" type="text" name="<?=strtolower($name)?>_am" style="width: 80px;margin-left:10px"> a.m <input style="width:20px" type="checkbox" name="<?=strtolower($name)?>_am_contrainte" id="<?=strtolower($name)?>_am_contrainte" value=""/> <br />
			   		<input READONLY value="<?= $tvalues_pm[0]." - ".$tvalues_pm[1]."" ?>" id="<?=$champ?>_pm" type="text" name="<?=strtolower($name)?>_pm" style="width: 80px;margin-left:10px"> p.m <input style="width:20px" type="checkbox" name="<?=strtolower($name)?>_pm_contrainte" id="<?=strtolower($name)?>_pm_contrainte" value=""/>
			   		<input READONLY value="<?= $tvalues_am[0]." - ".$tvalues_am[1].":".$tvalues_pm[0]." - ".$tvalues_pm[1]."" ?>" id="<?=$champ?>" type="hidden" name="<?=strtolower($name)?>" style="width: 80px;margin-left:10px">

				</td>
			</tr>
		</table>
		<?php $days = array('', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'); ?>
		<script>
			$(document).ready(function(){
				$('#<?= strtolower($name) ?>_am').slider({ animate: false, range:true, max: <?= $maximum_am ?>,
											min: <?= $minimum_am ?>,step: <?= $step ?>, values: [<?= $range_values[0] ?>], orientation:"horizontal"
											});

				$('#<?= strtolower($name) ?>_pm').slider({ animate: false, range:true, max: <?= $maximum_pm ?>,
						min: <?= $minimum_pm ?>,step: <?= $step ?>, values: [<?= $range_values[1] ?>], orientation:"horizontal"
						});

				<?php
				foreach($days as $day)
				{
					?>
					$('#<?=strtolower($name) ?>_am_contrainte').click( function()
							{
								if( $(this).attr('checked') == 'checked')
									$('#<?= strtolower($name)?>_am').slider("option", "disabled", true);
								else
									$('#<?= strtolower($name)?>_am').slider("option", "disabled", false);
							});

					$('#<?=strtolower($name) ?>_pm_contrainte').click( function()
							{
								if( $(this).attr('checked') == 'checked')
									$('#<?= strtolower($name)?>_pm').slider("option", "disabled", true);
								else
									$('#<?= strtolower($name)?>_pm').slider("option", "disabled", false);
							});
					<?php
				}
				?>
			});

			$('#<?=strtolower($name)?>_am').bind('slide', function(event, ui)
			{
				var from = ui.values[0]+"";
				if((from.length)>2)
					from = from.replace(".5",":30" );
				else from+="";
				var to = ui.values[1]+"";
				if((to.length)>2)
					to=to.replace(".5",":30" );
				else to+="";
		 		$( "#<?=$champ?>_am" ).val( from + " - " + to +"" );

		 		$( "#<?=$champ?>" ).val( from + " - " + to + ":" + $('#<?=strtolower($name)?>_pm').val() );
			});

			$('#<?=strtolower($name)?>_pm').bind('slide', function(event, ui)
					{
						var from = ui.values[0]+"";
						if((from.length)>2)
							from = from.replace(".5",":30" );
						else from+="";
						var to = ui.values[1]+"";
						if((to.length)>2)
							to=to.replace(".5",":30" );
						else to+="";
				 		$( "#<?=$champ?>_pm" ).val( from + " - " + to +"" );

				 $( "#<?=$champ?>" ).val( $('#<?=strtolower($name)?>_am').val() + ":" + from + " - " + to +":" );
			  });

			<?php
			if (isset($attribute['onchange']) && 0)
			{
				?>
				$('#<?= $name ?>').bind('slidechange', function(event, ui) {

					return <?= $onchange ?>($('#<?= $name ?>').slider('option', 'value'));
				});
				<?php
			}
			?>
		</script>
		<?php

	   return TRUE;
	}
}
