<?php

/**
 *  @class TypeModule
 *  @Revision $Revision: 3647 $
 *
 */

abstract class KilliTypeModule
{
	public $table		= 'killi_process_module_class';
	public $primary_key = 'class_id';
	public $database	= RIGHTS_DATABASE;
	public $reference	= 'name';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->class_id = new PrimaryFieldDefinition();

		$this->class_name = TextFieldDefinition::create(64)
				->setLabel('Class Name')
				->setRequired(TRUE);

		$this->name = TextFieldDefinition::create(255)
				->setLabel('Nom')
				->setRequired(TRUE);

		$this->desc = TextareaFieldDefinition::create()
				->setLabel('Description');

		$this->fa_icon = TextFieldDefinition::create(64)
				->setLabel('FontAwesome Icon');
	}
}
