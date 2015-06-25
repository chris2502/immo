<?php

/**
 *  @class TitleXMLNode
 *  @Revision $Revision: 3847 $
 *
 */

class TitleXMLNode extends XMLNode
{
	public function check_render_condition()
	{
		if(!parent::check_render_condition())
		{
			return FALSE;
		}
		
		//$create_parent=$this->getParent('create');

		//---Si mode create
		if (isset($_GET['view']) && $_GET['view']=='create' && (!isset($_GET['inside_popup']) || (isset($_GET['inside_popup']) && $_GET['inside_popup'] == 1)))
		{
			return FALSE;
		}

		//---Si on est dans un popup
		if (isset($_GET['input_name']) || (isset($_GET['inside_popup']) && $_GET['inside_popup'] == 1))
		{
			return FALSE;
		}
		
		return TRUE;
	}
	public function open()
	{
		//---Si action = historic on défini le titre soi même
		$raw = explode('.',$_GET['action']);
		$object = $raw[0];
		$action = $raw[1];

		if ($action=='historic')
		{
			$hObject = ORM::getObjectInstance($object);
			$title='Historique de l\'objet';
		}
		else
		{
			$title = '';
			$this->_getStringFromAttributes($title);
		}

		?><table class="title"><?php
		   ?><tr><?php
			   ?><td><?php
			   		echo $title;
			   ?></td><?php
			?></tr><?php
		?></table><?php
	}
}
