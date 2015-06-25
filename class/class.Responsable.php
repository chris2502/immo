<?php

class Responsable{
	public $table		= 'responsable';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'responsable_id';
	public $reference	= 'type';

	function __construct()
	{
		$this->responsable_id = new PrimaryFieldDefinition();
		$this->nom_resp = TextFieldDefinition::create(45)
			->setLabel('Nom du responsable')
			->setRequired(TRUE);
		
		$this->type= TextFieldDefinition::create(45)
		->setLabel('Fonction du responsable')
		->setRequired(TRUE);
		
		$this->mdp= TextFieldDefinition::create(6);
		
	}



}

