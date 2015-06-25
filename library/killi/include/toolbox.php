<?php
	/*. require_module 'standard'; .*/

	/**
	 * @param int $weekNumber
	 * @param string $year
	 * @return array
	 */
	function getDaysInWeek ($weekNumber, $year)
	{
		// Count from '0104' because January 4th is always in week 1
		// (according to ISO 8601).
		$time = strtotime($year . '0104 +' . ($weekNumber - 1). ' weeks');

		// Get the time of the first day of the week
		$mondayTime = strtotime('-'.(intval(date('w', $time))-1).' days', $time);

		// Get the times of days 0 -> 6
		$dayTimes = /*.(array[int]).*/ array ();

		for ($i = 0; $i < 7; ++$i)
		{
			$dayTimes[] = strtotime('+' . $i . ' days', $mondayTime);
		}
		return $dayTimes;
	}

	//-------------------------------------------------------------------------

	// mysqli_result n'existe pas de base
	// Voir http://php.net/manual/fr/function.mysql-result.php
	function mysqli_result($result , $row, $field = 0 )
	{
		if($result->num_rows==0) return FALSE;

		$result->data_seek($row);
		$row_result=$result->fetch_array(MYSQLI_BOTH);

		return $row_result[$field];
	}


	// réorganisation d'un tableau via une de ses clé :
	// order_by($data['typeofparticipant'], 'synonym/value');
	//-------------------------------------------------------------------------
	function order_by(&$array,$by=false,$desc=false)
	{
		if(empty($by) || !is_array($array))
		{
			return false;
		}
		if(is_array($by))
		{
			while($order=array_pop($by))
			{
				order_by($array,$order);
			}
			return true;
		}
		$copy_array=$array;
		$array=array();
		$assoc_array=array();
		foreach($copy_array as $key=>$value)
		{
			$sortable_value=$value;
			foreach(explode('/',$by) as $deep)
			{
				$sortable_value=$sortable_value[$deep];
			}
			$assoc_array[$key]=$sortable_value;
		}
		if(!empty($desc))
		{
			arsort($assoc_array);
		}
		else
		{
			asort($assoc_array);
		}

		foreach($assoc_array as $key=>$value)
		{
			$array[$key]=$copy_array[$key];
		}
		return true;
	}
	//-------------------------------------------------------------------------
	// Retourne TRUE ou FALSE, suivant que $date_processing soit ou non un jour ouvré.
	function is_working_day( $date_processing )
	{
		if( checkdate( date( 'm', $date_processing ), date( 'd', $date_processing ),  date( 'Y', $date_processing ) ) === false )
		{
			throw new Exception( '$date_processing INVALIDE: ' . $date_processing ) ;
		}

		/*
		 * samedi & dimanche
		 */
		if( date( 'w', $date_processing ) == 0 ||  date( 'w', $date_processing ) == 6 )
		{
			return FALSE ;
		}

		/*
		 * JF
		 */

		// Fixes
		$jour	= date( 'd', $date_processing ) ;
		$mois	= date( 'm', $date_processing ) ;
		$annee	= date( 'Y', $date_processing ) ;

		if( $jour == 1	&& $mois == 1 )	return FALSE ; // 1er janvier
		if( $jour == 1	&& $mois == 5 )	return FALSE ; // 1er mai
		if( $jour == 8	&& $mois == 5 )	return FALSE ; // 8 mai
		if( $jour == 14	&& $mois == 7 )	return FALSE ; // 14 juillet
		if( $jour == 15	&& $mois == 8 )	return FALSE ; // 15 aout
		if( $jour == 1	&& $mois == 11 )	return FALSE ; // 1er novembre
		if( $jour == 11	&& $mois == 11 )	return FALSE ; // 11 novembre
		if( $jour == 25	&& $mois == 12 )	return FALSE ; // 25 décembre

		// Mobiles
		// Lundi de Pâques
		$date_lundi_paques = easter_date( $annee ) + ( 1 *  86400 ) ;
		if(  date( 'd', $date_lundi_paques ) == $jour && date( 'm', $date_lundi_paques ) == $mois )
		{
			return FALSE ;
		}

		// Ascension
		$date_ascension = easter_date( $annee ) + ( 39 * 86400 ) ;
		if( date( 'd', $date_ascension ) == $jour && date( 'm', $date_ascension ) == $mois )
		{
			return FALSE ;
		}

		// Pentecote
		$date_pentecote = easter_date( $annee ) + ( 50 * 86400 ) ;
		if( date( 'd', $date_pentecote ) == $jour && date( 'm', $date_pentecote ) == $mois )
		{
			return FALSE ;
		}

		return TRUE ;
	}
	//-------------------------------------------------------------------------
	function working_day_diff($sooner, $later)
	{
		$diff = 0;
		while ($sooner <= $later)
		{
			if (is_working_day($cur_ts))
			{
				$diff++;
			}
			$sooner += 86400;
		}
		return $diff;
	}
