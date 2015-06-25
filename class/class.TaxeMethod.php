<?php
	class TaxeMethod extends Common{
		
	
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		
		parent::edit($view,$data,$total_object_list,$template_name);
		
		
	//input_name me permet de savoir que la fenêtre create de commentaire vient d'un workflow
		if (isset($_GET['view']) && $_GET['view']=='create' && isset($_GET['input_name']) && $_GET['input_name']=='Taxe/taxe_id'){
			$adress_list=array();
			$hORM_local=ORM::getORMInstance('Local');
			$taxe_local_id=$_GET['taxe_local_id'];
			//auto-liaison de commentaire au local
			$_POST['taxe/local_id']=$taxe_local_id;
		}
		
		return TRUE;
	}
}