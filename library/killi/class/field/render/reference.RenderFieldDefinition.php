<?php

/**
 *  @class ReferenceRenderFieldDefinition
 *  @Revision $Revision: 4676 $
 *
 */

class ReferenceRenderFieldDefinition extends TextRenderFieldDefinition
{
	public function renderFilter($name, $selected_value)
	{
		if(!is_array($selected_value))
		{
			if(empty($selected_value))
			{
				$selected_value = array();
			}
			else
			{
				$selected_value = array($selected_value);
			}
		}

		?><table class='ui-filter ui-filter-text'><?php
			?><tr><?php
				?><td><?php

					if(count($selected_value) <= 1)
					{
						?><input type='hidden' id="search_<?= $this->node->id ?>_op" name="<?= $name.'/op' ?>" value='LIKE'/><?php
						?><input class="search_input" style="width:100px;" type="text" name="<?= $name ?>" id="search_<?= $this->node->id ?>" value="<?= htmlentities(reset($selected_value),ENT_COMPAT,'UTF-8'); ?>"/><?php
					}
					else
					{
						?><input type='hidden' id="search_<?= $this->node->id ?>_op" name="<?= $name.'/op' ?>" value='IN'/><?php
						?><select style="width:100px;" class="search_input" multiple="multiple" name="<?= $name ?>[]" id="search_<?= $this->node->id ?>"><?php
						foreach($selected_value AS $v)
						{
							$v = htmlentities($v,ENT_COMPAT,'UTF-8');
							echo '<option selected value="', $v, '">', $v, '</option>';
						}
						?></select><?php
					}
				?></td><?php
				?><td class='ui-filter-clear'><?php
					?><img onclick="select_reference_<?= $this->node->id ?>($('#search_<?= $this->node->id ?>'));" src='library/killi/images/gtk-find.png'/><?php
				?></td><?php

			if(!empty($selected_value))
			{
				?><td class='ui-filter-clear'><?php
					?><img onclick="$('#search_<?= $this->node->id ?>').val('');$('#search_<?= $this->node->id ?>_txt').val('');trigger_search($('#search_<?= $this->node->id ?>'));" src='library/killi/images/delete.png'/><?php
				?></td><?php
			}

			?></tr><?php
		?></table><?php

		?>
		<div id="dialog_select_reference_<?= $this->node->id ?>" title="Sélection de références" style="display: none;">
			<h3>Sélection à partir de référence</h3>
			<br/>
			<div>
				Nombre de références : <span id="reference_count_<?= $this->node->id ?>"></span><br/>
				<div id="reference_selector_<?= $this->node->id ?>" style="overflow:scroll; width: 250px; height: 400px;"></div>
			</div>
		</div>

<script type="text/javascript">
			function select_reference_<?= $this->node->id ?>(field) {
				$('#dialog_select_reference_<?= $this->node->id ?>')
					.dialog({
							autoOpen: true,
							bgiframe: true,
							modal: false,
							width: '320px',
							buttons: {
								'Ajouter': function() {
											notValid = false;
											var dialog = $(this);
											$('#reference_selector_<?= $this->node->id ?>').data('handsontable').validateCells(function()
											{
												if(!notValid)
												{
													/* Replace input field */
													var container = $('#search_<?= $this->node->id ?>').parent();
													var old_field = $('#search_<?= $this->node->id ?>').clone(true);
													$('#search_<?= $this->node->id ?>').remove();

													container.append('<select style="width:100px;" class="search_input" multiple="multiple" name="<?= $name ?>[]" id="search_<?= $this->node->id ?>"></select>');
													$('#search_<?= $this->node->id ?>_op').val('IN');

													var reference_list = $('#reference_selector_<?= $this->node->id ?>').data('handsontable').getDataAtCol(0);
													for(var index in reference_list)
													{
														if(typeof reference_list[index] == 'string')
														{
															var r = reference_list[index];
															if(r.trim)
															{
																r = r.trim();
															}
															$('#search_<?= $this->node->id ?>').append('<option selected value="' + r +'">' + r +'</option>');
														}
													}

													/* Copy events */
													$.each($._data(old_field.get(0), 'events'), function() {
														// iterate registered handler of original
														$.each(this, function() {
															$('#search_<?= $this->node->id ?>').bind(this.type, this.handler);
														});
													});

													/* Submit search */
													trigger_search($('#search_<?= $this->node->id ?>'));
													dialog.dialog('close');
												}
											});
										},
								Cancel: function() {
											$(this).dialog('close');
											$('#reference_selector_<?= $this->node->id ?>').data('handsontable').loadData([]);
										},
							}
					});

				$('#reference_selector_<?= $this->node->id ?>').handsontable({
					colHeaders: ['Référence'],
					contextMenu: false,
					minSpareRows: 1,
					minCols:1,
					maxCols:1,
					minRows:1,
					maxRows:10000,
					colWidths: [200],
					columns: [
						{data: 'reference', allowInvalid: true, readOnly: false}
					],
					beforeChange: function (changes, source) {
						for(var i = changes.length - 1; i >= 0; i--)
						{
							changes[i][0] = (new String(changes[i][0])).trim();
						}
					},
					afterChange: function(changes, source)
					{
						var total_not_empty = 0;
						var datas = $('#reference_selector_<?= $this->node->id ?>').handsontable("getData");
						for(var index in datas)
						{
							if(datas.hasOwnProperty(index))
							{
								if(datas[index].hasOwnProperty('reference'))
								{
									if(datas[index]['reference'].length > 0)
									{
										total_not_empty++;
									}
								}
							}
						}
						$('#reference_count_<?= $this->node->id ?>').text(total_not_empty);
					},
					afterValidate: function(isValid, value, row, prop, source)
					{
						if(!isValid && value != undefined)
						{
							notValid = true;
							alert('La référence ' + value + ' n\'est pas valide');
						}
						return false;
					}
				});
			}
		</script><?php

		return TRUE;
	}
}
