<?php

class Local{
	public $table		= 'local';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'local_id';
	public $reference	= 'adresse';
	
	/*
	public function setDomain()
	{
		//---Si limiter à population d'un noeuds du workflow
		if (isset($_GET['workflow_node_id']))
		{
			$table     = array(DBSI_DATABASE.'.killi_workflow_token');
			$join_list = array(DBSI_DATABASE.'.local.local_id='.DBSI_DATABASE.'.killi_workflow_token.id');
			$filter    = array(array(DBSI_DATABASE.'.killi_workflow_token.node_id','=',$_GET['workflow_node_id']));
		
			$this->domain_with_join = array('table'=>$table,
					'join'=>$join_list,
					'filter'=>$filter);
		}
	}
	*/
	
	function __construct()
	{
		/*******************************************clé et relation*********************************************/
		$this->local_id = new PrimaryFieldDefinition();
	
		$this->nro_id = Many2oneFieldDefinition::create('nro', 'nro_id')
		->setLabel('Trigramme')
		->setRequired(FALSE);
		
		$this->plaque_id = Many2oneFieldDefinition::create('plaque', 'plaque_id')
			->setLabel('Plaque');
		
		$this->entite=Many2manyFieldDefinition::create('Entite');
		
		$this->responsable=Many2manyFieldDefinition::create('Responsable');
		
		$this->assemblee_generale_id=One2manyFieldDefinition::create('AssembleeGenerale','local_id')
		->setLabel('Date assemblée générale');
		
		$this->taxe_id=One2manyFieldDefinition::create('Taxe','local_id')
		->setLabel('Taxe');
		
		$this->offre_id=One2manyFieldDefinition::create('Offre','local_id')
		->setLabel('Offre');
		
		$this->travaux_id=One2manyFieldDefinition::create('Travaux', 'local_id')
		->setLabel('Travaux');
		
		$this->commentaire_id=One2manyFieldDefinition::create('Commentaire', 'local_id')
		->setLabel("Commentaire");		
		/**********************************setFunction*****************************************************************/
		$this->travaux=BoolFieldDefinition::create()
		->setLabel('Travaux')
		->setFunction('local', 'setTravaux');
		
		$this->myEntite=BoolFieldDefinition::create()
		->setLabel('Entite')
		->setFunction('local', 'setMyEntite');
		
		//pour mettre les champs (champs calculés) qui n'appartiennent pas à la table
		$this->estimationPrixByCarre = TextFieldDefinition::create()
		->setLabel('Estimation Prix/m²')
		->setFunction('local', 'setEstimationPrixByCarre');
		
		$this->acquisition_id = IntFieldDefinition::create()
		->setFunction('local', 'setAcquisitionId');
		
		$this->prixDemandeByCarre = TextFieldDefinition::create()
		->setLabel('Prix demandé/m²')
		->setFunction('local', 'setPrixDemandeByCarre');
		
		$this->adresse=TextFieldDefinition::create()
		->setLabel('Adresse')
		->setFunction('local', 'setAdresse');
		
		$this->myOffre=BoolFieldDefinition::create()
		->setLabel('Offre')
		->setFunction('local', 'setMyOffre');
		
		$this->dateAuthentique=BoolFieldDefinition::create()
		->setLabel('Date authentique')
		->setFunction('local', 'setDateAuthentique');
		
		/***************************************Champs de la table local*************************************************************/
			
		$this->departement = TextFieldDefinition::create(2)
		->setLabel('Département')
		->addConstraint('Constraints::checkReg([0-9]{2}$, 0)')
		->setRequired(TRUE);
		
		$this->numero_rue = TextFieldDefinition::create(5)
			->setLabel('Numéro de rue')
			->addConstraint('Constraints::checkReg([0-9]+$, 0)')
			->setRequired(TRUE);
		
		$this->type_rue = TextFieldDefinition::create(45)
		->setLabel('Type de rue')
		->setRequired(TRUE);
		
		$this->nom_rue = TextFieldDefinition::create(45)
		->setLabel('Nom de rue')
		->setRequired(TRUE);
		
		$this->code_postal = TextFieldDefinition::create(5)
		->setLabel('Code postal')
		->addConstraint('Constraints::checkReg([0-9]{5}$, 0)')
		->setRequired(TRUE);
		
		$this->commune = TextFieldDefinition::create(45)
		->setLabel('Commune')
		->setRequired(TRUE);
		$this->niveau = TextFieldDefinition::create(3)
		->setLabel('Niveau')
		->addConstraint('Constraints::checkReg(-?[0-9]+$, 0)');
		
		$this->surface_brut = TextFieldDefinition::create(14)
		->setLabel('Surface brute')
		->setRequired(FALSE)
		->addConstraint('Constraints::checkReg([0-9]+$, 0)');
		
		$this->surface_m_carre = TextFieldDefinition::create(10)
		->setLabel('Surface Carrez')
		->setRequired(FALSE)
		->addConstraint('Constraints::checkReg([0-9]+$, 0)')
		->addConstraint('LocalMethod::checkSurface()');
		
		
		$this->estimation_prix = TextFieldDefinition::create(11)
		->setLabel('Estimation du prix')
		->addConstraint('Constraints::checkReg([0-9]+$, 0)');
		
		$this->moyen_marche = TextFieldDefinition::create(11)
		->setLabel('Moyenne du marché')
		->addConstraint('Constraints::checkReg([0-9]+$, 0)');
		
		$this->prix_demande = TextFieldDefinition::create(11)
		->setLabel('Prix demandé')
		->addConstraint('Constraints::checkReg([0-9]+$, 0)');
		
		$this->cle_acces = TextFieldDefinition::create(45)
		->setLabel('Cle/Acces');
		
		$this->exploite = BoolFieldDefinition::create()
		->setLabel('Exploite')
		->setDefaultValue(FALSE);
		
		//$this->travaux = Many2ManyFieldDefinition::create('Travaux');
		/**************************workflow***************************************************/
		$this->statut_acquisition = WorkflowStatusFieldDefinition::create('wfacquisition', 'id')
			->setLabel('Statut acquisition');
	}
	
	
	
}