<?php

/**
 *  @class ColumnXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class ColumnXMLNode extends XMLNode
{
	public function open()
	{
		switch($this->getParent()->name)
		{
			case 'flexigrid':
				$this->flexigrid();
				break;
			default:
				throw new Exception('The element \''.$this->name.'\' is not a child of \'' . $this->getParent()->name . '\'');
		}
	}

	public function flexigrid()
	{
		$hInstance				= ORM::getObjectInstance($this->getParent()->getNodeAttribute('object'));
		$column					= array();
		$column['attribute']	= $this->getNodeAttribute('attribute');
		$attribute				= $column['attribute'];
		$column['name']			= $this->getNodeAttribute('string', $hInstance->$attribute->name);
		$column['sortable']		= $this->getNodeAttribute('sort', '0');
		$column['width']		= $this->getNodeAttribute('width', '');
		$column['align']		= $this->getNodeAttribute('align', 'center');
		$column['searchable']	= $this->getNodeAttribute('search', '0');

		if($column['searchable'] == '1')
		{
			$this->search_elements[$column_name] = $attribute;
		}
		$this->getParent()->columns[] = $column;
	}
}

