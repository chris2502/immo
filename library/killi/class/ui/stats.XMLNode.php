<?php

/**
 *  @class StatsXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class StatsXMLNode extends XMLNode
{
	public function open()
	{
		global $hDB;

		$this->_hDB = $hDB;

		$object = $this->attributes['object'];

		//---On recup les categories
		$query = "select distinct(cat_id) from killi_stats_query where type_id=2 and param_object='".$object."'";

		$this->_hDB->db_select($query,$result,$num);

		$cat_list = array();
		for ($i=0; $i<$num;$i++)
		{
		$row = $result->fetch_assoc();
		$cat_list[] = $row['cat_id'];
		}
		$result->free();

		//echo display_array($cat_list);

		?>
			<script>
			stat = new dTree('stat');
			stat.config.useCookies=false;
			stat.add(0,-1,'Statistiques');
			<?php

			$position = 1;

			foreach($cat_list as $cat_id)
			{
				$hORM = ORM::getORMInstance('statscategory');
				$category_list = array();
				$hORM->read(array($cat_id),$category_list,array('nom'));
				$raw = array_slice($category_list,0,1);
				$category = $raw[0];

				?>
				stat.add(<?= $position ?>,0,'<?= $category['nom']['value'] ?>');
				<?php

				$cat_position = $position;

				$position++;

				//---On recup la liste des query de la categorie
				$hORM = ORM::getORMInstance('statsquery');
				$stats_query_list = array();
				$hORM->browse($stats_query_list,$num,array('nom','object'),array(array('param_object','=',$object),
																array('type_id','=',2),
																array('cat_id','=',$cat_id)));

				foreach($stats_query_list as $stats_query_id=>$stats_query)
				{
					?>
					stat.add(<?= $position ?>,<?= $cat_position ?>,'<?= $stats_query['nom']['value'] ?>');
					<?php

					$query_position = $position;

					$position++;

					$last_monday_timestamp = strtotime("last Monday");

					//---Traitement du depth
					for($depth=0; $depth < $this->attributes['depth'] ;$depth++)
					{
						$from_timestamp = $last_monday_timestamp - (($depth-1)*7*24*60*60);
						$to_timestamp   = $last_monday_timestamp - (($depth+1-1)*7*24*60*60);

						//---On recup les valeur
						$query = "select `value` from killi_stats_value where query_id=$stats_query_id and param_value=".$_GET['primary_key']." and report_date between '".date('Y-m-d H:i:s',$to_timestamp)."' and '".date('Y-m-d H:i:s',$from_timestamp)."' order by report_date limit 1";

						//echo $query."\n";

						$this->_hDB->db_select($query,$result,$num);

						$value="";
						if ($num==1)
						{
							$row = $result->fetch_assoc();
							$value = $row['value'];
						}

						$result->free();

						if ($value!="")
						{
							$string = '['.date('d/m/Y',$from_timestamp).' au '.date('d/m/Y',$to_timestamp).'] <b>'.$value.' '.$stats_query['object']['value'].'(s)</b>';
							?>
								stat.add(<?= $position ?>,<?= $query_position ?>,'Semaine <?= str_pad(date('W'),2,'0',STR_PAD_LEFT)-$depth.'  '.$string ?>');
							<?php
						}

						$position++;
					}
				}
			}

			?>
			document.write(stat);
			</script>
			<?php

			return TRUE;
		}
}
