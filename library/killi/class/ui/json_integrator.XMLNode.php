<?php

/**
 *  @class Json_IntegratorXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class Json_IntegratorXMLNode extends XMLNode
{
	//.....................................................................
	public function open()
	{
		$object		= $this->getNodeAttribute('object', FALSE);
		$key		   = $this->getNodeAttribute('key', FALSE);
		$reference	 = $this->getNodeAttribute('reference', FALSE);
		$title		 = $this->getNodeAttribute('title', FALSE);
		$data_src	  = $this->getNodeAttribute('data_src', FALSE);
		$inner_padding = '3px';

		if ((!$object && !$key && !$data_src) ||
			(!$data_src && (!$key || !$object)))
		{
			throw new Exception('Json Integrator needs data_src or object AND key');
		}

		if (!$title && $reference !== FALSE)
		{
			$title = $this->_data_list[$object][$_GET['primary_key']][$reference]['value'];
		}
		elseif (!$title && !$reference)
		{
			throw new Exception('Json Integrator needs at least a title attribute');
		}

		if ($data_src !== FALSE)
		{
			$data = json_decode($this->_data_list[$data_src], TRUE);
		}
		else
		{
			$data = json_decode($this->_data_list[$object][$_GET['primary_key']][$key]['value'], TRUE);
		}

		$first = each($data);
		// Si la première valeur est de type array, on considère
		// le tableau comme multidimensionnel.
		if (!is_array($first[1]))
		{
			$data = array($data);
		}

		$raw  = reset($data);
		$cols = array_keys($raw);

		if (count($data) == 0)
		{
?>
<table cellspacing="0" class="listing_table" style="width:100%;table-layout:fixed;">
		<thead>
			<tr class="ui-widget-header ui-state-hover" style="height:20px">
				<th class="box_header leftcorner" style="width: 4px;"></th>
				<th class="box_header leftcorner_alternate" style="width: 1px;"></th>
				<th class="box_header leftcorner" style="width: 2px;"></th>
				<th class="box_header leftcorner_alternate" style="width: 1px;"></th>
				<th class="box_header leftcorner" style="width: 1px;"></th>
				<th class="box_header"><?=$title?></th>
				<th class="box_header" style="text-align:right; width:60px;"></th>
			</tr>
		</thead>
		<tbody>
			<tr><td colspan="7" style="text-align: center; font-size: 18px;"><br />- PAS DE DONNEES -<br /><br /></td></tr>
		</tbody>
</table>
<?php
			return TRUE;
		}
?>
<table cellspacing="0" class="listing_table" style="width:100%;table-layout:fixed;">
	<thead>
		<tr class="ui-widget-header ui-state-hover" style="height:20px">
			<th class="box_header leftcorner" style="width: 4px;"></th>
			<th class="box_header leftcorner_alternate" style="width: 1px;"></th>
			<th class="box_header leftcorner" style="width: 2px;"></th>
			<th class="box_header leftcorner_alternate" style="width: 1px;"></th>
			<th class="box_header leftcorner" style="width: 1px;"></th>
			<th class="box_header"><?=$title?></th>
			<th class="box_header" style="text-align:right; width:60px;"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="7">
				<div style="width:100%;overflow-x: auto;">
					<table class="table_list" cellspacing="0" style="width:100%;">
						<thead class="ui-widget-header">
							<tr style="height:18px;text-align:left">
								<?php foreach ($cols as $col): ?>
								<th style="padding: 0 <?=$inner_padding?>;"><?=$col?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php
							$cssclass='odd_tr';
							foreach ($data as $idx => $line)
							{
								$cssclass = ($cssclass=='odd_tr')?'even_tr':'odd_tr';
							?>
							<tr class="<?=$cssclass?>">
								<?php
								foreach ($cols as $col)
								{
								?>
								<td style="padding: 0 <?=$inner_padding?>;">
									<?php
									if (isset($line[$col]))
									{
										echo $line[$col];
									}
									else
									{
										echo '';
									}
									?>
								</td>
								<?php
								}
								?>
							</tr>
							<?php
							}
							?>
						</tbody>
					</table>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<?php
	}
	//.....................................................................
	public function close()
	{
?>
<script type="text/javascript">
$(window).resize(function(){
	// Hello!
});
</script>
<?php
	}
}
