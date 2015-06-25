<?php
	class AssembleeGeneraleMethod extends Common{
		public function edit( $view,&$data,&$total_object_list,&$template_name=NULL ){
		
			parent::edit($view,$data,$total_object_list,$template_name);//echo " <pre>"; print_r($GLOBALS); echo "</pre>"; die();
			if (isset($_GET['view']) && $_GET['view']=='create' && isset($_GET['input_name']) && $_GET['input_name']=='AssembleeGenerale/assemblee_generale_id'){
				$assemblee_genrale_local_id=$_GET['assemblee_generale_local_id'];
				$_POST['assembleegenerale/local_id']=$assemblee_genrale_local_id;
			}
			return TRUE;
		}
	}