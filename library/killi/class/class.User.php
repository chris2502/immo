<?php

/**
 *  @class KilliUser
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliUser
{
	public $table		= 'killi_user';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'killi_user_id';
	public $reference	= 'nom_complet';
	public $order		= array('nom','prenom');
	//---------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_user_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(24)
				->setLabel('Nom')
				->setRequired(TRUE)
				->addConstraint('Constraints::checkAlphaNum()')
				->setEditable(!defined('CAS_SERVER_URL'));

		$this->prenom = TextFieldDefinition::create(24)
				->setLabel('Prénom')
				->setRequired(TRUE)
				->addConstraint('Constraints::checkFirstName()')
				->setEditable(!defined('CAS_SERVER_URL'));

		$this->nom_complet = TextFieldDefinition::create()
				->setLabel('Utilisateur')
				->setEditable(FALSE)
				->setSQLAlias('CONCAT(%nom%, " ", %prenom%)');

		$this->mail = TextFieldDefinition::create(128)
				->setLabel('Courriel')
				->setRequired(TRUE)
				->setEditable(!defined('CAS_SERVER_URL'));

		$this->login = TextFieldDefinition::create(16)
				->setLabel('Identifiant')
				->setRequired(TRUE)
				->addConstraint('Constraints::checkSize(3,16)')
				->addConstraint('Constraints::checkAlphaNum()')
				->setEditable(!defined('CAS_SERVER_URL'));

		$this->password = PasswordFieldDefinition::create(40)
				->setLabel('Mot de passe')
				->setRequired(TRUE)
				->addConstraint('Constraints::checkSize(6,40)')
				->setCryptMethod((defined('CRYPT_PASSWORD_METHOD'))?CRYPT_PASSWORD_METHOD:null)
				->setEditable(!defined('CAS_SERVER_URL'));

		$this->actif = BoolFieldDefinition::create()
				->setLabel('Autorisation de connexion')
				->setDefaultValue(FALSE)
				->setEditable(!defined('CAS_SERVER_URL'));

		$this->profil_id = Many2manyFieldDefinition::create('profil')
				->setLabel('Profils');

		$this->last_connection = DatetimeFieldDefinition::create()
				->setLabel('Dernière connexion')
				->setEditable(FALSE);

		$this->certificat_duree = IntFieldDefinition::create()
				->setLabel('Durée validité certif')
				->setRequired(TRUE);

		$this->creation_date = DatetimeFieldDefinition::create()
				->setLabel('Date de création')
				->setEditable(FALSE);
	}
}
