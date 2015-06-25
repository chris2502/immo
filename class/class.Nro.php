<?php

/**
 *  @class NRO
 *  @Revision $Revision: 4220 $
 *
 */

class Nro
{
	public $table		= 'nro';
	public $database	= 	RIGHTS_DATABASE;
	public $primary_key	= 'nro_id';
	public $reference	= 'trigramme';
	//---------------------------------------------------------------------
	function __construct()
	{
		$this->nro_id = new PrimaryFieldDefinition();

		$this->trigramme = TextFieldDefinition::create(5)
				->setLabel('Trigramme')
				->setRequired(TRUE)
				->addConstraint('Constraints::checkReg([A-Z]{3}[0-9]{2}|(POP)$, 0)');

		$this->surface_nro = TextFieldDefinition::create(11)
				->setLabel('Surface du nro')
				->setRequired(TRUE);
		$this->estimation_prix = TextFieldDefinition::create(11)
		->setLabel('Estimation du nro')
		->setRequired(TRUE);
		
		$this->prix_nro = TextFieldDefinition::create(11)
			->setLabel('Prix du nro')
			->setRequired(TRUE);
	}
	
}
