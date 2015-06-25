<?php

/**
 *  @class ProgressbarXMLNode
 *  @Revision $Revision: 3847 $
 *
 */

class ProgressbarXMLNode extends XMLNode
{
	public function open()
	{
		$table 	= $this->getNodeAttribute('table');
		$format = $this->getNodeAttribute('format', "%s" );
		$unit 	= $this->getNodeAttribute('unit', '');
		$max 	= $this->getNodeAttribute('max');
		$value 	= $this->getNodeAttribute('value');

		$data = $this->_data_list[$table];

		?>
			<table class="progressbar_table">
				<tr>
					<td style="width: 50px;">Max : <?= $max." ".$unit ?></td>
					<td style="width: 50px;">Utilisé : <?php printf($format." %s",htmlentities($value,ENT_COMPAT,'UTF-8'),$unit) ?></td>
					<td style="width: 120px;"><div id="progressbar_node<?= $this->_node_index ?>"></div></td>
					</tr>
			</table>
		<?php
		if ($max!=0)
		{
		?>
			<script>
				$(document).ready(function(){
				$("#progressbar_node<?= $this->_node_index ?>").progressBar(<?= (int) (100.0*($data[$value]/$data[$max])) ?>);
		});
		</script>
		<?php
		}
		?>

		<?php
		return TRUE;
	}
}
