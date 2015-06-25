<?php

/**
 *  @class Application
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliApplication
{
	public $table		= 'killi_application';
	public $primary_key = 'killi_application_id';
	public $database	= RIGHTS_DATABASE;
	public $order		= array('nom');
	public $reference	= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_application_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(45)
				->setLabel('Application')
				->setRequired(TRUE);

		$this->app_token = TextFieldDefinition::create(64)
				->setLabel('App token')
				->setRequired(TRUE);

		$this->app_secret = TextFieldDefinition::create(64)
				->setLabel('App secret')
				->setRequired(TRUE);

		$this->redirect_uri = TextFieldDefinition::create(255)
				->setLabel('Redirect URI')
				->setRequired(TRUE);

		$this->active = BoolFieldDefinition::create()
				->setLabel('Active')
				->setDefaultValue(FALSE)
				->setRequired(TRUE);
	}
}
