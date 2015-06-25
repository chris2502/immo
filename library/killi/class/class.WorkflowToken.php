<?php

/**
 *  @class WorkflowToken
 *  @Revision $Revision: 4594 $
 *
 */

abstract class KilliWorkflowToken
{
	public $table		= 'killi_workflow_token';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'workflow_token_id' ;
	public $reference	= 'id';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->workflow_token_id = new PrimaryFieldDefinition();

		$this->node_id = Many2oneFieldDefinition::create('Node')
				->setLabel('Noeud')
				->setRequired(TRUE);

		// related de node_id
		$this->node_name = RelatedFieldDefinition::create('node_id','node_name')->setLabel('Noeud');
		$this->workflow_id = RelatedFieldDefinition::create('node_id', 'workflow_id');
		$this->workflow_name = RelatedFieldDefinition::create('node_id','workflow_name')->setLabel('Workflow');

		$this->id = IntFieldDefinition::create()
				->setLabel('ID')
				->setRequired(TRUE);

		$this->qualification_id = Many2oneFieldDefinition::create('NodeQualification')
				->setLabel('Qualification');

		$this->commentaire = TextareaFieldDefinition::create()
				->setLabel('Commentaire');

		$this->date = DatetimeFieldDefinition::create()
				->setLabel('Date')
				->setRequired(TRUE);
	}
}
