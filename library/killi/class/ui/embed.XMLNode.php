<?php

/**
 *  @class EmbedXMLNode
 *  @Revision $Revision: 4066 $
 *
 */

class EmbedXMLNode extends XMLNode
{
	public function open()
	{
		$type = $this->getNodeAttribute('type');
		$attribute = $this->getNodeAttribute('attribute');

		// FIXME: C'est trop moche !
		if(isset($_GET['primary_key']))
		{
			$src = $this->_current_data[$attribute]['value'];
		}
		else
		{
			$src = $this->_data_list[$attribute]['value'];
		}

		?><embed type="<?= $type ?>"
				 <?= $this->style(); ?>
				 <?= $this->css_class(); ?>
				 src="<?= $src ?>"
		/><?php
	}
}
