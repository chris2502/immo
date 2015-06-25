<?php

/**
 *  @class Object
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliObject
{
	public $primary_key = 'nom';
	//-------------------------------------------------------------------------
	public function setDomain(){}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->nom = TextFieldDefinition::create()
				->setLabel('Libellé')
				->setEditable(FALSE);

		$this->create = BoolFieldDefinition::create()
				->setLabel('Création')
				->setRequired(TRUE);

		$this->delete = BoolFieldDefinition::create()
				->setLabel('Suppression')
				->setRequired(TRUE);

		$this->view = BoolFieldDefinition::create()
				->setLabel('Affichage')
				->setRequired(TRUE);
	}
}
