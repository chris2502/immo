<?php

/**
 *  @class KeyXMLNode
 *  @Revision $Revision: 3545 $
 *
 */

class KeyXMLNode extends XMLNode
{
	public function open()
	{
		$object		= $this->getNodeAttribute('object');
		$attribute	= $this->getNodeAttribute('attribute');
		Security::crypt($this->_current_data[$attribute]['value'], $crypt_value);
		?><input value="<?= $crypt_value ?>" type="hidden" name="crypt/<?= $object.'/'.$attribute ?>" id="<?= $object.'/'.$attribute ?>"><?php
	}
}
