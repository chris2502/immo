<?php
class Acquisition{
	public $table		= 'acquisition';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'acquisition_id';
	public $reference	= 'adresse';

	
	
	 public function setDomain(){
		 //---Si limiter à population d'un noeuds du workflow
		 if (isset($_GET['workflow_node_id'])){
		 $table     = array(DBSI_DATABASE.'.killi_workflow_token');
		 $join_list = array(DBSI_DATABASE.'.acquisition.acquisition_id='.DBSI_DATABASE.'.killi_workflow_token.id');
		 $filter    = array(array(DBSI_DATABASE.'.killi_workflow_token.node_id','=',$_GET['workflow_node_id']));
		
		 $this->domain_with_join = array('table'=>$table,
		 'join'=>$join_list,
		 'filter'=>$filter);
		 }
	 }
	 
	function __construct()
	{
		$this->acquisition_id = new PrimaryFieldDefinition();
		$this->acquisition_id=Many2oneFieldDefinition::create('Local', 'local_id')
			->setLabel('Adresse')
			->setRequired(TRUE);
		
		/*************************************************************************************************/
		
		$this->nro_id = RelatedFieldDefinition::create('acquisition_id', 'nro_id');
		$this->plaque_id = RelatedFieldDefinition::create('acquisition_id', 'plaque_id');
		$this->adresse=RelatedFieldDefinition::create('acquisition_id', 'adresse');
		$this->niveau = RelatedFieldDefinition::create('acquisition_id', 'niveau');
		$this->surface_brut = RelatedFieldDefinition::create('acquisition_id', 'surface_brut');
		$this->surface_m_carre = RelatedFieldDefinition::create('acquisition_id', 'surface_m_carre');
		$this->estimation_prix = RelatedFieldDefinition::create('acquisition_id', 'estimation_prix');
		$this->moyen_marche = RelatedFieldDefinition::create('acquisition_id', 'moyen_marche');
		$this->prix_demande = RelatedFieldDefinition::create('acquisition_id', 'prix_demande');
		$this->cle_acces = RelatedFieldDefinition::create('acquisition_id', 'cle_acces');
		$this->exploite = RelatedFieldDefinition::create('acquisition_id', 'exploite');
		
		/****************************************************************************************************/
		
		$this->date_butoir_fisc = DateFieldDefinition::create()
		->setLabel('date butoire fiscale')
		->setRequired(TRUE);
		
		$this->surface_tiers = TextFieldDefinition::create(11)
		->setLabel('Surface tiers');
		
		$this->statut_acquisition = WorkflowStatusFieldDefinition::create('wfacquisition', 'id')
		->setLabel('Statut acquisition');
		
	}
	
	
	
}

