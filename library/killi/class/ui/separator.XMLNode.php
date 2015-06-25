<?php

/**
 *  @class SeparatorXMLNode
 *  @Revision $Revision: 2416 $
 *
 */

class SeparatorXMLNode extends XMLNode
{
	public function open()
	{
		$title = '';
		$this->_getStringFromAttributes($title);

		?><table id="<?= $this->id; ?>" <?= $this->css_class(array('separator')); ?> <?= $this->style(); ?>><?php
			?><tr><?php
				?><td><?= $title;
	}
	//.....................................................................
	public function close()
	{
		 		?></td><?php
			?></tr><?php
		?></table><?php
	}
}
