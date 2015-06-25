<?php

/**
 *  @class Image_annotationXMLNode
 *  @Revision $Revision: 4605 $
 *
 */

class Image_annotationXMLNode extends XMLNode
{
	public function open()
	{

		$width			= $this->getNodeAttribute('width', null);
		$height			= $this->getNodeAttribute('height', null);
		$maxWidth		= $this->getNodeAttribute('maxwidth', '600px');
		$maxHeight		= $this->getNodeAttribute('maxheight', '400px');
		$annot_id_list  = $this->getNodeAttribute('attribute', NULL);
		$object_src		= $this->getNodeAttribute('object_src', NULL);
		
		if (!empty($height) && is_integer($height)){
			$height = $height.'px';
		}
		if (!empty($width) && is_integer($width)){
			$width = $width.'px';
		}
		
		$image_id = $_GET['primary_key'];

		$edition = false;
		$creation = false;

		if(isset( $_GET['mode'] ))
		{
			switch( $_GET['mode'] )
			{
				case 'edition' : $edition = true; break;
				case 'creation' : $creation = true; break;
			}
		}

		Rights::getCreateDeleteViewStatus ( 'imageannotation', $create, $delete, $view );
		$creation = $creation && $create;

		$hInstance = ORM::getObjectInstance('imageannotation');

		Rights::getRightsByAttribute ( $hInstance->annotation_text->$attribute->objectName, 'annotation_texte', $read, $write );
		$edition = $edition && $write;

		$src = $this->_current_data['file_name']['value'];

		?>
		<table id="annotation_table" style="border:0; width:100%;">
			<tr>
				<td style="vertical-align:top;width:<?=$maxWidth?>;">
					<div id="annotation-image" style="margin:0 auto;<?=(!empty($width)?'width:'.$width.';':'').(!empty($height)?'height:'.$height.';':'')?>max-width:<?=$maxWidth?>;max-height:<?=$maxHeight?>;" >
						<div id="mapping" style="width:100%;height:100%; position:relative;">
							<img id="mappingImage" src="data:image/jpeg;base64,<?=base64_encode(ImageMethod::getContent($src, 800, 600)); ?>" alt="" style="max-width:<?=$maxWidth?>; max-height:<?=$maxHeight?>; display:block; position:relative;"/>
						</div>
					</div>
				</td>
				<td style="text-align:left; vertical-align:top">
					<table class="table_list" style="width: 95%; border: solid 1px #BBBBBB; border-spacing: 0;">
						<thead>
							<tr class="ui-widget-header ui-state-hover" style="height:1em">
								<th class="box_header" style="text-align:left;border-bottom: solid 1px #BBBBBB;"></th>
								<th class="box_header" style="text-align:center;border-bottom: solid 1px #BBBBBB;">Annotations</th>
								<th class="box_header" style="text-align: right; width: 60px; border-bottom: solid 1px #BBBBBB;"></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$annotations_list = array();
							if (!empty($object_src))
							{
								$annotations_list = $this->_data_list[$object_src];
							}
							elseif(!empty($annot_id_list))
							{
								ORM::getORMInstance('ImageAnnotation')->read($this->_current_data[$annot_id_list]['value'], $annotations_list);
							}
							else
							{
								ORM::getORMInstance('ImageAnnotation')->browse($annotations_list, $total_annotation, NULL, array(array('image_id', '=', $image_id)));
							}
							foreach($annotations_list AS $key => $annotation):
								Security::crypt($annotation['annotation_id']['value'], $area_id);
								$area_annotation_caption =  $annotation['annotation_texte']['value'];
								$area_annotation_caption =str_replace("\\",'/', $area_annotation_caption);
								$area_Ax =  $annotation['coord_Ax']['value'];
								$area_Ay =  $annotation['coord_Ay']['value'];
								$area_Bx =  $annotation['coord_Bx']['value'];
								$area_By =  $annotation['coord_By']['value'];
								$string_area = json_encode(array(
									'id' => $area_id,
									'caption' => $area_annotation_caption,
									'A' => array('x' => $area_Ax, 'y' => $area_Ay),
									'B' => array('x' => $area_Bx, 'y' => $area_By)
								));
								
								//$string_area = '{"id":"'.$area_id.'","caption":"'.$area_annotation_caption.'","A":{"x":'.$area_Ax.',"y":'.$area_Ay.'},"B":{"x":'.$area_Bx.',"y":'.$area_By.'}}';
								?>
								<tr id="tr<?= $area_id ?>" class="step ui-widget" data-mapping-area='<?=str_replace('\'','&#39;', $string_area)?>'>
									<td style="width:60px;text-align:center">
									<?php if($edition == true): ?>
										<input id="delete_<?=$area_id?>" type="image" src="<?=KILLI_DIR?>/images/gtk-delete.png" style="width:15px;height:15px;" onclick="if(confirm('Supprimer cette annotation ?')) {ImageAnnotation.unlink('<?=$area_id?>','<?=$_SESSION['_TOKEN']?>'); return false;}"/>
										&nbsp;
										<input id="edit_'<?=$area_id?>" type="image" src="<?=KILLI_DIR?>/images/edit.png" style="width:15px;height:15px;" onclick="ImageAnnotation.edit('<?=$area_id?>'); return false;"/>
									<?php endif; ?>
									</td>
									<td style="text-align:left;vertical-align:middle">
										<span id="<?= $area_id ?>">
											<span class="area_annotation_caption" id="span_area_annotation_caption_<?= $area_id ?>"><?= $area_annotation_caption ?></span>
											<input type="text" id="input_area_annotation_caption_<?= $area_id ?>" class="area_annotation_caption" style="display:none;"/>
										</span>
									</td>
									<td width="20px">
										<?php if($edition == true) : ?>
											<input id="btn_save_annotation_<?=$area_id?>" class="btn_save_annotation" type="image" src="<?=KILLI_DIR?>/images/true.png" style="width:15px;height:15px;"  onclick="ImageAnnotation.write('<?=$area_id?>','<?=$_SESSION['_TOKEN']?>'); return false;"/>
											<input id="btn_cancel_annotation_<?=$area_id?>" class="btn_cancel_annotation" type="image" src="<?=KILLI_DIR?>/images/false.png" style="width:15px;height:15px;"  onclick="ImageAnnotation.cancel('<?=$area_id?>'); return false;"/>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<?php if ($edition): ?>
						<tfoot>
							<tr>
								<td height="20px" style="vertical-align:bottom" colspan="3">
								&nbsp;Ajouter une annotation&nbsp;:
								</td>
							</tr>
							<tr>
								<td colspan="2" style="vertical-align:middle">
									<input type="text" name="annotation" id="annotation" />
								</td>
								<td>
									<input type="hidden" name="coord_Ax" id="coord_Ax" />
									<input type="hidden" name="coord_Ay" id="coord_Ay" />
									<input type="hidden" name="coord_Bx" id="coord_Bx"/>
									<input type="hidden" name="coord_By" id="coord_By"/>
									<input type="image" name="save" value="save" src="<?= KILLI_DIR ?>/images/true.png" style="width:15px; height:15px;" onclick="ImageAnnotation.create('<?= $_SESSION['_TOKEN'] ?>','<?= $_GET['crypt/primary_key'] ?>'); return false;">
									<input type="image" name="cancel" value="cancel" src="<?= KILLI_DIR ?>/images/false.png" style="width:15px; height:15px;" onclick="ImageAnnotation.reset(); return false;">
								</td>
							</tr>
						</tfoot>
						<?php endif; ?>
					</table>
				</td>
   			</tr>
		</table>
		<script type="text/javascript">
		$(document).ready( function()
		{
			$('#mappingImage').mousedown(function(e)
				{
		 		$("#current").attr({ id: '' })
				$("#currentToHide").detach();
				ImageAnnotation.isSelecting = true;
				box = $('<div style="border:2px #000000 solid;position:fixed;">').hide();
				$(document.body).append(box);
				var parentOffset = $('#mappingImage').parent().offset();

				ImageAnnotation.x1 = e.pageX - parentOffset.left;
				ImageAnnotation.y1 = e.pageY - parentOffset.top; // height relative image

				box.attr({id: 'current'}).css({
				top: e.pageY - ImageAnnotation.currentScrollTop ,
				left: e.pageX
				}).fadeIn();

				$('#boxtop').html(e.pageY );
				$('#boxleft').html(e.pageX );

				box.mousemove(function(e) {ImageAnnotation.selectArea_onMouseMove(e)});
				box.mouseup(function() {$("#current").attr({ id: 'currentToHide' }); ImageAnnotation.isSelecting = false});
				$("#coord_Ax").val( parseInt(Math.abs(ImageAnnotation.x1),10) );
				$("#coord_Ay").val( parseInt(Math.abs(ImageAnnotation.y1),10) );
				});

				$('#mappingImage').bind('dragstart', function(event) { event.preventDefault();
				});

				$('#mappingImage').mousemove(function(e) {ImageAnnotation.selectArea_onMouseMove(e);
				});

				$('#mappingImage').mouseup(function() {
				$("#current").attr({ id: 'currentToHide' });
				ImageAnnotation.isSelecting = false;
				});

				$(window).scroll(function(){

				ImageAnnotation.currentScrollTop = $(window).scrollTop();
				var diff = + ImageAnnotation.currentScrollTopOld - ImageAnnotation.currentScrollTop;
				$("#currentToHide").css('top','+=' + diff + 'px');
				ImageAnnotation.currentScrollTopOld = ImageAnnotation.currentScrollTop;
			});

		});

		</script>
	<?php

		return TRUE;

	}
}