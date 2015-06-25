<?php

/**
 *  @class Parametric
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliParametric
{
	public $table		= 'killi_parametric';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'parametric_id' ;
	public $order		= array('parametric_type_id', 'parametric_name');
	public $reference	= 'parametric_name';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->parametric_id = new PrimaryFieldDefinition();

		$this->parametric_type_id = Many2oneFieldDefinition::create('TypeParametric')
				->setLabel('Type ID')
				->setRequired(TRUE);

		$this->parametric_name = TextFieldDefinition::create(45)
				->setLabel('Nom')
				->setRequired(TRUE);

		$this->parametric_description = TextareaFieldDefinition::create()
				->setLabel('Description')
				->setRequired(TRUE);

		$this->parametric_datatype = EnumFieldDefinition::create()
				->setLabel('Type de données')
				->setRequired(TRUE)
				->setValues(array(
					'string' => 'string',
					'integer' => 'integer'
				));

		$this->parametric_value = TextFieldDefinition::create()
				->setLabel('Valeur')
				->setRequired(TRUE);
	}
}
