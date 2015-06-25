<?php

/**
 *  @class PiechartXMLNode
 *  @Revision $Revision: 3033 $
 *
 */

define ('JQPLOT_PIE_DEFAULT_MARGIN', 10);
define ('JQPLOT_DONUT_DEFAULT_MARGIN', 4);

class PiechartXMLNode extends ChartXMLNode
{
	public $default_margin = JQPLOT_PIE_DEFAULT_MARGIN;
	public $type = 'pie';
	public $count_point = 0;

	public function setPlotDatas($chart_datas, &$params, &$lines, &$count)
	{
		parent::setPlotDatas($chart_datas, $params, $lines, $count);

		$count['serie']++;
		$count['color'] = 0;

		return TRUE;
	}

	public function setLineDatas($label, $line_datas, &$params, &$lines, &$count)
	{
		$debug = $this->getParent()->debug;

		if ($count['serie'] > 0)
		{
			$this->default_margin = JQPLOT_DONUT_DEFAULT_MARGIN;
			$this->type = 'donut';
			JQPlotXMLNode::$jqPlotPlugins['pie']['import'] = FALSE;
		}

		JQPlotXMLNode::$jqPlotPlugins[$this->type]['import'] = TRUE;
		$params['seriesDefaults']['renderer'] = '$.jqplot.'.ucfirst($this->type).'Renderer';
		$params['seriesDefaults']['rendererOptions']['startAngle'] = -90;


		if (is_array($line_datas))
		{
			if (!array_key_exists('value', $line_datas))
			{
				throw new Exception('There is no array key "value" in the serie "'.$label.'"', 1);
			}

			// set value
			$lines[$count['serie']][$this->count_point] = array($label, $line_datas['value']);
			/* DEBUG */ if($debug['count']){ echo 'PIE:'.$label.' // Serie : '.$count['serie'].' / Point : '.$this->count_point.' / Color : '.$count['color'].'<br>'; }

			// set color
			if (isset($line_datas['color']))
			{
				$params['seriesDefaults']['rendererOptions']['seriesColors'][$this->count_point] = $line_datas['color'];
			}
			else
			{
				$params['seriesDefaults']['rendererOptions']['seriesColors'][$this->count_point] = $this->getParent()->color_list[$count['color']];
				$count['color']++;
			}

			// set tooltip
			if (isset($line_datas['tooltip']))
			{
				array_push($lines[$count['serie']][$this->count_point], $line_datas['tooltip']);
			}
			else
			{
				array_push($lines[$count['serie']][$this->count_point], '');
			}
		}
		else
		{
			$lines[$count['serie']][$this->count_point] = array($label, $line_datas);
			/* DEBUG */ if($debug['count']){ echo 'PIE:'.$label.' // Serie : '.$count['serie'].' / Point : '.$this->count_point.' / Color : '.$count['color'].'<br>'; }

			// set color
			$params['seriesDefaults']['rendererOptions']['seriesColors'][$this->count_point] = $this->getParent()->color_list[$count['color']];
			$count['color']++;
			if ($count['color'] == count($this->getParent()->color_list)-1)
			{
				$count['color'] = 0;
			}

			// set tooltip
			array_push($lines[$count['serie']][$this->count_point], '');
		}

		// legend
		$this->getParent()->legend['series'][$this->count_point]['value'] = $label;
		if (isset($line_datas['color']))
		{
			$this->getParent()->legend['series'][$this->count_point]['color'] = $line_datas['color'];
		}
		if (isset($line_datas['action']))
		{
			$this->getParent()->legend['series'][$this->count_point]['action'] = $line_datas['action'];
		}
		if (isset($line_datas['infos']))
		{
			$this->getParent()->legend['series'][$this->count_point]['infos'] = $line_datas['infos'];
		}

		// is visible
		if (isset($line_datas['visible']))
		{
			$this->getParent()->line_visible[$this->count_point] = $line_datas['visible'];
		}
		else
		{
			$this->getParent()->line_visible[$this->count_point] = TRUE;
		}

		parent::setLineDatas($label, $line_datas, $params, $lines, $count);

		$count['serie']--;
		$this->count_point++;

		return TRUE;
	}

	public function setNodeParams(&$label, &$params, &$lines, &$count)
	{
		$margin =		$this->getNodeAttribute('margin', $this->default_margin);
		$startangle =	$this->getNodeAttribute('startangle', '');
		$datalabels =	$this->getNodeAttribute('datalabels', '');

		$params['seriesDefaults']['rendererOptions']['showDataLabels'] = TRUE;
		$params['seriesDefaults']['rendererOptions']['sliceMargin'] = $margin;

		if (!empty($startangle))
		{
			$params['seriesDefaults']['rendererOptions']['startAngle'] = $startangle;
		}

		$datalabels_values = array('label', 'value', 'percent');
		if (!empty($datalabels) && in_array($datalabels, $datalabels_values))
		{
			$params['seriesDefaults']['rendererOptions']['dataLabels'] = $datalabels;
		}

		parent::setNodeParams($label, $params, $lines, $count);

		return TRUE;
	}

}