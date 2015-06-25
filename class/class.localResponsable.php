<?php

class localResponsable{
	public $table		= 'local_responsable';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'local_responsable_id';
	public $reference	= 'responsable_id';

	function __construct()
	{
		$this->local_responsable_id = new PrimaryFieldDefinition();
	
		$this->responsable_id=Many2oneFieldDefinition::create('Responsable', 'responsable_id')
		->setLabel('fonction du responsable')
		->setRequired(TRUE);
		
		$this->local_id=Many2oneFieldDefinition::create('Local', 'local_id')
		->setLabel('departement du local')
		->setRequired(TRUE);
		
		
	}



}

