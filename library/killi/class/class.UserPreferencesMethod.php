<?php

abstract class KilliUserPreferencesMethod extends Common
{
	public function edit($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		Rights::setAttributeRight('user','mail', true, true);
		Rights::setAttributeRight('user','prenom', true, true);
		Rights::setAttributeRight('user','nom', true, true);
		Rights::setAttributeRight('user','password', true, true);
		Rights::setAttributeRight('userpreferences','unlocked_header', true, true);
		Rights::setAttributeRight('userpreferences','ui_theme', true, true);
		Rights::setAttributeRight('userpreferences','items_per_page', true, true);

		$_GET['view'] = $view = 'form';
		$_GET['mode']='edition';
		$_GET['primary_key'] = $_SESSION['_USER']['killi_user_id']['value'];

		Security::crypt($_GET['primary_key'], $_GET['crypt/primary_key']);

		$hORMPrefs = ORM::getORMInstance('userpreferences', TRUE);
		$hORMPrefs->browse($object_list, $num, NULL, array(array('killi_user_id', '=', $_GET['primary_key'])));
		if ($num == 0)
		{
			global $hDB;
			$tbl = ORM::getObjectInstance('userpreferences')->table;
			$pk  = ORM::getObjectInstance('userpreferences')->primary_key;
			$sql =	'Insert Into '.$tbl.' Set '.
						$pk.' = '.$_GET['primary_key'].', '.
						'ui_theme = "'.$hDB->db_escape_string(UI_THEME).'"';
			$succeed = 0;
			$hDB->db_execute($sql, $succeed);
		}

		parent::edit($view,$data,$total_object_list,$template_name);

		// mise à jour de la session
		$_SESSION['_USER']['nom']['value'] 		= $data['userpreferences'][$_GET['primary_key']]['nom']['value'];
		$_SESSION['_USER']['prenom']['value']   = $data['userpreferences'][$_GET['primary_key']]['prenom']['value'];
		$_SESSION['_USER']['mail']['value']		= $data['userpreferences'][$_GET['primary_key']]['mail']['value'];
	}

	public function write($data)
	{
		$hORM = ORM::getORMInstance('userpreferences');
		if(isset($_POST['userpreferences/ui_theme']) && isset($_POST['userpreferences/items_per_page']))
		{
			$data = array('ui_theme' => $_POST['userpreferences/ui_theme'], 'items_per_page' => $_POST['userpreferences/items_per_page']);
			$hORM->write($_POST['killi_user_id'], $data);
		}
		parent::write($data);
		$hORM->read($_POST['killi_user_id'], $_SESSION['_USER_PREFERENCES']);
	}
}
