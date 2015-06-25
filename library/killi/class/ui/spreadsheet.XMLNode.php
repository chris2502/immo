<?php

/**
 *  @class SpreadsheetXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class SpreadsheetXMLNode extends XMLNode
{
	public function open()
	{
		$data_src   	= $this->getNodeAttribute('data_src');
		$readonly		= $this->getNodeAttribute('readonly', FALSE);
		$empty_message	= $this->getNodeAttribute('empty_message', 'Pas de données.');
		$data		   = $this->_data_list[$data_src];

		if (empty($data))
		{
			?><div id="<?php echo $this->id; ?>"><?php echo $empty_message; ?></div><?php

			return TRUE;
		}

		$rows = array();
		if(isset($data['rows']))
		{
			foreach($data['rows'] as $row)
			{
				$rows[] = '["' . implode('","', $row) . '"]';
			}
		}
		$handson_data = '[' . implode(',', $rows) . ']';

		$params[0] = 'data: handson_data';
		$params[1] = 'minSpareRows: 1';

		if(!empty($data['title']))
		{
			$params[] = 'colHeaders: '.'["' . implode('","', $data['title']) . '"]';
		}

		if ($readonly)
		{
			$params[] = 'readOnly: true';
			$params[1] = 'minSpareRows: 0';
		}

		?><div id="<?php echo $this->id; ?>"></div>

		<script>
		var handson_data = <?php echo $handson_data; ?>;

		$('#<?php echo $this->id; ?>').handsontable({ <?php echo implode(',', $params); ?> });

		</script><?php
	}
}
