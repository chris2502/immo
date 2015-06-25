<?php

/**
 *  @class BarchartXMLNode
 *  @Revision $Revision: 3033 $
 *
 */

class BarchartXMLNode extends ChartXMLNode
{
	public function setLineDatas($label, $line_datas, &$params, &$lines, &$count)
	{
		$debug = $this->getParent()->debug;

		// -- START > Bar chart ONLY
		JQPlotXMLNode::$jqPlotPlugins['bar']['import'] = TRUE;
		$params['series'][$count['serie']]['renderer'] = '$.jqplot.BarRenderer';
		// pointLabels
		JQPlotXMLNode::$jqPlotPlugins['pointLabels']['import'] = TRUE;
		$params['seriesDefaults']['pointLabels']['show'] = TRUE;
		// -- END > Bar chart ONLY

		if (!is_array($line_datas))
		{
			throw new Exception('Linechart datas "'.$label.'"" must must passed in array', 1);
		}
		if (isset($line_datas['value']))
		{
			foreach($line_datas['value'] AS $index => $value)
			{
				if(!is_array($value))
				{
					// set value
					$lines[$count['serie']][$count['point']] = array($count['point'], $value);
					/* DEBUG */ if($debug['count']){ echo 'BAR value...... :'.$label.' // Serie : '.$count['serie'].' / Point : '.$count['point'].' / Color : '.$count['color'].'<br>'; }
					array_push($lines[$count['serie']][$count['point']], '');
					$count['point']++;

					continue;
				}
				// set value
				//$lines[$count['serie']][$count['point']] = array($count['point'], $value['value']);
				$lines[$count['serie']][$count['point']] = array($index, $value['value']);
				/* DEBUG */ if($debug['count']){ echo 'BAR value in value.... :'.$label.' // Serie : '.$count['serie'].' / Point : '.$count['point'].' / Color : '.$count['color'].'<br>'; }

				// set tooltip
				if (isset($value['tooltip']))
				{
					array_push($lines[$count['serie']][$count['point']], $value['tooltip']);
				}
				else
				{
					array_push($lines[$count['serie']][$count['point']], '');
				}
				$count['point']++;
			}
		}
		else
		{
			foreach ($line_datas as $key => $value) {
				$lines[$count['serie']][$key] = array($key, $value);
				array_push($lines[$count['serie']][$key], '');
			}
			/* DEBUG */ if($debug['count']){ echo 'BAR no value. :'.$label.' // Serie : '.$count['serie'].' / Point : '.$count['point'].' / Color : '.$count['color'].'<br>'; }
			$count['point']++;
			$this->testNegativeValue($line_datas);
		}

		// legend
		$this->getParent()->legend['series'][$count['serie']]['value'] = $label;
		if (isset($line_datas['color']))
		{
			$params['series'][$count['serie']]['color'] = $line_datas['color'];
			$this->getParent()->legend['series'][$count['serie']]['color'] = $line_datas['color'];
		}
		else
		{
			$params['series'][$count['serie']]['color'] = $this->getParent()->color_list[$count['color']];
			$count['color']++;
		}		
		if (isset($line_datas['action']))
		{
			$this->getParent()->legend['series'][$count['serie']]['action'] = $line_datas['action'];
		}
		if (isset($line_datas['infos']))
		{
			$this->getParent()->legend['series'][$count['serie']]['infos'] = $line_datas['infos'];
		}

		// is visible
		if (isset($line_datas['visible']))
		{
			$this->getParent()->line_visible[$count['serie']] = $line_datas['visible'];
		}
		else
		{			
			$this->getParent()->line_visible[$count['serie']] = TRUE;
		}

		parent::setLineDatas($label, $line_datas, $params, $lines, $count);

		return TRUE;
	}

	public function setNodeParams(&$label, &$params, &$lines, &$count)
	{

		// BOTH LINECHART & BARCHART
		$formatx	= $this->getNodeAttribute('formatx', '');
		$formaty	= $this->getNodeAttribute('formaty', '');
		$date		= $this->getNodeAttribute('date', '');

		if ($this->axis2)
		{
			$params['series'][$count['serie']]['xaxis'] = $this->xaxis;
			$params['series'][$count['serie']]['yaxis'] = $this->yaxis;
		}

		if (!empty($formatx))
		{
			$params['axes'][$this->xaxis]['tickOptions']['formatString'] = $formatx;
		}
		if (!empty($formaty))
		{
			$params['axes'][$this->yaxis]['tickOptions']['formatString'] = $formaty;
		}

		if ($date)
		{
			JQPlotXMLNode::$jqPlotPlugins['dateAxis']['import'] = TRUE;
			$params['axes'][$this->xaxis]['renderer'] = '$.jqplot.DateAxisRenderer';
		}
		else if (is_string($label))
		{
			$params['series'][$count['serie']]['label'] = $label;
			JQPlotXMLNode::$jqPlotPlugins['categoryAxis']['import'] = TRUE;
			$params['axes'][$this->xaxis]['renderer'] = '$.jqplot.CategoryAxisRenderer';
		}

		parent::setNodeParams($label, $params, $lines, $count);

		return TRUE;
	}

	public function testNegativeValue($value)
	{
		if ($value < 0)
		{
			$this->getParent()->params['seriesDefaults']['rendererOptions']['fillToZero'] = TRUE;
		}
		return TRUE;
	}
}