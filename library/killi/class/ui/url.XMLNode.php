<?php

/**
 *  @class UrlXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class UrlXMLNode extends XMLNode
{
	public function open()
	{
		$href = $this->getNodeAttribute('href');
		$string = $this->getNodeAttribute('string');
		?>
		<a <?= $this->style() ?> href="<?= $href.'&token='.$_SESSION['_TOKEN'] ?>"><?= $string ?></a>
		<?php
	}
}
