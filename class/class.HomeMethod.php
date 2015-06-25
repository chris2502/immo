<?php
class HomeMethod extends Common{


	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		//parent::edit($view, $data, $total_object_list);
		if(isset($_SESSION['_USER']['profil_id']['value'][0]) && $_SESSION['_USER']['profil_id']['value'][0]==4){
			header("Location: index.php?action=local.edit&workflow_node_id=7&token=".$_GET['token']);
			//
		}
		else{
			header("Location: index.php?action=user.edit&token=".$_GET['token']);
		}
		return true;
	}
}