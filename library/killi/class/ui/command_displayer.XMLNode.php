<?php

class Command_DisplayerXMLNode extends XMLNode
{
	public function open()
	{
		$command = $this->getNodeAttribute('command');
		// Regexp to fit table columns
		$regexp  = $this->getNodeAttribute('separator', '');
		// Comma-separated matches index column list
		$cols	= $this->getNodeAttribute('cols', false);
		$debug   = ($this->getNodeAttribute('debug', '0') == '1');
		$title   = ($this->getNodeAttribute('title', '1') == '1');

		if ($cols !== false)
		{
			$cols = explode(',', $cols);
		}
		else
		{
			$cols = array('0:0:Retour');
		}

		$return_var = NULL;
		exec($command, $return_var);

		if ($debug)
		{
			echo display_array($return_var);
		}

		$cssclass = 'odd_tr';
?>
<table cellspacing="0" class="listing_table" style="width:100%;">
	<thead class="ui-widget-header">
		<tr>
			<th style="height:20px;" colspan="<?php echo count($cols); ?>">"<?php echo $command; ?>"</th>
		</tr>
		<tr>
			<?php
			foreach ($cols as $col):
				list ($index1, $index2, $title) = explode(':', $col);
			?>
			<th class="box_header"><?php echo $title; ?></th>
			<?php
			endforeach;
			?>
		</tr>
	</thead>
<?php
		foreach ($return_var as $cmdresponse)
		{
			$cssclass = ($cssclass=='odd_tr')? 'even_tr' : 'odd_tr';
?>
	<tr class="<?php echo $cssclass; ?>">
<?php
			if (empty($regexp))
			{
				echo '<td>'.$cmdresponse.'</td>';
			}
			else
			{
				preg_match_all($regexp, $cmdresponse, $matches);
				foreach ($cols as $col)
				{
					echo '<td>';
					list ($index1, $index2, $title) = explode(':', $col);
					if (isset($matches[$index1][$index2]))
					{
						echo $matches[$index1][$index2];
					}
					else
					{
						echo '- Err -';
					}
					echo '</td>';
				}
			}
?>
	</tr>
<?php
		}
	}

	public function close()
	{
?>
</table>
<?php
	}
}
