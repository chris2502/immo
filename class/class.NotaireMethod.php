<?php
class NotaireMethod extends Common{
	
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		
		parent::edit($view,$data,$total_object_list,$template_name);
		
		
		return TRUE;
	}
}
?>