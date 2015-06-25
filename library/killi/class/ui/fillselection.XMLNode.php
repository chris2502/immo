<?php

/**
 *  @class FillselectionXMLNode
 *  @Revision $Revision: 4605 $
 *
 */

class FillselectionXMLNode extends XMLNode
{
	public function open()
	{
		$object		= $this->getNodeAttribute('object');
		$emitter_attr	= $this->getNodeAttribute('emitter_attr');
		$receiver_attr	= $this->getNodeAttribute('receiver_attr');
		$emitter_dataf  = $this->getNodeAttribute('emitter_data');
		$receiver_dataf = $this->getNodeAttribute('receiver_data');
		$emitter_title  = $this->getNodeAttribute('emitter_title',  'Émetteur');
		$receiver_title = $this->getNodeAttribute('receiver_title', 'Récepteur');
		$emitter_width  = $this->getNodeAttribute('emitter_width',  '280px');
		$receiver_width = $this->getNodeAttribute('receiver_width', '280px');
		$editable	   = $this->getNodeAttribute('editable', '1');
		$autoheight	 = ($this->getNodeAttribute('auto_height', '0') == '1');

		if($editable == '1')
		{
			Rights::getCreateDeleteViewStatus ( $object, $create, $delete, $view );
			$editable = ($this->_edition===TRUE) ? $create && $delete : FALSE;
		}
		else
		{
			$editable = FALSE;
		}

		$emitter_data   = $this->_data_list[$emitter_dataf];
		$receiver_data  = $this->_data_list[$receiver_dataf];

		$current_attr   = 'slot_id';

		$hORM = ORM::getORMInstance($object);
		$object_list = array();
		$total = 0;
		$hORM->browse($object_list, $total, array($emitter_attr, $receiver_attr), array(array($current_attr, '=', $_GET['primary_key'])));

		$selectedByEmitter = array();
		$selectedByReceiver = array();
		foreach($object_list AS $id => $data)
		{
			/* Recherche de l'emetteur dans les groupes. */
			foreach($emitter_data AS $group_id => $group_data)
			{
				if(isset($group_data['item'][$data[$emitter_attr]['value']]))
				{
					$receiver_id = 0;
					Security::crypt($data[$receiver_attr]['value'], $receiver_id);
					$emitter_id = 0;
					Security::crypt($data[$emitter_attr]['value'], $emitter_id);
					$selectedByEmitter[$emitter_id] = $group_id;
					$record_id = 0;
					Security::crypt($id, $record_id);
					$selectedByReceiver[$receiver_id][$emitter_id] = array('group' => $group_id, 'id' => $record_id, 'editable' => TRUE);
				}
			}
		}

		?>
		<style>
		#emitter_area { margin: 0; padding: 0;}
		#emitter_area h2 { font-size: 14px; }

		#emitter { margin: 0; padding: 0;}
		.emitter-group { float: left; margin: 10px; padding: 0; width: <?= $emitter_width ?>;}
		.emitter-group h3 { font-size: 12px; }
		.emitter-group div {overflow: auto; height: 100px;}
		.emitter-group div > ol { list-style-type: none; margin: 0; padding: 0; width: auto;}

		.emitter-item { <?= $editable ? 'cursor: move;' : '' ?>margin: 3px; padding: 3px; padding-top: 3px; font-size: 11px; height: 14px; display: block; min-width: 200px; width: auto;}
		.emitter-item a { float: right; }

		#receiver_area { margin: 0; padding: 0;}
		#receiver_area h2 { font-size: 14px; }

		.receiver-item .emitter-item { display: block; width: auto;}

		.receiver-item { float: left; margin: 10px; padding: 0; width: <?= $receiver_width ?>;}
		.receiver-item h3 { font-size: 12px; }
		.receiver-item div { overflow: auto; height: 100px;}
		</style>
		<?php
			if($editable === TRUE)
			{
				?>
				<script type="text/javascript">
				<!--
				var autoresize = function(){
					// Basic padding (deprecated)
					var b_p = 45;
					// Notebook height
					var n_h = parseInt($('ul[id^=notebook]:first').css('height') || '0px');
					// Total elements heights
					var t_h = header_height() + footer_height() + n_h + b_p;
					// Set heights
					$('#receiver_area').css({
						'height':(($(window).height()) - t_h) + 'px',
						'overflow-y':'auto'
					});
					$('#emitter_area').css({
						'height':(($(window).height()) - t_h) + 'px',
						'overflow-y':'auto'
					});
				};

				<?php if ($autoheight): ?>
				$(window).resize(autoresize);
				$(document).ready(autoresize);
				<?php endif; ?>

				$(function() {
					$(".emitter-item").draggable({
						revert: "invalid",
						helper: "clone",
					});
					$("#emitter_area").droppable({
						accept: ".receiver-item .emitter-item",
						drop: function (event, ui) {
						removeEmitterFromReceiver( ui.draggable );
						},
					});
					$(".receiver-item").droppable({
						accept: ".emitter-item",
						drop: function(event, ui) {
							removeEmitterFromList( ui.draggable, this );
						},
					});
				});
				function removeEmitterFromList( item, receiver ) {

					removeEmitterFromReceiver( item );

					item.fadeOut(function() {
						var list = $("ol", receiver).length ? $("ol", receiver) : $('<ol class="set"/>').appendTo($("div", receiver));
						var group_title = $("h3", item.parent().parent().parent()).text();
						$('.emitter-hide', item).css('display', 'inline');
						$('.receiver-hide', item).hide();
						item.appendTo(list).fadeIn();
					});
					$.post('./index.php?action=<?= $object ?>.create&token=<?= $_SESSION['_TOKEN']?>&redirect=0',
						{
							'object': '<?= $object ?>',
							'crypt/<?= $object ?>/<?= $current_attr ?>': '<?= $_GET['crypt/primary_key'] ?>',
							'crypt/<?= $object ?>/<?= $emitter_attr ?>': item.attr('emitter_item'),
							'crypt/<?= $object ?>/<?= $receiver_attr ?>': $(receiver).attr('receiver'),
						},
						function(data) {
							if(typeof data.id != 'undefined')
							{
								item.data('id', data.id);
							}
							else
							{
								alert('Erreur: Veuillez réessayer plus tard.');
								removeEmitterFromReceiver( item );
							}
						}, 'json')
					.error(function(data) {
						alert('Erreur: Veuillez réessayer plus tard.');
						removeEmitterFromReceiver( item );
					});
				}
				function removeEmitterFromReceiver( item ) {
					if(typeof item.data('id') != 'undefined')
					{
						$.post('./index.php?action=<?= $object ?>.unlink&token=<?= $_SESSION['_TOKEN'] ?>&redirect=0',
							{
								'crypt/primary_key': item.data('id'),
							},
							function(data) {
								if(typeof data.success != 'undefined')
								{
									item.removeData('id');
									item.fadeOut(function() {
										var group = item.attr("group");
										var emitter = $('.emitter-group[emitter="' + group + '"] ol');
										$('.emitter-hide', item).css('display', 'none');
										$('.receiver-hide', item).show();
										item.appendTo(emitter).fadeIn();
									});
								}
								else
								{
									alert('Erreur: Veuillez réessayer plus tard.');
								}
							}, 'json')
						.error(function(data) {
							alert('Erreur: Veuillez réessayer plus tard.');
						});
					}
				}
				function showContextMenu(origin, menu )
				{
					var p = origin.position();
					menu.css('top', p.top+origin.height());
					menu.css('left', p.left+origin.width());
					if(menu.is(':visible'))
					{
						menu.hide();
					}
					else
					{
						menu.click(function(e) {
							menu.hide();
							e.stopPropagation();
						});
						menu.show();
						menu.focus();
					}
				}
				function hideContextMenu(origin, menu)
				{
					menu.focusout(function()
					{
						menu.hide();
					});
				}
				//-->
				</script>
				<?php
			}

		?><table width="100%"><?php
			?><tr><?php

					if($editable===TRUE)
					{
						?>
						<td align="center" style="vertical-align: top; margin: 0; padding: 0;" width="50%">
							<div id="emitter_area" class="ui-widget ui-helper-clearfix">
									<ul id="emitter" class="emitter ui-helper-reset ui-helper-clearfix">
									<h2 class="ui-widget-header"><?= $emitter_title ?></h2>
									<?php
									foreach($emitter_data AS $group_id => $group_data)
									{
										Security::crypt($group_id, $group_id);
									?>
									<li class="emitter-group ui-widget-content ui-state-default ui-corner-tr" emitter="<?= $group_id ?>">
										<h3 class="ui-widget-header"><?= $group_data['title'] ?></h3>
										<div>
										<ol>
											<?php
											foreach($group_data['item'] AS $id => $data)
											{
												Security::crypt($id, $id);
												if(!isset($selectedByEmitter[$id]) && (!isset($data['disabled']) || !$data['disabled']))
												{
													?>
													<li class="ui-widget-content emitter-item" group="<?= $group_id ?>" emitter_item="<?= $id ?>">
														<?php
														if(isset($data['object']))
														{
															?>
															<a target="_blank" href="./index.php?action=<?= $data['object']; ?>.edit&view=form&token=<?= $_SESSION['_TOKEN']; ?>&crypt/primary_key=<?= $id ?>" class="ui-icon ui-icon-pencil" style="margin-top: -2px;float: left;" title="Édition">Édition</a>
															<?php
														}
														?>
														<span class="emitter-hide" style="display: none;"><?= $group_data['title'] ?> - </span>
														<?= $data['title'] ?>
														<span class="receiver-hide" style="cursor: pointer;"><a id="menu_link_item_<?= $id ?>" onmouseout="hideContextMenu($(this), $('#item_menu_<?= $id ?>'))" onclick="showContextMenu($(this), $('#item_menu_<?= $id ?>'))" title="Associer" class="ui-icon ui-icon-arrowthick-2-e-w"></a></span>
														<span class="emitter-hide" style="cursor: pointer;display: none;"><a onclick="removeEmitterFromReceiver($(this).parent().parent())" title="Retirer" class="ui-icon ui-icon-trash" style="margin-top: -2px;">Retirer</a></span>
														<ul id="item_menu_<?= $id ?>" style="position: absolute; display: none;">
														<?php
															foreach($receiver_data AS $receiver_id => $receiver)
															{
																Security::crypt($receiver_id, $receiver_id);
																?>
																<li>
																	<a onclick="removeEmitterFromList($(this).parent().parent().parent(), $('li[receiver=<?= $receiver_id ?>]'))"><?= $receiver['title']?></a>
																</li>
																<?php
															}
														?>
														</ul>
													</li>
													<script type="text/javascript">
														$(function() {
															$("#item_menu_<?= $id ?>").menu();
														});
														var group_item = new Array();
														<?php
															foreach($data['group'] AS $group_id)
															{
																?>
																group_item.push('<?= $group_id ?>');
																<?php
															}
														?>
														$('li[emitter_item="<?= $id ?>"]').data('group', group_item);
													</script>
													<?php
												}
											}
											?>
										</ol>
										</div>
									</li>
									<?php
									}
									?>
								</ul>
							</div>
						</td>
						<?php
					}
				?>
				<td align="center" style="vertical-align: top; margin: 0; padding: 0;" width="50%">
					<div id="receiver_area" class="ui-widget ui-helper-clearfix">
							<ul id="receiver" class="receiver ui-helper-reset ui-helper-clearfix">
							<h2 class="ui-widget-header"><?= $receiver_title ?></h2>
							<?php
							foreach($receiver_data AS $id => $receiver)
							{
								if($receiver['editable'] == FALSE)
								{
									continue;
								}
								Security::crypt($id, $id);
							?>
							<li class="receiver-item ui-widget-content ui-state-default" receiver="<?= $id ?>">
								<h3 class="ui-widget-header"><?= $receiver['title'] ?></h3>
								<div>
									<ol>
									<?php
										if(isset($selectedByReceiver[$id]))
										{
											foreach($selectedByReceiver[$id] AS $item_id => $item_data)
											{
												$group_id = $item_data['group'];
												$item_editable = $item_data['editable'];
												$iid = 0;
												Security::decrypt($item_id, $iid);
												$data = $emitter_data[$group_id]['item'][$iid];
												$group_data = $emitter_data[$group_id];
												Security::crypt($group_id, $group_id);
												?>
												<li class="ui-widget-content emitter-item" group="<?= $group_id ?>" emitter_item="<?= $item_id ?>">
													<?php
													if(isset($data['object']))
													{
														?>
															<a target="_blank" href="./index.php?action=<?= $data['object']; ?>.edit&view=form&token=<?= $_SESSION['_TOKEN']; ?>&crypt/primary_key=<?= $item_id ?>" class="ui-icon ui-icon-pencil" style="margin-top: -2px;float: left;" title="Édition">Édition</a>
														<?php
													}
													?>
													<span class="emitter-hide"><?= $group_data['title'] ?> - </span>
													<?= $data['title'] ?>
													<?php
													if($editable === TRUE && $item_editable)
													{
														?>
														<span class="receiver-hide" style="cursor: pointer; display: none;"><a id="menu_link_item_<?= $item_id ?>" onmouseout="hideContextMenu($(this), $('#item_menu_<?= $item_id ?>'))" onclick="showContextMenu($(this), $('#item_menu_<?= $item_id ?>'))" title="Associer" class="ui-icon ui-icon-arrowthick-2-e-w"></a></span>
														<span class="emitter-hide" style="cursor: pointer;"><a onclick="removeEmitterFromReceiver($('li[emitter_item=<?= $item_id ?>]'))" title="Retirer" class="ui-icon ui-icon-trash" style="margin-top: -2px;">Retirer</a></span>
														<ul id="item_menu_<?= $item_id ?>" style="position: absolute; display: none;">
														<?php
															foreach($receiver_data AS $receiver_id => $receiver)
															{
																Security::crypt($receiver_id, $receiver_id);
																?>
																<li>
																	<a onclick="removeEmitterFromList($('li[emitter_item=<?= $item_id ?>]'), $('li[receiver=<?= $receiver_id ?>]'))"><?= $receiver['title']?></a>
																</li>
																<?php
															}
														?>
														</ul>
														<?php
													}
													?>
												</li>
												<?php
												if($editable === TRUE && $item_editable)
												{
													?>
													<script type="text/javascript">
														$(function() {
															$("#item_menu_<?= $item_id ?>").menu();
														});
														var group_item = new Array();
														<?php
															foreach($data['group'] AS $group_id)
															{
																?>
																group_item.push('<?= $group_id ?>');
																<?php
															}
														?>
														$('li[emitter_item="<?= $item_id ?>"]').data('group', group_item);
														$('li[emitter_item="<?= $item_id ?>"]').data('id', '<?= $item_data['id'] ?>');
													</script>
													<?php
												}
											}
										}
									?>
									</ol>
								</div>
								<script type="text/javascript">
									var group_item = new Array();
									<?php
										foreach($receiver['group'] AS $group_id => $group_number)
										{
											?>
											group_item.push({'id': '<?= $group_id ?>', 'max': '<?= $group_number ?>'});
											<?php
										}
									?>
									$('li[receiver="<?= $id ?>"]').data('group', group_item);
								</script>
							</li>
							<?php
							}
							?>
						</ul>
					</div>
				</td>
			</tr>
		</table>

		<?php
		return TRUE;
	}

	private function _fill_selection_item()
	{
		$items = $group_data['item'];

		foreach($items AS $id => $data)
		{
			Security::crypt($id, $id);
			if(!isset($selectedByEmitter[$id]))
			{
				?>
				<li class="ui-widget-content emitter-item" group="<?= $group_id ?>" emitter_item="<?= $id ?>">
					<?php
					if(isset($data['object']))
					{
					?>
						<a target="_blank" href="./index.php?action=<?= $data['object']; ?>.edit&view=form&token=<?= $_SESSION['_TOKEN']; ?>&crypt/primary_key=<?= $id ?>" class="ui-icon ui-icon-pencil" style="margin-top: -2px;float: left;" title="Édition">Édition</a>
					<?php
					}
					?>
					<span class="emitter-hide" style="display: none;"><?= $group_data['title'] ?> - </span>
					<?= $data['title'] ?>
					<span class="receiver-hide" style="cursor: pointer;"><a id="menu_link_item_<?= $id ?>" onmouseout="hideContextMenu($(this), $('#item_menu_<?= $id ?>'))" onclick="showContextMenu($(this), $('#item_menu_<?= $id ?>'))" title="Associer" class="ui-icon ui-icon-arrowthick-2-e-w"></a></span>
					<span class="emitter-hide" style="cursor: pointer;display: none;"><a onclick="removeEmitterFromReceiver($(this).parent().parent())" title="Retirer" class="ui-icon ui-icon-trash" style="margin-top: -2px;">Retirer</a></span>
					<ul id="item_menu_<?= $id ?>" style="position: absolute; display: none;">
					<?php
						foreach($receiver_data AS $receiver_id => $receiver)
						{
							Security::crypt($receiver_id, $receiver_id);
							?>
							<li>
								<a onclick="removeEmitterFromList($(this).parent().parent().parent(), $('li[receiver=<?= $receiver_id ?>]'))"><?= $receiver['title']?></a>
							</li>
							<?php
						}
					?>
					</ul>
				</li>
				<script type="text/javascript">
					$(function() {
						$("#item_menu_<?= $id ?>").menu();
					});
					var group_item = new Array();
					<?php
						foreach($data['group'] AS $group_id)
						{
							?>
							group_item.push('<?= $group_id ?>');
							<?php
						}
					?>
					$('li[emitter_item="<?= $id ?>"]').data('group', group_item);
					$('li[emitter_item="<?= $item_id ?>"]').data('id', '<?= $item_data['id'] ?>');
				</script>
			<?php
			}
		}
	}
}
