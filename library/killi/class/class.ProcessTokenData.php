<?php

/**
 *  @class ProcessTokenData
 *  @Revision $Revision: 3458 $
 *
 */

abstract class KilliProcessTokenData
{
	public $table   = 'killi_process_token_data';
	public $database  = RIGHTS_DATABASE;
	public $primary_key = 'token_id';
	public $reference	= 'token_id';
	//---------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->token_id = ExtendsFieldDefinition::create('ProcessToken');

		$this->data = JsonFieldDefinition::create()
				->setLabel('Data')
				->setRequired(TRUE);
	}
}
