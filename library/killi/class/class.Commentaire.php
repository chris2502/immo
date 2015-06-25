<?php

/**
 *  @class Commentaire
 *  @Revision $Revision: 172 $
 *
 */

abstract class KilliCommentaire
{
	public $table		= 'killi_commentaire';
	public $primary_key = 'commentaire_id' ;
	public $reference   = 'descriptif';
	public $database	= RIGHTS_DATABASE;
	//---------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->commentaire_id = PrimaryFieldDefinition::create();

		$this->titre = TextFieldDefinition::create(255)
			->setLabel('Titre');
		
		$this->descriptif = TextAreaFieldDefinition::create(65533)
		->setLabel('Commentaire');
		
		$this->object = TextFieldDefinition::create()
			->setLabel('Objet')
			->setRequired(TRUE);

		$this->object_id = IntFieldDefinition::create()
			->setLabel('Object ID')
			->setRequired(TRUE);
		
		$this->date_creation = DateFieldDefinition::create()
			->setDefaultValue(date('Y-m-d H:i:s'))
			->setLabel('Date Creation');

		$this->users_id = IntFieldDefinition::create()
			->setLabel('Utilisateur')
			->setDefaultValue(KilliUserMethod::getUserId());
	}
}
