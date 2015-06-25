<?php

/**
 *  @class NewlineXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class NewlineXMLNode extends XMLNode
{
	public function open()
	{
		?>
		<br/>
		<?php
	}
}
