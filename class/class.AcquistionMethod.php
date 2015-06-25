<?php
class AcquisitionMethod{
	
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		//echo "<pre>"; print_r($data['entite']); echo "</pre>"; die();
		parent::edit($view,$data,$total_object_list,$template_name);
		return TRUE;
	}
	
}