<?php

/**
 *  @class Avoir
 *  @Revision $Revision: 4220 $
 *
 */

class LocalEntite
{
	public $table		= 'local_entite';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'local_entite_id';
	public $reference	= 'entite_id';
	//---------------------------------------------------------------------
	function __construct()
	{
		$this->local_entite_id = new PrimaryFieldDefinition();
		
		$this->local_id =Many2oneFieldDefinition::create('Local', 'local_id')
		->setLabel('Local')
		->setRequired(TRUE);
		
		$this->entite_id = Many2oneFieldDefinition::create('Entite', 'entite_id')
		->setLabel("nom de l'entité")
		->setRequired(TRUE);
		
		$this->type=RelatedFieldDefinition::create('entite_id', 'type')
		->setLabel("type de l'entite");
		
		$this->numero_rue=RelatedFieldDefinition::create('local_id', 'numero_rue')
		->setLabel('Numéro de rue du Local')
		->setRequired(TRUE);

		$this->type_rue=RelatedFieldDefinition::create('local_id', 'type_rue')
		->setLabel('Type de rue du Local')
		->setRequired(TRUE);
		
		$this->code_postal=RelatedFieldDefinition::create('local_id', 'code_postal')
		->setLabel('code postal du Local')
		->setRequired(TRUE);
		
		$this->commune=RelatedFieldDefinition::create('local_id', 'commune')
		->setLabel('commune du Local')
		->setRequired(TRUE);
		
		$this->niveau=RelatedFieldDefinition::create('local_id', 'niveau')
		->setLabel('Niveau du Local')
		->setRequired(TRUE);
		
		$this->statut_acquisition=RelatedFieldDefinition::create('local_id', 'statut_acquisition')
		->setLabel('statut_acquisition');
	}
	
}



