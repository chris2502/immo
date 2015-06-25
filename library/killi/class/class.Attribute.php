<?php

/**
 *  @class Attribute
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliAttribute
{
	public $primary_key = 'attr_name';
	public $reference	= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->attr_name = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create()
				->setLabel('Libellé')
				->setEditable(FALSE);

		$this->type = TextFieldDefinition::create()
				->setLabel('Type')
				->setEditable(FALSE);

		$this->read = BoolFieldDefinition::create()
				->setLabel('Lecture')
				->setRequired(TRUE);
	
		$this->required = BoolFieldDefinition::create()
				->setLabel('Requis')
				->setRequired(TRUE);

		$this->write = BoolFieldDefinition::create()
				->setLabel('Ecriture')
				->setRequired(TRUE);
	}
}
