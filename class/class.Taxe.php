<?php

class Taxe{
	public $table		= 'taxe';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'taxe_id';
	public $reference	= 'montant';

	function __construct()
	{
		$this->taxe_id = new PrimaryFieldDefinition();
	
		$this->type_taxe_id=Many2oneFieldDefinition::create('typetaxe', 'type_taxe_id')
		->setLabel('Type de taxe')
		->setRequired(TRUE);
		
		
		$this->montant=TextFieldDefinition::create('11')
		->setLabel('Montant')
		->setRequired(TRUE);
		
		$this->local_id=Many2oneFieldDefinition::create('local', 'local_id')
		->setLabel('Local');
		$this->statut_acquisition=RelatedFieldDefinition::create('local_id', 'statut_acquisition')
			->setLabel('Statut acquisition');
		
	}



}

