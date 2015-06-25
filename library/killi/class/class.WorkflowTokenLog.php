<?php

/**
 *  @class WorkflowTokenLog
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliWorkflowTokenLog
{
	public $table		= 'killi_workflow_token_log';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'workflow_token_log_id' ;
	public $reference	= 'id';
	//-------------------------------------------------------------------------
	public function setDomain()
	{
		//---Seulement des logs associes a des node existants
		$table = array (
			'killi_workflow_node as from_node',
			'killi_workflow_node as to_node',
			'killi_workflow_token'
		);

		$join_list = array (
			'killi_workflow_token_log.from_node_id=from_node.workflow_node_id',
			'killi_workflow_token_log.to_node_id=to_node.workflow_node_id',
			'killi_workflow_token_log.workflow_token_id=killi_workflow_token.workflow_token_id'
		);

		$filter = array (
			array (
				'to_node.workflow_node_id',
				'is not null'
			),
			array (
				'killi_workflow_token_log.workflow_token_id',
				'is not null'
			)
		);

		$this->domain_with_join = array (
			'table' => $table,
			'join' => $join_list,
			'filter' => $filter
		);

		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->workflow_token_log_id = PrimaryFieldDefinition::create();

		$this->workflow_token_id = IntFieldDefinition::create()
				->setLabel('Token ID')
				->setRequired(TRUE);

		$this->to_node_id = Many2oneFieldDefinition::create('Node')
				->setLabel('Vers le noeud');

		// related de to_node_id
		$this->to_workflow_name = RelatedFieldDefinition::create('to_node_id','workflow_name')->setLabel('Vers le wf');
		$this->to_node_name = RelatedFieldDefinition::create('to_node_id','node_name')->setLabel('Vers le noeud');
		$this->etat = RelatedFieldDefinition::create('to_node_id','etat')->setLabel('Status');
		$this->object_of_to_node = RelatedFieldDefinition::create('to_node_id','object')->setLabel('Objet');

		$this->from_node_id = Many2oneFieldDefinition::create('Node')
				->setLabel('Depuis le noeud');

		// related de from_node_id
		$this->from_workflow_name = RelatedFieldDefinition::create('from_node_id','workflow_name')->setLabel('Depuis le wf');
		$this->from_node_name = RelatedFieldDefinition::create('from_node_id','node_name')->setLabel('Depuis le noeud');

		$this->id = IntFieldDefinition::create()
				->setLabel('ID');

		$this->qualification_id = Many2oneFieldDefinition::create('NodeQualification')
				->setLabel('Qualification');

		$this->commentaire = TextareaFieldDefinition::create()
				->setLabel('Commentaire');

		$this->date = DatetimeFieldDefinition::create()
				->setLabel('Date');

		$this->user_id = Many2oneFieldDefinition::create('User')
				->setLabel('Utilisateur');
	}
}
