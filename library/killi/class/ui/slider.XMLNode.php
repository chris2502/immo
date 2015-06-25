<?php

/**
 *  @class PageXMLNode
 *  @Revision $Revision: 4066 $
 *
 */

class SliderXMLNode extends XMLNode
{
	public function open()
	{
		$minimum	= $this->getNodeAttribute('min');
		$maximum	= $this->getNodeAttribute('max');
		$step		= $this->getNodeAttribute('step');
		$value		= $this->getNodeAttribute('value');
		$onchange   = $this->getNodeAttribute('onchange', false);
		$name 		= $this->getNodeAttribute('name');

		?>
		<table style="width: 100%;">
			<tr>
				<td style="white-space: nowrap;"><?= $this->getNodeAttribute('string') ?>&nbsp;&nbsp;</td>
				<td style="width: 90%;"><div id="<?= $name ?>"></div></td>
				<td style="white-space: nowrap;"><input READONLY value="<?= $value ?>" type="text" name="slider_value" style="width: 60px;">&nbsp;</td>
				<td style="white-space: nowrap;">&nbsp;<?= $this->getNodeAttribute('unit') ?></td>
			</tr>
		</table>

		<script>
			$(document).ready(function(){
				$('#<?= $name ?>').slider({ animate: false, max: <?= $maximum ?>, min: <?= $minimum ?>,step: <?= $step ?>, value: <?= $value ?> });
				return <?= $onchange ?>(<?= $value ?>);
			});

			$('#<?= $name ?>').bind('slide', function(event, ui) {
				document.main_form.slider_value.value = $('#<?= $name ?>').slider('option', 'value');
			});

			<?php
			if ($onchange)
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
