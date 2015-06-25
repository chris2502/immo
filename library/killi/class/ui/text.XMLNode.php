<?php

/**
 *  @class TextXMLNode
 *  @Revision $Revision: 4066 $
 *
 */

class TextXMLNode extends XMLNode
{
	public function open()
	{
		$name = $this->getNodeAttribute('name');

		//---Si listing
		if ($this->_view=='search')
		{
			$object = $this->getNodeAttribute('object', NULL, TRUE);
			$primary_key = ORM::getObjectInstance($object)->primary_key;

			Security::crypt($this->_current_data[$primary_key]['value'],$crypt_id);
			$name = $name.'/'.$crypt_id;
		}

		?><input <?= $this->style()  ?> name="<?= $name ?>" type="text"/><?php

		return TRUE;
	}
}
