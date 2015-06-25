<?php

/**
 *
 *  @class Timer
 *  @Revision $Revision: 4139 $
 *
 */

class Timer
{
	private $_start_timer = NULL; // date de début de la mesure
	private $_is_playing = FALSE; // en cours de mesure ou non
	private $_value = 0; // Somme des mesures précédentes

	/**
	 * Top chronos !
	 */
	public function start()
	{
		if(!$this->_is_playing)
		{
			$this->_start_timer = microtime(true)-$this->_value;

			$this->_is_playing = TRUE;
		}
	}

	/**
	 * On met en pause le chonomètre
	 */
	public function stop()
	{
		if($this->_is_playing)
		{
			$time_elapsed = $this->get() - $this->_value;

			$this->_value = $this->get();

			$this->_is_playing = FALSE;

			return $time_elapsed;
		}

		return 0;
	}

	/**
	 * Le chronomètre recommence à zéro
	 */
	public function reset()
	{
		if($this->_is_playing)
		{
			$this->_start_timer = microtime(true);
		}

		$this->_value = 0;
	}

	/**
	 * Retourne le total de temps écoulé pendant la mesure
	 *
	 * @return number Temps écoulé
	 */
	public function get()
	{
		if($this->_is_playing)
		{
			return microtime(true)-$this->_start_timer;
		}
		else
		{
			return $this->_value;
		}
	}
}
