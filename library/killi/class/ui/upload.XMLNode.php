<?php

/**
 *  @class UploadXMLNode
 *  @Revision $Revision: 3847 $
 *
 */

class UploadXMLNode extends XMLNode
{
	public function open()
	{
		$always_visible = ($this->getNodeAttribute('always_visible', '1') == '1');
		$label = $this->getNodeAttribute('label', FALSE);

		// 0  => Pas de multi (par défaut)
		// 1  => Multi, pas de limite
		// >1 => Multi, limité à N fichiers
		$n_max_multi = $this->getNodeAttribute('multi', '0');

		if ($always_visible || $this->_edition)
		{
			$pre  = '';
			$post = '';
			if ($label !== FALSE)
			{
				$pre =	'<table class="field" cellspacing="2" cellpadding="1">'.
							'<tr>'.
								'<td class="field_label">'.$label.' : </td>'.
								'<td>';
				$post =	'</td></tr></table>';
			}
			echo $pre;
			if ($n_max_multi > 0)
			{
			?>
			<div class="<?=$this->id?>">
				<input <?= $this->style() ?> type="file" name="<?= $this->getNodeAttribute('name'); ?>[]" />
				<img class="<?=$this->id?>_btn_plus" style="cursor:pointer;" src="<?=KILLI_DIR?>images/add.png"/>
				<img class="<?=$this->id?>_btn_minus" style="cursor:pointer;" src="<?=KILLI_DIR?>images/delete.png"/>
			</div>


			<script type="text/javascript">
			<?php if (!defined('MULTIUPLOAD_SCRIPT_INCLUDED')): define('MULTIUPLOAD_SCRIPT_INCLUDED', true); ?>
			function bindMultiuploadBtns() {
				$('[class$="_btn_plus"]').each(function(index, element){
					$(element).unbind('click');
					$(element).click(function(){
						<?php if ($n_max_multi > 1): ?>
						var cls = $(this).parent().attr('class');
						if ($('.' + cls).length >= <?=$n_max_multi?>)
						{
							return;
						}
						<?php endif; ?>
						var toclone = $(this).parent();
						toclone.clone().insertAfter(toclone);
						bindMultiuploadBtns();
					});
				});
				$('[class$="_btn_minus"]').each(function(index, element){
					$(element).unbind('click');
					$(element).click(function(){
						var cls = $(this).parent().attr('class');
						if ($('.' + cls).length <= 1)
						{
							return;
						}
						$(this).parent().remove();
					});
				});
			}
			<?php endif; ?>

			$(document).ready(bindMultiuploadBtns);
			</script>


			<?php
			}
			else
			{
			?><input <?= $this->style() ?> type="file" name="<?= $this->getNodeAttribute('name'); ?>" /><?php
			}
			echo $post;
		}
		return TRUE;
	}
}
