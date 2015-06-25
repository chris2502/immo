<?php

/**
 *  @class KilliUserConnection
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliUserConnection
{
	public $table		= 'killi_user_connection';
	public $database	= RIGHTS_DATABASE;
	public $primary_key	= 'user_connection_id';
	public $reference	= 'user_connection_id';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->user_connection_id = PrimaryFieldDefinition::create();

		$this->killi_user_id = Many2OneFieldDefinition::create('user')
			->setLabel('Utilisateur');

		$this->date = DateTimeFieldDefinition::create()
			->setDefaultValue(date('Y-m-d H:i:s'))
			->setLabel('Date de connexion');
	}
}
