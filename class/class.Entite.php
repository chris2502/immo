<?php

class Entite{
	public $table		= 'entite';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'entite_id';
	public $reference	= 'nom';

	function __construct()
	{
		$this->entite_id = new PrimaryFieldDefinition();

		$this->type = Many2oneFieldDefinition::create('typeEntite', 'type_entite_id')
		->setLabel('type')
		->setRequired(TRUE);
		$this->local=Many2manyFieldDefinition::create('Local');

		$this->nom = TextFieldDefinition::create(45)
		->setLabel('nom')
		->setRequired(TRUE);
		
		$this->tel = TextFieldDefinition::create(10)
		->setLabel('téléphone')
		->addConstraint('Constraints::checkReg(0[1-589][0-9]{8}$, 0)');
		
		$this->mobile = TextFieldDefinition::create(10)
		->addConstraint('Constraints::checkReg(0[67][0-9]{8}$, 0)');
		
		$this->adresse = TextFieldDefinition::create(100);
		
		$this->email = TextFieldDefinition::create(50)
		->addConstraint('Constraints::checkReg(.*@.*\..*$, 0)');
		
		$this->fax = TextFieldDefinition::create(10)
		->setLabel('faxe');
		
		//$this->statut_acquisition=RelatedFieldDefinition::create()
	}



}

