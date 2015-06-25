<?php

class MiseEnVente{
	public $table		= 'mise_en_vente';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'mise_en_vente_id';
	public $reference	= 'adresse';

	function __construct()
	{
		$this->mise_en_vente_id = new PrimaryFieldDefinition();
		$this->acquisition_id=Many2oneFieldDefinition::create('acquisition', 'acquisition_id')
			->setLabel('Local acquis')
			->setRequired(TRUE);
		$this->offre_id=One2manyFieldDefinition::create('Offre','mise_en_vente_id')
		->setLabel('Offre');
		
		$this->mandat_id=One2manyFieldDefinition::create('Mandat', 'mise_en_vente_id');
		
		$this->commentaire_id=One2manyFieldDefinition::create('Commentaire', 'mise_en_vente_id')
		->setLabel("Commentaire");
		/*********************************************************************************/
		
		$this->nro_id = RelatedFieldDefinition::create('acquisition_id', 'nro_id');
		$this->plaque_id = RelatedFieldDefinition::create('acquisition_id', 'plaque_id');
		$this->adresse = RelatedFieldDefinition::create('acquisition_id', 'adresse');
		
		/********************************************************************************/
		
		$this->notaire=BoolFieldDefinition::create()
		->setLabel('Contrainte de passage à authentique')
		->setFunction('MiseEnVente', 'setNotaire');
		
		$this->authentique=BoolFieldDefinition::create()
		->setLabel('Authentique pour passage revendu')
		->setFunction('MiseEnVente', 'setAuthentique');
		/********************************************************************************/
		
		$this->surface_brute = TextFieldDefinition::create(14)
		->setLabel('Surface brute à revendre')
		->setRequired(FALSE)
		->addConstraint('Constraints::checkReg([0-9]+$, 0)');
		
		$this->type_surface=TextFieldDefinition::create('45')
		->setLabel('type de Surface');
		
		$this->estimation_bien=TextFieldDefinition::create('11')
		->setLabel('Estimation du prix du bien');
		
		$this->estimation_valide_ci=TextFieldDefinition::create('11')
		->setLabel("Estimation valide par CI");
		
		$this->porte_anti_squat=TextFieldDefinition::create('1')
		->setLabel('Porte anti-squat');
		
		$this->alarme_surface=TextFieldDefinition::create('11')
		->setLabel('Alarme surface');
		
		$this->prix_rentabilite=TextFieldDefinition::create('11')
		->setLabel('Rentabilité');
		
		$this->niveau=TextFieldDefinition::create('3')
		->addConstraint('Constraints::checkReg(-?[0-9]+$, 0)')
		->setLabel('Niveau');
		
		$this->surface_m_carre=TextFieldDefinition::create('9')
		->setLabel("Surface_carrez");
		
		$this->estimation_prix=TextFieldDefinition::create('9')
		->setLabel("Estimation du prix");
		
		$this->moyen_marche=TextFieldDefinition::create('9')
		->setLabel("Moyen du marché");
		
		$this->prix_demande=TextFieldDefinition::create('9')
		->setLabel("Prix proposé");
		
		$this->cle_acces=TextFieldDefinition::create('45')
		->setLabel("Clé/accès");
		
		$this->revendu=BoolFieldDefinition::create()
		->setLabel("Revendu")
		->setDefaultValue(FALSE)
		->setRequired(True);
		
		$this->valide=BoolFieldDefinition::create()
		->setLabel("Dossier validé")
		->setDefaultValue(FALSE)
		->setRequired(TRUE);
		
		$this->statut_revente = WorkflowStatusFieldDefinition::create('wfrevente', 'id')
		->setLabel('Statut Revente');
	}



}

