<?php

/**
 *  @class Workflow
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliWorkflow
{
	public $table		= 'killi_workflow';
	public $database	 = RIGHTS_DATABASE;
	public $primary_key  = 'workflow_id' ;
	public $order		= array( 'nom' ) ;
	public static $reference = 'nom';
	//---------------------------------------------------------------------
	public function setDomain()
	{
		$this->object_domain[] = array('obsolete','=','0');
	}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->workflow_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(64)
				->setLabel('Libellé')
				->setRequired(TRUE);

		$this->workflow_name = TextFieldDefinition::create(32)
				->setLabel('Nom interne')
				->setRequired(TRUE);

		$this->obsolete = BoolFieldDefinition::create()
				->setLabel('Obsolète')
				->setDefaultValue(FALSE);
	}
}
