<?php

/**
 *  @class ProcessViewerXMLNode
 *  @Revision $Revision: 3951 $
 *
 */

class ProcessViewerXMLNode extends XMLNode
{
	public function open()
	{
		if(!isset($_GET['primary_key']))
		{
			echo 'Impossible d\'afficher ce composant dans une vue non formulaire !';
			return TRUE;
		}

		$pk = $_GET['primary_key'];

		$module = ModuleFactory::getModule($pk);

		?><table class="title"><?php
		   ?><tr><?php
			   ?><td><?php
			   		echo $module->getTitle();
			   ?></td><?php
			?></tr><?php
		?></table><?php

		$module->render();
	}
}
