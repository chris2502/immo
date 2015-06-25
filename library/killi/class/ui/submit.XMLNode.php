<?php

/**
 *  @class SubmitXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class SubmitXMLNode extends XMLNode
{
	public function open()
	{

		$name		= $this->getNodeAttribute('name','');
		$string		= $this->getNodeAttribute('string');
		$confirm 	= $this->getNodeAttribute('confirm','');
		$method 	= $this->getNodeAttribute('method','');
		$target	 = $this->getNodeAttribute('target', '');
		$with_table	= $this->getNodeAttribute('with_table', 'true') == 'true';
		$this->id 	= $this->getNodeAttribute('id', false);

		$id = '';
		if ($this->id)
		{
			$id = ' id="'.$this->id.'"';
		}

		if (!empty($confirm))
		{
			$confirm='if (!confirm(\''.addslashes($confirm).'\')){return false;}; ';
		}

		if( isset($method) === true && trim($method) != '' )
		{
		   if(!empty($target)) $target = 'document.main_form.target=\''.addslashes($target).'\'; ';
		   if ($with_table)
		   {
			   	?>
				<table style="width: 100%;">
					<tr>
						<td>
			   	<?php
		   }
			?>

			<button <?= $this->style() ?> <?= $this->css_class() ?> <?=$id?> onClick="<?= $confirm.' '.$target ?> $('#main_form').attr('action', './index.php?action=<?= $method ?>&token=<?= $_SESSION[ '_TOKEN' ] ?>');$('#main_form').submit();" type="button" <?php echo((empty($name))?'':' name="'.$name.'" value="'.$string.'"');?>><?= $string ?></button>
			<?php
			if ($with_table)
			{
			   	?>
						</td>
					</tr>
				</table>
			<?php
			}
		}
		else
		{
		   if ($with_table)
		   {
			   	?>
				<table style="width: 100%;">
					<tr>
						<td>
			   	<?php
		   }
			?>
							<button <?= $this->style() ?> <?= $this->css_class() ?> <?=$id?> type="submit" <?php echo((empty($name))?'':' name="'.$name.'" value="'.$string.'"');?>><?= $string ?></button>
			<?php
			if ($with_table)
			{
			   	?>
						</td>
					</tr>
				</table>
			<?php
			}

		}

		return TRUE;

	}
}
