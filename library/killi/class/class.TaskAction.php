<?php

/**
 *  @class TaskAction
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliTaskAction
{
	public $table		= 'killi_task_action';
	public $database	= RIGHTS_DATABASE;
	public $primary_key	= 'task_action_id' ;
	public $reference	= 'task_name';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->task_action_id = new PrimaryFieldDefinition();

		$this->task_name = TextFieldDefinition::create(255)
				->setLabel('Nom de la tâche')
				->setRequired(TRUE);

		$this->task_internal_name = TextFieldDefinition::create(200)
				->setLabel('Nom interne de la tâche')
				->setRequired(TRUE);
	}
}
