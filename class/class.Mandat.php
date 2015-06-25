<?php

class Mandat{
	public $table		= 'mandat';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'mandat_id';
	public $reference	= 'detenteur';
	function __construct()
	{
		$this->mandat_id = new PrimaryFieldDefinition();
	
		$this->montant = TextFieldDefinition::create(11)
		->setLabel('Montant')
		->setRequired(TRUE);
			
		$this->detenteur = TextFieldDefinition::create(45)
		->setLabel('Taxe foncière')
		->setRequired(FALSE);
		
			
		$this->validite = TextFieldDefinition::create(45)
		->setLabel('Validité')
		->setRequired(TRUE);
				
		$this->mise_en_vente_id = Many2oneFieldDefinition::create('MiseEnVente', 'mise_en_vente_id')
		->setLabel('Adresse')
		->setRequired(TRUE);
		
	}
	
	
	
}