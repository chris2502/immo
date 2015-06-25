<?php

/**
 *  @class AndroidAppError
 *  @Revision $Revision: 4214 $
 *
 */

abstract class KilliAndroidAppError
{
	public $table		= 'killi_android_app_error';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'android_app_error_id';
	//---------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->android_app_error_id = new PrimaryFieldDefinition();

		$this->date_err_client = DatetimeFieldDefinition::create()
				->setLabel('Date erreur du client Android')
				->setEditable(FALSE);
		
		$this->date_err_server = DatetimeFieldDefinition::create()
				->setLabel('Date erreur du serveur')
				->setEditable(FALSE);

		$this->app_name = TextFieldDefinition::create()
				->setLabel('Nom application')
				->setEditable(FALSE);

		$this->device_data = TextFieldDefinition::create()
				->setLabel('Données sur terminal')
				->setEditable(FALSE);

		$this->err_type = TextFieldDefinition::create()
				->setLabel('Type erreur')
				->setEditable(FALSE);

		$this->err_cause = TextFieldDefinition::create()
				->setLabel('Cause erreur')
				->setEditable(FALSE);

		$this->err_msg = TextFieldDefinition::create()
				->setLabel('Message erreur')
				->setEditable(FALSE);
		 
		$this->err_backtrace = TextareaFieldDefinition::create()
				->setLabel('Trace erreur')
				->setEditable(FALSE);
		
		$this->alert_is_sended = BoolFieldDefinition::create()
				->setLabel('alerte admin réalisée')
				->setEditable(FALSE);
		
		// --- Les versions "inline" (et raccourci) des champs longs ou textarea (pour affichage en vue liste)
		$this->err_backtrace_text = TextFieldDefinition::create()
				->setSQLAlias("IF(LENGTH(%err_backtrace%)>150,CONCAT(SUBSTRING(%err_backtrace%,1,150),'......'),%err_backtrace%)")
				->setLabel('Trace erreur')
				->setEditable(FALSE);

		$this->err_msg_text = TextFieldDefinition::create()
				->setSQLAlias("IF(LENGTH(%err_msg%)>100,CONCAT(SUBSTRING(%err_msg%,1,100),'......'),%err_msg%)")
				->setLabel('Message erreur')
				->setEditable(FALSE);

		$this->err_cause_text = TextFieldDefinition::create()
				->setSQLAlias("IF(LENGTH(%err_cause%)>100,CONCAT(SUBSTRING(%err_cause%,1,100),'......'),%err_cause%)")
				->setLabel('Cause erreur')
				->setEditable(FALSE);
	}
}