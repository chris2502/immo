<?php

/**
 *
 * @class  WorkflowObjectConversionMethod
 *
 */

class WorkflowObjectConversionMethod extends KilliWorkflowObjectConversionMethod
{
	
	//---------------------------------------------------------------------
	/*public function local_to_acquisition($local_id_list,&$acquistion_id_list)
	{
		$acquistion_id_list=array();
		$local_list=array();

		$hORMLOCAL = ORM::getORMInstance('local', FALSE, FALSE);

		foreach ($local_id_list as $key => $value)
		{
			$local_id[$key] = $key;
		}

		$hORMLOCAL->read($local_id, $local_list, array('local_id'));

		foreach($local_list as $local_id=>$local)
		{
			$acquistion_id_list[$local_id]= array(
				'local_id'         => $local['local_id']['value'],
				'acquisition_id'  => $local['local_id']['value']
			);
			// $hORMODT->unlink($odt_id);
		}
echo "<pre>"; print_r($acquistion_id_list); echo "</pre>"; die();
		return TRUE;
	}
	*/
	public function acquisition_to_mise_en_vente($acquisition_id_list,&$mise_en_vente_id_list)
	{
		$mise_en_vente_id_list=array();
		$acquisition_list=array();
	
		$hORMACQUISITION = ORM::getORMInstance('acquistion', FALSE, FALSE);
	
		foreach ($acquisition_id_list as $key => $value)
		{
			$acquisition_id[$key] = $key;
		}
	
		$hORMACQUISITION->read($acquisition_id, $acquisition_list, array('acquisition_id'));
	
		foreach($acquisition_list as $acquisition_id=>$acquisition)
		{
			$mise_en_vente_id_list[$acquisition_id]= array(
					'id'         => $acquisition['acquisition_id']['value'],
					'mise_en_vente_id'  => $acquisition['mise_en_vente_id']['value']
			);
			// $hORMODT->unlink($odt_id);
		}
	
		return TRUE;
	}
}
