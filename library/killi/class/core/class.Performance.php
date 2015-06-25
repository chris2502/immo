<?php

/**
 *  @class Common
 *  @Revision $Revision: 4311 $
 *
 */

class PerformanceExitDetector
{
	public function __destruct()
	{
		/* Détection d'arrêt inopiné */
		if(!Performance::stopped())
		{
			Performance::stop();
		}
		return TRUE;
	}
}

class Performance
{
	protected static $_hTimer = NULL;
	protected static $_memory = 0;
	protected static $_hInstance = NULL;

	public static function stopped()
	{
		return self::$_hInstance === NULL;
	}

	public static function start()
	{
		self::$_hTimer = new Timer();
		self::$_hTimer->start();
		self::$_memory = memory_get_usage();

		self::$_hInstance = new PerformanceExitDetector();
	}

	public static function stop()
	{
		self::$_hTimer->stop();

		/**
		 * Récupération des statistiques
		 */
		$exec_time	= self::$_hTimer->get();
		$mem_usage	= (memory_get_usage()-self::$_memory)/(1024*1024);
		$mem_peak	= memory_get_peak_usage()/(1024*1024);
		$sql_count = 0;

		global $hDB;
		if(isset($hDB) && $hDB instanceof DbLayer)
		{
			$sql_count = $hDB->_numberQuery++;
		}

		/**
		 * Définition des metrics.
		 */
		$metrics = array( 'exec_time' => $exec_time,
						  'mem_usage' => $mem_usage,
						  'mem_peak' => $mem_peak,
						  'sql_count' => $sql_count);

		/**
		 * Récupération du tableau des highscores !
		 */
		$bestQueries = array();
		Cache::get('HallOfShame', $bestQueries);

		if(is_string($bestQueries))
		{
			$bestQueries = (array)json_decode($bestQueries, TRUE);
		}

		/**
		 * Calcul des highscores !
		 */
		foreach($metrics AS $name => $value)
		{
			if(!isset($bestQueries[$name]))
			{
				$bestQueries[$name] = array();
			}
			self::checkAndAdd($bestQueries[$name], $value);
		}

		Cache::set('HallOfShame', json_encode($bestQueries), 7*24*3600);

		self::$_hInstance = NULL;
		return TRUE;
	}

	protected static function checkAndAdd(&$array, $value)
	{
		$item = array(	'value' => $value,
						'request' => $_SERVER['REQUEST_URI']);

		if(isset($_SERVER['HTTP_REFERER']))
		{
			$item['referer'] = $_SERVER['HTTP_REFERER'];
		}

		$array[] = $item;

		usort($array, function($a, $b) { return $b['value'] > $a['value'];});
		$array = array_slice($array, 0, 20);
	}

	public static function printList()
	{
		$bestQueries = NULL;
		Cache::get('HallOfShame', $bestQueries);

		if($bestQueries === NULL)
		{
			return FALSE;
		}
		$bestQueries = (array)json_decode($bestQueries, TRUE);

		echo '<h2>Hall Of Shame</h2>';
		foreach($bestQueries AS $type => $queries)
		{
			echo '<h3>', $type, '</h3>';
			echo '<ul>';
			if(is_array($queries))
			foreach($queries AS $query)
			{
				echo '<li><strong>',$query['value'],'</strong> : ', $query['request'],'</li>';
			}
			echo '</ul>';
		}
	}
}
