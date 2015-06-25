<?php

class transaction{
	public $table		= 'transaction';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'transaction_id';
	public $reference	= 'type_transaction';

	function __construct()
	{
		$this->transaction_id = new PrimaryFieldDefinition();
		
		$this->type_transaction= TextFieldDefinition::create(7)
			->setLabel('Type de transaction')
			->setRequired(TRUE);
		
	}



}

