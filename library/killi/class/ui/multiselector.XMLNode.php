<?php

/**
 *  @class SeparatorXMLNode
 *  @Revision $Revision: 4426 $
 *
 */

class MultiSelectorXMLNode extends XMLNode
{
	static $is_multiselector_javascript_loaded = FALSE;

	public function open()
	{
		// Champs obligatoires
		$title = $this->getNodeAttribute('string');
		$object_name = $this->getNodeAttribute('object');
		$reference = $this->getNodeAttribute('reference');

		// Champs optionnels
		$pk_name = $this->getNodeAttribute('id', ORM::getObjectInstance($object_name)->primary_key);
		$min_length = $this->getNodeAttribute('minlength', 2);
		$max_results = $this->getNodeAttribute('maxresults', 10);

		?><style>
			.multiselector-name.disabled { background-color: #E6E6E6 !important; }
			.multiselector-del { font-size: 0; text-indent: -1000px; background: url('./library/killi/images/false.png') no-repeat 4px 1px; width: 10px; height: 10px; padding: 3px 6px; }
		</style>
		<script type="text/javascript">

		</script>
		<table id="multiselector-<?= $this->id ?>" class="field" cellspacing="2" cellpadding="1">
			<tbody>
				<tr>
					<td class="field_label"><?= $title; ?> : </td>
					<td id="multiselector-list-<?= $this->id ?>">
						<div class="multiselector-item cl"><?php

							if (isset($reference))

							if (isset($_SESSION['_POST'][$object_name.'_id_list']))
							{								
								$data_list = array();
								ORM::getORMInstance($object_name)->browse(
									$data_list,
									$total,
									array($reference),
									array( array($pk_name, 'IN', $_SESSION['_POST'][$object_name.'_id_list']) )
								);
								foreach ($_SESSION['_POST'][$object_name.'_id_list'] as $index => $id)
								{
									?><div class="multiselector-item cl">
										<input class="multiselector-name l disabled" type="text" disabled="disabled" value="<?= $data_list[$id][$reference]['value']; ?>">
										<div class="multiselector-del l">X</div>
										<input class="multiselector-id" type="hidden" name="crypt/<?= $object_name; ?>_id_list[]" value="<?= $_SESSION['_POST']['crypt/'.$object_name.'_id_list'][$index]; ?>">
									</div><?php
								}
							}
						?>
							<input class="multiselector-name l" type="text"/>
						</div>
					</td>
				</tr>
			</tbody>
		</table><?php

		if (self::$is_multiselector_javascript_loaded)
		{
			return TRUE;
		}
		?>
		<script type="text/javascript">
		if (multiselector !== undefined)
		{
			alert("La variable 'multiselector' réservée pour le XMLNode du même nom !");
		}

		var multiselector =
		{
			init: function()
			{
				multiselector.set_autocomplete($('.multiselector-name'));
				multiselector.set_delete($('.multiselector-del'));
			},
			add_item: function(id, value)
			{
				var item	= $('<div class="multiselector-item cl"/>');
				var input	= $('<input class="multiselector-name l disabled" type="text" disabled="disabled" value="'+value+'"/>');
				var del		= $('<div class="multiselector-del l">X</div>');
				var key		= $('<input class="multiselector-id" type="hidden" name="crypt/<?= $object_name ?>_id_list[]" value="'+id+'">');

				multiselector.set_delete(del);

				item.append(input).append(del).append(key);

				$('#multiselector-list-<?= $this->id ?>').prepend(item);
			},
			set_autocomplete: function(input)
			{
				input.autocomplete(
				{
					source: function(request, response)
					{
						var object_id_list = [];
						if ($('.multiselector-id').length > 0)
						{
							var count = 0;			
							$('.multiselector-id').each(function()
							{
								var input_val = $(this).val();
								object_id_list[count] = input_val;
								count++;
							});
						}
						$.ajax(
						{
							url: './index.php?action=common.getMultiSelectorDatas'+add_token()+'<?php if (!empty($reference)){ echo "&reference=".$reference; } ?>',
							dataType: "json",
							type: "GET",
							data: {
								term : request.term,
								maxresults : <?= $max_results ?>,
								id : '<?= $pk_name ?>',
								object : '<?= $object_name ?>',
								data_list : object_id_list
							},
							success: function(data)
							{
								response(data);
							},
							error: function()
							{
								alert('Une erreur est survenue, merci de contacter l\'administrateur de l\'application.');
							}
						});
					},
					minLength: 1,
					select: function( event, ui )
					{
						// ajout de l'ID dans un champs caché
						$(this).after('<input class="multiselector-id" type="hidden" name="crypt/<?= $object_name; ?>_id_list[]" value="'+ui.item.id+'"/>');

						// create clone
						next_input = $('<div class="multiselector-item cl"><input class="multiselector-name l" type="text"/></div>');
						multiselector.set_autocomplete(next_input.children('.multiselector-name'));
						$(this).parent().after(next_input);

						// add button after input and disable it
						var delete_button = $('<div class="multiselector-del l">X</div>');
						multiselector.set_delete(delete_button);
						$(this).after(delete_button);
						$(this).attr('disabled', 'disabled').addClass('disabled');

						next_input.focus();
					}
				});
			},
			set_delete: function(button)
			{					
				button.click(function()
				{
					$(this).parent().remove();
				});
			}
		}

		$(document).ready(function()
		{
			multiselector.init();
		});
		</script><?php

		self::$is_multiselector_javascript_loaded = TRUE;

		return TRUE;
	}
}
