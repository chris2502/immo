<?php

class TypeTaxe{
	public $table		= 'type_taxe';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'type_taxe_id';
	public $reference	= 'nom_taxe';
	
	function __construct()
	{
		$this->type_taxe_id = new PrimaryFieldDefinition();
		
		$this->nom_taxe= TextFieldDefinition::create(45);
		
	}
	
	
}