<?php

class OffreNotaire{
	public $table		= 'offre_notaire';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'offre_notaire_id';
	public $reference	= 'date_acte_authentique';

	function __construct()
	{
		
		$this->offre_notaire_id=PrimaryFieldDefinition::create();
		
		$this->offre_id=Many2oneFieldDefinition::create('Offre', 'offre_id')
		->setLabel('Offre')
		->setRequired(TRUE);
		
		$this->notaire_id=Many2oneFieldDefinition::create('Notaire', 'notaire_id')
		->setLabel('notaire')
		->setRequired(TRUE);
		
		$this->statut_acquisition= RelatedFieldDefinition::create('offre_id', 'statut_acquisition');
		$this->date_acte_authentique=DateFieldDefinition::create();


	}



}


