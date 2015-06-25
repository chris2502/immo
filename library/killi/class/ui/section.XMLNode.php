<?php

/**
 *  @class SectionXMLNode
 *  @Revision $Revision: 4054 $
 *
 */

class SectionXMLNode extends XMLNode
{
	public function open()
	{
		$title			= $this->getNodeAttribute('title', '');
		$condition		= $this->getNodeAttribute('condition', '');
		$border			= $this->getNodeAttribute('border', '1') == '1';
		$datanotempty	= $this->getNodeAttribute('datanotempty', '');
		$dataempty		= $this->getNodeAttribute('dataempty', '');

		if ($border)
		{
			$border_style = 'padding:4px;margin-bottom:3px;';
		}
		else
		{
			$border_style = 'border:none;';
		}
		if(!empty($datanotempty))
		{
			$condition = count($this->_data_list[$datanotempty]) > 0;
		}
		else
		if(!empty($dataempty))
		{
			$condition = count($this->_data_list[$dataempty]) == 0;
		}
		else
		if(!empty($condition))
		{
			/* Remplace les AND et les OR */
			$pattern = array('(\bAND\b)', '(\bOR\b)', '(\band\b)', '(\bor\b)');
			$replacement = array('&&', '||', '&&', '||');
			$condition = preg_replace($pattern, $replacement, $condition);

			$pattern = '((\{)([a-zA-Z_]+(/[a-zA-Z_]+)*)(\}))';
			$replacement = 'retrieveFieldValue(\$("input[name=\'$2\'],select[name=\'$2\']"), \'$2\')';
			$condition = preg_replace($pattern, $replacement, $condition);

		}
		else
		{
			$condition = '1';
		}

		?><script type="text/javascript">
		$(document).ready(function() {
			$("#<?= $this->id ?>").Updatable({callback: function(obj, attribute, new_value) {
				if(eval('<?= addslashes($condition) ?>'))
				{
					$("#<?= $this->id ?>").slideDown(300);
					// Hidden section blow off range sliders in it at page start.
					// These have to be built as soon as the section is shown.
					$("#<?= $this->id ?>").find('table.multirange').each(function(idx, elt){
						$(elt).colResizable({
							liveDrag :true,
							draggingClass: 'rangeDrag',
							gripInnerHtml: '<div class="rangeGrip"></div>',
							onDrag: horaire_calculate,
							minWidth:8
						});
						horaire_calculate(elt);
					});
				}
				else
				{
					$("#<?= $this->id ?>").slideUp(300);
				}
			}
			});
			if(eval('<?= addslashes($condition) ?>'))
			{
				$("#<?= $this->id ?>").show();
			}
		});
		</script><?php

		?><div id="<?= $this->id ?>" <?= $this->style(array('display' => 'none')); ?> <?= $this->css_class() ?>><?php
			?><fieldset style="<?= $border_style ?>"><?php

			if (!empty($title))
			{
				?><legend style="padding: 2px;"><?php echo $title; ?></legend><?php
			}

		return TRUE;
	}

	public function close()
	{
		?></fieldset><?php
		?></div><?php
		return TRUE;
	}
}
