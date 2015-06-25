<?php

/**
 *  @class JetonLog
 *  @Revision $Revision: 644 $
 *
 */

class KilliJetonLog
{
	public $description  = 'Jeton Log';
	public $table        = 'killi_jeton_log';
	public $database     = DBSI_DATABASE;
	public $primary_key  = 'jeton_log_id';
	public $reference    = 'jeton_id';
	
	function __construct()
	{
		$this->jeton_log_id = new PrimaryFieldDefinition();
			
		$this->jeton_id = IntFieldDefinition::create()
			->setLabel('Jeton ID')
			->setRequired(TRUE);
		
		$this->killi_user_id = IntFieldDefinition::create()
			->setLabel('Utilisateur')
			->setRequired(TRUE);
		
		$this->date_creation = DatetimeFieldDefinition::create()
			->setLabel('Date de création')
			->setEditable(FALSE);
		
		$this->code = TextAreaFieldDefinition::create(255)
			->setLabel('Code')
			->setRequired(TRUE);
		
		$this->actif = BoolFieldDefinition::create()
			->setLabel('Actif')
			->setRequired(TRUE);
		
		$this->object = TextFieldDefinition::create()
			->setLabel('object');
		
		$this->method = TextFieldDefinition::create()
			->setLabel('method');
		
		$this->object_id = IntFieldDefinition::create()
			->setLabel('Object ID')
			->setRequired(FALSE);
		
		$this->end_time = DatetimeFieldDefinition::create()
			->setLabel('Date d\'expiration')
			->setRequired(FALSE);
	}
}
