<?php

namespace Killi\Core\Logger;

/**
 *
 * @class  LoggerService
 * @Revision $Revision: 4576 $
 *
 */

use Psr\Log\LoggerAwareTrait;

class LoggerService
{
	use LoggerAwareTrait;

	private static $instance = NULL;

	private function __construct() {}

	public static function instance()
	{
		if(self::$instance == NULL)
		{
			self::$instance = new LoggerService();
		}
		return self::$instance;
	}

	public function logger()
	{
		if($this->logger == NULL)
		{
			throw new Exception('Logger must be defined !');
		}
		return $this->logger;
	}

	public static function emergency($message, array $context = array())
	{
		return self::instance()->logger()->emergency($message, $context);
	}

	public static function alert($message, array $context = array())
	{
		return self::instance()->logger()->alert($message, $context);
	}

	public static function critical($message, array $context = array())
	{
		return self::instance()->logger()->critical($message, $context);
	}

	public static function error($message, array $context = array())
	{
		return self::instance()->logger()->error($message, $context);
	}

	public static function warning($message, array $context = array())
	{
		return self::instance()->logger()->warning($message, $context);
	}

	public static function notice($message, array $context = array())
	{
		return self::instance()->logger()->notice($message, $context);
	}

	public static function info($message, array $context = array())
	{
		return self::instance()->logger()->info($message, $context);
	}

	public static function debug($message, array $context = array())
	{
		return self::instance()->logger()->debug($message, $context);
	}

	public static function log($level, $message, array $context = array())
	{
		return self::instance()->logger()->log($level, $message, $context);
	}
}
