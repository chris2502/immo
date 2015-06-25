<?php

/**
 *  @class Travaux
 *  @Revision $Revision: 4220 $
 *
 */

class Travaux
{
	public $table		= 'travaux';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'travaux_id';
	public $reference	= 'typeTravaux_id';
	//---------------------------------------------------------------------
	function __construct()
	{
		$this->travaux_id = new PrimaryFieldDefinition();

		$this->typeTravaux_id = Many2oneFieldDefinition::create('typeTravaux', 'typeTravaux_id')
				->setLabel('Type de travaux')
				->setRequired(TRUE);

		$this->local_id = Many2oneFieldDefinition::create('local', 'local_id')
		->setLabel('Local')
		->setRequired(TRUE);
		
		$this->mise_en_vente_id = Many2oneFieldDefinition::create('MiseEnVente', 'mise_en_vente_id')
		->setLabel('Local acquis')
		->setRequired(FALSE);
		
		$this->projection_travaux = TextFieldDefinition::create(25)
				->setLabel('projection de travaux')
				->setRequired(FALSE);
		
		$this->description_travaux = TextFieldDefinition::create(500)
			->setLabel('description des travaux')
			->setRequired(TRUE);
		
		$this->date_debut_travaux = DateFieldDefinition::create()
		->setLabel('Date de début des travaux')
		->setRequired(FALSE);
		
		$this->date_fin_travaux = DateFieldDefinition::create()
		->setLabel('Date de fin des travaux')
		->setRequired(FALSE);

		$this->statut_acquisition = RelatedFieldDefinition::create('local_id', 'statut_acquisition')
		->setLabel('Statut acquisition');
	}
	
}
