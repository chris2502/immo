<?php

abstract class KilliAndroidAppErrorMethod extends Common
{
	//--------------------------------------------------------------------------
	public function getReferenceString(array $id_list, array &$reference_list)
	{
	   $object_list = array();
	   $hORM = ORM::getORMInstance('androidapperror');
	   $hORM->read($id_list,$object_list, array( 'date_err_client','app_name','err_type') );

	   foreach ($object_list as $key=>$value)
	   {
		  $reference_list[$key] = $value['app_name']['value'].'-'.$value['err_type']['value'].'-'.$value['date_err_client']['value'].'-'.$value['date_err_server']['value'];
	   }

	   return TRUE;
	}
	//--------------------------------------------------------------------------
	public static function androidRecordThrowableErrors(array $data, &$id_list) {
		$hORM = ORM::getORMInstance ( 'androidapperror' );
		foreach ( $data as $offset => $errData ) {
			isset ( $errData ["_id"] ) ? $app_android_id = $errData ["_id"] : $app_android_id = "";
			$object_data = array (
					"app_name" => $errData ["app_name"],
					"device_data" => $errData ["device_data"],
					"err_type" => $errData ["err_type"],
					"err_cause" => $errData ["err_cause"],
					"err_msg" => $errData ["err_msg"],
					"err_backtrace" => $errData ["err_backtrace"] 
			);
			
			// On exclue les alertes intempestives "SystemTimeChanged" pour toute différence definie dans méthode timeDiffAccepted() :
			$id_gen = null;
			$diffAccepted = false;
			if ($errData ["err_msg"] == "system_time_changed") {
				$diffAccepted = AndroidAppErrorMethod::timeDiffAccepted($errData["err_backtrace"]);
				$id_gen = '';
			}
			
			/*
			 * Si "date_err" n'est pas à "null", (toujours le cas en théorie) : on complète le champ "date_err_client" Note : MySql gère automatiquement celui "date_err_server"
			 */
			if (! (trim ( strtolower ( $errData ["date_err"] ) ) == "null")) {
				$object_data ["date_err_client"] = $errData ["date_err"];
			}
			
			if($diffAccepted === false) {
				$hORM->create ( $object_data, $id_gen );
			}
			$id_list [$offset] ["_id"] = $app_android_id;
			$id_list [$offset] ["android_app_error_id"] = $id_gen;
		}
	   
	   return true;
	}
	//--------------------------------------------------------------------------
	public function unlinkSelected()
	{
		$obj_list = $_POST['listing_selection'];
		$hORM = ORM::getORMInstance('androidapperror');

		foreach($obj_list as $obj_list_id=>$obj_id)
		{
			$hORM->unlink($obj_id);
		}
		
		$this->_hDB->db_commit();
		UI::quitNBack();

		return;
	}
	//--------------------------------------------------------------------------
	public static function alert_admin_of_throwable_error() {
	   
	   /*
	   * Notes :
	   * On ne tiens pas compte d une quelconque date pour le regroupement des erreurs (ce qui nous interesse est la consolidation par type/genre)
	   * On exclue les erreurs particulieres avec message "system_time_changed", feront partie d un traitement independant.
	   */
	   $hORM = ORM::getORMInstance('androidapperror');
	   $fields = array("app_name" , "device_data" , "date_err_client", "date_err_server", "err_type" , "err_cause" , "err_msg" , "err_backtrace");
	   $args = array(array("alert_is_sended", "=", 0), array("err_msg", "!=", "system_time_changed"));
	   
	   // Recuperation des erreurs (sauf les "system_time_changed") :
	   $hORM->browse($object_list, $nb, $fields, $args);

	  // Regroupement par application, par "genre" d'erreur et "device_data" :
	   $app_err_group = array();
	   foreach($object_list as $k => $v)
	   {
		  if(!isset($app_err_group[$v["app_name"]["value"]])) // Pas de data deja presente, on y pousse les valeurs et on initialise le "device_data_tab"
		  {
			 $app_err_group[ $v["app_name"]["value"] ] = array();
			 
			 $values_to_push = array("err_type" => $v["err_type"]["value"],
									 "err_cause" => $v["err_cause"]["value"],
									 "err_msg" => $v["err_msg"]["value"],
									 "err_backtrace" => $v["err_backtrace"]["value"],
									 "device_data_tab" => array($v["device_data"]["value"] => 1),
									 "android_app_error_id_tab" => array($k)
									 );
			 array_push($app_err_group[ $v["app_name"]["value"] ], $values_to_push);
		  }
		  else
		  {
			 // Pour chaque Groupe, controle si on y trouve un genre d'erreur identique aux data en cours de traitement :
			 $val_found = false;
			 foreach($app_err_group[ $v["app_name"]["value"] ] as $k2 => &$err_data)
			 {
				// Valeurs identiques trouvees :
				if(AndroidAppErrorMethod::is_same_kind_of_error($err_data, $v))
				{
				   // Si le "device_data" existe, on l incremente :
				   if(isset($err_data["device_data_tab"][ $v["device_data"]["value"] ]))
				   {
					  $err_data["device_data_tab"][ $v["device_data"]["value"] ]++;
				   }
				   // sinon on l'ajoute et on l initialise :
				   else
				   {
					  $err_data["device_data_tab"][ $v["device_data"]["value"] ] = 1;
				   }
				   
				   // On ajoute l'ID de l'erreur au "android_app_error_id_tab" :
				   if(isset($err_data["android_app_error_id_tab"]) && is_array($err_data["android_app_error_id_tab"]))
				   {
					  array_push($err_data["android_app_error_id_tab"], $k);
				   }
				   else
				   {
					  $err_data["android_app_error_id_tab"] = array($k);
				   }
				   
				   // flag que valeur trouvee pour ne pas faire de traitement à la fin du foreach et sortie de boucle :
				   $val_found = true;
				   break;
				}
			 }
			 if ($val_found === false)
			 {
				// On les pousse en initialisant le "device_data_tab"
			   $values_to_push = array("err_type" => $v["err_type"]["value"],
					 "err_cause" => $v["err_cause"]["value"],
					 "err_msg" => $v["err_msg"]["value"],
					 "err_backtrace" => $v["err_backtrace"]["value"],
					 "device_data_tab" => array($v["device_data"]["value"] => 1),
					  "android_app_error_id_tab" => array($k)
			   );
			   array_push($app_err_group[ $v["app_name"]["value"] ], $values_to_push);
			 }
		  }
	   }
	   
	   /*
		* Formatage du corp et envoi des données du tableau de regroupement :
		* TODO : FORMATAGE PARTICULIER SI ERREUR DE TYPE "DATE_ALTEREE"
	   */
	   $hMAILER = new PHPMailer() ;
		
	   $hMAILER->IsSMTP() ;
	   $hMAILER->IsHTML();
	   $hMAILER->Host = "127.0.0.1"; // SMTP server
		
	   $hMAILER->SetFrom('noreply@corp.free.fr', 'noreply');
		$addressFormatList = array ();
		array_push($addressFormatList, array ('jcolant@corp.free.fr', 'Julien COLANT'));
		array_push($addressFormatList, array ('vpouchard@corp.free.fr', 'Vivien LETOMBEUR'));
		array_push($addressFormatList, array ('fraoult@n3.free.fr', 'Francois RAOULT'));
		array_push($addressFormatList, array ('jdelattre@n3.free.fr', 'Jose DELATTRE'));
		
		foreach ($addressFormatList as $coupleAddr) {
			$hMAILER->addAddress($coupleAddr[0], $coupleAddr[1]);
		}
	   
	   $hMAILER->Subject  =  'Erreurs App. Android' ;

	   $msg = "Liste des erreurs Android par application, par type et comptage par terminal : \n";
	   $msg .= "<ul>";
	   if(empty($app_err_group)) {
		  $msg .= "<li>Liste vide !</li>" ;
	   }
	   else {
		  foreach ($app_err_group as $app => $type_data_list) {
   		  $msg .= "<li>Application : ".$app;
   		  $msg .= "<ul>";
   		  foreach($type_data_list as $key => $err_data_kind) {
   			 $msg .= "<li>Type erreur : ".trim($err_data_kind["err_type"]);
   			 $msg .= "<ul>";
	  			 $msg .= "<li>Cause : ".trim($err_data_kind["err_cause"])."</li>";
	  			 $msg .= "<li>Message : ".trim($err_data_kind["err_msg"])."</li>";
	  			 $msg .= "<li>Backtrace : \n".trim($err_data_kind["err_backtrace"])."</li>";
	  			 $msg .= "<li>Nbr par terminal :";
	  			 $msg .= "<ul>";
	  			 foreach ($err_data_kind["device_data_tab"] as $device => $nb) {
	  				$msg .= "<li>";
	  				$msg .= $device . "(".$nb.")";
	  				$msg .= "</li>";
	  			 }
	  			 $msg .= "</ul>";
	  			 $msg .= "<li>Liste des \"android_app_error_id\" : ".implode(', ', $err_data_kind['android_app_error_id_tab'])."</li>";
	  			 $msg .= "</li>";
   			 $msg .= "</ul>";
   			 $msg .= "</li>";
   		  }
   		  $msg .= "</ul>";
	  	   $msg .= "</li>";
   	   }
	   }
	   $msg .= "</ul>";
	   
	   $msg = nl2br($msg);
	   
	   //$hMAILER->Body = "url : http://conges.proxad.net/myconges/\nlogin : " . $_POST['userLogGen'] . "\npassword : " . $_POST['pwdNoCrypt'] . "\n";
	   $hMAILER->Body = $msg;
		
	   // Si les données sont bien envoyées, marquage en BDD :
	   if($hMAILER->Send()) {
		  $prepared_tab = array("alert_is_sended" => "1");
		  foreach ($object_list as $app_error_list)
		  {
			 $id = $app_error_list["android_app_error_id"]["value"];
			 $hORM->write($id, $prepared_tab);
		  }
	   }
	   
	   return true;
	}
	//--------------------------------------------------------------------------
	private static function timeDiffAccepted($backtrace) {
		$isTolerated = true;
		$timeDiff = 0;
		$time1Str = substr( AndroidAppErrorMethod::trim_all($backtrace ), 36, 8 ); // ça pue mais c'est rapide à DEV ! Pas besoin $
		$time2Str = substr( rtrim ( $backtrace ), - 8 ); // ça pue toujours mais un peu moins !!
		
		$t1 = DateTime::createFromFormat ( 'G:i:s', $time1Str );
		$t2 = DateTime::createFromFormat ( 'G:i:s', $time2Str );
		
		if( ! ($t1 === NULL || $t2 === NULL) ) {
			$timeDiff = $t2->getTimestamp () - $t1->getTimestamp ();
			if ($timeDiff < 0) $timeDiff = $t1->getTimestamp () - $t2->getTimestamp ();
		}
		else {
			$export = array();
			$export['time1'] = $time1Str;
			$export['time2'] = $time2Str;
			mail('jdelattre@n3.free.fr', 'NullPointerException à déboguer !', 'Erreur sur extraction des heures de backtrace ; Méthode timeDiffAccepted() ; Classe AndroidAppErrorMethod\n Données extraites : \n'+var_export($export, true));
		}
 
		// Si la différence est supérieure à 5 minutes, on doit enregistrer l'erreur donc différence pas tolérée :
		if ($timeDiff > 360) {
			$isTolerated = false;
		}
		return $isTolerated;
	}
	//--------------------------------------------------------------------------
	private static function trim_all( $str , $what = NULL , $with = NULL )
	{
		if(is_null($with)) $with = '';
		
		if( $what === NULL )
		{
			//  Character      Decimal      Use
			//  "\0"            0           Null Character
			//  "\t"            9           Tab
			//  "\n"           10           New line
			//  "\x0B"         11           Vertical Tab
			//  "\r"           13           New Line in Mac
			//  " "            32           Space
			 
			$what   = "\\x00-\\x20";    //all white-spaces and control chars
		}
		 
		return trim( preg_replace( "/[".$what."]+/" , $with , $str ) , $what );
	}
	//--------------------------------------------------------------------------
	private static function is_same_kind_of_error($currentGroupRecord, $dataBaseRecord) {
	   if ($currentGroupRecord["err_type"] == $dataBaseRecord["err_type"]["value"]
	   && $currentGroupRecord["err_cause"] == $dataBaseRecord["err_cause"]["value"]
	   && $currentGroupRecord["err_msg"] == $dataBaseRecord["err_msg"]["value"]
	   && $currentGroupRecord["err_backtrace"] == $dataBaseRecord["err_backtrace"]["value"] )
	   {
		  return true;
	   }
	   else {
		  return false;
	   }
	}

}

