<?php

class Commentaire{
	public $table		= 'commentaire';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'commentaire_id';
	public $reference	= 'titre_commentaire';

	function __construct()
	{
		$this->commentaire_id = new PrimaryFieldDefinition();

		$this->local_id = Many2oneFieldDefinition::create('local', 'local_id')
		->setLabel('Local');
		
		$this->mise_en_vente_id=Many2oneFieldDefinition::create('MiseEnVente', 'mise_en_vente_id')
		->setLabel('Local à revendre');
		
		$this->titre_commentaire =EnumFieldDefinition::create(array('', 'Non défini', 'Risque contentieux', 'Taxe', 'AG', 'Compteur', 'Travaux', 'Nro', 'Plaque', 'Entite:Prop, Agence, PCS, Syndic', 'notaire', 'offre', 'mandat', 'Acheteur'))
		->setRequired(TRUE)
		->setLabel('Titre du commentaire');
		
		$this->date_commentaire = DatetimeFieldDefinition::create()
		->setLabel('Date du commentaire')
		->setRequired(TRUE);
		
		$this->contenu =  TextareaFieldDefinition::create(500)
		->setLabel('Commentaire')
		->setRequired(TRUE);
		
		$this->rappel= BoolFieldDefinition::create()
		->setLabel('Rappel')
		->setDefaultValue(FALSE)
		->setRequired(TRUE);
		
		$this->date_rappel=DateFieldDefinition::create()
		->setLabel('Date de rappel');
		
		$this->statut_acquisition = RelatedFieldDefinition::create('local_id', 'statut_acquisition')
		->setLabel('Statut acquisition');
		
		$this->etape = TextFieldDefinition::create(45)
		->setLabel('Etape')
		//->setFunction('Commentaire', 'setEtape')
		->setRequired(TRUE);
		
		
		
	}



}

