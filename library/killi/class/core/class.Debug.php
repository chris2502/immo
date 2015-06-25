<?php

/**
 *  @class Debug
 *  @Revision $Revision: 4469 $
 *
 */

DEFINE('RED_BOLD' , '01;31');
DEFINE('LIME_BOLD', '01;32');
DEFINE('ORANGE_BOLD', '01;33');
DEFINE('BLUE_BOLD', '01;34');

function print_color($color, $str)
{
	if(is_string($str))
	{
		file_put_contents('php://stderr', "\033[" . $color . "m".$str."\033[0m");
	}
	else
	{
		file_put_contents('php://stderr', "\033[" . $color . "m".var_export($str)."\033[0m");
	}
}

if(file_exists(KILLI_DIR. '/dev/firebug/fb.php') && FIREPHP_ENABLE && !KILLI_SCRIPT)
{
	include_once(KILLI_DIR . '/dev/firebug/fb.php');
	FB::info('Firebug loaded !');
	
	global $firephp_options;
	if (isset($firephp_options))
	{
		if (is_array($firephp_options))
		{
			FB::setOptions($firephp_options);
			FB::info($firephp_options, 'Firebug options loaded !');
		}
		else{
			FB::warn($firephp_options, '$firephp_options is not an array');
		}
	}
}

class Debug
{
	public static $exceptionData = array();
	public static $messages = array();
	private static $level = 5;

	public static function setLevelListener($level)
	{
		switch($level)
		{
			case 'log':self::$level=1;break;
			case 'info':self::$level=2;break;
			case 'warn':self::$level=3;break;
			case 'error':self::$level=4;break;

			// pas de debug
			default:self::$level=5;break;
		}
	}

	public static function log($object, $label = NULL)
	{
		if(isset($_SERVER['SHELL']) && self::$level==1)
		{
			print_color(LIME_BOLD, $object.PHP_EOL);
		}
		else if(class_exists('FB'))
		{
			FB::log($object, $label);
		}
	}

	public static function moreInfo($object)
	{
		if(isset($_SERVER['SHELL']) && self::$level==1)
		{
			print_color(LIME_BOLD, $object.PHP_EOL);
		}
		else if(class_exists('FB'))
		{
			$debug=debug_backtrace();
			FB::info($object, $debug[0]['file'].':'.$debug[0]['line'].':'.$debug[1]['function'].'('.count($debug[1]['args']).' args ...)');
		}
	}

	public static function info($object, $label = NULL)
	{
		if(isset($_SERVER['SHELL']) && self::$level<=2)
		{
			print_color(BLUE_BOLD, $object.PHP_EOL);
		}
		else if(class_exists('FB'))
		{
			FB::info($object, $label);
		}
	}

	public static function warn($object, $label = NULL)
	{
		if(isset($_SERVER['SHELL']) && self::$level<=3)
		{
			print_color(ORANGE_BOLD, $object.PHP_EOL);
		}
		else if(class_exists('FB'))
		{
			FB::warn($object, $label);
		}
	}

	public static function error($object, $label = NULL)
	{
		if(isset($_SERVER['SHELL']) && self::$level<=4)
		{
			print_color(RED_BOLD, $object.PHP_EOL);
		}
		else if(class_exists('FB'))
		{
			FB::error($object, $label);
		}
	}

	public static function firephp_end()
	{
		global $start_memory;
		global $hDB;
		global $start_time;

		if(class_exists('FB'))
		{
			$mem_usage		= (memory_get_usage()-$start_memory)/(1024*1024);
			$mem_peak		= memory_get_peak_usage()/(1024*1024);
			Debug::log(round($mem_usage, 2), 'Utilisation mémoire');
			Debug::log(round($mem_peak, 2), 'Peak mémoire');
			$ellapsed_time	= microtime(true)-$start_time;
			Debug::log(round($ellapsed_time, 2), 'Temps d\'exécution (s)');
			$sql_time		= $hDB->_cumulateProcessTime*1000;
			Debug::log(round($sql_time, 2), 'Temps SQL (ms)');
			$sql_count		= $hDB->_numberQuery - 1;
			Debug::log($sql_count, 'Queries');
		}
	}

	public static function debug_end()
	{
		foreach(self::$messages AS $message)
		{
			echo $message, '<br>';
		}
	}

	public static function printAtEnd($message)
	{
		self::$messages[] = $message;
	}

	public static function printInException($data)
	{
		self::$exceptionData[] = $data;
	}

	public static function backtrace()
	{
		$bt = debug_backtrace(FALSE);
		$backtrace = '';
		foreach($bt AS $call_id => $call)
		{

			$backtrace .= isset($call['file']) ? $call['file']. ' ' : '';
			$backtrace .= isset($call['line']) ? '(' . $call['line'] . ') ' : '';
			$backtrace .= isset($call['function']) ? $call['function'] : '';
			$backtrace .= '<br>';
		}
		return $backtrace;
	}

	private static function get_memory()
	{
		foreach(file('/proc/meminfo') as $ri)
			$m[strtok($ri, ':')] = strtok('');
		return 100 - (($m['MemFree'] + $m['Buffers'] + $m['Cached']) / $m['MemTotal'] * 100);
	}
	public static function displayFooter()
	{
		if (isset($_SESSION['_USER']))
		{
			global $start_time,
				   $start_memory,
				   $object_list,
				   $hDB;

			$mem_usage		= (memory_get_usage()-$start_memory)/(1024*1024);
			$mem_peak		= memory_get_peak_usage()/(1024*1024);
			$ellapsed_time	= microtime(true)-$start_time;
			$sql_time		= $hDB->_cumulateProcessTime*1000;
			$sql_prc		= $sql_time!=0?100/($ellapsed_time/($sql_time/1000)):0;
			$sql_count		= $hDB->_numberQuery++;
			$cpu			= 0;
			$mem			= self::get_memory();

			$span_error=' style="color:red;font-weight:bold"';

			$mem_usage_alert 	 = $mem_usage>30 		? $span_error : null;
			$mem_peak_alert  	 = $mem_peak>30 		? $span_error : null;
			$ellapsed_time_alert = $ellapsed_time>1.5 ? $span_error : null;
			$sql_time_alert  	 = $sql_time>500 		? $span_error : null;
			$sql_count_alert  	 = $sql_count>200 	? $span_error : null;

			$cpu1=sys_getloadavg();
			$cpu_usage=($cpu1[0]/(count(explode("\n",`cat /proc/cpuinfo | grep processor`))-1))*100;

			?><center style='margin-top:40px;margin-bottom:10px' id="benchmark_infos">Cpu : <span><?php printf("%1.2f",$cpu_usage) ?> %</span><?php
			?> / Memory : <span><?php printf("%1.2f",$mem) ?> %</span><?php
			?> / Mem Usage : <span<?= $mem_usage_alert ?>><?php printf("%1.2f",$mem_usage) ?> Mo</span><?php
			?> / Peak : <span<?= $mem_peak_alert ?>><?php printf("%1.2f",$mem_peak) ?> Mo</span><?php
			?> / Page generated in <span<?= $ellapsed_time_alert ?>><?php printf("%1.3f",$ellapsed_time) ?> sec</span><?php
			?> / MySQL : <span<?= $sql_count_alert ?>><?= $sql_count ?> queries</span> in <span<?= $sql_time_alert ?>><?php printf("%1.3f",$sql_time) ?> msec</span> (<?php printf("%1.2f",$sql_prc) ?> %)<?php

			if (class_exists('KilliCurl'))
			{
				$curl = KilliCurl::getCurl();
				$curl_count			 = $curl->curl_queries_number;
				$curl_time			 = $curl->curl_time;
				$curl_count_alert  	 = $curl_count>20 	? $span_error : null;
				$curl_time_alert  	 = $curl_time>60 		? $span_error : null;

				if($curl_count > 0)
				{
					?> / Curl : <span<?= $curl_count_alert ?>><?= $curl_count ?> queries</span> in <span<?= $curl_time_alert ?>><?php printf("%1.3f",$curl_time) ?> sec</span><?php
				}
			}
			?></center><?php
			$rows_count_alert = $hDB->_numberRows>1000 ? $span_error : null;
			?><center style='margin-bottom:10px' id="benchmark_infos">
				Selected rows : <span<?= $rows_count_alert ?>><?php printf('%s',$hDB->_numberRows) ?></span>
				/ APC : <?= (function_exists('apc_fetch'))?'<span style="color:green;font-weight:bold">ON</span>':'<span style="color:red;font-weight:bold">OFF</span>'?>
				/ ORM read : <span><?php printf('%1.3f', ORM::$_cumulate_process_time*1000) ?> msec</span>
				/ UI->render() : <span><?php printf('%1.3f', UI::$_start_time_render*1000) ?> msec</span>
				/ Crypt : <span><?php printf('%1.3f', Security::$_cumulateProcessTime*1000) ?> msec</span>
			  </center><?php
		}
			$xhprof_data = xhprof_disable();

			$XHPROF_ROOT = "/var/www/xhprof/";
			include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
			include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

			$xhprof_runs = new XHProfRuns_Default();
			$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");

			echo '<a href="http://localhost/xhprof/xhprof_html/index.php?run=', $run_id, '&source=xhprof_testing">Données du Profiler</a>', "\n", '<br><br>';
	}
}

