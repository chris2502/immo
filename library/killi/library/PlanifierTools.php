<?php

class PlanifierTools
{
	/**
	 * Fonction de construction de plage horaire pour la couche de la grille de planification.
	 * Un appel de cette fonction permet de définir une plage horaire. Lorsqu'une plage horaire
	 * se superpose à une plage déjà existante, l'ancienne plage est soit retirée, soit redimensionnée.
	 *
	 * @param array			$data	Donnée à renvoyer à la grille de planification.
	 * @param int			$jour	Jour.
	 * @param string		$debut	Heure de début (format: HH:MM:SS).
	 * @param string		$fin	Heure de fin   (format: HH:MM:SS).
	 * @param array|string	$class	Classe CSS à affecter à la plage horaire.
	 * @return				TRUE si OK, FALSE sinon
	 */
	public static function setArea(&$data, $jour, $debut, $fin, $class)
	{
		if($data == NULL)
		{
			$data = array();
		}
		$jour = (int) $jour;

		if($jour < 1 || $jour > 7)
		{
			return FALSE;
		}

		$heure_debut = (int)substr($debut, 0, 2);
		$minute_debut = (int)substr($debut, 3, 2);
		$heure_fin = (int)substr($fin, 0, 2);
		$minute_fin = (int)substr($fin, 3, 2);

		if($heure_debut < 0 || $heure_debut > 23 || $minute_debut < 0 || $minute_debut > 59 ||
		   $heure_fin < 0 || $heure_fin > 23 || $minute_fin < 0 || $minute_fin > 59)
		{
			return FALSE;
		}

		$time_start = (int)($heure_debut .  substr('0'.$minute_debut, -2));
		$time_end = (int)($heure_fin .  substr('0'.$minute_fin, -2));

		if( $time_end < $time_start )
		{
			return false;
		}

		$data2 = array();

		$total = count($data);

		$flag_zone_debut_chevauchement_identifiee = false;
		$inserted = false;
		foreach($data AS $id => $zone)
		{
			$zone_start = (int)($zone['start']['hour'] .  substr('0'.$zone['start']['minute'], -2));
			$zone_end = (int)($zone['end']['hour'] .  substr('0'.$zone['end']['minute'], -2));

			// trouver la zone qui encadre le debut
			if($zone['start']['day'] == $jour)
			{
				$inserted = true;
				if($zone_start < $time_start && $zone_start < $time_end && $zone_end < $time_end && $zone_end > $time_start)
				{

						$flag_zone_debut_chevauchement_identifiee = true;

						$zone['end']['hour'] = $heure_debut;
						$zone['end']['minute'] = $minute_debut;
						$data2[] = $zone;
				}
				else
				if($zone_start == $time_start && $zone_start < $time_end && $zone_end < $time_end && $zone_end > $time_start)
				{
					$flag_zone_debut_chevauchement_identifiee = true;
				}
            	else
			// trouver la zone qui encadre l'horaire fin
				if($flag_zone_debut_chevauchement_identifiee == true
			  		&& $zone_start > $time_start && $zone_end > $time_start && $zone_end > $time_end && $zone_start < $time_end)
				{
					$flag_zone_debut_chevauchement_identifiee = false;
					$zone['start']['hour'] = $heure_fin;
					$zone['start']['minute'] = $minute_fin;

					$data2[] = array('start' => array('day' => $jour,
					'hour' => $heure_debut,
					'minute' => $minute_debut),
					'end' => array('day' => $jour,
							'hour' => $heure_fin,
							'minute' => $minute_fin),
					'className' => $class);

					$data2[] = $zone;
				}
				else
				if($flag_zone_debut_chevauchement_identifiee == true
			  		&& $zone_start > $time_start && $zone_end > $time_start && $zone_end == $time_end && $zone_start < $time_end)
				{
					$flag_zone_debut_chevauchement_identifiee = false;
					$data2[] = array('start' => array('day' => $jour,
					'hour' => $heure_debut,
					'minute' => $minute_debut),
					'end' => array('day' => $jour,
							'hour' => $heure_fin,
							'minute' => $minute_fin),
					'className' => $class);
				}
				else
				if($zone_start <= $time_start	&& $zone_end >= $time_end)
				{
					if($zone_start == $time_start	&& $zone_end == $time_end)
					{
						$zone['className'] = $class;
						$data2[] = $zone;
					}
					else
					if($zone_start == $time_start)
					{
						$zone['start']['hour'] = $heure_fin;
						$zone['start']['minute'] = $minute_fin;

					$data2[] = array('start' => array('day' => $jour,
					'hour' => $heure_debut,
					'minute' => $minute_debut),
					'end' => array('day' => $jour,
							'hour' => $heure_fin,
							'minute' => $minute_fin),
					'className' => $class);
					$data2[] = $zone;
				}
				else
				if($zone_end == $time_end)
				{
						$zone['end']['hour'] = $heure_debut;
						$zone['end']['minute'] = $minute_debut;
						$data2[] = $zone;

						$data2[] = array('start' => array('day' => $jour,
					'hour' => $heure_debut,
					'minute' => $minute_debut),
					'end' => array('day' => $jour,
							'hour' => $heure_fin,
							'minute' => $minute_fin),
					'className' => $class);
				}
				else
				{
					$zone2 = $zone;

					$zone['end']['hour'] = $heure_debut;
					$zone['end']['minute'] = $minute_debut;
					$zone2['start']['hour'] = $heure_fin;
					$zone2['start']['minute'] = $minute_fin;

					$data2[] = $zone;

					$data2[] = array('start' => array('day' => $jour,
					'hour' => $heure_debut,
					'minute' => $minute_debut),
					'end' => array('day' => $jour,
							'hour' => $heure_fin,
							'minute' => $minute_fin),
					'className' => $class);
					$data2[] = $zone2;
				}
				}
				else
				if(!$flag_zone_debut_chevauchement_identifiee)
				{
					$data2[] = $zone;
				}
			}
			else
			if(!$flag_zone_debut_chevauchement_identifiee)
			{
				$data2[] = $zone;
			}
		}

		if(!$inserted)
		{
			$data2[] = array('start' => array('day' => $jour,
					'hour' => $heure_debut,
					'minute' => $minute_debut),
					'end' => array('day' => $jour,
							'hour' => $heure_fin,
							'minute' => $minute_fin),
					'className' => $class);
		}

		$data = $data2;
		return TRUE;
	}
}

