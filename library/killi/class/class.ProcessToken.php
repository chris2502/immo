<?php

/**
 *  @class ProcessToken
 *  @Revision $Revision: 3647 $
 *
 */

abstract class KilliProcessToken
{
	public $table		= 'killi_process_token';
	public $primary_key = 'token_id';
	public $database	= RIGHTS_DATABASE;
	public $reference	= 'token_id';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->token_id = new PrimaryFieldDefinition();

		$this->module_id = Many2oneFieldDefinition::create('ProcessModule')
				->setRequired(TRUE);
		
		$this->class_name				= RelatedFieldDefinition::create('module_id', 'class_name');
		$this->process_id				= RelatedFieldDefinition::create('module_id', 'process_id');
		$this->process_name				= RelatedFieldDefinition::create('module_id', 'process_name');
		$this->process_internal_name	= RelatedFieldDefinition::create('module_id', 'process_internal_name');
		$this->module_data				= RelatedFieldDefinition::create('module_id', 'data');
		$this->visibility_user			= RelatedFieldDefinition::create('module_id', 'visibility_user');
		$this->visibility_profile		= RelatedFieldDefinition::create('module_id', 'visibility_profile');
		$this->visibility_company		= RelatedFieldDefinition::create('module_id', 'visibility_company');

		$this->killi_user_id = Many2oneFieldDefinition::create()
				->setLabel('Utilisateur')
				->setObjectRelation('user');
		
		$this->profil_id = RelatedFieldDefinition::create('killi_user_id', 'profil_id');

		$this->date = DateTimeFieldDefinition::create()
				->setLabel('Date')
				->setRequired(TRUE);
	}
}
