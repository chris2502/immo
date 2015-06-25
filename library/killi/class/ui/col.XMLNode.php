<?php

/**
 *  @class ColXMLNode
 *  @Revision $Revision: 4406 $
 *
 */

class ColXMLNode extends XMLNode
{
	public function open()
	{
		$responsive	   = $this->getParent()->getNodeAttribute('responsive', '0') == '1';
		if($responsive)
		{
			$count = $this->getParent()->countChildren();
			$sep = 12;
			if($count > 0)
			{
				$sep = round(12 / $count);
			}
			$sep = $this->getNodeAttribute('lg', $sep);
			?><div class="col-xs-<?= $sep ?> col-sm-<?= $sep ?> col-md-<?= $sep ?> col-lg-<?= $sep ?>"><?php
			return TRUE;
		}
		?><td <?php echo $this->css_class(); echo $this->style(); ?>><?php
	}
	//.....................................................................
	public function close()
	{
		$responsive	   = $this->getParent()->getNodeAttribute('responsive', '0') == '1';
		if($responsive)
		{
			?></div><?php
			return TRUE;
		}
		?></td><?php
	}
}
