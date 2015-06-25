<?php

/**
 *  @class DynaloopXMLNode
 *  @Revision $Revision: 4290 $
 *
 */

class DynaloopXMLNode extends XMLNode
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
			$structure = array();
			$structure['markup'] = $data['name'];
			$structure['attributes'] = array();
			$structure['value'] = array();
			$structure['attributes'] = $data['attributes'];

			$classNode = ucfirst($data['name']) . 'XMLNode';

			$c = new $classNode($structure, $this, $view);

			if(isset($data['data']))
			{
				$c->setData($data['data']);
			}
			$c->render($data_list, $view);
		}
	}
}
