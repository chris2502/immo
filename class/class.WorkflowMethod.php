<?php

class WorkflowMethod extends KilliWorkflowMethod{
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		
		parent::edit($view,$data,$total_object_list,$template_name);
		/*if(isset($_SESSION['_USER']['profil_id']) && $_SESSION['_USER']['profil_id']['value'][0]==1){
			$hORM_workflow=ORM::getORMInstance('workflow');
			echo "<pre>"; print_r($hORM_workflow); echo "</pre>"; die();
		}*/
		if(!isset($_GET['view'])){
			//$total=0;
			$comment_list=array();
			$hORM_rappel=ORM::getORMInstance('Commentaire');
			$hORM_rappel->browse($comment_list, $total, array('commentaire_id','rappel', 'date_rappel', 'titre_commentaire', 'contenu'), array(array('rappel', '=', TRUE)));
			if($comment_list!=array()){
				$i=1;
				$rappel="";
				$span_test = "";
				//echo "<pre>"; print_r($comment_list); echo "</pre>"; die();
				foreach ($comment_list as $key => $value){
					
					//echo "<pre>"; print_r($comment_list); echo "</pre>"; die();
					//echo "  ".date("d/m/y");die();
					$new_date=date("y-d-m", strtotime($value['date_rappel']['reference']));
					$date1= new DateTime($new_date);
					
					$date2= new DateTime();
					//echo "<pre>"; print_r($date1); echo "</pre>";
					//echo "<pre>"; print_r($date2); echo "</pre>"; die();
					$interval=$date1->diff($date2, false);
					//echo "<pre>"; print_r($interval); echo "</pre>"; die();
					if($interval->invert==0){
						$rappel .=
								$i.')'.'-Titre:'.$value['titre_commentaire']['reference'].'
										'.'-contenu:'.$value['contenu']['value']."\n";
						//$rappel .= "Salut";
						//$rappel = str_replace("<br />","",$rappel);
						
						$i++;
					}
					//$span_test .= '<span id="test">'.$rappel.'</span>';
				}
				$span_test = '<input type="hidden" value="'.$rappel.'" id="test"></input>';
				echo $span_test;
			//	die();		
			//Alert::info("rappel", "test");
			?>
			<!DOCTYPE html>
			<html>
			<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
			
			<!-- JQUERY -->
			<link type="text/css" rel="stylesheet" href="<?= KILLI_DIR ?>/css/base/jquery.ui.all.css"/>
			
					<script src="<?= KILLI_DIR ?>/js/jquery/jquery.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.core.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.widget.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.mouse.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.draggable.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.position.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.resizable.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.dialog.js"></script>
			
					<!-- KILLI -->
					<link type="text/css" rel="stylesheet" href="<?= KILLI_DIR ?>/css/UI.css" />
				</head>
			
			<body>
				 
				<SCRIPT language="javascript">
					/*
  				  window.onload=function(){
  	  				 
  						alert(message);
   				   };
   				   */

					$(document).ready(function(){
						alert($("#test").val());
						//console.log($("#test").val());
					});
   				   
 				 </SCRIPT>
					
			</body>
			</html>
			<?php
				foreach ($comment_list as $key => $value){
					$hORM_rappel->write($value['commentaire_id']['value'], array('rappel'=>FALSE));
				}
			}
			
		}
		return TRUE;
	}
	
	/*public static function checkConstraints($object_name, $id_list, $constraints_string, $qualification_required = FALSE, $redirect = TRUE)
	{
		// Field list
		$matches = array();
		if(preg_match('acquisition', $constraints_string) && preg_match('local', $constraints_string)){
			$ACQ=ORM::getORMInstance('acquisition');
		}
		
		//$pattern = '#((\{)([a-zA-Z_]+)(/)([a-zA-Z_]+)(\}))#';
		$pattern = '#\{([a-zA-Z0-9_]+)(/)([a-zA-Z0-9_]+)(/[a-zA-Z0-9_]+)*\}#';
		preg_match_all($pattern, $constraints_string, $matches);
		$field_list = array();
		foreach ($matches[3] as $field)
		{
			$field_list[$field] = TRUE;
		}
		$field_list = array_keys($field_list);
	
		// Operators
		$pattern	 = array('/\s(AND)\s/', '/\s(OR)\s/', '/\s(and)\s/', '/\s(or)\s/');
		$replacement = array(' && ', ' || ', ' && ', ' || ');
		$condition   = preg_replace($pattern, $replacement, $constraints_string);
	
		// Fields to Variables
		$pattern   = '#(\{)([a-zA-Z0-9_]+)(/)([a-zA-Z0-9_]+)(/)*([a-zA-Z0-9_]+)*(\})#';
		$condition = preg_replace_callback($pattern, function($matches) {
			$remplacement = '$object[\''.$matches[4].'\'][\''.((empty($matches[6]))? 'value' : $matches[6]).'\']';
			return $remplacement;
		}, $condition);
		$condition = 'return ('.$condition.');';
	
		// Loop
		$object_list = array();
		ORM::getORMInstance($object_name)->read($id_list, $object_list, $field_list);
		$crypt_object_id = '';
		foreach ($object_list as $object_id => $object)
		{
			// Verify qualification
			Security::crypt($object_id, $crypt_object_id);
			if ($qualification_required == true &&
					(
							!isset($_POST['qualification_id/'.$crypt_object_id]) ||
							empty($_POST['qualification_id/'.$crypt_object_id])
					))
			{
				$_SESSION['_ERROR_LIST']['Déplacemement'] = 'Le déplacement a échoué, la qualification est requise.';
				// @codeCoverageIgnoreStart
				if ($redirect)
				{
					UI::goBackWithoutBrutalExit();
				}
				// @codeCoverageIgnoreEnd
				return FALSE;
			}
	
			// Verify conditions
			if (!empty($constraints_string) && !eval($condition))
			{
				$_SESSION['_ERROR_LIST']['Déplacemement'] = 'Le déplacement a échoué, un objet ne répond pas aux critères demandés.';
				// @codeCoverageIgnoreStart
				if ($redirect)
				{
					UI::goBackWithoutBrutalExit();
				}
				// @codeCoverageIgnoreEnd
				return FALSE;
			}
		}
		return TRUE;
	}*/
	
}