<?php

/**
 *  @class Task
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliTask
{
	public $table		= 'killi_task';
	public $database	= RIGHTS_DATABASE;
	public $primary_key	= 'task_id';
	public $reference	= 'task_code';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->task_id = new PrimaryFieldDefinition();

		$this->task_code = TextFieldDefinition::create()
				->setLabel('Code')
				->setSQLAlias('CONCAT("Task #", %task_id%)');

		$this->task_action_id = Many2oneFieldDefinition::create('TaskAction')
				->setLabel('Type de tâche')
				->setRequired(TRUE);

		$this->task_internal_name = RelatedFieldDefinition::create('task_action_id','task_internal_name')
				->setLabel('Nom interne de la tâche');

		$this->object = TextFieldDefinition::create(64)
				->setLabel('Objet')
				->setRequired(TRUE)
				->setEditable(FALSE);

		$this->object_id = IntFieldDefinition::create()
				->setLabel('Objet ID')
				->setRequired(TRUE);

		$this->date_creation = DatetimeFieldDefinition::create()
				->setLabel('Date de création');

		$this->users_id = Many2oneFieldDefinition::create('User')
				->setLabel('Créateur');

		$this->status_task = WorkflowStatusFieldDefinition::create('task')
				->setLabel('Statut de la tâche');
	}
}
