<?php

class AssembleeGenerale{
	public $table		= 'assemblee_generale';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'assemblee_generale_id';
	public $reference	= 'date_ag';

	function __construct()
	{
		$this->assemblee_generale_id = new PrimaryFieldDefinition();
		
		$this->type = TextFieldDefinition::create(45)
		->setLabel("Type d'assemblée");
		$this->date_ag=DateFieldDefinition::create()
		->setLabel("Date de l'assemblée générale")
		->setRequired(FALSE);
		$this->local_id=Many2oneFieldDefinition::create('local', 'local_id')
		->setLabel('Local')
		->setRequired(TRUE);
		
		
		$this->statut_acquisition = RelatedFieldDefinition::create('local_id', 'statut_acquisition')
		->setLabel('Statut acquisition');
	}



}

