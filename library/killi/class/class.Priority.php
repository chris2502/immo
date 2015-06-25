<?php

/**
 *  @class Priority
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliPriority
{
	public $table		= 'killi_priority';
	public $primary_key = 'killi_priority_id';
	public $database	= RIGHTS_DATABASE;
	public $reference	= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_priority_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(32)
				->setLabel('Libellé')
				->setRequired(TRUE);
	}
}
