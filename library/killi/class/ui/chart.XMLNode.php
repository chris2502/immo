<?php

/**
 *  @class ChartXMLNode
 *  @Revision $Revision: 3570 $
 *
 */

class ChartXMLNode extends XMLNode
{
	public $xaxis = 'xaxis';
	public $yaxis = 'yaxis';

	public $axis2 = FALSE;

	public function open()
	{
		switch($this->getParent()->name)
		{
			case 'jqplot':
				$chart_datas = array();

				$params = $this->getParent()->params;
				$lines = $this->getParent()->lines;
				$count = $this->getParent()->count;

				$this->getPlotDatas($chart_datas);
				$this->setPlotDatas($chart_datas, $params, $lines, $count);

				$this->getParent()->params = $params;
				$this->getParent()->lines = $lines;
				$this->getParent()->count = $count;
			break;

			default:
				throw new Exception('The element \''.$this->name.'\' is not a child of \'' . $this->getParent()->name . '\'');
			break;
		}

		return TRUE;
	}

	public function getPlotDatas(&$chart_datas)
	{
		$object		 = $this->getNodeAttribute('object', '');
		$data_src	 = $this->getNodeAttribute('data_src', '');
		$this->axis2 = $this->getNodeAttribute('axis2', '');

		if (empty($object) && empty($data_src))
		{
			throw new Exception('Please define object or datasource in Plotchart node.');
		}

		if(empty($method) && empty($data_src))
		{
			throw new Exception('Please define either method or datasource in Plotchart node.');
		}

		if(!isset($this->_data_list[$data_src]))
		{
			throw new Exception($data_src . ' does not exist in _data_list.');
		}

		if ($this->getParent()->debug['data'])
		{
			echo '<div class="title">Debug : Données</div>';
			echo display_array($this->_data_list[$data_src]);
		}

		$chart_datas = $this->_data_list[$data_src];

		return TRUE;
	}

	public function setPlotDatas($chart_datas, &$params, &$lines, &$count)
	{
		if ($this->axis2)
		{
			$this->xaxis = 'x2axis';
			$this->yaxis = 'y2axis';
		}

		foreach ($chart_datas as $label => $line_datas) {
			if (empty($line_datas))
			{
				return TRUE;
			}
			$this->setLineDatas($label, $line_datas, $params, $lines, $count);
			$this->setNodeParams($label, $params, $lines, $count);

		}

		return TRUE;
	}

	public function setLineDatas($label, $line_datas, &$params, &$lines, &$count)
	{

		if ($count['color'] >= count($this->getParent()->color_list))
		{
			$count['color'] = 0;
		}

		return TRUE;
	}

	public function setNodeParams(&$label, &$params, &$lines, &$count)
	{

		$color = $this->getNodeAttribute('color', '');

		if (!empty($color))
		{
			$params['series'][$count['serie']]['color'] = $color;
		}

		// counts
		$count['serie']++;
		$count['point'] = 0;

		return TRUE;
	}
}