<?php

/**
 *  @class GroupXMLNode
 *  @Revision $Revision: 4406 $
 *
 */

class GroupXMLNode extends XMLNode
{
	//.....................................................................
	public function open()
	{
		$responsive	   = $this->getNodeAttribute('responsive', '0') == '1';

		if($responsive)
		{
			$this->open_row();
			return TRUE;
		}

		$this->open_table();
		return TRUE;
	}
	//.....................................................................
	public function close()
	{
		$responsive	   = $this->getNodeAttribute('responsive', '0') == '1';

		if($responsive)
		{
			$this->close_row();
			return TRUE;
		}

		$this->close_table();
		return TRUE;
	}

	//.....................................................................
	public function open_table($string = '')
	{
		$string 	   = $this->getNodeAttribute('string', '');
		?><table id="<?php echo $this->id; ?>"<?php echo $this->css_class(array('group'));?> <?php echo $this->style(); ?>><?php

		if(!empty($string))
		{
			?>
			<thead>
				<tr>
					<th colspan="<?= count($this->getChildren()); ?>" class="group_title"><?= $string; ?></th>
				</tr>
				<tr style="height:10px">&nbsp;</tr>
			</thead>
			<?php
		}
		?><tr><?php
	}

	//.....................................................................
	public function close_table()
	{
		$resizable = ($this->getNodeAttribute('resizable', '0') == '1');

		?></tr><?php
		?></table><?php

		if ($resizable)
		{
			?><script type="text/javascript">
			$(document).ready(function(){
				var len = $('#<?php echo $this->id; ?> td').length;
				$('#<?php echo $this->id; ?> > tbody > tr > td').each(function(idx, obj){
					if (idx < len - 1)
					{
						$(obj).resizable({
							handles: 'e'
						});
						$(obj).css({
							borderRight: '3px double #ccc'
						});
					}
				});
			});
			</script><?php
		}
	}

	//.....................................................................
	public function open_row()
	{
		?><div class="row"><?php
	}

	//.....................................................................
	public function close_row()
	{
		?></div><?php
	}
}
