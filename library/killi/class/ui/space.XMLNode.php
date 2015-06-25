<?php

/**
 *  @class SeparatorXMLNode
 *  @Revision $Revision: 4066 $
 *
 */

class SpaceXMLNode extends XMLNode
{
	public function open()
	{
		$height = $this->getNodeAttribute('height', '20');
		?><div class="killi-space" style="height:<?php echo $height; ?>px"></div><?php
	}
}
