<?php

/**
 *  @class MessageXMLNode
 *  @Revision $Revision: 3303 $
 *
 */

class MessageXMLNode extends XMLNode
{
	public function open()
	{
		$string = $this->getNodeAttribute('string', '');
		$object = $this->getNodeAttribute('object', '');
		$attribute = $this->getNodeAttribute('attribute', '');

		if(!empty($object) && !empty($attribute))
		{
			$string = $this->_current_data[$attribute]['value'];
		}
		else
		if(empty($string) && isset($this->_data_list[$attribute]))
		{
			$string = $this->_data_list[$attribute];
		}

		echo '<div '.self::css_class().' '.$this->style().'>'.preg_replace('/\n/', '<br/>', $string);

		return TRUE;
	}
	public function close()
	{
		echo '</div>';
	}
}
