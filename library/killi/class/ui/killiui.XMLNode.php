<?php

class KilliUIXMLNode extends XMLNode
{
	public $current_data = array();

	public function open()
	{
		$this->call('open');
	}

	public function close()
	{
		$this->call('close');
	}

	public function call($open)
	{
		$node_name = $this->name;

		if($this->_view == 'form')
		{
			$form = $this->getParent('form');
			if($form != NULL)
			{
				$object = $form->attributes['object'];
				$this->attributes['object'] = $object;
				$this->_current_data = reset($this->_data_list[$object]);
			}
		}

		if($this->_view == 'search')
		{
			$form = $this->getParent('list');
			if($form != NULL)
			{
				$object = $form->attributes['object'];
				$this->attributes['object'] = $object;
			}
		}

		$current_node = array('name' => 'render' . $this->name, 'attributes' => $this->attributes);
		UI::call_node($node_name, $open, $current_node, $this->_current_data);
	}
}
