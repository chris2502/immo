<?php

	class localOffreMethod extends Common{
		public static function setPrixLocationByCarre(&$faireoffre_list){
			self::checkAttributesDependencies('FaireOffre', $faireoffre_list, array('surface_m_carre', 'prix_location'));
			if(!empty($faireoffre_list))
			{
				global $hDB;
			
				foreach($faireoffre_list as &$faireoffre)
				{
					if($faireoffre['surface_m_carre']['value']!=0){
						$faireoffre['prixLocationByCarre']['value']=$faireoffre['prix_location']['value'] / $faireoffre['surface_m_carre']['value'];
						$faireoffre['prixLocationByCarre']['editable'] = FALSE;
					}
				}
			}
			return TRUE;
		}
	}