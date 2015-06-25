<?php

class LocalMethod extends Common{
	/****************************************Modification, edtion de l'objet local pour la vue************************** */
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		parent::edit($view,$data,$total_object_list,$template_name);
		if(!empty($data) && !empty($data['local'])){
			foreach ($data['local'] as $key => $value){
				if(($value['moyen_marche']['value']*2)< $value['prix_demande']['value']){
					Alert::warning('Adresse:'.$value['numero_rue']['value'].' '.$value['nom_rue']['value'].' '.
									$value['code_postal']['value'].' '.$value['commune']['value'], 
					'Attention:Le prix demandé est au moins 2 fois > à la moyenne du marché');
				}
			}
		}
		/****************************** Si on est dans une node de workflow ********************************************/
		if(isset($_GET['workflow_node_id'])){//echo "<pre>"; print_r($data); echo "</pre>"; die();
			//chargement des tables local et workflowtoken dans des objets
			$hORM_local=ORM::getORMInstance('local');
			$hORM_test=ORM::getObjectInstance('local');
			//echo "<pre>"; print_r($hORM_local); echo "</pre>"; die();
			$hORM_work=ORM::getORMInstance('workflowtoken');
			$local_id=array();
			$work=array();
			$work_id=array();
			$total1=0;
			$total2=0;
			// Lecture de l'objet(table) workflowtoken
			
			$hORM_work->browse($work, $total2, array('id'));
			//On mémorise des id(local_id de la table workflowtoken
			foreach ($work as $key => $value ){
				$work_id[]=$value['id']['value'];
			}
			// On recupère les id des locaux non associés aux id du workflowtoken  
			$hORM_local->browse($local_id, $total, array('local_id'), array(array('local_id', 'NOT IN', $work_id)));
			/*On les associe prématurément. Ceci permet d'afficher le statut du workflow dans la vue liste après
			rafraichissement de la page. Ceci permet d'éviter de retourner à la page du shéma des workflow 
			et réactualiser cette page.*/
			foreach($local_id as $key => $value){
				$hORM_work->create(array('node_id' => '7', 'id' =>$value['local_id']['value']));
			}
		}
		
		
		/*Ceci permet entre autre d'éviter une imbrication de création d'objet
		A noter que lorsque l'on est en vue form on peut accéder au données des objets grâce à $data
		mais aussi grace $_GET 
		*/
		if (isset($_GET['view']) && $_GET['view']=='form')
		{
			$pk = $_GET['primary_key'];
			//permet d'empecher l'édition de champs ci-dessous dans la vue forme
			if(!in_array(7, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])   &&
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
			//permet d'afficher dans la vue form l'objet entite, responsable
			//les données contenues dans data sont celle qui seront utilisées par la vue pour l'affichage
			$hORM_ent = ORM::getORMInstance('entite');
			$hORM_resp=ORM::getORMInstance('responsable');
			$hORM_trav=ORM::getORMInstance('travaux');
			$hORM_ass=ORM::getORMInstance('assembleegenerale');
			$hORM_offre=ORM::getORMInstance('offre');
			$hORM_taxe=ORM::getORMInstance('taxe');
			$hORM_comment=ORM::getORMInstance('commentaire');
			$resultat_ent = array();
			$resultat_resp=array();
			$resultat_trav=array();
			$resultat_ass=array();
			$resultat_offre=array();
			$resultat_taxe=array();
			$resultat_commentaire=array();
			
			$hORM_resp->read($data['local'][$pk]['responsable']['value'],$resultat_resp,array('nom_resp','type'));			
			$hORM_ent->read($data['local'][$pk]['entite']['value'],$resultat_ent,array('type','nom'));
			$hORM_trav->read($data['local'][$pk]['travaux_id']['value'],$resultat_trav,array('description_travaux','date_debut_travaux', 'date_fin_travaux'));			
			$hORM_ass->read($data['local'][$pk]['assemblee_generale_id']['value'],$resultat_ass,array('date_ag'));			
			$hORM_offre->read($data['local'][$pk]['offre_id']['value'],$resultat_offre,array('montant_offre', 'validation_commission','reponse_a_offre', 'prixLocationByCarre', 'montantOffreByCarre'));			
			$hORM_taxe->read($data['local'][$pk]['taxe_id']['value'],$resultat_taxe,array('type_taxe_id','montant'));			
			$hORM_comment->read($data['local'][$pk]['commentaire_id']['value'],$resultat_commentaire,array('titre_commentaire','contenu', 'etape'));
			//echo "<pre>"; print_r($resultat_offre); echo "</pre>"; die();
			$data['entite'] = $resultat_ent;
			$data['responsable']=$resultat_resp;	
			$data['travaux']=$resultat_trav;
			$data['assembleegenerale']=$resultat_ass;
			$data['offre']=$resultat_offre;
			$data['taxe']=$resultat_taxe;
			$data['commentaire'] = $resultat_commentaire;
			//permet de personnaliser le titre l'objet affiché dans la vue
			$data['title']['entity'] = 'Agence/Syndicat/PCS/Propriétaire/Notaire/Avocats';
			
			// Par défaut c'est le template de l'objet qui est utilisé dans la vue form
			//Ici je le chemin du template pour afficher le template de chaque noeud du workflow
			if(in_array(7, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])){
				$template_name='./../workflow/template/saisie_initiale.xml';
				
			}
			if(in_array(10, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])){
				$template_name='./../workflow/template/prospect.xml';	
			}
			
			if(in_array(12, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])){
				$template_name='./../workflow/template/offre.xml';
			}
			
			if(in_array(13, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])){
				$template_name='./../workflow/template/promesse.xml';
			}
			if(in_array(36, $data['local'][$pk]['statut_acquisition']['workflow_node_id'])){
				$template_name='./../workflow/template/authentique.xml';
			}			
		}
		return TRUE;
	}
	
	public function getVal(){
		return 0;
	}
	
	/**************************************************csv**************************************/
	
	public function export_csv($view, $fields_to_export = NULL, $export_name ='local.php'){
		$fields = ORM::getObjectInstance('local');
		parent::export_csv($view, $fields_to_export, $export_name);
		return true;
	}
	
	
	
	/*******************************Pagination***************************************************/
	
	//Pour la pagination des objets affichés dans la vue form de local
	public static function listing_assemblee_generale(&$data_src, &$total, $limit, $offset, $filters = array())
	{
		$pk = $_GET['primary_key'];

		$filters[] = array('local_id', '=', $pk);
		
		$hORM_ass=ORM::getORMInstance('assembleegenerale', TRUE);
		$hORM_ass->browse($data_src, $total, array('date_ag'), $filters, array(), $offset, $limit);

		return TRUE;
	}
	
	public static function listing_offre(&$data_src, &$total, $limit, $offset, $filters = array())
	{
		$pk = $_GET['primary_key'];
	
		$filters[] = array('local_id', '=', $pk);
	
		$hORM_ass=ORM::getORMInstance('offre', TRUE);
		$hORM_ass->browse($data_src, $total, array('montant_offre', 'validation_commission','reponse_a_offre', 'prixLocationByCarre', 'montantOffreByCarre'), $filters, array(), $offset, $limit);
	
		return TRUE;
	}
	
	public static function listing_taxe(&$data_src, &$total, $limit, $offset, $filters = array())
	{
		$pk = $_GET['primary_key'];
	
		$filters[] = array('local_id', '=', $pk);
	
		$hORM_ass=ORM::getORMInstance('taxe', TRUE);
		$hORM_ass->browse($data_src, $total, array('montant'), $filters, array(), $offset, $limit);
	
		return TRUE;
	}
	
	public static function listing_entite(&$data_src, &$total, $limit, $offset, $filters = array())
	{
		$pk = $_GET['primary_key'];
	
		$filters[] = array('local', '=', $pk);
	
		$hORM_ass=ORM::getORMInstance('entite', TRUE);
		$hORM_ass->browse($data_src, $total, array('type', 'nom'), $filters, array(), $offset, $limit);
	
		return TRUE;
	}
	
	public static function listing_commentaire(&$data_src, &$total, $limit, $offset, $filters = array())
	{
		$pk = $_GET['primary_key'];
	
		$filters[] = array('local_id', '=', $pk);
	
		$hORM_ass=ORM::getORMInstance('commentaire', TRUE);
		$hORM_ass->browse($data_src, $total, array('titre_commentaire','contenu', 'etape'), $filters, array(), $offset, $limit);
	
		return TRUE;
	}
	
	public static function listing_travaux(&$data_src, &$total, $limit, $offset, $filters = array())
	{
		$pk = $_GET['primary_key'];
	
		$filters[] = array('local_id', '=', $pk);
	
		$hORM_ass=ORM::getORMInstance('travaux', TRUE);
		$hORM_ass->browse($data_src, $total, array('description_travaux','date_debut_travaux', 'date_fin_travaux'), $filters, array(), $offset, $limit);
	
		return TRUE;
	}
	
	
	
	/*****************************************setFunction (champs calculé en fonction d'autres champs)*********/
	
	//calcul le prix par metre carre en fonction  de  la surface en metre carre et du prix moyen
	public static function setEstimationPrixByCarre(&$local_list)
	{
		//checkAttributedependencies remplit local_list avec les champs de la classe Local. 
		//Ainsi je peux acceder aux attributs de la classe
		self::checkAttributesDependencies('Local', $local_list, array('surface_brut', 'estimation_prix'));
		if(!empty($local_list))
		{
			global $hDB;
	
			foreach($local_list as &$local)
			{
				if($local['surface_brut']['value']!=0){
					$local['estimationPrixByCarre']['value']=(0.0 + $local['estimation_prix']['value']) / (0.0 + $local['surface_brut']['value']);
					$local['estimationPrixByCarre']['value']=round($local['estimationPrixByCarre']['value'],2);
					$local['estimationPrixByCarre']['editable'] = FALSE;
				}
			}
		}
		return TRUE;
	}
	
	
	public static function setPrixDemandeByCarre(&$local_list)
	{
		//checkAttributedependencies remplit local_list avec les champs de la classe Local.
		//Ainsi je peux acceder aux attributs de la classe
		self::checkAttributesDependencies('Local', $local_list, array('surface_brut', 'estimation_prix'));
		if(!empty($local_list))
		{
			global $hDB;
	
			foreach($local_list as &$local)
			{
				if($local['surface_brut']['value']!=0){
					$local['prixDemandeByCarre']['value']=(0.0 + $local['prix_demande']['value']) / (0.0 + $local['surface_brut']['value']);
					$local['prixDemandeByCarre']['value']=round($local['prixDemandeByCarre']['value'],2);
					$local['prixDemandeByCarre']['editable'] = FALSE;
				}
			}
		}
		return TRUE;
	}
	
	public static function checkSurface(){
		$local_list=array();
		self::checkAttributesDependencies('Local', $local_list, array('surface_brut', 'surface_m_carre'));
		if(!empty($local_list)){
			foreach ($local_list as &$local){
				if ($local['surface_m_carre']['value'] > $local['surface_brut']['value']){
					$message="La surface brute doit être supérieure ou égale à la surface en mètre carré";
					Alert::warning("poorly completed form", $message);
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	
	
	
	public static function setAcquisitionId(&$local_list)
	{
		//checkAttributedependencies remplit local_list avec les champs de la classe Local.
		//Ainsi je peux acceder aux attributs de la classe
		$ACQ=ORM::getORMInstance('Acquisition');
		$objectAcq=array();
		//$ACQ->readOne('acquisition_id', $objectAcq, array('acquisition_id'));
		if(!empty($local_list))
		{
			global $hDB;
	
			foreach($local_list as &$local)
			{
				foreach ($objectAcq as $keys => $value ){
					
					if($value['acquisition_id']['value']== $local['local_id']['value']){
				
						$local['acquisition_id']['value']=$local['local_id']['value'];
						$local['acquisition_id']['editable'] = FALSE;
					}
				}
			}
		}
		return TRUE;
	}
	
	public static function setTravaux(&$adresse_list){
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr)
		{
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['travaux']['value'] = FALSE;
			$adresse_list[$adr_id]['travaux']['editable'] = FALSE;
		}
		
		$hORM = ORM::getORMInstance('travaux');
		
		$hORM->browse($assoc_list, $num, array('travaux_id'), array(array('local_id', 'in', $adresse_list_id)));
		//echo "<pre>"; print_r($assoc_list); echo "</pre>"; die();
		$idLocal=array();
		foreach($assoc_list AS $key => $value)
		{
			$idLocal[]=$value['local_id']['value'];
		}
		foreach ($idLocal as $key){
			if(in_array($key, $adresse_list_id)){
				$adresse_list[$key]['travaux']['editable'] = TRUE;
				$adresse_list[$key]['travaux']['value'] = TRUE;
			}
		}
		
		unset($assoc_list);
		unset($adresse_list_id);
		
		return TRUE;
	}
	
	
	public static function setMyEntite(&$adresse_list){
		static $tab=array();
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr)
		{
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['myEntite']['value'] = FALSE;
			$adresse_list[$adr_id]['myEntite']['editable'] = FALSE;
		}
		
		$hORM = ORM::getORMInstance('Entite');
		$hORM_local_entite=ORM::getORMInstance('LocalEntite');
		$assoc_list=array();
		//on recupère toutes les lignes de entite
		$hORM->browse($assoc_list, $num, array('entite_id', 'type', 'nom', 'tel', 'mobile', 'adresse', 'email', 'fax'), array(array('local', 'in', $adresse_list_id)));
		$idEntite=array();
		foreach($assoc_list AS $id => $ra){
			//on vérifie les lignes de entite qui satisfont la condition suivante
			if(($ra['type']['reference']==strtolower('Agence') || $ra['type']['reference']==strtolower('Proprietaire') || 
					$ra['type']['reference']==strtolower('Notaire')) && $ra['nom']['value']!=NULL && 
					$ra['tel']['value']!=NULL && $ra['mobile']['value']!=NULL &&
					$ra['adresse']['value']!=NULL && $ra['email']['value']!=NULL && $ra['fax']['value']!=NULL){
					$tab[]=$ra['type']['reference'];
					$idEntite[]=$id;
			}
			
		}
		if(!in_array(strtolower('Agence'), $tab) || !in_array(strtolower('Proprietaire'), $tab) || !in_array(strtolower('Notaire'), $tab)){
			return TRUE;
		}
		//on recupère les locaux qui respectent la condition ci-dessus sur les entités	
		$hORM_local_entite->browse($assoc_list2, $num, array('local_entite_id'), array(array('entite_id', 'in', $idEntite)));

		foreach($assoc_list2 AS $key => $value)
		{		
				if(in_array($value['local_id']['value'], $adresse_list_id)){
					$adresse_list[$value['local_id']['value']]['myEntite']['editable'] = TRUE;
					$adresse_list[$value['local_id']['value']]['myEntite']['value'] = TRUE;
				}
		}
		$tab=array();
		unset($assoc_list);
		unset($adresse_list_id);
	
		return TRUE;
	}
	
	
	public static function setMyOffre(&$adresse_list){
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr)
		{
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['myOffre']['value'] = FALSE;
			$adresse_list[$adr_id]['myOffre']['editable'] = FALSE;
		}
		$assoc_list=array();
		$hORM = ORM::getORMInstance('Offre');
		$hORM->browse($assoc_list, 
					  $total, 
					  array('local_id', 'validation_commission', 'reponse_a_offre', 'date_validation_commission', 'date_retour_offre', 
					  		'date_promesse', 'montant_offre', 'prix_location', 'frai_fai'),
						array(array('local_id', 'in', $adresse_list_id)));
		
		
		foreach($assoc_list AS $id => $ra){
			if($ra['validation_commission']['value']!=NULL && $ra['reponse_a_offre']['value']!=NULL && 
				$ra['date_validation_commission']['value']!=NULL && $ra['date_retour_offre']['value']!=NULL && 
				$ra['date_promesse']['value']!=NULL && $ra['montant_offre']['value']!=NULL && 
				$ra['prix_location']['value']!=NULL && $ra['frai_fai']['value']!=NULL) {
					$idLocal=$ra['local_id']['value'];
					$adresse_list[$idLocal]['myOffre']['value']=TRUE;
				}
		}
		//echo "<pre>"; print_r($adresse_list); echo "</pre>"; die();
		return TRUE;
	}
	
	
	public static function setDateAuthentique(&$adresse_list){
		$adresse_list_id = array();
		foreach($adresse_list as $adr_id => $adr)
		{
			$adresse_list_id[] = $adr_id;
			$adresse_list[$adr_id]['dateAuthentique']['value'] = FALSE;
			$adresse_list[$adr_id]['dateAuthentique']['editable'] = FALSE;
		}
		$assoc_list=array();
		$hORM = ORM::getORMInstance('OffreNotaire');
		$hORM->browse($assoc_list, $total, array('date_acte_authentique', 'notaire_id', 'offre_id'));
		
		$pkOffre=array();
		foreach ($assoc_list as $key => $value){
			if(isset($value['date_acte_authentique']['reference'])){
				$pkOffre[]=$value['offre_id']['value'];
			}
		}
		$hORM_offre = ORM::getORMInstance('Offre');
		$assoc_list=array();
		$hORM_offre->browse($assoc_list, $total, array('local_id'), (array(array("offre_id", "in", $pkOffre))));
		
		foreach ($assoc_list as $key => $value){
			if(in_array($value['local_id']['value'], $adresse_list_id)){
				$adresse_list[$adr_id]['dateAuthentique']['value'] = TRUE;
			}
		}
		//echo "<pre>"; print_r($assoc_list); echo "</pre>"; die();
		//echo "<pre>"; print_r($adresse_list); echo "</pre>"; die();
		return TRUE;
	}
	
	public static function setAdresse(&$local_list){
		self::checkAttributesDependencies('Local', $local_list, array('numero_rue', 'type_rue', 'nom_rue', 'commune', 'code_postal'));
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
	
}