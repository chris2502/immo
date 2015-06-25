<?php
	class OffreNotaireMethod extends Common{
		public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
		{
			//echo "<pre>"; print_r($data['entite']); echo "</pre>"; die();
			parent::edit($view,$data,$total_object_list,$template_name);
			//permet d'afficher dans la vue form l'objet entite et responsable...
			//Ceci permet entre autre d'éviter une imbrication de création d'objet
			if (isset($_GET['view']) && $_GET['view']=='form')
			{
				$pk = $_GET['primary_key'];
				//echo "<pre>"; print_r($data); echo "</pre>"; die();
				if((isset($data['offrenotaire'][$pk]['statut_acquisition']['workflow_node_id'])) && in_array(12, $data['offrenotaire'][$pk]['statut_acquisition']['workflow_node_id'])){
					$data['offrenotaire'][$pk]['offre_id']['editable']=FALSE;
					$data['offrenotaire'][$pk]['notaire_id']['editable']=FALSE;
					$data['offrenotaire'][$pk]['date_acte_authentique']['editable']=FALSE;
				}
			}
			if (isset($_GET['view']) && $_GET['view']=='create' && isset($_GET['input_name']) && 
					 $_GET['input_name']=='OffreNotaire/offre_notaire_id'){
				//echo "<pre>"; print_r($_GET); echo "</pre>"; die();
				$adress_list=array();
				$hORM_local=ORM::getORMInstance('Offre');
				$offrenotaire_offre_id=$_GET['offrenotaire_offre_id'];
				$_POST['offrenotaire/offre_id']=$offrenotaire_offre_id;
	//			$_POST['offrenotaire/local_id']=$offre_local_id;
				$hORM_local->browse($adress_list, $total, array('statut_acquisition'), array(array($offrenotaire_offre_id, '=', 'offre_id')));
				if(in_array(12,$adress_list[$offrenotaire_offre_id]['statut_acquisition']['workflow_node_id'])){
					ORM::getObjectInstance('OffreNotaire')->create=FALSE;
				}
			}
			return TRUE;
		}
	}