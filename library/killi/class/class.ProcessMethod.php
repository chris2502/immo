<?php

/**
 *  @class ProcessMethod
 *  @Revision $Revision: 3458 $
 *
 */

abstract class KilliProcessMethod extends Common
{
	public function ajaxSaveModule()
	{
		if (!isset($_POST['process_id']) || !isset($_POST['class_name']) || !isset($_POST['x']) || !isset($_POST['y']))
		{
			return FALSE;
		}

		$process_id = '';
		Security::decrypt($_POST['process_id'], $process_id);
		
		$hORMTypeModule = ORM::getORMInstance('typemodule');
		$hORMProcessModule = ORM::getORMInstance('processmodule');
		
		$typemodule_data = array();
		$hORMTypeModule->browse(
			$typemodule_data,
			$total,
			array('class_id'),
			array( array('class_name', '=', $_POST['class_name']) )
		);

		$typemodule_data = reset($typemodule_data);
		$class_id = $typemodule_data['class_id']['value'];

		$create_data = array(
			'process_id' => $process_id,
			'class_id' => $class_id,
			'x' => $_POST['x'],
			'y' => $_POST['y']
		);

		if (isset($_POST['name']))
		{
			$create_data['name'] = $_POST['name'];
		}

		$module_id = '';
		$hORMProcessModule->create(
			$create_data,
			$module_id
		);

		$crypted_module_id = '';
		Security::crypt($module_id, $crypted_module_id);

		$response['module_id'] = $crypted_module_id;

		echo json_encode($response);
		return TRUE;
	}


}