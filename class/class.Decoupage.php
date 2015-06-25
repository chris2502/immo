<?php
class Decoupage{
	public $table		= 'decoupage';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'decoupage_id';
	public $reference	= 'libelle';

	function __construct()
	{
		$this->decoupage_id = new PrimaryFieldDefinition();

		$this->libelle = TextFieldDefinition::create(45)
		->setRequired(TRUE);

		$this->mise_en_vente_id=Many2oneFieldDefinition::create('MiseEnVente', 'mise_en_vente_id')
		->setLabel('Local');

	}



}

