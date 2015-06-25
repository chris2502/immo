<?php

/**
 *  @class Picture_documentXMLNode
 *  @Revision $Revision: 4506 $
 *
 */

class Picture_documentXMLNode extends XMLNode
{
	public function open()
	{

		$object_id 	=  $this->_current_data[$this->attributes['attribute']]['value'];
		$object	 = $this->getNodeAttribute('object', '');

		$width         = $this->getNodeAttribute('width', '100%');
		$cols          = $this->getNodeAttribute('cols', '1');
		$string        = $this->getNodeAttribute('string', '');
		$document_type = $this->getNodeAttribute('document_type', '');
		
		?>
			<div style="border-bottom: solid 2px #000000;">
			<b>Images associées<?php echo (!empty($string))?' ('.$string.')':''?> :</b>
			</div>
		<?php

		//---On recup les document images associés
		$hORM = ORM::getORMInstance('document');
		$picture_list = array();
		$args = array(
			array('mime_type','in',array('image/jpeg', 'image/jpg', 'image/png')),
			array('object_id','=',$object_id),
			array('object','=',$object)
		);
		if (!empty($document_type))
		{
			$document_type_id = NULL;
			if (is_int($document_type))
			{
				$document_type_id = $document_type;
			}
			elseif(is_string($document_type) && defined($document_type))
			{
				$document_type_id = constant($document_type);
			}
			else
			{
				throw new Exception('La valeur "'.$document_type.'" de l\'attribut \'document_type\' n\'est ni une constante définie ni un ID');
			}
			$args[] = array('document_type_id', '=', $document_type_id);
		}
		
		$hORM->browse($picture_list, $num, array('file_name'), $args);

		if(isset($width))
		{
			$pos = strpos($width, '%');
			if($pos === false)
			{ // px
	   		$colwidth = (int) ($width / $cols);
	   		$unit='px';
	   		}
	   		else
	   		{ // percent
	   		$colwidth = 100 / $cols;
	   		$unit='%';
	   		}
		}

		$image_path = array();
		foreach($picture_list as $picture_id=>$picture)
		{
			$image_path[] = $picture['file_name']['value'];
		}

		// si plus d'images disponibles qu'indiqué dans le xml

		if(isset($image_path))
		   	if( count($image_path) > $cols)
			   	$cols=count($image_path);

		echo '<table style="width:'.$width.'">';
		echo '<tr>';

		?>
		 	<script type="text/javascript">
				if (!document.foo_picture_document)
				{
	 				function displayImage(i)
					{
						var img = new Image();
						img = $('#img_'+i).attr('src');
						prev = window.open('', 'large');
						prev.document.write('<html><body><img src="' + img + '" /></body></html>');
						prev.document.close();
					}
				}
	 			document.foo_picture_document = true;
	 		 </script>
		<?php

		for($c=0; $c<$cols;$c++)
		{
			if(isset($image_path[$c]) && file_exists($image_path[$c]))
			{
				?>
			   	<td width="<?= $colwidth.$unit ?>">
			   	<img onclick="displayImage(<?=$c?>)" id="img_<?= $c ?>" src="data:image/jpeg;base64,<?=base64_encode(file_get_contents($image_path[$c])) ?>" width="100%">
			   	</td>
			   	<?php
			}
		}

		?>
		</tr>
		<tr>

		<?php

		foreach($picture_list as $picture_id=>$picture)
		{
  			echo '<td>';
			Security::crypt($picture_id, $id_crypt);

			$hORM = ORM::getORMInstance('imageannotation');

			$annotation_list = array();
			$hORM->browse($annotation_list, $num, array('annotation_id'), array(array('image_id', '=', $picture_id)));

			$num = count($annotation_list);

			echo '<a href="javascript:void(0)" onclick="window.open(\'index.php?action=image.edit&crypt/primary_key='.$id_crypt.'&view=form&token='.$_SESSION['_TOKEN'].'=edition\');window.close();">'.'annotations ('.$num.')</a>';

			echo '</td>';
		}
		?>

		</tr>
		</table>

		<?php

	}
}