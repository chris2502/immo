<?php

/**
 *  @class ReportingXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class ReportingXMLNode extends XMLNode
{
	public function open()
	{
		?>
		<form name="reporting_form" id="reporting_form" method="POST">
		<?php
	}

	public function close()
	{
		?>
		</form>
		<?php
	}

}
