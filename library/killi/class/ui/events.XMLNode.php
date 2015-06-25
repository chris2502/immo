<?php

/**
 *  @class EventsXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class EventsXMLNode extends XMLNode
{
	public function open()
	{
		switch($this->getParent()->name)
		{
			case 'planifier':
				$this->planifier();
				break;
			default:
				throw new Exception('The element \''.$this->name.'\' is not a child of \'' . $this->getParent()->name . '\'');
		}
	}

	public function planifier()
	{
		$event = array();

		$object = $this->getNodeAttribute('object', '');
		$method = $this->getNodeAttribute('method', '');

		if(empty($object) && empty($method))
		{
			throw new Exception('Object or method not defined to retrieve the events.');
		}

		$event['object'] = $object;
		$event['method'] = $method;

		$event['color']		= $this->getNodeAttribute('color', '');
		$event['editable']	= $this->getNodeAttribute('editable', '1');
		$event['deletable']	= $this->getNodeAttribute('deletable', '0');
		$event['draggable']	= $this->getNodeAttribute('draggable', '1');
		$event['resizable']	= $this->getNodeAttribute('resizable', '1');

		$this->getParent()->events[] = $event;
	}
}

