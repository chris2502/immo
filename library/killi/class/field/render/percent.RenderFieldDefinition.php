<?php

/**
 *  @class PercentRenderFieldDefinition
 *  @Revision $Revision: 4640 $
 *
 */

class PercentRenderFieldDefinition extends IntRenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		$percent = $value['value']*100;
		
		?>
		<div class="percent-field progress-container" style="min-width: 23px;position:relative;height:14px;margin: 1px;background-color:white;border:1px solid #C5DBEC;border-radius:3px;overflow:hidden;">
			<div class="progress-text" style="color:#333;text-align:center;position: absolute;top:0;bottom:0; left:0;right:0; z-index:1"><span class="percent"><?= round($percent); ?></span>%</div>
			<div class="progress-bar" style="position:absolute; top:0;bottom:0; left:0; width:<?= $percent ?>%;background-color:<?= self::getColorFromPercent(round($percent)) ?>"></div>
		</div>
		<?php
	}
	
	private static function getColorFromPercent($percent)
	{
		 $r1 = 100; $g1 = 255; $b1 = 0;
		 $r2 = 255; $g2 = 100; $b2 = 0;
	
		 $dr = ($r2 - $r1) / 50;
		 $dg = ($g2 - $g1) / 50;
		 $db = ($b2 - $b1) / 100;
	
		for($i = 0; $i < $percent && $i < 50; $i++)
		{
			//r2 = r2 - dr;
			$g2 = $g2 - $dg;
			$b2 = $b2 - $db;
		}
	
		for($i = 50; $i < $percent; $i++)
		{
			$r2 = $r2 - $dr;
			$b2 = $b2 - $db;
		}
	
		return 'rgb(' . round($r2) . ', ' . round($g2) . ', ' . round($b2) . ')';
	}
}
