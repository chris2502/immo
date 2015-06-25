<?php

/**
 *
 *  @class ReportingMethod
 *  @Revision $Revision: 4139 $
 *
 */

class ReportingMethod
{
	protected $_hDB	 = NULL ;   //---Handle sur une connexion MySQL active

	//-------------------------------------------------------------------------
	function __construct()
	{
		global $hDB; //---On la recup depuis l'index ;-)
		$this->_hDB = &$hDB;
	}
	//-------------------------------------------------------------------------
	public function reporting(&$data,&$total_object_list,&$template_name=NULL)
	{

		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function reportingexportcsv(&$data,&$total_object_list,$template_name)
	{
		$bNoZeroStats = (isset($_POST['nozerostats']) && $_POST['nozerostats'] == '1');
		// Chargement du template.
		$oDom = new DOMDocument();
		$oDom->load('./reporting/template/'.$template_name);
		$oXpath	   = new DOMXPath($oDom);
		// Objet paramétrique (présent en attrib du listing).
		$oListing	 = $oXpath->query('//reporting_listing');
		$param_object = $oListing->item(0)->getAttribute('param_object');
		// Requêtes.
		$oNodeList	= $oXpath->query('//reporting_query');

		// Récupération des stats en fonction des requêtes.
		$data = array();
		$data_previous_week = array();
		$sql =  'Select '.
					'value, '.
					'param_value '.
				'From '.
					'killi_stats_value, '.
					'killi_stats_query '.
				'Where '.
					'(killi_stats_query.query_id = killi_stats_value.query_id) And '.
					'(killi_stats_query.query_name = "[name]") And '.
					'(UNIX_TIMESTAMP(report_date) >= [from]) And '.
					'(UNIX_TIMESTAMP(report_date) < [to]) '.
				'Group By '.
					'param_value '.
				'Order By '.
					'report_date';
		$datePart = date('d-m-Y');
		$year  = date('Y');
		$dateW = date('W');
		$tplParts = explode('.', $template_name);
		//---Stream du résultat
		header('Pragma: private');
		header('Content-Type: application/octet-stream; charset=utf-8');
		header('Content-Disposition: attachment; filename="export_'.strtolower($tplParts[0]).'-'.$datePart.'.csv"' ) ;
		// Colonne 1
		echo $param_object.';';
		$arColList = array();
		foreach ($oNodeList as $oNode)
		{
			$nodeName = $oNode->getAttribute('name');
			echo $nodeName.';';
			$arColList[] = array('name' => $nodeName, 'delta' => $oNode->getAttribute('delta'));
			//---Selected week
			$data[$nodeName] = array();

			if (isset($_POST['week_period']) && !empty($_POST['week_period']))
				$week = $_POST['week_period'];
			else
				$week = $dateW;

			//---Construction query
			$diw   = getDaysInWeek($week, $year);
			$from  = $diw[0];
			$to	= $diw[6]+(24*60*60);
			$query = str_replace(array('[name]', '[from]', '[to]'), array($nodeName, $from, $to), $sql);
			$this->_hDB->db_select($query,$result,$num);

			for ($i=0; $i<$num;$i++)
			{
				$row = $result->fetch_assoc();
				$data[$nodeName][$row['param_value']] = $row['value'];
			}

			$result->free();

			//--- week - 1
			if (isset($_POST['delta_week_period']) && !empty($_POST['delta_week_period']))
			{
				$week = $_POST['delta_week_period'];
			}
			else
			{
				$week = $dateW - 1;
				$_POST['delta_week_period'] = $week;
			}

			//---Construction query
			$diw   = getDaysInWeek($week, $year);
			$from  = $diw[0];
			$to	= $diw[6] + (24 * 60 * 60);
			$query = str_replace(array('[name]', '[from]', '[to]'), array($nodeName, $from, $to), $sql);
			$this->_hDB->db_select($query,$result,$num);

			for ($i=0; $i<$num;$i++)
			{
				$row = $result->fetch_assoc();
				$data_previous_week[$nodeName][$row['param_value']] = $row['value'];
			}

			$result->free();
		}
		echo "\n";

		$param_object_id_list = array();
		$hORM = ORM::getORMInstance($param_object);
		$hORM->search($param_object_id_list,$num,array());

		$hMethod = ORM::getControllerInstance($param_object);
		$param_object_reference_list = array();
		$hMethod->getReferenceString($param_object_id_list, $param_object_reference_list);
		foreach ($param_object_reference_list as $param_object_id => $param_object_reference)
		{
			$values = '';
			$hasContents = false;
			foreach ($arColList as $col)
			{
				$curValue = '-';
				if (isset($data[$col['name']][$param_object_id]))
				{
					$delta = '';
					if (isset($data_previous_week[$col['name']][$param_object_id]) && $data_previous_week[$col['name']][$param_object_id] > 0)
					{
						$npv = $data_previous_week[$col['name']][$param_object_id];
						$nv  = $data[$col['name']][$param_object_id];
						$diff = $nv - $npv;
						$percent = 100 * ($diff / $npv);
						$signe = '+';
						if ($percent < 0)
						{
							$signe = '';
						}
						$delta = ' ('.$signe.' '.sprintf('%.02f', $percent).' %) ('.$signe.' '.$diff.' %)';
					}
					$curValue = $data[$col['name']][$param_object_id].$delta;
					$hasContents = true;
				}
				$values.= $curValue.';';
			}
			if (!$bNoZeroStats || ($bNoZeroStats && $hasContents))
			{
				echo $param_object_reference.';'.$values."\n";
			}
		}
		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function process()
	{
		$query_id = $_GET['primary_key'];

		//---On recup la query
		$hORM = ORM::getORMInstance('statsquery');
		$stats_query_list = array();
		$hORM->read(array($query_id),$stats_query_list);
		$raw = array_slice($stats_query_list,0,1);
		$stats_query = $raw[0];

		$query = $stats_query['query_sql']['value'];

		//---Si on a un object parametrique, on le récupere
		if ($stats_query['param_object']['value']!=NULL)
		{
			$param_pkey = ORM::getObjectInstance($stats_query['param_object']['value'])->primary_key;

			$hORM = ORM::getORMInstance($stats_query['param_object']['value']);
			$param_object_id_list = array();
			$hORM->search($param_object_id_list,$num,array(),NULL,0,20000);

			$query = str_replace(':'.$param_pkey.':',join(',',$param_object_id_list),$query);

			//---Process query
			$this->_hDB->db_select($query,$result,$num);

			$resultat = array();
			for ($i=0; $i<$num;$i++)
			{
				$row = $result->fetch_assoc();

				$resultat[$row[$param_pkey]] = $row['result'];
			}

			$result->free();

			foreach($resultat as $param_id=>$value)
			{
				$hORM = ORM::getORMInstance('statsvalue');

				$data = array();
				$data['query_id']	= $query_id;
				$data['value']	   = $value;
				$data['param_value'] = $param_id;
				$data['report_date'] = date('Y-m-d H:i:s',time());

				$hORM->create($data,$id);
			}
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	public function processAll()
	{
		//---On recup les query
		$hORM = ORM::getORMInstance('statsquery');
		$query_id_list = array();
		//$hORM->search($query_id_list,$num,array(array('query_id','=','37'),array('type_id','=',2)));
		$hORM->search($query_id_list,$num,array(array('type_id','=',2)));

		set_time_limit(60*60*2); //---Max 2 heures

		foreach($query_id_list as $id)
		{
			$_GET['primary_key'] = $id;
			$this->process();
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------

}

