<?php

abstract class KilliNodeMethod extends Common
{
	//.....................................................................
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		parent::edit($view,$data,$total_object_list,$template_name);

		if ($view=='form')
		{
			$data['nodequalification'] = array();
			ORM::getORMInstance('nodequalification')->browse(
				$data['nodequalification'],
				$num,
				array('nom'),
				array(array('node_id','=',$_GET['primary_key']))
			);
		}

		return TRUE;
	}
	//.....................................................................
	public function getReferenceString(array $id_list, array &$reference_list)
	{
		$object_list = array();
		$hORM = ORM::getORMInstance('node');
		$hORM->read($id_list,$object_list, array( 'etat','workflow_id' ) );

		foreach ($object_list as $key=>$value)
		{
			$reference_list[$key] = $value['workflow_id']['reference'].'::'.$value['etat']['value'];
		}

		return TRUE;
	}
	//.....................................................................
	public static function setPopulation(&$node_list)
	{
		self::checkAttributesDependencies('Node', $node_list, array('object'));
		if(!empty($node_list))
		{
			global $hDB;

			foreach($node_list as &$node)
			{
				$node['population']['value']	= 0;
			}

			$sql =	'SELECT '.
						'COUNT(workflow_token_id) AS total, '.
						'node_id '.
					'FROM '.$hDB->db_escape_string(RIGHTS_DATABASE).'.killi_workflow_token '.
					'WHERE node_id IN ('.implode(',',array_keys($node_list)).') '.
					'GROUP BY node_id';
			$hDB->db_select($sql, $result);

			while($row = $result->fetch_assoc())
			{
				Security::crypt($row['node_id'],$crypt_node_id);

				$url =	'./index.php?action='.$node_list[$row['node_id']]['object']['value'].'.edit'.
						'&crypt/workflow_node_id='.$crypt_node_id;

				$node_list[$row['node_id']]['population']['value'] = $row['total'];
				$node_list[$row['node_id']]['population']['url']   = $url;
			}

			$result->free();
		}
		return TRUE;
	}
}

