<?php

/**
 *  @class Actionmenu
 *  @Revision $Revision: 4646 $
 *
 */

abstract class KilliActionmenu
{
	public $table		= 'killi_actionmenu';
	public $primary_key = 'actionmenu_id';
	public $database	= RIGHTS_DATABASE;
	public $order		= array('actionmenu_parent','actionmenu_label');
	public $reference	= 'actionmenu_label';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->actionmenu_id = new PrimaryFieldDefinition();

		$this->actionmenu_name = TextFieldDefinition::create(32)
				->setLabel('Objet cible');

		$this->actionmenu_function = TextFieldDefinition::create(64)
				->setLabel('Action')
				->setDefaultValue('edit')
				->setRequired(TRUE);

		$this->actionmenu_label = TextFieldDefinition::create(64)
				->setLabel('Libellé')
				->setRequired(TRUE);

		$this->actionmenu_parent = Many2oneFieldDefinition::create('Actionmenu')
				->setLabel('Menu parent');
	}
}
