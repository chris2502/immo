<?php

class AcheteurMiseEnvente{
	public $table		= 'acheteur_mise_en_vente';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'acheteur_mise_en_vente_id';
	public $reference	= 'date_revente';

	function __construct()
	{
		$this->acheteur_mise_en_vente_id = new PrimaryFieldDefinition();
		
		$this->acheteur_id = Many2oneFieldDefinition::create('acheteur', 'acheteur_id')
		->setLabel('Acheteur')
		->setRequired(TRUE);
		
		$this->mise_en_vente_id = Many2oneFieldDefinition::create('MiseEnVente', 'mise_en_vente_id')
		->setLabel('Local')
		->setRequired(TRUE);
		
		$this->date_revente = DateFieldDefinition::create(11)
		->setRequired(TRUE);
		
	}



}

