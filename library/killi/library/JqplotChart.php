<?php

class JqplotChart
{
	private $_curves      = array();
	private $_curve2index = array();
	private $_data        = array();
	private $_options     = array();
	// X coordinates
	private $_tick2index  = array();
	private $_ticks       = array();
	// Y coordinates
	private $_ruler       = array();
	// Additional tooltip data
	private $_tooltipdata = array();

	// Set option within the option array, like a xpath query.
	// Example 1 : set_option('axes/xaxis/tickOptions/formatString', '%d foobar');
	// Example 2 : set_option('axes/xaxis/tickOptions/formatString', '%d foobar');
	public function set_option($path, $value)
	{
		$path_list = explode('/', $path);
		$ar = &$this->_options;
		foreach ($path_list as $dir)
		{
			if (!isset($ar[$dir]))
			{
				$ar[$dir] = array();
			}
			$ar = &$ar[$dir];
		}
		$ar = $value;
		return TRUE;
	}

	// Ajouter du texte au highlighter, avant l'affichage standard des valeurs.
	public function add_tooltipdata($curve_name, $tick, $string)
	{
		$this->_tooltipdata[$this->_curve2index[$curve_name]][$this->_tick2index[$tick]] = $string;
		return TRUE;
	}

	// Add x coordinate (tick)
	public function add_tick($value)
	{
		if (in_array($value, $this->_ticks))
		{
			return FALSE;
		}
		$this->_ticks[] = $value;
		$this->_tick2index[$value] = count($this->_ticks) - 1;
		return TRUE;
	}

	// Add y coordinate (measurement)
	public function add_measurement($value)
	{
		$this->_ruler[$value] = true;
		return TRUE;
	}

	// Set Y axis from $min to $max increment by $step
	public function bound_measurements($min, $max, $step = 1)
	{
		for ($idx = $min ; $idx <= $max ; $idx += $step)
		{
			$this->add_measurement($idx);
		}
		return TRUE;
	}

	// Once chart data is complete, call this method to automatically set bounds
	// to Y axis.
	public function auto_bound_measurements($step = 1)
	{
		$values = array();
		foreach ($this->_data as $curve => $xaxis)
		{
			foreach ($xaxis as $value)
			{
				$values[] = $value;
			}
		}
		if (!empty($values))
		{
			$this->bound_measurements(min($values) - $step, max($values) + $step, $step);
		}
		return TRUE;
	}

	// Set $value to coordinates. If value exists, erase.
	// If $value is not set, returns value at coordinates.
	public function value_at($curve_name, $tick, $value = FALSE)
	{
		if ($value !== FALSE)
		{
			$this->_data[$this->_curve2index[$curve_name]][$this->_tick2index[$tick]] = $value;
			return TRUE;
		}
		else
		{
			if (isset($this->_data[$this->_curve2index[$curve_name]][$this->_tick2index[$tick]]))
			{
				return $this->_data[$this->_curve2index[$curve_name]][$this->_tick2index[$tick]];
			}
			return FALSE;
		}
	}

	// Set or increment value at coordinates by $value.
	// Then, returns current value at coordinates.
	public function increment_value_at($curve_name, $tick, $value)
	{
		if (($cur_val = $this->value_at($curve_name, $tick)) !== FALSE)
		{
			$cur_val += $value;
			$this->value_at($curve_name, $tick, $cur_val);
		}
		else
		{
			$this->value_at($curve_name, $tick, $value);
		}
		return $this->value_at($curve_name, $tick);
	}

	// Convert all measurements to percentages of values from $calculate_from
	// (no turning back)
	public function make_percentages($calculate_from)
	{
		foreach ($this->_data as $curve => $xaxis)
		{
			foreach ($xaxis as $tick => $value)
			{
				$this->_data[$curve][$tick] = round(($value / $calculate_from) * 100);
			}
		}
		return TRUE;
	}

	// Show or hide the legend.
	// The placement can be modified afterwards with
	// set_option('legend/placement', 'new_position');
	public function set_legend($show)
	{
		if ($show)
		{
			$this->_options['legend'] = array(
				'show'      => true,
				'placement' => 'outsideGrid'
			);
		}
		else
		{
			$this->_options['legend'] = array('show' => false);
		}
		return TRUE;
	}

	// Add a curve, give it an index, then returns TRUE.
	// Therefore checks if the curve already exists.
	// In that case, returns FALSE.
	public function add_curve($curve_name)
	{
		if ($this->has_curve($curve_name))
		{
			return FALSE;
		}
		$this->_curves[] = $curve_name;
		$this->_curve2index[$curve_name] = count($this->_curves) - 1;
		return TRUE;
	}

	// Build options table to pass to jqplot builder.
	// You can either choose to set those options manually
	// by using the set_option method.
	public function build_options()
	{
		$this->_options['axes']['xaxis']['ticks'] = $this->get_ticks();
		$this->_options['axes']['yaxis']['ticks'] = $this->get_measurements();
		$series_list = array();
		foreach ($this->_curves as $curve_name)
		{
			$series_list[] = array('label' => $curve_name);
		}
		$this->_options['series'] = $series_list;
		return TRUE;
	}

	// Checks if a curve exists.
	public function has_curve($curve_name)
	{
		return isset($this->_curve2index[$curve_name]);
	}


	//--- Getters

	public function get_curve_index($curve_name)
	{
		return $this->_curve2index[$curve_name];
	}

	public function get_options()
	{
		return $this->_options;
	}

	public function get_data()
	{
		//--- Bug json_encode
		$newdata = array();
		foreach ($this->_ticks as $tick)
		{
			$tick_index = $this->_tick2index[$tick];
			foreach ($this->_data as $curve => $xaxis)
			{
				if (isset($xaxis[$tick_index]))
				{
					$newdata[$curve][$tick_index] = $xaxis[$tick_index];
				}
				else
				{
					$newdata[$curve][$tick_index] = 0;
				}
			}
		}
		return $newdata;
	}

	public function get_curves()
	{
		return $this->_curves;
	}

	public function get_ticks()
	{
		return $this->_ticks;
	}

	public function get_measurements()
	{
		return array_keys($this->_ruler);
	}

	public function get_tooltipdata()
	{
		return $this->_tooltipdata;
	}

}