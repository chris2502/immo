<?php

/**
 *  @class StaticXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class StaticXMLNode extends XMLNode
{
	public function open()
	{
		$value = $this->getNodeAttribute('value', '');
		if(!empty($value))
		{
			?>
			<table>
				<tr>
					<td><?= htmlentities($value); ?></td>
				</tr>
			</table>
			<?php
		}
		return TRUE;
	}
}
