<?php

/**
 *  @class ButtonXMLNode
 *  @Revision $Revision: 3913 $
 *
 */

class ButtonXMLNode extends XMLNode
{
	public function open()
	{
		switch($this->getParent()->name)
		{
			case 'flexigrid':
				$this->flexigrid();
				break;
			default:
				$this->listing();
				break;
		}
	}

	public function listing()
	{
		$object_to_use = $this->getParent()->getNodeAttribute('object');
		$hInstance = ORM::getObjectInstance($object_to_use);
		$primary_key = $this->getParent()->getNodeAttribute('key', $hInstance->primary_key);
		Security::crypt($this->_current_data[$primary_key]['value'],$crypt_id, TRUE);

		$title = $this->getNodeAttribute('string');
		$onclick = $this->getNodeAttribute('onclick', '');
		if(!empty($onclick))
		{
			$onclick .= '($(this), \'' . $crypt_id .'\')';
		}

		$icon = $this->getNodeAttribute('icon', '');
		if(!empty($icon))
		{
			$icon = '<img style="float:left;" src="' . $icon . '"/>';
		}

		$width = $this->getNodeAttribute('width', '');
		if(!empty($width))
		{
			$width = 'style="width: ' . $width . '"';
		}

		?><button type="button" onclick="<?= $onclick ?>" <?= $width ?>><?= $icon ?><?= $title ?></button><?php

		/*?><input style="width: 16px;" class="multi_selector" name="crypt/listing_selection[]" value="<?= $crypt_id ?>" type="checkbox"/><?php*/
	}

	public function flexigrid()
	{
		$button['name']		= $this->getNodeAttribute('name');
		$button['class']	= $this->getNodeAttribute('class', '');
		$button['onpress']	= $this->getNodeAttribute('onpress', '');
		$this->buttons[]	= $button;
	}
}

