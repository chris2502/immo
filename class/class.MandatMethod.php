<?php
	class mandatMethod extends Common{
		
	
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		
		parent::edit($view,$data,$total_object_list,$template_name);
		//on n'est pas encore dans revente. Donc on rend ce champs inutilisable
		if (isset($_GET['view']) && $_GET['view']=='form')
		{
			$pk = $_GET['primary_key'];
		}
	//input_name me permet de savoir que la fenêtre create de mandat vient d'un workflow
		if (isset($_GET['view']) && $_GET['view']=='create' && isset($_GET['input_name']) && $_GET['input_name']=='Mandat/mandat_id'){
			$adress_list=array();
			$hORM_mise_en_vente=ORM::getORMInstance('MiseEnVente');
			$mandat_local_id=$_GET['mandat_mise_en_vente_id'];
			//auto-liaison de mandat au local
			$_POST['mandat/mise_en_vente_id']=$mandat_local_id;
		}
		return TRUE;
	}
	
}