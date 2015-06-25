<?php

/**
 *  @class KilliCron
 *  @Revision $Revision: 4582 $
 *
 */
abstract class KilliCron
{
	private $time_start = 0;
	private $actions = array();

	function __construct()
	{
		if (!DISPLAY_ERRORS && !KILLI_SCRIPT)
		{
			header('HTTP/1.0 403 Forbidden');
			die();
		}

		Debug::log('Starting CRON time : ' . date('Y-m-d H:i:s'));
		$this->time_start = time();
		
		set_time_limit ( 60 * 60 * 2 ); //---Max 2 heures
	}

	function __destruct()
	{
		Debug::log('Total execution time : ' . (time() - $this->time_start) . 's');
	}

	protected function setAction($object, $method)
	{
		$backtrace = debug_backtrace();
		$time = $backtrace[1]['function'];

		$this->actions[$time][$object][$method] = $method;

		return TRUE;
	}

	private function executeActions($time)
	{
		global $hDB;

		if (empty($this->actions[$time]))
		{
			return TRUE;
		}

		foreach ($this->actions[$time] as $object => $methods)
		{
			foreach ($methods as $method)
			{
				try
				{
					$class = $object . 'Method';
					$objInstance = new $class();

					Debug::warn('');
					Debug::warn('EXECUTING ' . $class . '->' . $method);

					$objInstance->$method();
					
					$hDB->db_commit();
					Mailer::commit();
				}
				catch ( Exception $exception )
				{
					$hDB->db_rollback();
					new NonBlockingException ( $exception );
				}
			}
		}

		return TRUE;	
	}

	//-------------------------------------------------------------------------
	public function hour() //---every hour
	{
		$this->executeActions(__FUNCTION__);

		if (date ( 'H' ) == 4)
		{
			$this->day ();
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function day() //---every day, ~ 04h00
	{
		$this->executeActions(__FUNCTION__);

		//--- Si lundi
		if (date ( 'N' ) == 1)
		{
			$this->monday ();
		}
		
		//--- Si Dimanche et semaine paire
		if (date ( 'N' ) == 7 && date ( 'W' ) % 2 == 0)
		{
			$this->twoweeks ();
		}
		
		//--- Si 1er du mois
		if (date ( 'j' ) == 1)
		{
			$this->month ();
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function monday() //--- every monday, ~ 04h00
	{
		$this->executeActions(__FUNCTION__);

		//---Calcul reporting
		$hReportingMethod = new ReportingMethod ();
		$hReportingMethod->processAll ();

		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function twoweeks()
	{
		$this->executeActions(__FUNCTION__);

		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function month()
	{
		$this->executeActions(__FUNCTION__);

		return TRUE;
	}
}
