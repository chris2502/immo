<?php

/**
 *  @class ProcessTransition
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliProcessTransition
{
	public $table		= 'killi_process_transition';
	public $primary_key = 'transition_id';
	public $database	= RIGHTS_DATABASE;
	public $reference	= 'transition_id';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->transition_id = new PrimaryFieldDefinition();

		$this->module_depart_id = Many2oneFieldDefinition::create('processmodule')
			->setLabel('Module de départ')
			->setRequired(TRUE);

		$this->module_arrivee_id = Many2oneFieldDefinition::create('processmodule')
			->setLabel('Module d\'arrivé')
			->setRequired(TRUE);

		$this->answer_key = TextFieldDefinition::create(64)
			->setLabel('Clé de transition')
			->setRequired(FALSE);
	}
}
