<?php
class WFAcquisitionMethod extends WorkflowAction
{
	public function start_point()
	{
		ini_set( 'max_execution_time' , 0 );
		$step = 100;
		$query = 'SELECT local_id FROM local WHERE nro_id is NULL AND local_id NOT IN(SELECT id FROM killi_workflow_token )';
		$this->_hDB->db_select($query,$result,$num);
		$adresse_id_list = array() ;
		
		$curr = 0 ;
		while( $row=mysqli_fetch_array($result) )
		{
			if( $curr == $step )
			{
				//echo display_array($adresse_id_list);
				$this->createTokenTo($adresse_id_list, 'WFAcquisition');
				$adresse_id_list = array() ;
				$curr = 0 ;
			}
			$adresse_id_list[] = array( 'id' => $row[ 0 ], 'adresse_id'=>NULL ) ;
			$curr++ ;
		
		}//print_r($adresse_id_list);
		mysqli_free_result($result);
		if( $curr != 0 )
		{
			$this->createTokenTo($adresse_id_list,'WFAcquisition','start_point','WFAcquisition','saisie_initiale');
		}
		
		$this->_hDB->db_commit() ;
		return TRUE;
	}
	//id_list: id selectinné dans le workflow
	// $from_workflow_name: workflow dentree
	//$from_node_name: node d'entree
	/*function check_insert_prospect(&$id_list, $from_workflow_name, $from_node_name){
		 $retour = TRUE;
		 if ($from_node_name == 'prospect')
		 {
		 	$retour = $this->check_prestataire_ok($id_list);
		 }
		 return $retour;
	}*/
	
	
	/**********On vérifie que l'élement à déplacer se trouve dans le noeud dans lequel on se trouve**********/
	
	public function pre_insert_prospect(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
		
		$assoc=array();
		$total_record=0;
		
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
	
	
	public function pre_insert_refus_du_sujet(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
	
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
	
	
	public function pre_insert_refus_doffre(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
	
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
	
	
	
	
	public function pre_insert_retrait_promesse(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
	
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
	
	
	public function pre_insert_offre(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
		foreach ($id_list as $key => $value){
			$id[]=$value['id'];
		}
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
	
	
	
	
	public function pre_insert_promesse(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
		foreach ($id_list as $key => $value){
			$id[]=$value['id'];
		}
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
	
	public function pre_insert_authentique(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
		foreach ($id_list as $key => $value){
			$id[]=$value['id'];
		}
		$assoc=array();
		$total_record=0;
	
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
	
	
	public function pre_insert_acquis(&$id_list, $from_workflow_name, $from_node_name, $from_object, $to_object){
		$id=array();
		foreach ($id_list as $key => $value){
			$id[]=$value['id'];
		}
		$assoc=array();
		$total_record=0;
		
		$hORM=ORM::getORMInstance('Node');
		$hORM->browse($assoc,$total_record, array('workflow_node_id'), array(array('node_name', 'like', $from_node_name)));
		foreach ($assoc as $key => $value){
			$idNode=$value['workflow_node_id']['value'];
		}
		$assoc2=array();
		$hORM_id=ORM::getORMInstance('WorkflowToken');
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
		if($from_workflow_name=='wfacquisition' && $from_node_name=='authentique'){
			$hormACQ=ORM::getORMInstance('Acquisition');
			foreach ($id_list as $local_id => $local){
				$hormACQ->create(array('acquisition_id' => $local_id));
			}
			return true;
		}
	}
	
	
}