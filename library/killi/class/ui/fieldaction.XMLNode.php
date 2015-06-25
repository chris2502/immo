<?php

/**
 *  @class FieldActionXMLNode
 *  @Revision $Revision: 4660 $
 *
 */

class FieldActionXMLNode extends XMLNode
{
	public function open()
	{
		$parent_key = $this->getParent()->getNodeAttribute('key', $this->getParent()->data_key);
		$parent_id = $this->_current_data[$parent_key]['value'];
		$key = null;
		Security::crypt($parent_id, $key);
		
		$string = $this->getNodeAttribute('string');
		$object = $this->getNodeAttribute('object', FALSE);
		if (empty($object))
		{
			$object = $this->getParent()->getNodeAttribute('object');
		}
		$method = $this->getNodeAttribute('method');
		$type = 'button';
		$class = $this->getNodeAttribute('class', FALSE);
		$confirm = $this->getNodeAttribute('confirm', FALSE);
		$title = $this->getNodeAttribute('title', FALSE);
		
		$token = $_SESSION['_TOKEN'];
		
		$href = null;
		$href .= './index.php';
		$href .= '?action='.$object.'.'.$method;
		$href .= '&token='.$token;
		$href .= '&crypt/primary_key='.$key;

		$html = null;
		$html .= '<input ';
		$html .= 'type="'.$type.'" ';
		$html .= 'class="'.$class.'" ';
		$html .= 'value="'.$string.'" ';
		$html .= 'id="fieldaction_'.$method.'_'.$key.'" ';
		if (!empty($title))
		{
			$html .= 'title="'.$title.'" ';
		}
		$html .= 'style="cursor:pointer;" ';
		$html .= '/>';
		
		if (!empty($confirm))
		{
			$html .= '<div class="fieldaction_dialog" ';
			$html .= 'id="fieldaction_dialog_'.$method.'_'.$key.'" ';
			if (!empty($title))
			{
				$html .= 'title="'.$title.'" ';
			} else {
				$html .= 'title="'.$string.'" ';
			}
			$html .= 'style="display:none;">';
			if ($confirm == 1)
			{
				$html .= 'Etes-vous sûr(e) de vouloir effectuer cette action ?';
			} else {
				$html .= $confirm;
			}
			$html .= '</div>';
			$html .= '<script type="text/javascript">';
			$html .= '$(function(){';
			$html .= '$("#fieldaction_'.$method.'_'.$key.'").click(function(){';
			$html .= '$("#fieldaction_dialog_'.$method.'_'.$key.'").dialog({';
			$html .= 'buttons:{';
			$html .= 'Continuer:function(){';
			$html .= 'location.href="'.$href.'";';
			$html .= '$(this).dialog("close");';		
			$html .= '},';
			$html .= '"Autre Onglet":function(){';
			$html .= 'window.open("'.$href.'");';
			$html .= '$(this).dialog("close");';
			$html .= '},';
			$html .= 'Annuler:function(){$(this).dialog("close");}';
			$html .= '}';
			$html .= '});';
			$html .= '});';
			$html .= '});';
			$html .= '</script>';
		} else {
			$html .= '<script type="text/javascript">';
			$html .= '$(function(){';
			$html .= '$("#fieldaction_'.$method.'_'.$key.'").click(function(){';
			$html .= 'location.href="'.$href.'";';
			$html .= '});';
			$html .= '});';
			$html .= '</script>';
		}
		
		echo $html;

		return TRUE;
	}
	
	public function close()
	{
		return TRUE;
	}
}
