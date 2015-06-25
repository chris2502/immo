<?php

class CalendarXMLNode extends XMLNode
{
	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		if($view != 'calendar')
		{
			return false;
		}
		parent::__construct($structure, $parent, $view);
	}

	public function render($data_list, $view)
	{
		if($view != 'calendar')
		{
			return false;
		}
		parent::render($data_list, $view);
	}
	
	public function open()
	{
		?>
		<style type="text/css">
		.calendar_selection_div
		{
			width:200px;
			height:100px;
			border:solid 2px #c0c0c0;
			background-color:#e1e1e1;
			font-size:11px;
			font-family:verdana;
			color:#000;
			padding:2px;
			float: right;
			position: absolute;
		}

		.fc-view {
			background-color: #ddffdd;
			width: 100%;
			overflow: hidden;
		}
		.fc .fc-agenda-body td div {
			height: 60px;
		}

		.fc-event-editable span.fc-event-time {
			background-color: #9f2f2f;
			font-size: 11px;
			font-weight: bold;
		}

		</style>

		<table cellspacing="0" class="navigator" style="width: 100%;">
			<tr>
				<th style="height: 20px; width: 200px; background-color: #E1E1E1;">
					Affichage
					<select name="calendar_view" style="width: 100px;">
						<option value="agendaWeek">Agenda</option>
						<option value="basicWeek">Basique</option>
					</select>
				</th>
				<th style="background-color: #E1E1E1;">&nbsp;</th>
				<th style="width: 200px; text-align: right; background-color: #E1E1E1;">
					<?= $this->_renderCalendarNavigator() ?>
				</th>
			</tr>

		</table>
		<?php
		$calendar_view = isset($_GET['calendar_view']) ? $_GET['calendar_view'] : $this->getNodeAttribute('view');
		switch($calendar_view)
		{
			case('week'):
				$this->_displayWeekCalendar();
				break;
		}
	}
	//.........................................................................
	private function _displayWeekCalendar()
	{
		$display_week  = (isset($_GET['dw'])) ? $_GET['dw'] : date('W') ;

		if(isset($_GET['primary_key']))
		{
			$objs = array();
			$hORM = ORM::getORMInstance($this->getNodeAttribute('create_object'));
			$hORM->read(array($_GET['primary_key']), $objs);
			$obj = $objs[$_GET['primary_key']];
			$rdvdate = $obj['date']['value'];
			$days = getDaysInWeek ($display_week, date('Y', is_numeric($rdvdate) ? $rdvdate : strtotime($rdvdate)));
		}
		else
		{
			//---On recup tous les jours de la semaine
			$days = getDaysInWeek ($display_week, date('Y'));
		}

		?>

		<div id='calendar'></div>

		<script>

		var calendar_create_field_from = "test_from";
		var calendar_create_field_to = "test_to";

		//---------------------------------------------------------------------
		function createEvent(start,end)
		{
			var url = './index.php?action=<?= $this->getNodeAttribute('create_object') ?>.edit&view=create&token=<?= $_SESSION['_TOKEN'] ?>&'+calendar_create_field_from+'='+((start.getTime()/1000)-(60*start.getTimezoneOffset()))+'&'+calendar_create_field_to+'='+((end.getTime()/1000)-(60*start.getTimezoneOffset()));
			window.open(url,'popup',config='height=400, width=600, toolbar=no, scrollbars=yes');

			return true;
		}
		//---------------------------------------------------------------------
		function saveEvent(event)
		{
			var date = new Date();

			raw = event['id'].split('/');

			object		= raw[0];
			crypt_id	= raw[1];
			field_from	= object+'/'+raw[2];
			field_to	= object+'/'+raw[3];
			from		= (event['start'].getTime()/1000)-(60*date.getTimezoneOffset());
			to			= (event['end'].getTime()/1000)-(60*date.getTimezoneOffset());
			pk_field	= escape('crypt/primary_key');

			var url  = './index.php?action='+object+'.write&token=<?= $_SESSION['_TOKEN'] ?>';

			var post_data = new Array();
			eval('post_data={"'+field_from+'": '+from+',"'+field_to+'": '+to+',"'+pk_field+'": "'+crypt_id+'"}');

			$.post(url,post_data,function(data){

			},'html');

			return true;
		}
		//---------------------------------------------------------------------
		$(document).ready(function() {

			<?php
			$editable = 'false';
			if (isset($_GET['mode']) && $_GET['mode']=='edition')
				$editable = 'true';
			?>

			var date = new Date();

			var d = <?= date('j',$days[0]) ?>;
			var m = <?= date('m',$days[0])-1 ?>;
			var y = <?= date('Y',$days[0])+(floor($display_week/52)) ?>;

			main_calendar = $('#calendar').fullCalendar({
				header: {
					left: '',
					center: 'title',
					right: ''
				},
				year: <?= date('Y',$days[0]) ?>,
				month: <?= date('m',$days[0])-1 ?>,
				date: <?= date('j',$days[0]) ?>,
				theme: true,
				allDaySlot: false,
				firstDay: 1,
				weekends: false,
				disableResizing: true,
				editable: <?= $editable ?>,
				selectable: true,
				monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
				dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
				dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
				axisFormat: 'HH:mm',
				lazyFetching: true,
				defaultEventMinutes: 30,
				timeFormat: 'H:mm{ - H:mm}',
				columnFormat: 'ddd dd MMMM yyyy',
				titleFormat: 'ddd dd MMMM yyyy { - ddd dd MMMM yyyy}',
				defaultView: 'agendaWeek',
				minTime: <?= $this->getNodeAttribute('from') ?>,
				maxTime: <?= $this->getNodeAttribute('to') ?>,
				slotMinutes: <?= $this->getNodeAttribute('slot_length') ?>,

				eventDrop: function(event,ddelat,mdelta,allDay,revertFunc,jsEvent,ui,view)
				{
					if(ddelat >= 0 && confirm('Etes-vous sur de changer ?'))
					{
						saveEvent(event);
					}
					else
					{
						revertFunc();
					}
				},

				eventResize: function(event)
				{
					//saveEvent(event);
				},

				eventClick: function(event)
				{
					if (event.url)
					{
						window.open(event.url,'popup',config='height=400, width=600, toolbar=no, scrollbars=yes');
						return false;
					}
				},

				select: function(start,end)
				{
					createEvent(start,end);
				},
			});
		});

		</script>

		<?php

		return TRUE;
	}
	//.........................................................................
	private function _renderCalendarNavigator()
	{
		$calendar_view = (isset($_GET['calendar_view'])) ? $_GET['calendar_view'] : $this->getNodeAttribute('view');
		$display_week  = (isset($_GET['dw'])) ? $_GET['dw'] : date('W') ;

		//---Base URL
		$base_url='./index.php?action='.$_GET['action'];
		foreach($_GET as $key=>$value)
		{
			if ((!isset($_GET['crypt/'.$key])) && ($key!='action') && ($key!='dw') && ($key!='view') && ($key!='mode'))
			{
				$base_url.='&'.$key.'='.$value;
			}
		}

		switch($calendar_view)
		{
			case('week'):
				?>
				<a href="<?= $base_url.'&dw='.($display_week-1) ?>">< précédent</a>&nbsp;&nbsp;&nbsp;[S-<?= $display_week ?>]&nbsp;&nbsp;&nbsp;<a href="<?= $base_url.'&dw='.($display_week+1) ?>">suivant ></a>
				<?php
				break;
		}

		return TRUE;
	}
}
