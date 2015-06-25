<?php

namespace Killi\Core\ORM\Debug;

trait Performance
{
	private static $_start_time = 0;
	public static $_cumulate_process_time = 0;

	private static function start_counter()
	{
		self::$_start_time = microtime(TRUE);
	}

	private static function stop_counter()
	{
		self::$_cumulate_process_time += (microtime(TRUE) - self::$_start_time);
	}
}
