<?php

/**
 *  @class PageXMLNode
 *  @Revision $Revision: 4641 $
 *
 */

class PageXMLNode extends XMLNode
{
	public function open()
	{
		$css_class = $this->getNodeAttribute('css_class', NULL);

		$parent = $this->getParent();
		if($parent->name=='accordion')
		{
			$title = '';
			$this->_getStringFromAttributes($title);
			?><h3><?= $title; ?></h3><?php
		}

		$class='';
		if (isset($css_class))
		{
			$class=' class="'.$css_class.'"';
		}

		?><div id="<?= $this->id; ?>"<?= $class ?>><?php
	}
	//.....................................................................
	public function close()
	{
		?></div><?php
	}
}