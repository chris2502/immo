<?php
class OffreMethod extends Common{

	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{	
		parent::edit($view,$data,$total_object_list,$template_name);
		if (isset($_GET['view']) && $_GET['view']=='form'){
			$pk = $_GET['primary_key'];
			if (isset($_GET['view']) && $_GET['view']=='form')
			{//echo "<pre>"; print_r($data); echo "</pre>"; die();
				$pk = $_GET['primary_key'];
				//tant qu'on est pas dans revente on ne peut éditer mise_en_vente
				if((isset($data['offre'][$pk]['statut_acquisition']['workflow_node_id'])) && 
						(in_array(7, $data['offre'][$pk]['statut_acquisition']['workflow_node_id']) ||
						in_array(10, $data['offre'][$pk]['statut_acquisition']['workflow_node_id']) ||
						in_array(12, $data['offre'][$pk]['statut_acquisition']['workflow_node_id']) ||
						in_array(13, $data['offre'][$pk]['statut_acquisition']['workflow_node_id']) ||
						in_array(15, $data['offre'][$pk]['statut_acquisition']['workflow_node_id']) ||
						in_array(8, $data['offre'][$pk]['statut_acquisition']['workflow_node_id'])  ||
						in_array(11, $data['offre'][$pk]['statut_acquisition']['workflow_node_id']) ||
						in_array(14, $data['offre'][$pk]['statut_acquisition']['workflow_node_id']))){
					if(in_array(12, $data['offre'][$pk]['statut_acquisition']['workflow_node_id'])){
						$template_name='./offretemp.xml';
					}
					foreach ($data['offre'] as $keys => &$values){
						$values['etape']['editable']=FALSE;
						$values['mise_en_vente_id']['editable']=FALSE;
					}
				}
				if((isset($data['offre'][$pk]['statut_revente']['workflow_node_id'])) && 
						(in_array(42, $data['offre'][$pk]['statut_revente']['workflow_node_id']))){
					$template_name='./offretemp0.xml';
					foreach ($data['offre'] as $keys => &$values){
						$values['local_id']['editable']=FALSE;
						$values['date_promesse']['editable']=FALSE;
					}
				}
				if((isset($data['offre'][$pk]['statut_revente']['workflow_node_id'])) &&
						(in_array(19, $data['offre'][$pk]['statut_revente']['workflow_node_id']))){
					$template_name='./offretemp.xml';
				}
				
			}
			$hORM_offre=ORM::getORMInstance('notaire');
			$hORM_offre_notaire = ORM::getORMInstance('offrenotaire');
			
			$resultat_not=array();
			$resultat_offre_notaire = array();
			
			$hORM_offre->read($data['offre'][$pk]['notaire']['value'],$resultat_not,array('nom', 'adresse','frais', 'autre_acte'));
			$hORM_offre_notaire->read($data['offre'][$pk]['offre_notaire_id']['value'],$resultat_offre_notaire, array('date_acte_authentique', 'offre_id', 'notaire_id'));
			//echo "<pre>"; print_r($_GET); echo "</pre>"; die();
			$data['offrenotaire']=$resultat_offre_notaire;
			$data['notaire']=$resultat_not;
			
			//echo "<pre>"; print_r($data); echo "</pre>"; die();
		}
		//input_name me permet de savoir que la fenêtre create de offre vient d'un workflow
		if (isset($_GET['view']) && $_GET['view']=='create' && isset($_GET['input_name']) && $_GET['input_name']='Offre/offre_id'){
			$adress_list=array();
			$hORM_offre=ORM::getORMInstance('Local');
			
			if(isset($_GET['offre_local_id'])){
				$offre_local_id=$_GET['offre_local_id'];
				$_POST['offre/local_id']=$offre_local_id;
				$hORM_offre->browse($adress_list, $total, array('statut_acquisition'), array(array($offre_local_id, '=', 'local_id')));
				/*if(in_array(7,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id'])  ||
						in_array(10,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id']) ||
						in_array(12,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id']) ||
						in_array(13,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id']) ||
						in_array(15,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id']) ||
						in_array(8,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id'])  ||
						in_array(11,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id']) ||
						in_array(14,$adress_list[$offre_local_id]['statut_acquisition']['workflow_node_id'])){*/
					ORM::getObjectInstance('Offre')->mise_en_vente_id->setEditable(FALSE);
				//}
			}
			
			if(isset($_GET['offre_mise_en_vente_id'])){
				$offre_mise_en_vent_id=$_GET['offre_mise_en_vente_id'];
				$_POST['offre/mise_en_vente_id']=$offre_mise_en_vent_id;
				ORM::getObjectInstance('Offre')->local_id->setEditable(FALSE);
			}
			
		}
		
		return TRUE;
	}
	
	
	public static function setPrixLocationByCarre(&$offre_list){
		self::checkAttributesDependencies('Offre', $offre_list, array('surface_m_carre', 'prix_location'));
		
		if(!empty($offre_list))
		{
			global $hDB;
		
			foreach($offre_list as &$offre)
			{
				if($offre['surface_m_carre']['value']!=0 && $offre['prix_location']['value']!=NULL){
					$offre['prixLocationByCarre']['value']=(float) $offre['prix_location']['value'] /$offre['surface_m_carre']['value'];
					$offre['prixLocationByCarre']['value']=round($offre['prixLocationByCarre']['value'], 2);
					$offre['prixLocationByCarre']['editable'] = FALSE;
				}
				
			}
		}
	//echo "<pre>"; print_r($offre_list); echo "</pre>";
	//die();
		return TRUE;
	}
	
	
	public static function setMontantOffreByCarre(&$offre_list){
		self::checkAttributesDependencies('Offre', $offre_list, array('surface_m_carre', 'montant_offre'));
	
		if(!empty($offre_list))
		{
			global $hDB;
	
			foreach($offre_list as &$offre)
			{
				if($offre['surface_m_carre']['value']!=0){
					$offre['montantOffreByCarre']['value']=(float) $offre['montant_offre']['value'] /$offre['surface_m_carre']['value'];
					$offre['montantOffreByCarre']['value']=round($offre['montantOffreByCarre']['value'], 2);
					$offre['montantOffreByCarre']['editable'] = FALSE;
				}
	
			}
		}
		//echo "<pre>"; print_r($offre_list); echo "</pre>";
		//die();
		return TRUE;
	}
	
	public static function setNotaireCheck(&$adresse_list){
		//self::checkAttributesDependencies('Offre', $offre_list, array('surface_m_carre', 'montant_offre'));
		static $tab=array();
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr)
		{
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['notaireCheck']['value'] = FALSE;
			$adresse_list[$adr_id]['notaireCheck']['editable'] = FALSE;
		}
		
		$hORM = ORM::getORMInstance('Notaire');
		
		$hORM_offre_notaire=ORM::getORMInstance('OffreNotaire');
		$assoc_list=array();
		//on recupère toutes les lignes de entite
		$hORM->browse($assoc_list, $num, array('notaire_id', 'type', 'nom', 'tel', 'mobile', 'adresse', 'email', 'fax'), array(array('offre', 'in', $adresse_list_id)));
		$idNotaire=array();
		//echo "<pre>"; print_r($adresse_list); echo "</pre>";
		//die();
		foreach($assoc_list AS $id => $ra){
			//on vérifie les lignes de notaire qui satisfont la condition suivante
			if(($ra['nom']['value']!=NULL && $ra['tel']['value']!=NULL && $ra['mobile']['value']!= NULL && 
				$ra['adresse']['value']!=NULL && $ra['email']['value']!=NULL && $ra['fax']['value']!=NULL)){
				$idNotaire[]=$id;
				//$adresse_list[$value['offre_id']['value']]['notaireCheck']['editable'] = TRUE;
			}
				
		}
		$hORM_offre_notaire->browse($assoc_list2, $num, array('offre_notaire_id'), array(array('notaire_id', 'in', $idNotaire)));
		
		foreach($assoc_list2 AS $key => $value)
		{
			if(in_array($value['offre_id']['value'], $adresse_list_id)){
				//$adresse_list[$value['offre_id']['value']]['notaireCheck']['editable'] = TRUE;
				$adresse_list[$value['offre_id']['value']]['notaireCheck']['value'] = TRUE;
			}
		}
		
		unset($assoc_list);
		unset($adresse_list_id);
	
		return TRUE;
	}
	
	public static function setAuthentiqueCheck(&$adresse_list){
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr){
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['authentiqueCheck']['value'] = FALSE;
			$adresse_list[$adr_id]['authentiqueCheck']['editable'] = FALSE;
		}
		$hORM_offre_notaire=ORM::getORMInstance('OffreNotaire');
		$assoc_list=array();
		$hORM_offre_notaire->browse($assoc_list, $num, array('offre_notaire_id', 'date_acte_authentique'), array(array('offre_id', 'in', $adresse_list_id)));
		
		foreach ($assoc_list as $key => $value){
			if($value['date_acte_authentique']['value']!=NULL){
				$adresse_list[$value['offre_id']['value']]['authentiqueCheck']['value']=TRUE;
			}
		}
		
		//echo "<pre>"; print_r($offre_list); echo "</pre>";
		//die();
		return TRUE;
	}
	
}