<?php

/**
 *  @class UserProfil
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliUserProfil
{
	public $table				  = 'killi_user_profil';
	public $database			   = RIGHTS_DATABASE;
	public $primary_key			= 'user_profil_id' ;
	//---------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->user_profil_id = new PrimaryFieldDefinition();

		$this->killi_user_id = Many2oneFieldDefinition::create('user')
				->setLabel('Utilisateur')
				->setRequired(TRUE);

		$this->killi_profil_id = Many2oneFieldDefinition::create('profil')
				->setLabel('Profil')
				->setRequired(TRUE);
	}
}
