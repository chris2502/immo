<?php

namespace Killi\Core\ORM\Debug;

trait Trace
{
	private static $no_fields		= array();

	/**
	 *
	 * DEBUG TRACE && DEV TOOLS
	 *
	 *
	 */

	// @codeCoverageIgnoreStart
	static function dump_bt($bts)
	{
		$return='';
		foreach($bts as $bt)
		{
			$return.=$bt.'<br/>';
		}
		return $return;
	}
	//---------------------------------------------------------------------
	static function check_trace_no_fields($original_fields)
	{
		if(empty($original_fields))
		{
			//backtrace
			$bt=array();
			foreach(debug_backtrace(false) as $sub_bt)
			{
				//if($i==0 && substr($sub_bt['file'], -10)=='Common.php') break;
				//if($i==0 && substr($sub_bt['file'], -17)=='MappingObject.php') break;
				$bt[]=(isset($sub_bt['file']) ? $sub_bt['file'] : 'unknown_file').':'.(isset($sub_bt['line']) ? $sub_bt['line'] : 'unknown_line');
			}

			if(!empty($bt))
			{
				self::$no_fields[]=$bt;
			}
		}
		return TRUE;
	}
	//---------------------------------------------------------------------
	static function trace_no_fields()
	{
		if(count(self::$no_fields)==0)
		{
			return TRUE;
		}

		$table="<table class='table_list' style='table-layout: fixed'><tr><th style='text-align:left'>Backtrace</th></tr>";

		foreach(self::$no_fields as $i=>$field)
		{
			$table.="<tr".($i%2==0?' style="background-color:#eee;"':'')."><td style='text-align:left;word-wrap:break-word'>".self::dump_bt($field).'</td></tr>';
		}

		echo "<h3 style='margin:5px'>".count(self::$no_fields)." accès à l'ORM sans paramètre field spécifiés !</h3>".$table."</table>";
	}
	//---------------------------------------------------------------------
	static function traceObjectLoader()
	{
		$loaded = 0;
		$total = 0;
		$managed = 0;
		$loaded_str = '';
		foreach(self::$_objects AS $name => $object)
		{
			if($object['class'] !== NULL)
			{
				$loaded_str .= $name . '<br>';
				$loaded++;
			}

			if($object['rights'] == TRUE)
			{
				$managed++;
			}
			$total++;
		}
		echo '<h3>Chargement des objets dans l\'ORM :</h3>';
		echo 'Total : ', $total, ', Chargés : ', $loaded, ', Objets avec gestion des droits : ', $managed, '<br>';//, $loaded_str, '<br>';
	}

	// @codeCoverageIgnoreEnd
}
