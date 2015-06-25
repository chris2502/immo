<?php

class Acheteur{
	public $table		= 'acheteur';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'acheteur_id';
	public $reference	= 'code_acheteur';

	function __construct()
	{
		$this->acheteur_id = new PrimaryFieldDefinition();
		
		$this->code_acheteur = TextFieldDefinition::create(45)
		->setRequired(TRUE);
		
		$this->leve=TextFieldDefinition::create(25);
		
	}



}

