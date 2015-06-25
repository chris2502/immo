<?php

/**
 *  @class ModuleNode
 *  @Revision $Revision: 4139 $
 *
 */

class ModuleNode implements NodeInterface
{
	public $id;

	public function __construct($id)
	{
		$this->id = $id;
	}

	public function getNodeAttribute($attribute, $default_value = ATTRIBUTE_HAS_NO_DEFAULT_VALUE, $from_parent = FALSE)
	{

	}

	public function css_class($base_style = array())
	{

	}

	public function style($base_style = array())
	{

	}

	public function _getClass($attribute, &$class = '')
	{

		return TRUE;
	}

}
