<?php
class Compteur{
	public $table		= 'compteur';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'compteur_id';
	public $reference	= 'type';

	function __construct()
	{
		$this->compteur_id = new PrimaryFieldDefinition();

		$this->acquisition_id = Many2oneFieldDefinition::create('acquisition', 'acquisition_id')
		->setLabel('Local acquis')
		->setRequired(TRUE);
		
		
		$this->type = EnumFieldDefinition::create(array('ERDF','EAU','GAZ'))
		->setLabel('Type de compteur')
		->setRequired(TRUE);
	}
	
	
	
}

