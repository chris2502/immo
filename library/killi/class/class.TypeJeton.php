<?php

/**
 *  @class TypeJeton
 *  @Revision $Revision: 644 $
 *
 */

class KilliTypeJeton
{
	public $description  = 'Types de Jeton';
	public $table        = 'killi_type_jeton';
	public $database     = DBSI_DATABASE;
	public $primary_key  = 'type_jeton_id';
	public $reference    = 'name';

	function __construct()
	{
		$this->type_jeton_id = new PrimaryFieldDefinition();
		
		$this->name	= TextFieldDefinition::create(255)
			->setLabel('Nom')
			->setRequired(TRUE);
		
		$this->url = TextFieldDefinition::create(255)
			->setLabel('URL');
		
		$this->object = TextFieldDefinition::create(64)
			->setLabel('Object')
			->setRequired(TRUE);
		
		$this->method = TextFieldDefinition::create(255)
			->setLabel('Method');
	}
}