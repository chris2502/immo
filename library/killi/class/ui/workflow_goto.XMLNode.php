<?php

/**
 *  @class Workflow_GotoXMLNode
 *  @Revision $Revision: 2774 $
 *
 */

class Workflow_GotoXMLNode extends XMLNode
{
	public function open()
	{
		$button 			  = array();
		$button['id'] 		  = $this->getNodeAttribute('id','wgt'+uniqid());
		$button['display_com']= $this->getNodeAttribute('display_form_comment','0');
		$button['type']		  = 'button';
		$button['value']	  = $this->getNodeAttribute('string');
		$button['style']	  = str_replace(array('style=','"'),'',$this->style());
		$source_str			  = $this->getNodeAttribute('source');
		$destination 		  = $this->getNodeAttribute('destination');
		
		Security::crypt($_GET['primary_key'],$id_obj_wf_selected);
		$button['url'] = $_SERVER['PHP_SELF'].'?action=workflow.moveTokenToNodeName&source='.$source_str.'&destination='.$destination.'&crypt/primary_key='.$id_obj_wf_selected.'&token='.$_SESSION['_TOKEN'];
		$this->_getArrayWorkflowNodeName($source,$source_str);
		$hORM_workflowToken = ORM::getORMInstance('workflowtoken',true);
		$object_id_list = array();
		$hORM_workflowToken->search($object_id_list, $total_record, array(array('workflow_name','=',$source[0]),array('node_name','=',$source[1]),array('id','=',$_GET['primary_key'])));
		if ($total_record != 0)
		{
			$this->_showButton($button);
		}
		return TRUE;
	}
	
	private function _getArrayWorkflowNodeName(&$workflowname_array,$workflowname_str)
	{
		$workflowname_array = explode('.', $workflowname_str);
		if (sizeof($workflowname_array) != 2)
		{
			throw new Exception('workflow_goto : Le format de la chaine source/destination est mauvais ('.$workflowname_str.')');
		}
		return TRUE;
	}

	private function _showButton($button)
	{
		if ($button['display_com'] == 1)
		{
			$this->_showScriptFormEchec($id_form,$button);
			$button['id'] = $id_form;
		}
		else
		{
			$button['onclick'] = '$(location).attr(\'href\',\''.$button['url'].'\');';
		}
		unset($button['url']);
		unset($button['display_com']);
		
		echo '<input';
		foreach ($button as $att_name=>$att_val)
		{
			echo ' '.$att_name.'="'.$att_val.'"';
		}
		echo '/>';
		return TRUE;
	}

	private function _showScriptFormEchec(&$div_form_id,$button)
	{
		$div_form_id = uniqid();		
		echo '<div id="div'.$div_form_id.'" style="display:none;">';
				echo '<input type="hidden" name="id_obj_wf_selected_qualification_id" value="" />';
				echo '<label for="comment_'.$div_form_id.'" class="required">Commentaire :</label>';
				echo '<input type="text" class="required" name="comment" id="comment_'.$div_form_id.'" value="" />';
		echo '</div>';
 		echo '<script type="text/javascript">';
 		echo '$(function(){$("#'.$div_form_id.'").click(function() {$("#div'.$div_form_id.'").dialog({modal:true,buttons: {
											"Envoyer": function() {
 												var comment = $("#comment_'.$div_form_id.'").val();
												self.location.href=\''.$button['url'].'&comment=\'+comment;
											},
											\'Annuler\': function() {
												$( this ).dialog( "close" );
											}
										}});});});';
 		echo '</script>';
 		return TRUE;
	}

	//.....................................................................
	public function close()
	{
		return TRUE;
	}

}