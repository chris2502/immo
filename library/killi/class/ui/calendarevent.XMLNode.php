<?php

class CalendarEventXMLNode extends XMLNode
{
	public function open()
	{
		$display_week  = (isset($_GET['dw'])) ? $_GET['dw'] : date('W') ;

		//---On recup tous les jours de la semaine
		$days = getDaysInWeek ($display_week, date('Y'));

		Security::crypt($days[0],$crypt_from);
		Security::crypt($days[6]+(3600*24),$crypt_to);

		//---On recup l'objet
		$object	= $this->getNodeAttribute('object');
		$date_from = $this->getNodeAttribute('date_from');
		$date_to   = $this->getNodeAttribute('date_to');
		$label	 = $this->getNodeAttribute('label');

		Security::crypt($date_from,$crypt_field_from);
		Security::crypt($date_to,$crypt_field_to);
		Security::crypt($label,$crypt_field_label);

		?>
		<script>

		calendar_create_field_from  = '<?= $date_from ?>';
		calendar_create_field_to	= '<?= $date_to ?>';

		$(document).ready(function() {
			main_calendar.fullCalendar('addEventSource','./index.php?action=<?= $object ?>.getCalendarEvents&token=<?= $_SESSION['_TOKEN'] ?>&crypt/from=<?= $crypt_from ?>&crypt/to=<?= $crypt_to ?>&crypt/field_from=<?= $crypt_field_from ?>&crypt/field_to=<?= $crypt_field_to ?>&crypt/label=<?= $crypt_field_label ?><?= (isset($_GET['crypt/primary_key']) ? '&crypt/id=' . $_GET['crypt/primary_key'] : ''); ?><?= (isset($_GET['mode']) && $_GET['mode'] == 'edition' ? '&edition=1' : ''); ?>');
		});
		</script>
		<?php

		return TRUE;
	}
}
