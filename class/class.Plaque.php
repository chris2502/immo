<?php

class Plaque{
	public $table		= 'plaque';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'plaque_id';
	public $reference	= 'libelle_plaque';
	
	function __construct()
	{
		$this->plaque_id = new PrimaryFieldDefinition();
		
		$this->libelle_plaque= TextFieldDefinition::create(9)
		->addConstraint('Constraints::checkReg(PLA[0-9]{2}_[0-9]{3}|(POP)$, 0)');
	}
	
	
}