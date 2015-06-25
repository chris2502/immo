<?php

/**
 *  @class Profil
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliProfil
{
	public $table		= 'killi_profil';
	public $primary_key = 'killi_profil_id';
	public $database	= RIGHTS_DATABASE;
	public $order		= array('nom');
	public $reference	= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain()
	{
		if (isset($_SESSION['_USER']) && !in_array(ADMIN_PROFIL_ID, $_SESSION['_USER']['profil_id']['value']))
		{
			$this->object_domain = array(
				array('killi_profil_id', '<>', ADMIN_PROFIL_ID)
			);
		}
	}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_profil_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(32)
				->setLabel('Libellé')
				->setRequired(TRUE);

		$this->copy_of = Many2oneFieldDefinition::create('profil')
				->setLabel('Copier les droits d\'un profil')
				->addDomain(array('killi_profil_id','NOT IN',array(ADMIN_PROFIL_ID,READONLY_PROFIL_ID)))
				->setVirtual(TRUE);
		
		$this->user_id = Many2manyFieldDefinition::create('User')
				->setLabel('Utilisateurs');
	}
}
