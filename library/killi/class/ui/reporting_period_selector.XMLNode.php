<?php

/**
 *  @class Reporting_period_selectorXMLNode
 *  @Revision $Revision: 3847 $
 *
 */

class Reporting_period_selectorXMLNode extends XMLNode
{
	public function open()
	{
		?>
		Période :
		<select name="week_period" style="width: 250px;" onchange="document.reporting_form.submit();">
		<?php
		$semaine_annee = date('W');

		for ($week=$semaine_annee; $week>0;$week--)
		{
			$selected='';
			if (isset($_POST['week_period']) && ($_POST['week_period']==$week))
			$selected='selected';

			?><option <?= $selected ?> value="<?= $week ?>">Semaine <?= $week ?> (<?= date('Y') ?>)</option><?php
		}

		?>
		</select>
		&nbsp;&nbsp;&nbsp;&nbsp;
		Delta par raport à :

		<select name="delta_week_period" style="width: 250px;" onchange="document.reporting_form.submit();">
		<?php
		$semaine_annee = date('W') - 1;

		if (isset($_POST['week_period']))
			$semaine_annee = $_POST['week_period']-1;

		for ($week=$semaine_annee; $week>0;$week--)
		{
			$selected='';
			if (isset($_POST['delta_week_period']) && ($_POST['delta_week_period']==$week))
				$selected='selected';

			?><option <?= $selected ?> value="<?= $week ?>">Semaine <?= $week ?> (<?= date('Y') ?>)</option><?php
		}

		?></select><?php
	}
}
