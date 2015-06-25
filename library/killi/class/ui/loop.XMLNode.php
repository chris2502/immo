<?php

/**
 *  @class LoopXMLNode
 *  @Revision $Revision: 4615 $
 *
 */

class LoopXMLNode extends XMLNode
{
	public function render($data_list, $view)
	{
		$this->_data_list	= &$data_list;
		$this->_view		= $view;

		$data_src = $this->getNodeAttribute('data_src');

		if(!isset($data_list[$data_src]))
		{
			throw new Exception('Pas de data !');
		}

		foreach($data_list[$data_src] AS $data)
		{
			foreach($this->_childs AS $child)
			{
				$structure = array();
				$structure['markup'] = $child->name;
				$structure['attributes'] = $child->attributes;
				$structure['value'] = $child->child_struct;

				foreach($data['attributes'] AS $field_name => $field)
				{
					if(!isset($structure['attributes'][$field_name]))
					{
						$structure['attributes'][$field_name] =  $field;
					}
				}

				$node_name = $child->name;
				$classNode = ucfirst($node_name) . 'XMLNode';

				$c = new $classNode($structure, $this, $view);

				if(isset($data['data']))
				{
					$c->setData($data['data']);
					foreach($data['data'] AS $k => $v)
					{
						$data_list[$k] = $v;
					}
				}
				$c->render($data_list, $view);
			}
		}
	}
}
