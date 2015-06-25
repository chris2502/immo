<?php

/**
 *  @class ProcessItemXMLNode
 *  @Revision $Revision: 3967 $
 *
 */

class ProcessItemXMLNode extends XMLNode
{
	public function open()
	{
		if(empty($this->_data_list['structure'][$this->id]))
		{
			return TRUE;
		}

		$data = $this->_data_list['structure'][$this->id];
		$nodes = $this->_data_list['nodes'];

		foreach($data AS $attribute)
		{
			$xmlNode = $nodes[$attribute]['XMLNode'];
			$attrs = $nodes[$attribute];

			$attrs['attribute'] = $attribute;

			$data_list = array();

			$data_id = uniqid();
			if(isset($attrs['connector']))
			{
				$connector = $attrs['connector'];
				switch($connector['type'])
				{
					case 'array':
						$attrs['data'] = $data_id;
						$data_list[$data_id] = $connector['values'];
						break;
					case 'object':
						// Le cas est géré par le render field du Many2One.
						break;
				}
			}
			$structure = array('markup' => $xmlNode, 'attributes' => $attrs, 'value' => array());
			$nx = $xmlNode . 'XMLNode';
			$n = new $nx($structure, $this, $this->_view);

			$n->render($data_list, 'process');
		}

		return TRUE;
	}
}
