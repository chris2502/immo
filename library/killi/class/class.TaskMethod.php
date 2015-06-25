<?php

/**
 *  @class TaskMethod
 *  @Revision $Revision: 4519 $
 *
 */

abstract class KilliTaskMethod extends Common
{
	//---------------------------------------------------------------------
	public function preCreate($original_data, &$data)
	{
		parent::preCreate($original_data, $data);

		/* Vérifie si une tâche en attente ou en cours n'existe pas déjà. */

	}

	//---------------------------------------------------------------------
	public function postCreate($original_id, &$id)
	{
		parent::postCreate($original_id, $id);

		$id_list = array();
		$id_list[$original_id] = array('id' => $original_id);
		$wf = new WorkflowAction();
		$wf->createTokenTo($id_list, 'task', 'start_point', 'task', 'tache_en_attente');
		return TRUE;
	}

	//---------------------------------------------------------------------
	public function setTaskRunning($task_id)
	{
		$wf = new WorkflowAction();
		$id_list[$task_id] = array('id' => $task_id);
		$wf->moveTokenTo($id_list, 'task', 'tache_en_attente', 'task', 'tache_en_cours');
		return TRUE;
	}

	//---------------------------------------------------------------------
	public function setTaskCancelled($task_id)
	{
		$wf = new WorkflowAction();
		$id_list[$task_id] = array('id' => $task_id);
		$wf->moveTokenTo($id_list, 'task', 'tache_en_attente', 'task', 'tache_annulee');
		return TRUE;
	}

	//---------------------------------------------------------------------
	public function setTaskSucceed($task_id)
	{
		$wf = new WorkflowAction();
		$id_list[$task_id] = array('id' => $task_id);
		$wf->moveTokenTo($id_list, 'task', 'tache_en_cours', 'task', 'tache_traitee');
		return TRUE;
	}

	//---------------------------------------------------------------------
	public function setTaskFailed($task_id)
	{
		$wf = new WorkflowAction();
		$id_list[$task_id] = array('id' => $task_id);
		$wf->moveTokenTo($id_list, 'task', 'tache_en_cours', 'task', 'tache_echouee');
		return TRUE;
	}

	//---------------------------------------------------------------------
	/* Re-exécution d'une tache. */
	public function relaunch()
	{
		if(!isset($_GET['primary_key']))
		{
			return FALSE;
		}

		$hORM   = ORM::getORMInstance('task');
		$task   = NULL;
		$new_pk = NULL;

		$hORM->read(
			$_GET['primary_key'],
			$task,
			array('task_action_id', 'object', 'object_id')
		);


		$hORM->create(
			array(
				'task_action_id'	=> $task['task_action_id']['value'],
				'object'			=> $task['object']['value'],
				'object_id'			=> $task['object_id']['value']
			),
			$new_pk
		);

		Alert::success('Nouvelle tâche', 'La nouvelle tâche Task #' . $new_pk . ' a été créée !');

		UI::quitNBack(NULL, NULL, TRUE);
		return TRUE;
	}
}
