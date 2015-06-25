<?php

class Offre{
	public $table		= 'offre';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'offre_id';
	public $reference	= 'montant_offre';

	function __construct()
	{
		$this->offre_id = new PrimaryFieldDefinition();
		
		$this->local_id=Many2oneFieldDefinition::create('local', 'local_id')
		->setLabel('Local');
		
		$this->mise_en_vente_id = Many2oneFieldDefinition::create('MiseEnVente', 'mise_en_vente_id')
		->setLabel('Local à revendre')
		->setRequired(FALSE);
		
		$this->notaire=many2manyFieldDefinition::create('Notaire');
		//non utilisé dans le xml
		$this->offre_notaire_id=One2manyFieldDefinition::create('OffreNotaire', 'offre_id');
		
		$this->montant_offre=TextFieldDefinition::create(11)
		->setLabel("Montant de l'offre")
		->addConstraint('Constraints::checkReg(([0-9]+\.[0-9]+)|[0-9]+$, 0)');
		
		$this->prix_location=TextFieldDefinition::create(11)
		->setLabel("Prix de location")
		->addConstraint('Constraints::checkReg(([0-9]+\.[0-9]+)|[0-9]+$, 0)');
		
		$this->frai_fai= TextFieldDefinition::create(11)
			->setLabel('Frai du FAI')
			->addConstraint('Constraints::checkReg(([0-9]+\.[0-9]+)|[0-9]+$, 0)');

		
		$this->surface_m_carre=RelatedFieldDefinition::create('local_id', 'surface_m_carre')
		->setLabel("Surface m²");
		
	/******************************************************************************************************/	
		$this->prixLocationByCarre=TextFieldDefinition::create()
		->setLabel("Prix de location/m²")
		->setFunction('offre', 'setPrixLocationByCarre');
		
		$this->montantOffreByCarre=TextFieldDefinition::create()
		->setLabel("Montant de l'offre/m²")
		->setFunction('offre', 'setMontantOffreByCarre');
		
		$this->notaireCheck=BoolFieldDefinition::create()
		->setFunction('offre', 'setNotaireCheck');
		
		$this->authentiqueCheck=BoolFieldDefinition::create()
		->setFunction('Offre', 'setAuthentiqueCheck');
	/****************************************************************************************************/
		
		$this->validation_commission= EnumFieldDefinition::create(array('','Accepté','En etude','Refusé'))
		->setLabel('Validation de la commission');
		
		$this->reponse_a_offre = EnumFieldDefinition::create(array('', 'Accepté', 'En etude', 'En attente','Refusé'))
		->setLabel('Reponse_a_offre');
		
		
		/*
		$this->validation_commission= EnumFieldDefinition::create(array(' ', 'Accepter','refuser','en étude','renvoyer','autre'))
		->setLabel('validation de la commission')
		->setRequired(TRUE);
		
		$this->reponse_a_offre = EnumFieldDefinition::create(array(' ', 'Accepter','refuser','en étude','renvoyer','autre'))
		->setLabel('reponse_a_offre')
		->setRequired(TRUE);
		*/
		$this->date_validation_commission = DateFieldDefinition::create()
		->setLabel('Date validation commission');
		
		$this->date_retour_offre = DateFieldDefinition::create()
		->setLabel('Date retour de l\'offre');
		
		$this->date_promesse = DateFieldDefinition::create()
		->setLabel('Date promesse');
		
		$this->statut_acquisition = RelatedFieldDefinition::create('local_id', 'statut_acquisition')
		->setLabel('Statut acquisition');
		
		$this->statut_revente = RelatedFieldDefinition::create('mise_en_vente_id', 'statut_revente')
		->setLabel('Statut Revente');
		
	}



}

