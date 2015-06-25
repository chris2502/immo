<?php
	class EntiteMethod extends Common{
		public function edit( $view,&$data,&$total_object_list,&$template_name=NULL ){
			
			parent::edit($view,$data,$total_object_list,$template_name);
			/*if (isset($_GET['view']) && $_GET['view']=='form'){//echo "<pre>"; print_r($data); echo "</pre>"; die();
				
				$pk = $_GET['primary_key'];
				if(!in_array(7, $data['entite'][$pk]['statut_acquisition']['workflow_node_id'])   &&
						!in_array(10, $data['entite'][$pk]['statut_acquisition']['workflow_node_id']) &&
						!!in_array(12, $data['entite'][$pk]['statut_acquisition']['workflow_node_id']) &&
						!in_array(13, $data['entite'][$pk]['statut_acquisition']['workflow_node_id'])){

					//$data['entite'][$pk]['estimation_prix']['editable']=FALSE;
						}
			}*/
			//$hORM_local = ORM::getObjectInstance('localentite');
			
			if (isset($_GET['view']) && $_GET['view']=='form'){
				$pk = $_GET['primary_key'];
				$hORM_local = ORM::getORMInstance('local');
				$resultat_local = array();
				$hORM_local->read($data['entite'][$pk]['local']['value'],$resultat_local,array('statut_acquisition'));
				$data['local']=$resultat_local;//echo "<pre>"; print_r($data); echo "</pre>"; die();
				$pk_local=$data['entite'][$pk]['local']['value'];
				foreach ($pk_local as $key){
					if(!in_array(7, $data['local'][$key]['statut_acquisition']['workflow_node_id'])   &&
							!in_array(10, $data['local'][$key]['statut_acquisition']['workflow_node_id']) &&
							!in_array(12, $data['local'][$key]['statut_acquisition']['workflow_node_id']) &&
							!in_array(13, $data['local'][$key]['statut_acquisition']['workflow_node_id'])){
						if($data['entite'][$pk]['type']['reference']=='Agence' || $data['entite'][$pk]['type']['reference']=='Syndicat'){
							
							$data['entite'][$pk]['type']['editable']=FALSE;
							$data['entite'][$pk]['nom']['editable']=FALSE;
							$data['entite'][$pk]['tel']['editable']=FALSE;
							$data['entite'][$pk]['mobile']['editable']=FALSE;
							$data['entite'][$pk]['adresse']['editable']=FALSE;
							$data['entite'][$pk]['email']['editable']=FALSE;
							$data['entite'][$pk]['fax']['editable']=FALSE;
							$data['entite'][$pk]['local']['editable']=FALSE;
							
						}
					}
				}
			}
			if (isset($_GET['view']) && $_GET['view']=="selection" && $_GET['input_name']=='Entite/entite_id'){
				
				$pk=array();
				$assoc=array();
				$hORM_notaire=ORM::getORMInstance('notaire');
				$hORM_notaire->browse($assoc, $total, array('notaire_id'));
				foreach ($assoc as $key => $value){
					$pk[]=$value['notaire_id']['value'];
				}
				foreach ($data['entite'] as $key => $value){
					if(!in_array($value['type']['value'], $pk) && in_array($value['type']['reference'], array('notaire', 'notaire free'))){
						$hORM_notaire->create(array('notaire_id' => $value['entite_id']['value']));
					}
					
				}
				//echo "<pre>"; print_r($hORM_notaire); echo "</pre>"; die();
				
				$hORM_local_entite=ORM::getORMInstance('localentite');
				
				
			}
			return TRUE;
		}
	}