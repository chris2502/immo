<?php

/**
 *  @class Jeton
 *  @Revision $Revision: 644 $
 *
 */

class KilliJeton
{
	public $description  = 'Jeton (Codes d\'administration)';
	public $table        = 'killi_jeton';
	public $database     = DBSI_DATABASE;
	public $primary_key  = 'jeton_id';
	public $reference    = 'code';
	
	function __construct()
	{
		$this->jeton_id = new PrimaryFieldDefinition();
		
		$this->code = TextAreaFieldDefinition::create(255)
			->setLabel('Code')
			->setRequired(TRUE);
		
		$this->actif = BoolFieldDefinition::create()
			->setLabel('Actif')
			->setRequired(TRUE)
			->setDefaultValue(1);

		$this->type_jeton_id = Many2OneFieldDefinition::create()
			->setLabel('Type de jeton')
			->setRequired(TRUE)
			->setObjectRelation('TypeJeton');
		
		$this->name = RelatedFieldDefinition::create('type_jeton_id')
			->setFieldRelation('name');
		
		$this->url = RelatedFieldDefinition::create('type_jeton_id')
			->setFieldRelation('url');
		
		$this->object = RelatedFieldDefinition::create('type_jeton_id')
			->setFieldRelation('object');
		
		$this->method = RelatedFieldDefinition::create('type_jeton_id')
			->setFieldRelation('method');

		$this->destinataire_id = Many2OneFieldDefinition::create()
			->setLabel('Destinataire du jeton')
			->setRequired(TRUE)
			->setObjectRelation('cascontact')
			->setFieldRelation('killi_user_id');

		$this->killi_user_id = Many2OneFieldDefinition::create()
			->setLabel('Utilisateur')
			->setEditable(FALSE)
			->setObjectRelation('user');
		if (isset($_SESSION['_USER']['killi_user_id']['value']))
		{
			$this->killi_user_id->setDefaultValue($_SESSION['_USER']['killi_user_id']['value']);
		}
		
		$this->date_creation = DatetimeFieldDefinition::create()
			->setLabel('Date de création')
			->setEditable(FALSE);
		
		$this->date_modification = DatetimeFieldDefinition::create()
			->setLabel('Date modification')
			->setEditable(FALSE);
		
		$this->object_id = IntFieldDefinition::create()
			->setLabel('Object ID')
			->setRequired(FALSE);
		
		$this->end_time = DatetimeFieldDefinition::create()
			->setLabel('Date d\'expiration')
			->setRequired(FALSE);
	}
}
