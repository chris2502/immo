<?php

/**
 * Yet Another Workflow Button
 *
 *  @class YawbXMLNode
 *  @Revision $Revision: 4334 $
 *
 */
class YawbXMLNode extends XMLNode
{
	static protected $_load = true;

	protected $_comment = false;
	protected $_attribut;
	protected $_workflow_list = array();
	protected $_order;

	protected function _loadLinks()
	{
		$order = array('traitement_echec ASC', 'label ASC');
		if ($this->_order)
		{
			$order = explode(',', $this->_order);
		}

		$link_rights_list = array();
		ORM::getORMInstance('LinkRights')->browse($link_rights_list, $num_rows,
			NULL,
			array(
				array('killi_profil_id', 'IN', $_SESSION['_USER']['profil_id']['value']),
				array('move', '=', 1),
				array('input_node', 'IN', $this->_current_data[$this->_attribute]['workflow_node_id']),
				array('deplacement_manuel', '=', 1)
		), $order);

		$link_list = array();
		foreach ($link_rights_list as $link_right)
		{
			$link_list[$link_right['link_id']['value']] = $link_right;
		}
		return $link_list;
	}

	protected function _checkPreInsert($link)
	{
		$won = $link['output_workflow_name']['value'];
		$non = $link['output_node_name']['value'];

		$win = $link['input_workflow_name']['value'];
		$nin = $link['input_node_name']['value'];

		if (!array_key_exists($won, $this->_workflow_list))
		{
			$class_name = $won.'Method';
			$this->_workflow_list[$won] = new $class_name();
		}
		$function_name = 'check_insert_'.$non;
		if (method_exists($this->_workflow_list[$won], $function_name))
		{
			$id_list = array($this->_object_id => array('id' => $this->_object_id));
			$return = $this->_workflow_list[$won]->$function_name($id_list, $win, $nin, $this->_current_data);
			return $return AND !empty($id_list);
		}
		return TRUE;
	}

	public function open()
	{
		if (empty($this->_current_data))
		{
			return TRUE;
		}

		$action				= $this->getNodeAttribute('action', 'workflow.moveTokenToNodeName');
		$this->_attribute	= $this->getNodeAttribute('attribute');
		$this->_order		= $this->getNodeAttribute('order', false);
		$empty				= $this->getNodeAttribute('empty', false);
		$id_attribute		= $this->getNodeAttribute('object_id_attribute', NULL);

		if($id_attribute == NULL || !isset($this->_current_data[$id_attribute]['value']))
		{
			$this->_object_id = $_GET['primary_key'];
		}
		else
		{
			$this->_object_id = $this->_current_data[$id_attribute]['value'];
		}

		$link_rights_list	= $this->_loadLinks();

		$button 			  	= array();
		$button['type']		  	= 'button';
		$button['style']		= $this->style();

		$display = false;
		$workflow_list = array();
		foreach ($link_rights_list as $link)
		{
			if ($this->_checkPreInsert($link))
			{
				$button['value']	  	= $link['label']['value'];
				$button['class']		= array('yawb');
				Security::crypt($this->_object_id, $id_obj_wf_selected);

				if (empty($button['value']))
				{
					$button['value'] = 'Pas de Label';
				}
				if (!empty($link['mandatory_comment']['value']))
				{
					$button['class'][] = 'comment';
					$this->_comment = true;
				}
				$button['data-url'] = $_SERVER['PHP_SELF'].'?action=' . $action
					.'&destination='.$link['output_workflow_name']['value'].'.'.$link['output_node_name']['value']
					.'&source='.$link['input_workflow_name']['value'].'.'.$link['input_node_name']['value']
					.'&crypt/primary_key='.$id_obj_wf_selected.'&token='.$_SESSION['_TOKEN'];
				$this->_writeButton($button);
				$display = true;
			}
		}
		if ($display)
		{
			$this->_writeDialog();
			if (self::$_load)
			{
				echo '<script type="text/javascript" src="',KILLI_DIR,'/js/yawb.js"></script>';
				self::$_load = false;
			}
		}
		else
		{
			if ($empty)
			{
					echo '<p>'.$empty.'</p>';
			}
		}
		return TRUE;
	}

	private function _writeButton($button)
	{
		echo '<input';
		if ($button['class'] = $this->css_class($button['class']))
		{
			echo ' '.$button['class'];
		}
		unset($button['class']);

		foreach ($button as $att_name => $att_val)
		{
			if ($att_val)
			{
				echo ' '.$att_name.'="'.$att_val.'"';
			}
		}
		echo '/>';
		return TRUE;
	}

	private function _writeDialog()
	{
		if ($this->_comment)
		{
			echo '<div id="Yawb_dialog" style="display:none;">';
			echo '<input type="hidden" name="id_obj_wf_selected_qualification_id" />';
			echo '<label for="Yawb_dialog_comment" class="required">Commentaire :</label>';
			echo '<textarea rows="10" cols="40" class="required" name="comment" id="Yawb_dialog_comment"></textarea>';
			echo '</div>';
		}
 		return TRUE;
	}
}
