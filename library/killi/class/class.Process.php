<?php

/**
 *  @class Process
 *  @Revision $Revision: 3458 $
 *
 */

abstract class KilliProcess
{
	public $table			= 'killi_process';
	public $database		= RIGHTS_DATABASE;
	public $primary_key		= 'process_id';
	public $reference		= 'name';
	public $order			= array('name');
	//---------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->process_id = new PrimaryFieldDefinition();

		$this->name = TextFieldDefinition::create(24)
				->setLabel('Nom')
				->setRequired(TRUE)
				->addConstraint('Constraints::checkAlphaNum()');

		$this->internal_name = TextFieldDefinition::create(64)
				->setLabel('Nom interne du process')
				->setRequired(TRUE)
				->addConstraint('Constraints::checkAlphaNum()');

		$this->module_depart_id	= Many2OneFieldDefinition::create('ProcessModule')
				->setLabel('Module')
				->setRequired(FALSE);

		$this->actif = BoolFieldDefinition::create()
				->setLabel('actif')
				->setDefaultValue(FALSE);
	}
}
