<?php

/**
 *  @class TypeParametric
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliTypeParametric
{
	public $table		= 'killi_type_parametric';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'type_parametric_id' ;
	public $order		= array('type_parametric_name');
	public $reference	= 'type_parametric_name';
	//---------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->type_parametric_id = new PrimaryFieldDefinition();

		$this->type_parametric_name = TextFieldDefinition::create(45)
				->setLabel('Nom')
				->setRequired(TRUE);
	}
}
