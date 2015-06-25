<?php

/**
 *  @class DocuploaderXMLNode
 *  @Revision $Revision: 4671 $
 *
 */

class DocuploaderXMLNode extends XMLNode
{
	private $_htmlSelectString = '';

	public function open()
	{
		$doc_type_filter	= $this->getNodeAttribute('document_type_filter', '');
		$name				= $this->getNodeAttribute('name', '');
		$object				= $this->getNodeAttribute('object');
		$limit_nfiles		= $this->getNodeAttribute('limit', '');
		$title				= $this->getNodeAttribute('string', 'Ajouter des documents');

		if ($this->_edition != TRUE)
		{
			// Affichage uniquement en mode édition.
			return TRUE;
		}

		$input_name = $this->id;
		if (!empty($name))
		{
			$input_name = $name;
		}

		$doc_type_list = array();
		if (empty($doc_type_filter))
		{
			ORM::getORMInstance('documenttype')->browse($doc_type_list, $num_doctype, array('name', 'obsolete'), array(
				array('obsolete', '=', '0')
			));
		}
		else
		{
			$doc_type_id_list = explode(',', $doc_type_filter);

			foreach ($doc_type_id_list as &$doc_type_id)
			{
				if (!is_numeric($doc_type_id))
				{
					if (defined($doc_type_id))
					{
						$doc_type_id = constant($doc_type_id);
					}
				}
			}
			ORM::getORMInstance('documenttype')->read($doc_type_id_list, $doc_type_list, array('name', 'obsolete'));
		}

		try
		{
			$hInstance = ORM::getObjectInstance($object);
		}
		catch(UndeclaredObjectException $e)
		{
			// Rien à faire.
		}

		$enable_dnd = (/*isset(ORM::getObjectInstance($object)->mime2doctype) && */$this->getNodeAttribute('dnd', '0') == '1');

		$select_pair_list = array();
		if (!isset($hInstance->mime2doctype) && !isset($hInstance->extension2doctype) && count($doc_type_list) != 1)
		{
			$this->_htmlSelectString =	'<select name="crypt/doctype_'.$input_name.'[]">'.
											'<option value="">-- Choisir --</option>';
			foreach ($doc_type_list as $doc_type_id => $doc_type)
			{
				if ($doc_type['obsolete']['value'] == TRUE)
				{
					continue;
				}
				$crypt_doc_type_id = '';
				Security::crypt($doc_type_id, $crypt_doc_type_id);
				$this->_htmlSelectString .= '<option value="'.$crypt_doc_type_id.'">'.$doc_type['name']['value'].'</option>';
				$select_pair_list[] = array(
					'id'	=> $crypt_doc_type_id,
					'label' => $doc_type['name']['value']
				);
			}
			$this->_htmlSelectString .=	'</select>';
		}
		elseif (!isset($hInstance->mime2doctype) && !isset($hInstance->extension2doctype) && count($doc_type_list) == 1)
		{
			$doc_type_id = reset($doc_type_id_list);
			$crypt_doc_type_id = '';
			Security::crypt($doc_type_id, $crypt_doc_type_id);
			$this->_htmlSelectString = '<input type="hidden" name="crypt/doctype_'.$input_name.'[]" value="'.$crypt_doc_type_id.'" />';
		}

		if(!empty($title))
		{
			?>
				<table class="separator">
					<tr>
						<td><?= $title ?></td>
					</tr>
				</table>
			<?php
		}
		?>
			<script type="text/javascript">
			if(!doctype_select_content)
			{
				var doctype_select_content = new Array();
			}
			</script>
			<div id="docuploader_<?= $input_name; ?>" data-id="<?= $input_name; ?>" class="docuploader"<?php if ($enable_dnd){ echo ' docuploader_dnd="true"'; } ?>>
				<input type="hidden" id="docuploader_limit_<?= $input_name; ?>" value="<?= $limit_nfiles; ?>"/>

				<?php
				if(!empty($_GET['primary_key']))
				{
					echo '<input type="hidden" name="document_object_ref_id_', $input_name, '" value="', $_GET['primary_key'], '"/>';
				}

				if ($enable_dnd)
				{
					if (!isset($hInstance->mime2doctype) && !isset($hInstance->extension2doctype) && count($doc_type_list) != 1)
					{
						?>
						<script type="text/javascript">
							doctype_select_content["<?= $input_name ?>"] = <?php echo json_encode($select_pair_list); ?>;
						</script>
						<?php
					}
					else
					if(count($doc_type_list) == 1)
					{
						$doc_type_id = key($doc_type_list);
						Security::crypt($doc_type_id, $crypt_doc_type_id);
						?><input type="hidden" name="crypt/doctype_<?= $input_name ?>[]" value='<?php echo $crypt_doc_type_id; ?>' /><?php
					}
				?>

				<br />
				<h2>Glissez un ou plusieurs fichiers ici.</h2>
				<br />

				<?php
				}
				else
				{
				?>

				<div data-container="FileInputNode_<?= $input_name; ?>" class="cl docuploader_line">
					<div style="float: left">
						<button type="button" onclick="docuploader_on_del(this);" class="docuploader_btn_del"><i class="fa fa-minus"></i></button>
						<input type="file" name="docupload_<?php echo $input_name; ?>[]" />
					</div>
					<div style="float: right;">
					<?php
					if (!isset($hInstance->mime2doctype) && !isset($hInstance->extension2doctype))
					{
						echo $this->_htmlSelectString;
					}
					?>
					</div>
				</div>
				<button type="button" onclick="docuploader_on_add(this);" class="docuploader_btn_add"><i class="fa fa-plus"></i></button>

				<?php
				}
				?>
		<?php
	}
	//.....................................................................
	public function close()
	{
		if ($this->_edition != TRUE)
		{
			// Affichage uniquement en mode édition.
			return TRUE;
		}

		?>
			</div>
			<script type="text/javascript">
			<?php
			if (!defined('DOCUPLOADER_JS_FUNC'))
			{
				define('DOCUPLOADER_JS_FUNC', true);
			?>
			var global_filelist = [];

			function getDocTypeSelectList(xmlnode_id)
			{
				var select_list = doctype_select_content[xmlnode_id];
				var select_obj = $('<select name="crypt/doctype_' + xmlnode_id + '[]"><option value="">--- Choisir ---</option></select>');
				for (var i = 0 ; i < select_list.length ; i++)
				{
					var select_item = select_list[i];
					select_obj.append($('<option value="' + select_item.id + '">' + select_item.label + '</option>'));
				}

				return select_obj;
			}

			function docuploader_on_add(btn)
			{
				var $btn = $(btn);
				var id = $btn.parent().data('id');
				var len = $('div[data-container="FileInputNode_' + id + '"]').length;
				var max = $('#docuploader_limit_' + id).val();

				if (max != '' && len >= parseInt(max))
				{
					return;
				}
				var last = $('div[data-container="FileInputNode_' + id + '"]').last().clone(true).fadeIn().insertBefore($btn);
			}

			function docuploader_on_del(btn)
			{
				var $btn = $(btn);
				var div_container = $btn.parent().parent();
				var len = div_container.parent().find('div.docuploader_line').length;

				if (len <= 1)
				{
					return;
				}

				div_container.fadeOut(function(){ $(this).remove(); });
			}

			function du_assign()
			{
				// On empêche une redirection di l'user drag le fichier à l'extérieur de la drop box.
				$(document).on('dragenter', function (e)
				{
					e.stopPropagation();
					e.preventDefault();
				});
				$(document).on('dragover', function (e)
				{
					e.stopPropagation();
					e.preventDefault();
					$('div[docuploader_dnd=true]').css('background-color', '#104ba9');
				});
				$(document).on('drop', function (e)
				{
					e.stopPropagation();
					e.preventDefault();
				});

				// Assignation des évènements drag + drop.
				$('div[docuploader_dnd=true]').on('dragenter', function(e){
					e.stopPropagation();
					e.preventDefault();
					$('div[docuploader_dnd=true]').css('background-color', '#3bda00');
				});

				$('div[docuploader_dnd=true]').on('dragleave', function(e){
					e.stopPropagation();
					e.preventDefault();
					$(this).css('background-color', 'transparent');
				});

				$('div[docuploader_dnd=true]').on('dragover', function(e){
					e.stopPropagation();
					e.preventDefault();
					$(this).css('background-color', '#3bda00');
				});

				$('div[docuploader_dnd=true]').on('drop', function(e){

					var id = $(this).attr('id');
					id	 = id.substr(12, id.length)
					$(this).css('background-color', 'transparent');
					e.preventDefault();
					var files = e.originalEvent.dataTransfer.files;

					var initial_count = $('input[name=xhrdocupload_' + id + ']').length;

					var max = $('#docuploader_limit_' + id).val();

					for (var i = 0 ; i < files.length ; i++)
					{
						if (max != '' && initial_count + i + 1 > parseInt(max))
						{
							continue;
						}

						global_filelist.push(files[i]);
						var container = $('<div class="cl docuploader_line"></div>');
						var input = $('<div style="float:left;"><span>' + files[i].name + '</span><input type="hidden" name="xhrdocupload_' + id + '" value=\'' + (global_filelist.length - 1) + '\'/></div>');
						var selector = $('<div style="float:right;"></div>');

						// Bouton supprimer
						var btn = $('<button type="button" onclick="docuploader_on_del(this);" class="btn_del_' + id + ' docuploader_btn_del">-</button>');
						btn.click(function(event){
							$(event.target).parent().fadeOut(function(){ $(this).remove(); });
						});
						input.prepend(btn);// Select box doctype

						if (doctype_select_content && doctype_select_content[id] && doctype_select_content[id].length > 0)
						{
							selector.append(getDocTypeSelectList(id));
						}
						container.append(input);
						container.append(selector);
						$(this).append(container.fadeIn());
					}
				});
			}
			$(document).ready(du_assign);
			<?php
			}
			?>

			</script>
		<?php
	}
}
