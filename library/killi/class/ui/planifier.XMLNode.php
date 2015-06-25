<?php

/**
 *  @class PlanifierXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class PlanifierXMLNode extends XMLNode
{
	public $events = array();

	public function open()
	{

	}

	public function close()
	{
		$planifier_mode = $this->getNodeAttribute('mode', 'week');
		$events_src = $this->getNodeAttribute('events_src', '');
		if(!empty($events_src)) {
			$this->_displayPlanifierEvents($events_src);
			echo '<div style="float:right;width: 80%;">';
		}
		?>
		<table cellspacing="0" width="100%">
			<tr class="odd_tr">
				<th style="height: 20px; width: 200px;"></th>
				<th>&nbsp;</th>
				<th style="width: 200px; text-align: right;">
					<?php $this->_renderPlanifierNavigator() ?>
				</th>
			</tr>

		</table>
		<?php
		switch($planifier_mode)
		{
			case('week'):
				$this->_displayPlanifierView('agendaWeek');
				break;
			case('day'):
				$this->_displayPlanifierView('agendaDay');
				break;
		}
		if($events_src !== NULL)
		{
			echo '</div>';
		}
		echo '<div style="clear:both"></div>';

		return TRUE;
	}

	private function _renderPlanifierNavigator()
	{
		return TRUE;
	}

	//.........................................................................
	private function _displayPlanifierView($viewMode)
	{
		$minTime		= $this->getNodeAttribute('from', 8);
		$maxTime		= $this->getNodeAttribute('to', 22);
		$slotLength		= $this->getNodeAttribute('slot_length', 30);
		$create_object	= $this->getNodeAttribute('create', '');
		$layerMethod	= $this->getNodeAttribute('layer', '');
		$lockPastEvent	= true;

		$display_week  = (isset($_GET['dw'])) ? $_GET['dw'] : date('W');
		$editable = (isset($_GET['mode']) && $_GET['mode']=='edition') ? true : false;

		//---On recupère tous les jours de la semaine
		$days = getDaysInWeek ($display_week, date('Y'));

		/* TODO: récupérer l'objet différement. */
		$raw = explode('.', $_GET['action']);
		$current_object = $raw[0];
		?>
		<div id='<?= $this->id ?>'></div>
		
  		<script type='text/javascript'>
			//---------------------------------------------------------------------
			$(document).ready(function() {
					var date = new Date();

					var d = <?= date('j', $days[0]) ?>;
					var m = <?= date('m', $days[0])-1 ?>;
					var y = <?= date('Y', $days[0])+(floor($display_week/52)) ?>;
					
					$('#<?= $this->id ?>').fullCalendar({
						header: {
							left: 'prev,next,today',
							center: 'title',
							right: '',
						},
						buttonText: {
							today: 'Aujourd\'hui',
						},
						year: <?= date('Y', $days[0]) ?>,
						month: <?= date('m', $days[0])-1 ?>,
						date: <?= date('j', $days[0]) ?>,
						height: '5000',
						theme: true,
						allDaySlot: false,
						allDayDefault: false,
						firstDay: 1,
						weekends: false,
						disableResizing: false,
						editable: <?= ($editable)?'true':'false' ?>,
						selectable: true,
						selectHelper: <?= !empty($create_object) ? 'true' : 'false' ?>,
						monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
						dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
						dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
						axisFormat: 'HH:mm',
						lazyFetching: true,
						defaultEventMinutes: <?= $slotLength ?>,
						timeFormat: 'H:mm{ - H:mm}',
						columnFormat: 'ddd dd MMMM yyyy',
						titleFormat: 'ddd dd MMMM yyyy { - ddd dd MMMM yyyy}',
						defaultView: '<?= $viewMode ?>',
						minTime: <?= $minTime ?>,
						maxTime: <?= $maxTime ?>,
						slotMinutes: <?= $slotLength ?>,
						params: {},
						viewDisplay: function(view) {
							<?php
							  if(!empty($layerMethod))
							  {
							  	$url = './index.php?action='.$layerMethod.'&token='.$_SESSION['_TOKEN'];
								?>
								var params = {<?= isset($_GET['primary_key']) ? '\'crypt/primary_key\': \''. $_GET['crypt/primary_key'] . '\', ': '' ?>start: view.start.getTime()/1000, end: view.end.getTime()/1000, slot_length: <?= $slotLength ?>};
								if(typeof view.layerParams != 'undefined')
								{
									for(i in view.layerParams)
									{
										params[i] = view.layerParams[i];
									}
								}
								$.post('<?= $url ?>',
										params,
										function(timearea) {
											$('#<?= $this->id ?>').find('.CellHack').each(function() {
												var classes = $(this).attr('class').split(/\s+/);
												for(var i in classes)
												{
													className = classes[i];
													if(className.substr(0, 8) != 'fc-cell_' && className != 'CellHack')
													{
														$(this).removeClass(className);
													}
												}
											});
											for(var zone in timearea)
											{
												var h_s = timearea[zone].start.hour;
												var m_s = timearea[zone].start.minute;
												var d_s = timearea[zone].start.day;

												var h_e = timearea[zone].end.hour;
												var m_e = timearea[zone].end.minute;
												var d_e = timearea[zone].end.day;
												var className = timearea[zone].className;

												var sl = <?= $slotLength ?>;
												var h = h_s, m = m_s, d = d_s;

												var grid = $('#<?= $this->id ?>');
												//alert('Change Zone !');
												m = Math.ceil(m / sl) * sl;
												h = h + Math.floor(sl / 60);
												m = m % 60;
												while((d < d_e) || (d == d_e && h < h_e) || (d == d_e && h == h_e && m < m_e))
												{
													//alert('d:'+d+', h:'+h+', m:'+m);
													if(typeof className == 'object')
													{
														for(i in className)
														{
															grid.find('.fc-cell_' + h + '_' + m + '_' + d).addClass(className[i]);
														}
													}
													else
													{
														grid.find('.fc-cell_' + h + '_' + m + '_' + d).addClass(className);
													}

													h = (h + Math.floor((m + sl) / 60));
													d = d + Math.floor(h / 24);
													h = h % 24;
													m = (m + sl) % 60;
												}
											}
										}, "json").error(function(data) { alert("Erreur de récupération du layer de la grille de planification."); });
							<?php } ?>
						},
						eventSources: [
						<?php
						$url = './index.php?action='.$current_object.'.planning&token='.$_SESSION['_TOKEN'] . (isset($_GET['crypt/primary_key'])?'&crypt/primary_key='.$_GET['crypt/primary_key'] : '');
						foreach($this->events AS $src)
						{
							?>
							{
								events: function(start, end, callback)
								{
									var data_param = $('#<?= $this->id?>').data('filters');
									if(typeof data_param == 'undefined')
									{
										data_param = {};
									}
									<?php
									if(!empty($src['method']))
									{
										?>
										data_param.method = "<?= $src['method'] ?>";
										<?php
									}
									if(!empty($src['object']))
									{
										?>
										data_param.object = "<?= $src['object'] ?>";
										<?php
									}
									?>
									data_param.start = Math.round(start.getTime() / 1000);
									data_param.end = Math.round(end.getTime() / 1000);
									$.ajax({
										type: 'POST',
										dataType: 'json',
										url: "<?= $url ?>",
										data: data_param,
										error: function() {
											alert('Erreur du serveur, impossible de récupérer les évènements.');
										},
										success: function(doc) {
											var events = [];
											$(doc).each(function() {
												events.push({
													id:			  $(this).attr('id'),
													title:		   $(this).attr('title'),
													objectid:		$(this).attr('objectid'),
													object:		  $(this).attr('object'),
													color:		   $(this).attr('color'),
													opacity:		 $(this).attr('opacity'),
													start:		   $(this).attr('start'),
													end:			 $(this).attr('end'),
													duration:		$(this).attr('duration'),
													editable:		$(this).attr('editable'),
													deletable:	   $(this).attr('deletable'),
													disableResizing: $(this).attr('disableResizing'),
													disableDragging: $(this).attr('disableDragging'),
												});
											});
											callback(events);
										}
									});
								},
							allDayDefault: false,
							error: function() {
								alert('Erreur du serveur, impossible de récupérer les évènements.');
							},
							color: "<?= $src['color'] ?>",
							editable: <?= !($src['editable'] == 1) ? 'false' : 'true' ?>,
							deletable: <?= !($src['deletable'] == 1) ? 'false' : 'true' ?>,
							disableResizing: <?= !($src['resizable'] == 1) ? 'true' : 'false' ?>,
							disableDragging: <?= !($src['draggable'] == 1) ? 'true' : 'false' ?>,
							},
							<?php
							}
							?>
						],
						eventRender: function(event, element, view) {
							if(event.id && typeof event.unavailable === 'undefined')
							{
			   					if(typeof event.disableResizing === 'undefined')
								{
									event.disableResizing = event.source.disableResizing;
								}
								if(typeof event.disableDragging === 'undefined')
								{
									event.disableDragging = event.source.disableDragging;
								}
								var elementid = 'tooltip-' + event.id;
								var divContent = '<div>';
								divContent += '<div style="width: 16px; float: right; text-align: right;"><a class="ui-dialog-titlebar-close ui-corner-all" onclick="$(\'#ui-tooltip-'+elementid+'\').hide();"><span class="ui-icon ui-icon-closethick">&nbsp;</span></a></div>';
								divContent += '<h3>Évènement</h3><br/>';
								divContent += 'Durée : ' + event.duration + ' min<br/>';
								divContent += '<br/><br/>';
								if(event.object)
								{
									divContent += '<input style="width: 100px;" type="submit" name="Consulter" value="Consulter" onclick="return window.open(\'./index.php?action=' + event.object + '.edit&view=form&inside_popup=1&token=<?= $_SESSION['_TOKEN']?>&crypt/primary_key=';
									divContent += event.objectid;
									divContent +='\', \'popup\', config=\'height=600, width=800, toolbar=no, scrollbars=yes\');"/>';
								}

								if((typeof event.deletable === 'undefined' && event.source.deletable ) || event.deletable)
								{
									divContent += '<input style="width: 100px;margin-left: 30px;" type="submit" name="Retirer" value="Retirer" onclick="removeEvent(\'<?= $this->id ?>\', \''+event.id+'\');"/>';
								}
								divContent += '</div>';

								element.qtip({
									id: elementid,
									content: divContent,
									position: {
										my: 'bottom center',
										at: 'top center',
									},
									show: 'click',
									hide: 'click',
								});
							}
						},
						droppable: true,
						dropAccept: '.movable-event',
						drop: function(current_date, is_allDay) {
									var originalEventObject = $(this).data('eventObject');

									var date_end = new Date(current_date.getTime() + (60 * 1000 * originalEventObject.duration));

									var newMap = $.extend({}, originalEventObject, {
										start: current_date,
										start_ts: current_date.getTime(),
										end: date_end,
										end_ts: date_end.getTime(),
										allDay: is_allDay
									});

									var copiedEventObject = $.extend({}, newMap);
									copiedEventObject.element = $(this);

									if (isAvailableArea($('#<?= $this->id ?>'), newMap.start, newMap.end, <?= $slotLength ?>) && !isOverlapping($('#<?= $this->id ?>').fullCalendar('clientEvents'), copiedEventObject))
									{
										$.post(
											'<?php echo './index.php?action='.$current_object.'.createByDrop&token='.$_SESSION['_TOKEN']. (isset($_GET['crypt/primary_key']) ? '&crypt/primary_key=' . $_GET['crypt/primary_key'] : ''); ?>',
											newMap,
											function(data) {
												if(data.id)
												{
													copiedEventObject.objectid = data.id;
													$('#<?= $this->id ?>').fullCalendar('renderEvent', copiedEventObject, true);
													copiedEventObject.element.hide();
												}
												else
												{
													if(data.error)
													{
														alert(data.error);
													}
													else
													{
														alert('Invalid server response : '+data);
													}
												}
											},
											"json"
										);
									}
							},
						eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc) {
							var array = $('#<?= $this->id ?>').fullCalendar('clientEvents');

							if (!isAvailableArea($('#<?= $this->id ?>'), event.start, event.end, <?= $slotLength ?>) || isOverlapping(array, event))
							{
								revertFunc();
							}
							else
							{
								var postdata = {object: event.object,
												id: event.objectid,
												daydelta: dayDelta,
												minutedelta: minuteDelta,
												allday: allDay}
								$.post('./index.php?action='+event.object+'.moveEvent&token=<?= $_SESSION['_TOKEN'] ?>',
										postdata,
										function(data) {
											if(data.success)
											{

											}
											else
											{
												if(data.error)
												{
													alert(data.error);
												}
												else
												{
													alert('Invalid server response : '+data);
												}
												revertFunc();
											}
										}, "json").error(function() { alert('Bad response from server !'); revertFunc(); });
							}
						},
						eventResize: function(event, dayDelta, minuteDelta, revertFunc)
						{
							var postdata = {object: event.object,
									id: event.objectid,
									daydelta: dayDelta,
									minutedelta: minuteDelta,
									}
							$.post('./index.php?action='+event.object+'.resizeEvent&token=<?= $_SESSION['_TOKEN'] ?>',
								   postdata,
								   function(data) {
										if(data.success)
										{
											event.duration = (event.end - event.start)/60000;
										}
										else
										{
											if(data.error)
											{
												alert(data.error);
											}
											else
											{
												alert('Invalid server response : '+data);
											}
											revertFunc();
										}
									}, "json").error(function() { alert('Bad response from server !'); revertFunc(); });
						},
						select: function(start, end, allDay, jsEvent, view){
							<?php
								if(!empty($create_object)) {
							?>
									var url = './index.php?action=<?= $create_object ?>.edit&view=create&token=<?= $_SESSION['_TOKEN'] ?>&from='+(start.getTime()/1000)+'&to='+(end.getTime()/1000);
									if(isAvailableArea($('#<?= $this->id ?>'), start, end, <?= $slotLength ?>))
									{
										window.open(url,'popup',config='height=400, width=600, toolbar=no, scrollbars=yes');
									}
									else
									{
										alert('Vous ne pouvez pas créer un évènement hors zone.');
									}
							<?php
							/*
							?>
							var d = new Date();
							var duration = (end - start)/60000;
							var newEventObject = {start: start, end: end, duration: duration, title: 'Evenement', color: 'green', id: 'local_'+d.getTime(), editable: true, disableResizing: false};
							$('#<?= $id ?>').fullCalendar('renderEvent', newEventObject, true);
							<?php //*/
	 							}
	 						?>
							return true;
						},
					});
					// Integration bouton export_ical
					$('<span class="fc-button ui-state-default ui-corner-left ui-corner-right">'+
							'<span class="fc-button-inner">'+
							'<span class="fc-button-content">'+
							'Exporter (iCal)'+
							'<span class="fc-button-effect"><span></span></span></span></span>')
							.appendTo('td.fc-header-right')
							.button()
							.addClass('exporterICalButton')
							.click(function(){
								var iCalCalendarStart = parseInt($('#<?=$this->id?>').fullCalendar('getView').visStart.getTime()/1000);
								var iCalCalendarStop = parseInt($('#<?=$this->id?>').fullCalendar('getView').visEnd.getTime()/1000);
								var iCalCalendarFilters_srcData = $('#<?=$this->id?>').data('filters');
								if(typeof iCalCalendarFilters_srcData == 'undefined')
								{
									iCalCalendarFilters_srcData = {start:iCalCalendarStart,end:iCalCalendarStop};
								}
								var iCalCalendarFilters_src = JSON.stringify(iCalCalendarFilters_srcData, null, 2);
								var iCalCalendarEvents_src = '<?=base64_encode(serialize($this->events));?>';
								var iCalCalendarPrimaryKey = '';
								var iCalCalendarCurrentObject = '<?=$current_object;?>';
								$(location).attr('href','index.php?action=<?=$current_object?>.export_ical'+
														'&token=<?=$_SESSION['_TOKEN']?>'+
														'&iCalCalendarEvents_src='+iCalCalendarEvents_src+
														'&iCalCalendarFilters_src='+iCalCalendarFilters_src+
														'&iCalCalendarPrimaryKey='+iCalCalendarPrimaryKey+
														'&iCalCalendarCurrentObject='+iCalCalendarCurrentObject);
							});
					$("#<?= $this->id ?>").Updatable({callback: function(obj, attribute, new_value)
						{
							var p = $("#<?= $this->id ?>").data('filters');
							if(typeof p == 'undefined')
							{
								p = {};
							}
							p[attribute] = new_value;
							$("#<?= $this->id ?>").data('filters', p);
							$("#<?= $this->id ?>").fullCalendar('refetchEvents');
						}
					});
					<?php
						if($this->getParent() != NULL && $this->getParent()->name == 'page')
						{
							$page_id = $this->getParent()->id;
							?>
							$('#<?= $page_id ?>').onShow(function() {
								$("#<?= $this->id ?>").fullCalendar('render');
							});
							<?php
						}
					?>
					});
				//---------------------------------------------------------------------
				</script>
				<?php
		return TRUE;
	}
	//.........................................................................
	private function _displayPlanifierEvents($events)
	{
		$id = 'planifier_events_' . $this->id;
		?>
		<style>
			#<?= $id ?> {
				float: left;
				width: 15%;
				padding: 5px 10px;
				border: 1px solid #ccc;
				border-radius: 8px;
				background: #eee;
				text-align: left;
			}
			#<?= $id ?> h4 {
				font-size: 16px;
				margin-top: 0;
				padding-top: 1em;
			}
			.<?= $id ?> { /* try to mimick the look of a real event */
				min-height: 35px;
				width: 100%;
			}
			#<?= $id ?> p {
				margin: 1.5em 0;
				font-size: 11px;
				color: #666;
			}
			#<?= $id ?> p input {
				margin: 0;
				vertical-align: middle;
			}
		</style>
		<div id="<?= $id ?>" class="followscroll">
			<h4>Éléments à planifier</h4>
		<?php

		if(isset($this->_data_list[$events]))
		{
			$toplan = $this->_data_list[$events];
			foreach($toplan AS $key => $value) {
				$style = 'style="';
				$color = '';
				if (isset($value['color']))
				{
					$style .=	'background-color: ' . $value['color'] . ';'.
								'border-color: ' . $value['color'] . ';';
					$color  =	'color="' . $value['color'] . '"';
				}
				if (isset($value['opacity']))
				{
					$style .= 'opacity='.$value['opacity'].';';
				}
				$style .= '"';

				$resizable	= isset($value['resizable']) && $value['resizable'] ? 'resizable="1"' : 'resizable="0"';
				$deletable	= isset($value['deletable']) && !($value['deletable']) ? 'deletable="0"' : 'deletable="1"';

				$heure_str = floor($value['duration'] / 60);
				$minute_str = floor($value['duration'] % 60);
				if($heure_str == '0')
				{
					$duree_str =  $value['duration'] . ' min';
				}
				else
				{
					$duree_str =  $heure_str . 'h';
					if($minute_str != '0')
					{
						$duree_str .= $minute_str;
					}
				}
			  ?>
				<div id="<?= $value['id'] ?>" object="<?= $value['object'] ?>" <?= $style . ' ' . $color . ' ' . $resizable . ' ' . $deletable ?> duration="<?= $value['duration'] ?>" class="<?= $id ?> movable-event fc-event fc-event-skin fc-event-vert fc-corner-top fc-corner-bottom">
					<div class="fc-event-head fc-event-skin" <?= $style ?>>
						<div class="fc-event-time">Durée : <?= $duree_str ?></div>
					</div>
					<div class="fc-event-content">
						<div class="fc-event-title"><?= $value['title']?></div>
					</div>
					<div class="fc-event-bg"></div>
				</div>
				<br/>
			  <?php
			}
		}
		?>
		</div>

		<script type='text/javascript'>
			$(function() {
				var $sidebar   = $("#<?= $this->id ?>").parent(),
					$window	= $(window),
					offset	 = $sidebar.offset(),
					topPadding = -15;

				$window.scroll(function() {
					if ($window.scrollTop() > offset.top) {
						$sidebar.stop().animate({
							marginTop: $window.scrollTop() - offset.top + topPadding
						});
					} else {
						$sidebar.stop().animate({
							marginTop: 0
						});
					}
				});
			});

			/* initialize the external events
			 -----------------------------------------------------------------*/
			$('#<?= $id ?> div.<?= $id ?>').each(function() {
				var eventObject = {
					id: $(this).attr('id'),
					object: $(this).attr('object'),
					title: $.trim($(this).find('div.fc-event-title').text()),
					duration: $(this).attr('duration'),
					color: $(this).attr('color'),
					opacity: $(this).attr('opacity'),
					editable: true,
					disableResizing: $(this).attr('resizable') == '0',
					deletable: $(this).attr('deletable') == '1',
				};
				$(this).data('eventObject', eventObject);
				$(this).draggable({
					zIndex: 999,
					revert: true,
					revertDuration: 0
				});
			});
		</script>

		<?php
		return TRUE;
	}
}
