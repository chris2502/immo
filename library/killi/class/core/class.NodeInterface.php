<?php

/**
 *  @interface NodeInterface
 *  @Revision $Revision: 4139 $
 *
 */

interface NodeInterface
{
	public function getNodeAttribute($attribute, $default_value = ATTRIBUTE_HAS_NO_DEFAULT_VALUE, $from_parent = FALSE);

	public function css_class($base_style = array());

	public function style($base_style = array());
}
