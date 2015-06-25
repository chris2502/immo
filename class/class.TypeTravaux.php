<?php

/**
 *  @class Effectuer
 *  @Revision $Revision: 4220 $
 *
 */

class TypeTravaux
{
	public $table		= 'typeTravaux';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'typeTravaux_id';
	public $reference	= 'type';
	//---------------------------------------------------------------------
	function __construct()
	{
		$this->typeTravaux_id = new PrimaryFieldDefinition();

		$this->type = TextFieldDefinition::create(45)
				->setLabel('type')
				->setRequired(TRUE);	
	}
	
}
