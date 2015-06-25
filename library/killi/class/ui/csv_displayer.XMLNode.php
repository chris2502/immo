<?php

class Csv_DisplayerXMLNode extends XMLNode
{
	public function open()
	{
		$data_src = $this->getNodeAttribute('data_src');
?>

<table style="border:1px solid #969696;padding:2px;width: 100%;margin: 12px 0;">
	<tr>
		<th colspan="2">Lecture de document (format CSV uniquement !)</th>
	</tr>
	<tr>
		<td style="width: 205px;vertical-align:top;">
			<div style="width:200px;overflow-x:auto;">
				<table style="white-space: nowrap;">
				<?php
				foreach ($this->_data_list[$data_src] as $document_id => $document):
				?>
					<tr>
				<?php
					Security::crypt($document_id, $crypt_document_id);
					$dotted = explode('.', $document['hr_name']['value']);
					$extension = end($dotted);
					if ($extension == 'csv'):
				?>
						<td><a href="javascript:void(0);" onclick="getDocument('<?php echo $crypt_document_id; ?>', 'csv_displayer_container_<?php echo $this->id; ?>')"><?php echo $document['hr_name']['value']; ?></a></td>
				<?php
					else:
				?>
						<td><?php echo $document['hr_name']['value']; ?></td>
				<?php
					endif;
				?>
						<td>(<?php echo $document['document_type_id']['reference']; ?>)</td>
					</tr>
				<?php
				endforeach;
				?>
				</table>
			</div>
		</td>
		<td style="background-color:#eee;overflow-x:auto;vertical-align:top;">
			<div id="csv_displayer_container_<?php echo $this->id; ?>">

			</div>
		</td>
	</tr>
</table>
<?php
	}

	public function close()
	{
?>

<script type="text/javascript">
$(window).resize(function(){
	var w = $('#' + target_container).parent().css('width');
	$('#' + target_container).css('width', w);
});
</script>

<?php
	}

}
