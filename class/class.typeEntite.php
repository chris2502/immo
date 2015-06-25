<?php

class typeEntite{
	public $table		= 'type_entite';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'type_entite_id';
	public $reference	= 'type_entite_nom';

	function __construct()
	{
		$this->type_entite_id = new PrimaryFieldDefinition();
	
		$this->type_entite_nom=TextFieldDefinition::create(25)
		->setLabel("type d'entite")
		->setRequired(TRUE);
				
	}



}

