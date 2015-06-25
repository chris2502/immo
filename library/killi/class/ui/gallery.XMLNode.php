<?php

/**
 *  @class galleryXMLNode
 *  @Revision $Revision: 4685 $
 *
 */

class galleryXMLNode extends XMLNode
{
	
	private static $_gallery_count = 0;
	private static $_behavior = array(
		'photo' => array(
			'can_rotate' => true,
			'show_user'  => true,
			'show_date'  => true,
			'show_label' => false,
			'show_counter' => false,
			'show_annotations' => false,
			'show_editlink' => false,
			'string' => NULL,
			'empty_message' => 'Aucune photo disponible'
		),
		
		'picture_document' => array(
			'can_rotate' => false,
			'show_user'  => false,
			'show_date'  => false,
			'show_label' => false,
			'show_counter' => false,
			'show_annotations' => true,
			'show_editlink' => false,
			'string' => 'Images associée',
			'empty_message' => ''
		),
		
	);
	
	
	public function open()
	{
		$behavior = $this->getNodeAttribute('behavior', 'photo');
		if (empty($behavior))
		{
			$behavior = 'photo';
		}
		
		if ($behavior != 'photo' && $behavior!='picture_document')
		{
			throw new Exception('attribute "behavior" has a bad value. Only "photo" or "picture_document" allowed');
		}
		
		$can_rotate       = $this->getNodeAttribute('can_rotate', self::$_behavior[$behavior]['can_rotate']);
		$show_user        = $this->getNodeAttribute('show_user', self::$_behavior[$behavior]['show_user']);
		$show_date        = $this->getNodeAttribute('show_date', self::$_behavior[$behavior]['show_date']);
		$show_label       = $this->getNodeAttribute('show_label', self::$_behavior[$behavior]['show_label']);
		$show_counter     = $this->getNodeAttribute('show_counter', self::$_behavior[$behavior]['show_counter']);
		$show_annotations = $this->getNodeAttribute('show_annotations', self::$_behavior[$behavior]['show_annotations']);
		$show_editlink    = $this->getNodeAttribute('show_editlink', self::$_behavior[$behavior]['show_editlink']);
		$string           = $this->getNodeAttribute('string', self::$_behavior[$behavior]['string']);
		$empty_message    = $this->getNodeAttribute('empty_message', self::$_behavior[$behavior]['empty_message']);

		$scroll           = $this->getNodeAttribute('scroll', 'horizontal');
		$empty_label_msg  = $this->getNodeAttribute('empty_label_msg', '');
		$show_caption     = $this->getNodeAttribute('show_caption', true);
		$resizeDuration   = $this->getNodeAttribute('resizeDuration', 0);
		$animate          = $this->getNodeAttribute('animate', false);
		$animateFade      = $this->getNodeAttribute('animateFade', false);
		$width            = $this->getNodeAttribute('width', '100%');
		$height           = $this->getNodeAttribute('height', '150px');
		$thumbwidth       = $this->getNodeAttribute('thumbwidth', KilliImageMethod::THUMB_MAX_WIDTH.'px');
		$thumbheight      = $this->getNodeAttribute('thumbheight', KilliImageMethod::THUMB_MAX_HEIGHT.'px');
		$css_class        = $this->getNodeAttribute('css_class', NULL);
		$style            = $this->getNodeAttribute('style', NULL);
		$figure_style     = $this->getNodeAttribute('figure_style', NULL);
		$title_style      = $this->getNodeAttribute('title_style', NULL);
		$global_style     = $this->getNodeAttribute('global_style', NULL);
		$id               = $this->getNodeAttribute('id', NULL);

		$uniqid     = md5($id.uniqid().rand().self::$_gallery_count); // identifiant unique
		
		$picture_array  = array();
		
		if (array_key_exists('src', $this->attributes))
		{
			$picture_array = $this->_data_list[$this->getNodeAttribute('src')];
		}
		else
		{
			$attribute   = $this->getNodeAttribute('attribute', NULL);
			$object_id   = $this->getNodeAttribute('object_id', NULL);
			$object	     = $this->getNodeAttribute('object', ATTRIBUTE_HAS_NO_DEFAULT_VALUE, TRUE);
			if (empty($object_id))
			{
				$attribute = ORM::getObjectInstance($object)->primary_key;
			}
			$object_id 	    = $this->_current_data[$attribute]['value'];
			$document_type  = $this->getNodeAttribute('document_type', '');
			$order_by       = $this->getNodeAttribute('order_by', NULL);
			
			$args = array(
				array('object', 'like', $object),
				array('object_id', '=', $object_id)
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
			$total_pictures = 0;
			ORM::getORMInstance('image')->browse($picture_array, $total_pictures, array('document_type_id', 'mime_type', 'size', 'file_name', 'hr_name', 'users_id', 'date_creation', 'annotations', 'file_found', 'label', 'comment'), $args, $order_by);
		}

		$annotations_list = array();
		ORM::getORMInstance('ImageAnnotation')->browse($annotations_list, $total_annotations, NULL, array(array('image_id', 'in', array_keys($picture_array))));
		foreach($annotations_list as $annotation_id => $annotation)
		{
			if (!isset($picture_array[$annotation['image_id']['value']]['annotations']['list']))
			{
				$picture_array[$annotation['image_id']['value']]['annotations']['list'] = array();
			}
			$picture_array[$annotation['image_id']['value']]['annotations']['list'][$annotation_id] = $annotation;
		}
		
		// On force ces éléments à ne pas être vide
		
		if (empty($width))
		{
			$width = '100%';
		}
		if (empty($thumbwidth))
		{
			$thumbwidth =  KilliImageMethod::THUMB_MAX_WIDTH.'px';
		}
		if (empty($thumbheight))
		{
			$thumbheight =  KilliImageMethod::THUMB_MAX_HEIGHT.'px';
		}
		
		if(empty($scroll))
		{
			$scroll = 'none';
		}

		if ($scroll != 'none' && $scroll != 'horizontal' && $scroll != 'vertical' && $scroll != 'both')
		{
			throw new Exception('$scroll has a bad value. Only "none", "horizontal", "vertical" and "both" are valid');
		}

		
		// C'est parti !
		
		if (self::$_gallery_count++ == 0): // n'inclure le js et le css qu'une seule fois ! ?>
		
			<script src="./library/killi/js/shadowbox.js"></script>
			<link type="text/css" rel="stylesheet" href="./library/killi/css/shadowbox.css"/>
			
			<script type="text/javascript">
				//<![CDATA[
				
				// --- ShadowBox
				
				Shadowbox.init({
					onOpen:function(element){
						$('.header, .navigator, #main_menu, #main_form, .title').css('-webkit-filter','grayscale(1) blur(2px)');
						$('.header, .navigator, #main_menu, #main_form, .title').css('filter','grayscale(1) blur(2px)');
						$('#sb-body-inner').css('position','relative');
						$('#sb-body').addClass('mappingArea');
						$('#sb-body').attr('data-mapping-areas', $(element.link).attr('data-mapping-areas'));
						$('#sb-info').attr('data-uniqid', $(element.link).closest('.photo_container').attr('data-uniqid'));
						ImageAnnotation.showOnMove();
					},
					onClose:function(element){
						$('.header, .navigator, #main_menu, #main_form, .title').css('-webkit-filter','none');
						$('.header, .navigator, #main_menu, #main_form, .title').css('filter','none');
					},
					resizeDuration:<?=$resizeDuration?>,
					animate:<?=$animate?'true':'false'?>,
					animateFade:<?=$animateFade?'true':'false'?>,
					displayCounter:true,
					displayNav:true
				});

				$(document).ready(function(){

					$('a.popuplink').click(function(event){
						event.preventDefault();
						var href = $(this).attr('href');
						var w = 0.75*screen.width;
						var h = 0.75*screen.height;
						var popup = window.open(href,'','width='+w+',height='+h+',scrollbars=1,top=1,left=1,toolbar=no,menubar=no,scrollbars=yes,resizable=yes,location=no,directories=no,status=no');
						/*
						popup.onbeforeunload = function () {
					        window.location.reload();
					    }
					    */
					    if (window.focus)
						{
					    	popup.focus()
						}
				   });
			
				});
				

				// --- Rotation
				
				function rotate(delta)
				{
					var current_obj = Shadowbox.getCurrent();
					var current_id  = $(current_obj.link).attr('data-id-referer');
		
					$.ajax({
						url: './index.php?action=image.ajaxRotate',
						data: {
							'crypt/document_id': current_id,
							'delta': delta,
							'token': '<?= $_SESSION['_TOKEN'] ?>'
						},
						dataType:'html',
						success: function(response)
						{
							if(response=='DONE')
							{
								$("#"+current_id+"_thumb").attr('src', $("#"+current_id+"_thumb").attr('src')+"&"+(new Date()).getTime());
								current_obj.content += "&"+(new Date()).getTime();
								Shadowbox.close();Shadowbox.open(current_obj);
							}
						},
						error: function()
						{
							alert('Impossible d\'effectuer la rotation');
						}
					});
				}

			//]]>
			</script>
			
			<style type="text/css">
			<!--
				.photo_container
				{
					overflow: scroll
					overflow-y: hidden;
					-ms-overflow-y: hidden;
					white-space: nowrap;
					width: 100%;
					margin-top: 5px;
					margin-bottom: 10px;
				}
				
				.photo_container figure 
				{
					position: relative;
					display: inline-block;
					margin: 5px;
					height: 120px;
					width: 90px;
				}
				
				.photo_container figure img
				{
					display: block;
					border: 1px solid #666;
					margin: 0;
					padding: 0;
					cursor: pointer;
					max-width: 100%;
					max-height: 100%;
				}
				
				.photo_container figure figcaption
				{
					display: block;
					left:0; 
					text-align: center;
					padding: 0;
					margin: 0;
					margin-top: 2px;
					white-space: normal;
					width: 100%;
				}
				
				.photo_container figure figcaption .editlink
				{
					display:inline-block;
					height: 100%;
					float: left;
				}
				
				#sb-container {
					z-index: 99999;
				}
			-->
			</style>
			
		<?php endif; ?>
		
		<style type="text/css">
		<!--
			/* Largeur générale (titre + conteneur de photos) */
			
			section.gallery_container[data-uniqid="<?=$uniqid?>"] 
			{
				width: <?= $width ?>;
			}
			
			/* Propriétés du conteneur de photos (hauteur, scrollbars, ...) */
			
			.photo_container[data-uniqid="<?=$uniqid?>"]
			{
				<?php if ($scroll!='horizontal'):?>
					overflow-y: scroll;
					-ms-overflow-y: scroll;
				<?php endif;?>
				<?php if ($scroll!='vertical'):?>
					overflow-x: scroll;
					-ms-overflow-x: scroll;
				<?php endif;?>
				<?php if ($scroll == 'none'): ?>
				overflow: hidden !important;
				<?php endif; ?>
				<?php if (!empty($height)): ?>
				height: <?=$height?>;
				<?php endif;?>
			}
						
			/* Propriétés du titre */
			
			h3.gallery_title[data-uniqid="<?=$uniqid?>"]
			{
				border-bottom:2px solid #000;
				margin-bottom: 5px;
				margin-top: 5px;
			}
			
			/* Propriétés des photos + titre (hauteurs, largeur) */
			
			.photo_container[data-uniqid="<?=$uniqid?>"] figure 
			{
				height:<?= $thumbheight ?>;
				width:<?= $thumbwidth ?>;
			}
			
			
			<?php if(! $show_caption): ?>
			
			/* On ne veux pas voir le titre... */
			
			.photo_container[data-uniqid="<?=$uniqid?>"] figure figcaption
			{
				visibility: hidden;
				display: none;
				width: 0;
				height: 0;
			}
			<?php else:?>
			
			/* Position du titre */
			
			.photo_container[data-uniqid="<?=$uniqid?>"] figure figcaption
			{
				top: <?=$thumbheight?>;
			}
			<?php endif; ?>
					
			<?php if (!$show_counter): ?>
			
			/* On masque le compteur d'images */
			
			#sb-info[data-uniqid="<?=$uniqid?>"] #sb-counter
			{
				display:none;
			}
			<?php endif;?>
			
			<?php if (!$can_rotate): ?>
			
			/* On masque le bouton de rotation */
			
			#sb-info[data-uniqid="<?=$uniqid?>"] #sb-nav-rotate_left,
			#sb-info[data-uniqid="<?=$uniqid?>"] #sb-nav-rotate_right
			{
				display: none !important;
				width:   0 !important;
				height:  0 !important;
			}
			<?php endif; ?>
		-->
		</style>
		
		<section class="gallery_container <?=$css_class?>" style="<?=$global_style?>" data-uniqid="<?=$uniqid?>">
			<?php if (!empty($string)):?>
				<h3 class="gallery_title <?=$css_class?>" style="<?=$title_style ?>" <?=trim($id)?'id="title_'.$id.'"':''?> data-uniqid="<?= $uniqid ?>"><?= $string ?></h3>
			<?php endif; ?>
			
			<div class="photo_container <?=$css_class?>" style="<?=$style?>" id="<?= $id ?>" data-uniqid="<?= $uniqid ?>">
			
				<?php if(empty($picture_array)): ?>
				
					<strong class="empty_message" style="display: block; margin-left:2%; margin-top:2em;"><?= $empty_message ?></strong>
					
				<?php endif;
		
				foreach ($picture_array as $picture_id => $picture):
					Security::crypt($picture_id, $crypted_id);
					Security::crypt($picture['file_name']['value'], $crypted_filename);
					
					$title = array();
					
					$title[] = $show_label? (empty($picture['label']['value'])?$empty_label_msg:$picture['label']['value']) : null;
					$title[] = $show_user ? $picture['users_id']['reference'] : null;
					$title[] = $show_date ? date('H:i:s d/m/Y',$picture['date_creation']['timestamp']) : null;
					
					$figcaption = $title;
					$title = join("\n", array_filter($title));
					
					$link = 'index.php?action=image.edit&inside_popup=1&crypt/primary_key='.$crypted_id.'&view=form&token='.$_SESSION['_TOKEN'];
					if ($show_annotations)
					{
						$figcaption[] = '<a href="'.$link.'" class="popuplink">annotations ('.count($picture['annotations']['value']).')</a>';
					}
					if ($show_editlink)
					{
						$figcaption[] = '<a href="'.$link.'" class="editlink popuplink" id="editlink-'.$crypted_id.'"><span class="ui-icon ui-icon-pencil" style="display:block;"></span></a>';
					}
					
					$figcaption = join("\n", array_filter($figcaption));
					
					$string_area = array();
					if (isset($picture['annotations']['list']))
					{
						foreach($picture['annotations']['list'] as $annotation)
						{
							Security::crypt($annotation['annotation_id']['value'], $area_id);
							$area_annotation_caption = $annotation['annotation_texte']['value'];
							$area_annotation_caption = str_replace("\\",'/', $area_annotation_caption);
							$area_Ax =  $annotation['coord_Ax']['value'];
							$area_Ay =  $annotation['coord_Ay']['value'];
							$area_Bx =  $annotation['coord_Bx']['value'];
							$area_By =  $annotation['coord_By']['value'];
							$string_area[] = array(
								'id' => $area_id,
								'caption' => $area_annotation_caption,
								'A' => array('x' => $area_Ax, 'y' => $area_Ay),
								'B' => array('x' => $area_Bx, 'y' => $area_By)
							);
						}
					}
					$string_area = json_encode($string_area);
					
					?>
						<figure id="<?= $crypted_id ?>_figure" style="<?=$figure_style?>">
							<a href="index.php?action=image.show&crypt/primary_key=<?= $crypted_id ?>&token=<?= $_SESSION['_TOKEN']; ?>" rel="shadowbox[images];player=img;gallery=<?=$uniqid?>" data-player="img"  title="<?=htmlentities($title)?>" data-id-referer='<?= $crypted_id ?>' style="position:relative;display:block;" data-mapping-areas="<?=htmlentities($string_area)?>">
								<img id='<?= $crypted_id ?>_img' src='index.php?action=image.show&view=thumb&crypt/file_name=<?= $crypted_filename ?>&token=<?= $_SESSION['_TOKEN']; ?>' title="<?=htmlentities($title)?>" style="position:relative;">
							</a>
							<figcaption id='<?= $crypted_id ?>_figcaption'>
								<?= $show_caption ? $figcaption : '' ?>
							</figcaption>
						</figure>
				<?php endforeach; ?>
				
			</div> <!-- Conteneur des photos -->
		</section> <!-- Conteneur de toute la gallerie (titre + photos) -->
		
		<?php

		return TRUE;
	}
	
}
