<?php
class WFReventeMethod extends WorkflowAction
{
	public function start_point()
	{
		ini_set( 'max_execution_time' , 0 );
		$step = 100;
		//3 étant l'id du workflow revente
		$query = 'SELECT mise_en_vente_id FROM mise_en_vente WHERE mise_en_vente_id NOT IN (SELECT id FROM  killi_workflow_token , killi_workflow_node WHERE node_id=workflow_node_id AND workflow_id=3)';
		$this->_hDB->db_select($query,$result,$num);
		$adresse_id_list = array() ;
		
		$curr = 0 ;
		while( $row=mysqli_fetch_array($result) )
		{
			if( $curr == $step )
			{
				//echo display_array($adresse_id_list);
				$this->createTokenTo($adresse_id_list, 'WFRevente');
				$adresse_id_list = array() ;
				$curr = 0 ;
			}
			$adresse_id_list[] = array( 'id' => $row[ 0 ], 'adresse_id'=>NULL ) ;
			$curr++ ;
		
		}//print_r($adresse_id_list);
		mysqli_free_result($result);
		if( $curr != 0 )
		{
			$this->createTokenTo($adresse_id_list,'WFRevente','start_point_revente','WFRevente','dossier_revente');
		}
		//echo " <pre>"; print_r($_GET); echo "</pre>"; die();
		$this->_hDB->db_commit() ;
		return TRUE;
	}
	
	
/**********On vérifie que l'élement à déplacer se trouve dans le noeud dans lequel on se trouve**********/

	
	public function pre_insert_offre_revente(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		//recherche de l'id de la node de départ à partir de son nom
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
		//recherche des id des locaux correspondant aux id de node ci-dessus
		$hORM_id->browse($assoc2, $total_record, array('id'), array(array('node_id', '=', $idNode)));
		foreach ($assoc2 as $key =>$value){
			$id[]=$value['id']['value'];
		}
		//supression de la id_list des id qui ne sont pas dans le noeud d'id 'node_id'
		foreach ($id_list as $key => $value){
			if(!in_array($value['id'], $id)){
				Alert::info('Déplacement', 'Un ou plusieurs élements que vous voulez déplacer n\'exitent pas dans le noeud et donc n\'ont pas été déplacés');
				unset($id_list[$key]);
			}
	
		}
		return true;
	}
	public function pre_insert_offre_revente_refusee(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		//recherche de l'id de la node de départ à partir de son nom
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		//
		$hORM_id=ORM::getORMInstance('WorkflowToken');
		//recherche des id des locaux correspondant aux id de node ci-dessus
		$hORM_id->browse($assoc2, $total_record, array('id'), array(array('node_id', '=', $idNode)));
		foreach ($assoc2 as $key =>$value){
		$id[]=$value['id']['value'];
		}
			foreach ($id_list as $key => $value){
			if(!in_array($value['id'], $id)){
					Alert::info('Déplacement', 'Un ou plusieurs élements que vous voulez déplacer n\'exitent pas dans le noeud et donc n\'ont pas été déplacés');
				unset($id_list[$key]);
			}
	
			}
			return true;
	}
	
	
	public function pre_insert_promesse_revente(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		//recherche de l'id de la node de départ à partir de son nom
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		//
		$hORM_id=ORM::getORMInstance('WorkflowToken');
		//recherche des id des locaux correspondant aux id de node ci-dessus
		$hORM_id->browse($assoc2, $total_record, array('id'), array(array('node_id', '=', $idNode)));
		foreach ($assoc2 as $key =>$value){
		$id[]=$value['id']['value'];
		}
			foreach ($id_list as $key => $value){
			if(!in_array($value['id'], $id)){
					Alert::info('Déplacement', 'Un ou plusieurs élements que vous voulez déplacer n\'exitent pas dans le noeud et donc n\'ont pas été déplacés');
				unset($id_list[$key]);
			}
	
			}
			return true;
	}
	
	
	
	public function pre_insert_sortie_promesse(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		//recherche de l'id de la node de départ à partir de son nom
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		//
		$hORM_id=ORM::getORMInstance('WorkflowToken');
		//recherche des id des locaux correspondant aux id de node ci-dessus
		$hORM_id->browse($assoc2, $total_record, array('id'), array(array('node_id', '=', $idNode)));
		foreach ($assoc2 as $key =>$value){
		$id[]=$value['id']['value'];
		}
		foreach ($id_list as $key => $value){
		if(!in_array($value['id'], $id)){
				Alert::info('Déplacement', 'Un ou plusieurs élements que vous voulez déplacer n\'exitent pas dans le noeud et donc n\'ont pas été déplacés');
				unset($id_list[$key]);
		}
	
		}
		return true;
	}
	
	
	public function pre_insert_authentique_revente(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		//recherche de l'id de la node de départ à partir de son nom
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		//
		$hORM_id=ORM::getORMInstance('WorkflowToken');
		//recherche des id des locaux correspondant aux id de node ci-dessus
		$hORM_id->browse($assoc2, $total_record, array('id'), array(array('node_id', '=', $idNode)));
		foreach ($assoc2 as $key =>$value){
		$id[]=$value['id']['value'];
		}
		foreach ($id_list as $key => $value){
		if(!in_array($value['id'], $id)){
				Alert::info('Déplacement', 'Un ou plusieurs élements que vous voulez déplacer n\'exitent pas dans le noeud et donc n\'ont pas été déplacés');
				unset($id_list[$key]);
		}
	
		}
		return true;
	}
	
	
	
	public function pre_insert_revendu(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		//recherche de l'id de la node de départ à partir de son nom
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		//
		$hORM_id=ORM::getORMInstance('WorkflowToken');
		//recherche des id des locaux correspondant aux id de node ci-dessus
		$hORM_id->browse($assoc2, $total_record, array('id'), array(array('node_id', '=', $idNode)));
		foreach ($assoc2 as $key =>$value){
		$id[]=$value['id']['value'];
		}
		foreach ($id_list as $key => $value){
		if(!in_array($value['id'], $id)){
				Alert::info('Déplacement', 'Un ou plusieurs élements que vous voulez déplacer n\'exitent pas dans le noeud et donc n\'ont pas été déplacés');
				unset($id_list[$key]);
		}
	
		}
		return true;
	}
	
	/*public function pre_insert_dossier_revente(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		if($from_workflow_name=='wfacquisition' && $from_node_name=='acquis'){
			$hormACQ=ORM::getORMInstance('mise_en_vente');
			foreach ($id_list as $acquisition_id => $acquisition){
				$hormACQ->create(array('acquisition_id' => $acquisition_id));
			}
			return true;
		}
		return true;
	}
	*/
}