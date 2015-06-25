<?php

/**
 *  @class MacroFilter
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliMacroFilter
{
	public $table			= 'killi_macro_filter';
	public $database		= RIGHTS_DATABASE;
	public $primary_key		= 'killi_macro_filter_id';
	public $order			= array('descript');
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_macro_filter_id = new PrimaryFieldDefinition();

		$this->view_name = TextFieldDefinition::create(64)
				->setLabel('Nom de la vue')
				->setRequired(TRUE);

		$this->filter = TextFieldDefinition::create()
				->setLabel('Filtre')
				->setRequired(TRUE);

		$this->descript = TextFieldDefinition::create(64)
				->setLabel('Description')
				->setRequired(TRUE);
	}
}
