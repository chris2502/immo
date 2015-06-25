<?php

/**
 *  @class DebugXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class DebugXMLNode extends XMLNode
{
	public function open()
	{
		if(DISPLAY_ERRORS==1)
		{
			echo '<pre>'."\n";
			print_r($this->_current_data);
			echo '</pre>'."\n";
		}
		return true;
	}
}
