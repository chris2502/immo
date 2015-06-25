<?php

class MiseEnVenteMethod extends Common{

	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		//echo "<pre>"; print_r($data['entite']); echo "</pre>"; die();
		parent::edit($view,$data,$total_object_list,$template_name);
		if(isset($_GET['workflow_node_id'])){
				
				$hORM_mise_en_vente=ORM::getORMInstance('miseenvente');
				$hORM_work=ORM::getORMInstance('workflowtoken');
				$mise_en_vente_id=array();
				$work=array();
				$work_id=array();
				$total2=0;
				$hORM_work->browse($work, $total2, array('id'));
				
				foreach ($work as $key => $value ){
					$work_id[]=$value['id']['value'];
				}
				//echo "<pre>"; print_r($work_id); echo "</pre>"; die();
				$hORM_mise_en_vente->browse($mise_en_vente_id, $total, array('mise_en_vente_id'), array(array('mise_en_vente_id', 'NOT IN', $work_id)));
				
				foreach($mise_en_vente_id as $key => $value){
					$hORM_work->create(array('node_id' => '37', 'id' =>$value['mise_en_vente_id']['value']));
				}
		}
		
		/************************************************************************************************/
		if (isset($_GET['view']) && $_GET['view']=='form')
		{
			//echo "<pre>"; print_r($_SESSION); echo "</pre>"; die();
			/*if(isset($_SESSION['_USER']['profil_id']['value']) && $_SESSION['_USER']['profil_id']['value'][0]!=1){
				$data['miseenvente']['valide']['editable']=FALSE;	
			}*/
			$pk = $_GET['primary_key'];
			/*if(!in_array(37, $data['miseenvente'][$pk]['statut_acquisition']['workflow_node_id'])   &&
					!in_array(10, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])){
				$data['local'][$pk]['departement']['editable']=FALSE;
				$data['local'][$pk]['plaque_id']['editable']=FALSE;
				$data['local'][$pk]['numero_rue']['editable']=FALSE;
				$data['local'][$pk]['type_rue']['editable']=FALSE;
				$data['local'][$pk]['nom_rue']['editable']=FALSE;
				$data['local'][$pk]['code_postal']['editable']=FALSE;
				$data['local'][$pk]['commune']['editable']=FALSE;
					
				if(!in_array(12, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])){
					$data['local'][$pk]['estimation_prix']['editable']=FALSE;
						
				}
		
			}
			*/
			/*$hORM_ent = ORM::getORMInstance('entite');
			$hORM_resp=ORM::getORMInstance('responsable');
			$hORM_trav=ORM::getORMInstance('travaux');
			$hORM_ass=ORM::getORMInstance('assembleegenerale');*/
			$hORM_offre=ORM::getORMInstance('offre');
			$hORM_mandat=ORM::getORMInstance('mandat');
			//$hORM_taxe=ORM::getORMInstance('taxe');
			$hORM_comment=ORM::getORMInstance('commentaire');
			/*$resultat_ent = array();
			$resultat_resp=array();
			$resultat_trav=array();
			$resultat_ass=array();*/
			$resultat_offre=array();
			$resultat_mandat=array();
			//$resultat_taxe=array();
			$resultat_commentaire=array();
				
			/*$hORM_resp->read($data['local'][$pk]['responsable']['value'],$resultat_resp,array('nom_resp','type'));
			$hORM_ent->read($data['local'][$pk]['entite']['value'],$resultat_ent,array('type','nom'));
			$hORM_trav->read($data['local'][$pk]['travaux_id']['value'],$resultat_trav,array('date_debut_travaux', 'date_fin_travaux'));
			$hORM_ass->read($data['local'][$pk]['assemblee_generale_id']['value'],$resultat_ass,array('date_ag'));*/
			$hORM_offre->read($data['miseenvente'][$pk]['offre_id']['value'],$resultat_offre,array('montant_offre','validation_commission','reponse_a_offre', 'prixLocationByCarre', 'montantOffreByCarre'));
			$hORM_mandat->read($data['miseenvente'][$pk]['mandat_id']['value'],$resultat_mandat,array('montant', 'detenteur', 'validite'));
			//$hORM_taxe->read($data['local'][$pk]['taxe_id']['value'],$resultat_taxe,array('type_taxe_id','montant'));
			$hORM_comment->read($data['miseenvente'][$pk]['commentaire_id']['value'],$resultat_commentaire,array('titre_commentaire','contenu', 'etape'));
				
			/*$data['entite'] = $resultat_ent;
			$data['responsable']=$resultat_resp;
			$data['travaux']=$resultat_trav;
			$data['assembleegenerale']=$resultat_ass;*/
			$data['offre']=$resultat_offre;
			$data['mandat']=$resultat_mandat;
			//$data['taxe']=$resultat_taxe;
			$data['commentaire'] = $resultat_commentaire;
			//$data['title']['entity'] = 'Agence/Syndicat/PCS/Propriétaire';
			if(in_array(42, $data['miseenvente'][$pk]['statut_revente']['workflow_node_id'])){
				$template_name='./../workflow/template/offre_revente.xml';
			
			}
			
			if(in_array(19, $data['miseenvente'][$pk]['statut_revente']['workflow_node_id'])){
				$template_name='./../workflow/template/promesse_revente.xml';
					
			}
			
			if(in_array(37, $data['miseenvente'][$pk]['statut_revente']['workflow_node_id'])){
				$template_name='./../workflow/template/dossier_revente.xml';
					
			}
			if(in_array(22, $data['miseenvente'][$pk]['statut_revente']['workflow_node_id'])){
				$template_name='./../workflow/template/authentique_revente.xml';
					
			}
		}
		return true;
	}

	public static function setNotaire(&$adresse_list){
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr)
		{
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['notaire']['value'] = FALSE;
			$adresse_list[$adr_id]['notaire']['editable'] = FALSE;
		}
		//Offre et pas notaire car il existe notaireCheck qui est un setFunction de notaire dans offre
		$hORM = ORM::getORMInstance('Offre');
		
		$hORM_mandat = ORM::getORMInstance('Mandat');
		
		$assoc_list=array();
		$assoc_list2=array();
		$hORM->browse($assoc_list, $num, array('offre_id', 'notaireCheck', 'montant_offre', 'prix_location', 'validation_commission',
											  'reponse_a_offre', 'date_validation_commission','date_promesse'), 
										 array(array('mise_en_vente_id', 'in', $adresse_list_id)));
		$hORM_mandat->browse($assoc_list2, $num2, array('mandat_id', 'montant', 'detenteur', 'validite'),
										  array(array('mise_en_vente_id', 'in', $adresse_list_id)));
		
		//echo "<pre>"; print_r($assoc_list); echo "</pre>"; die();
		$idMiseVente=array();
		$idMiseVente2=array();
		foreach($assoc_list AS $key => $value)
		{//echo "<pre>"; print_r($value['validation_commission']); echo "</pre>"; die();
			if($value['notaireCheck']==TRUE && $value['montant_offre']!=NULL && $value['prix_location']!=NULL &&
					isset($value['validation_commission']) && isset($value['reponse_a_offre']) &&
					in_array($value['validation_commission']['reference'], array('Accepté', 'En etude', 'En attente','Refusé')) &&
					in_array($value['reponse_a_offre']['reference'], array('Accepté', 'En etude', 'En attente','Refusé')) && 
					$value['date_validation_commission']!=NULL && $value['date_promesse']!=NULL){
				$idMiseVente[]=$value['mise_en_vente_id']['value'];
			}
		}
		foreach ($assoc_list2 as $key => $value){
			if($value['montant']!=NULL && $value['detenteur']!=NULL && $value['validite']!=NULL){
				$idMiseVente2[]=$value['mise_en_vente_id']['value'];
			}
		}
		$size1=count($idMiseVente);
		$size2=count($idMiseVente2);
		//echo "<pre>"; print_r($idMiseVente); echo "</pre>";
		//echo "<pre>"; print_r($idMiseVente2); echo "</pre>"; die();
		for($i=0, $j=0; $i< $size1 && $j<$size2; $i=$i+1, $j=$j+1){
			if(in_array($idMiseVente[$i], $adresse_list_id) && in_array($idMiseVente2[$j], $adresse_list_id) &&
				$idMiseVente[$i]==$idMiseVente2[$j]){
				$key=$idMiseVente[$i];
				$adresse_list[$key]['notaire']['value'] = TRUE;
			}
		}
		
		unset($assoc_list);
		unset($adresse_list_id);
		
		return TRUE;
	}
	
	
	//verifier que la clé de mise_en_vente corresponde à celle de offre et que cette dernière qui est reliée à notaire
	
	public static function setAuthentique(&$adresse_list){
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr)
		{
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['authentique']['value'] = FALSE;
			$adresse_list[$adr_id]['authentique']['editable'] = FALSE;
		}
		//Offre et pas notaire car il existe notaireCheck qui est un setFunction de notaire dans offre
		$hORM = ORM::getORMInstance('Offre');
	
		$assoc_list=array();
		$hORM->browse($assoc_list, $num, array('offre_id', 'authentiqueCheck', 'notaireCheck'), array(array('mise_en_vente_id', 'in', $adresse_list_id)));
		//echo "<pre>"; print_r($assoc_list); echo "</pre>"; die();
		$idMiseVente=array();

		foreach($assoc_list AS $key => $value)
		{//echo "<pre>"; print_r($value['validation_commission']); echo "</pre>"; die();
			if($value['authentiqueCheck']['value']==TRUE && $value['notaireCheck']['value']==TRUE){
				$idMiseVente[]=$value['mise_en_vente_id']['value'];
			}
		}
	
		//echo "<pre>"; print_r($assoc_list); echo "</pre>";
		//echo "<pre>"; print_r($adresse_list_id); echo "</pre>"; die();
		foreach($idMiseVente as $key => $value){
			if(in_array($value, $adresse_list_id)){
				$adresse_list[$value]['authentique']['value'] = TRUE;
			}
		}
		unset($assoc_list);
		unset($adresse_list_id);
	
		return TRUE;
	}
	
	
/*	
	public static function setAdresse(&$local_list){
		self::checkAttributesDependencies('MiseEnVente', $local_list, array('numero_rue', 'type_rue', 'nom_rue', 'commune', 'code_postal'));
		if(!empty($local_list))
		{
			global $hDB;
	
			foreach($local_list as &$local)
			{
	
				$local['adresse']['value']=$local['numero_rue']['value'].' '.
						$local['type_rue']['value'].' '.$local['nom_rue']['value'].' '.
						$local['commune']['value'].' '.$local['code_postal']['value'];
	
				$local['adresse']['editable'] = FALSE;
	
	
			}
		}
		return TRUE;
	}	
	
*/	

	
}