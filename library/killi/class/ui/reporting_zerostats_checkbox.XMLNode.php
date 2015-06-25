<?php

/**
 *  @class Reporting_zerostats_checkboxXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class Reporting_zerostats_checkboxXMLNode extends XMLNode
{
	public function open()
	{
		$bNoZeroStats = (isset($_POST['nozerostats']) && $_POST['nozerostats'] == '1');

		?>
		<input style="vertical-align:middle;width:auto;" type="checkbox" name="nozerostats" onclick="document.reporting_form.submit();" id="cbx_displayall"<?php if ($bNoZeroStats):?> checked<?php endif;?> value="1" />&nbsp;<label for="cbx_displayall">Ne pas afficher les lignes à zéro</label>
		<?php
	}
}