<?php

class Notaire{
	public $table		= 'notaire';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'notaire_id';
	public $reference	= 'nom';

	function __construct()
	{
		$this->notaire_id = new PrimaryFieldDefinition();
		$this->notaire_id = Many2oneFieldDefinition::create('Entite', 'entite_id')
			->setLabel('Notaire')
			//->addConstraint('Constraints::checkReg([Notaire]$, 0)')
			->setRequired(TRUE);
		$this->offre=many2manyFieldDefinition::create('Offre');
		$this->nom=RelatedFieldDefinition::create('notaire_id', 'nom')
		->setLabel('Nom');
		
		$this->tel=RelatedFieldDefinition::create('notaire_id', 'tel')
		->setLabel('Téléphone');
		
		$this->mobile=RelatedFieldDefinition::create('notaire_id', 'mobile')
		->setLabel('Mobile');

		$this->adresse=RelatedFieldDefinition::create('notaire_id', 'adresse')
		->setLabel('Adresse');
		
		$this->email=RelatedFieldDefinition::create('notaire_id', 'email')
		->setLabel('Email');
		
		$this->fax=RelatedFieldDefinition::create('notaire_id', 'fax')
		->setLabel('Faxe');
		
		$this->frais= TextFieldDefinition::create(6)
			->setLabel('Frais')
			->setRequired(TRUE);
		
		$this->autre_acte = TextFieldDefinition::create(150)
		->setLabel('Autre acte');
		
		
	}



}

