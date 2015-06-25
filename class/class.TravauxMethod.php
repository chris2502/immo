<?php

class TravauxMethod extends Common
{
	
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		
		parent::edit($view,$data,$total_object_list,$template_name);
		if (isset($_GET['view']) && $_GET['view']=='form')
		{
			foreach ($data['travaux'] as $keys => &$values){
				$values['mise_en_vente_id']['editable']=FALSE;
			}
		}
		if (isset($_GET['view']) && $_GET['view']=='create' && isset($_GET['input_name']) && $_GET['input_name']='Travaux/travaux_id'){
			$adress_list=array();
			$hORM_offre=ORM::getORMInstance('Local');
			$offre_local_id=$_GET['travaux_local_id'];
			$travaux_local_id=$_GET['travaux_local_id'];
			$_POST['travaux/local_id']=$travaux_local_id;
			$hORM_offre->browse($adress_list, $total, array('statut_acquisition'), array(array($offre_local_id, '=', 'local_id')));
				
			//On empêche la creation  de offre si on est pas au moins à l'étape 10(travaux) du workflow
			/*if($adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id'][0]<10){
		
				ORM::getObjectInstance("Travaux")->create=FALSE;
			}*/
			ORM::getObjectInstance('Travaux')->mise_en_vente_id->setEditable(FALSE);
		}
		return TRUE;
	}
	
}
	