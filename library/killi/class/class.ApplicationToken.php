<?php

/**
 *  @class ApplicationToken
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliApplicationToken
{
	public $table		= 'killi_application_token';
	public $primary_key = 'killi_application_token_id';
	public $database	= RIGHTS_DATABASE;
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_application_token_id = new PrimaryFieldDefinition();

		$this->token_type_id = EnumFieldDefinition::create()
				->setLabel('Type')
				->setRequired(TRUE)
				->setValues(array(
					1 => 'authorization_token',
					2 => 'refresh_token',
					3 => 'access_token'
				));

		$this->killi_application_id = Many2oneFieldDefinition::create('Application')
				->setLabel('Application')
				->setRequired(TRUE);

		$this->killi_user_id = Many2oneFieldDefinition::create('User')
				->setLabel('User')
				->setRequired(TRUE);

		$this->token = TextFieldDefinition::create(64)
				->setLabel('Token')
				->setRequired(TRUE);

		$this->validity_date = DatetimeFieldDefinition::create()
				->setLabel('Date')
				->setRequired(TRUE);
	}
}
