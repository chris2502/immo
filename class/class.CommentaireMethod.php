<?php
	class CommentaireMethod extends Common{
		
	
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		
		parent::edit($view,$data,$total_object_list,$template_name);
		//on n'est pas encore dans revente. Donc on rend ce champs inutilisable
		if (isset($_GET['view']) && $_GET['view']=='form')
		{
			$pk = $_GET['primary_key'];
			if(in_array(7, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id'])   ||
				in_array(10, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id']) ||
				in_array(12, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id']) ||
				in_array(13, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id']) ||
				in_array(15, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id']) ||
				in_array(8, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id'])  ||
				in_array(11, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id']) ||
				in_array(14, $data['commentaire'][$pk]['statut_acquisition']['workflow_node_id'])){
				foreach ($data['commentaire'] as $keys => &$values){
					$values['etape']['editable']=FALSE;
					$values['mise_en_vente_id']['editable']=FALSE;
				}
			}
		}
	//input_name me permet de savoir que la fenêtre create de commentaire vient d'un workflow
		if (isset($_GET['view']) && $_GET['view']=='create' && isset($_GET['input_name']) && $_GET['input_name']=='Commentaire/commentaire_id'){
					
			$adress_list=array();
			if(isset($_GET['commentaire_local_id'])){
				$hORM=ORM::getORMInstance('Local');
				$commentaire_etape_id=$_GET['commentaire_local_id'];
				//auto-liaison de commentaire au local
				$_POST['commentaire/local_id']=$commentaire_etape_id;
				$hORM->browse($adress_list, $total, array('statut_acquisition'), array(array($commentaire_etape_id, '=', 'local_id')));
				//echo " <pre>"; print_r($adress_list); echo "</pre>"; die();
				//foreach ($adress_list as)
				//ORM::getObjectInstance('Commentaire')->etape->editable=0;
				if(in_array(7,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id'])   ||
					in_array(10,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id']) ||
					in_array(12,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id']) ||
					in_array(13,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id']) ||
					in_array(15,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id']) ||
					in_array(8,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id'])  ||
					in_array(11,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id']) ||
					in_array(14,$adress_list[$commentaire_etape_id]['statut_acquisition']['workflow_node_id'])){
					ORM::getObjectInstance('Commentaire')->mise_en_vente_id->setEditable(FALSE);
				}
			}
			if(isset($_GET['commentaire_mise_en_vente_id'])){
				$hORM=ORM::getORMInstance('MiseEnVente');
				$commentaire_etape_id=$_GET['commentaire_mise_en_vente_id'];
				//auto-liaison de commentaire au local
				$_POST['commentaire/mise_en_vente_id']=$commentaire_etape_id;
				$hORM->browse($adress_list, $total, array('statut_revente'), array(array($commentaire_etape_id, '=', 'mise_en_vente_id')));
				//echo " <pre>"; print_r($adress_list); echo "</pre>"; die();
				//foreach ($adress_list as)
				//ORM::getObjectInstance('Commentaire')->etape->editable=0;
				if(in_array(37,$adress_list[$commentaire_etape_id]['statut_revente']['workflow_node_id'])   ||
						in_array(42,$adress_list[$commentaire_etape_id]['statut_revente']['workflow_node_id']) ||
						in_array(19,$adress_list[$commentaire_etape_id]['statut_revente']['workflow_node_id']) ||
						in_array(22,$adress_list[$commentaire_etape_id]['statut_revente']['workflow_node_id']) ||
						in_array(43,$adress_list[$commentaire_etape_id]['statut_revente']['workflow_node_id'])){
					ORM::getObjectInstance('Commentaire')->local_id->setEditable(FALSE);
				}
			}
			//$hORM_comment->etape->setEditable(FALSE);
			//echo " <pre>"; print_r($_GET); echo "</pre>"; die();
			//$data['commentaire']	
			//echo " <pre>"; print_r($data); echo "</pre>"; die();
		}
		
		/*if(!(in_array(10, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])))
		 {
		 $data['local'][$pk]['departement']['editable']=FALSE;
		 $data['local'][$pk]['plaque_id']['editable']=FALSE;
		 $data['local'][$pk]['numero_rue']['editable']=FALSE;
		 $data['local'][$pk]['type_rue']['editable']=FALSE;
		 $data['local'][$pk]['nom_rue']['editable']=FALSE;
		 $data['local'][$pk]['code_postal']['editable']=FALSE;
		 $data['local'][$pk]['commune']['editable']=FALSE;
		 }
		 */
		return TRUE;
	}
	
	
	public function create($data,&$id,$ignore_duplicate=false){
		$adress_list=array();
		if(isset($data['local_id'])){
			$local_id=$data['local_id'];
			$hORM_local=ORM::getORMInstance('Local');
			$hORM_local->browse($adress_list, $total, array('statut_acquisition'), array(array('local_id', '=', $local_id)));
			$data['etape']=$adress_list[$local_id]['statut_acquisition']['value'];
			$data['date_commentaire']=str_replace('-','/', date("d-m-Y H:i:s"));
		}
		
		if(isset($data['mise_en_vente_id'])){
			$mise_en_vente_id=$data['mise_en_vente_id'];
			$hORM_local=ORM::getORMInstance('MiseEnVente');
			$hORM_local->browse($adress_list, $total, array('statut_revente'), array(array('mise_en_vente_id', '=', $mise_en_vente_id)));
			$data['etape']=$adress_list[$mise_en_vente_id]['statut_revente']['value'];
			$data['date_commentaire']=str_replace('-','/', date("d-m-Y H:i:s"));
		}
		parent::create($data, $id);
	}
}