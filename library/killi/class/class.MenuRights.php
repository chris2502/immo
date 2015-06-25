<?php

/**
 *  @class MenuRights
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliMenuRights
{
	public $primary_key = 'actionmenu_id';
	//-------------------------------------------------------------------------
	function setDomain(){}
	//---------------------------------------------------------------------
	function __construct()
	{
		$this->actionmenu_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create()
				->setLabel('Menu')
				->setRequired(TRUE)
				->setEditable(FALSE);

		$this->view = BoolFieldDefinition::create()
				->setLabel('Affiché');
	}
}
