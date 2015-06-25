<?php

/**
 *  @class WorkflowMethod
 *  @Revision $Revision: 4655 $
 *
 */

abstract class KilliWorkflowMethod extends Common
{
	public static function get_constraints()
	{
		if (!isset($_GET['workflow_node_id']))
		{
			return FALSE;
		}

		// Rétro-compatibilité.
		// TODO : virer ce bout de code après la mise en prod.
		$hInstance = ORM::getObjectInstance('nodelink');
		if (!isset($hInstance->constraints) || !isset($hInstance->constraints_label))
		{
			return FALSE;
		}

		// On récupère tous les links ayant pour noeud d'entrée notre node actuel :
		$link_list = array();
		ORM::getORMInstance('nodelink')->browse($link_list, $num, array(
			'input_node',
			'output_node',
			'constraints',
			'constraints_label',
			'constraints_qualification'
		), array(array('input_node', '=', $_GET['workflow_node_id'])));

		// On récupère une liste de tous ses noeuds de sortie :
		$output_node_id_list = array();
		foreach ($link_list as $link_id => $link)
		{
			$output_node_id_list[] = $link['output_node']['value'];
		}
		$output_node_list = array();
		ORM::getORMInstance('node')->read($output_node_id_list, $output_node_list, array('etat'));

		// Puis on affiche les contraintes.
		foreach ($link_list as $link_id => $link)
		{
			if (empty($link['constraints']['value']) &&
				!$link['constraints_qualification']['value'])
			{
				continue;
			}
			$node_name = $output_node_list[$link['output_node']['value']]['etat']['value'];
			$constraint_string = $link['constraints_label']['value'];
			// On affiche si la qualification est requise pour ce link.
			if ($link['constraints_qualification']['value'] == true)
			{
				if (!empty($constraint_string))
				{
					$constraint_string .= "\n";
				}
				$constraint_string .= '<b>Qualification requise</b>';
			}
			$_SESSION['_MESSAGE_LIST']['Contrainte "'.$node_name.'"'] = nl2br($constraint_string);
		}
		return TRUE;
	}
	//.....................................................................
	public static function checkConstraints($object_name, $id_list, $constraints_string, $qualification_required = FALSE, $redirect = TRUE)
	{
		// Field list
		$matches = array();

		//$pattern = '#((\{)([a-zA-Z_]+)(/)([a-zA-Z_]+)(\}))#';
		$pattern = '#\{([a-zA-Z0-9_]+)(/)([a-zA-Z0-9_]+)(/[a-zA-Z0-9_]+)*\}#';
		preg_match_all($pattern, $constraints_string, $matches);
		$field_list = array();
		foreach ($matches[3] as $field)
		{
			$field_list[$field] = TRUE;
		}
		$field_list = array_keys($field_list);

		// Operators
		$pattern	 = array('/\s(AND)\s/', '/\s(OR)\s/', '/\s(and)\s/', '/\s(or)\s/');
		$replacement = array(' && ', ' || ', ' && ', ' || ');
		$condition   = preg_replace($pattern, $replacement, $constraints_string);

		// Fields to Variables
		$pattern   = '#(\{)([a-zA-Z0-9_]+)(/)([a-zA-Z0-9_]+)(/)*([a-zA-Z0-9_]+)*(\})#';
		$condition = preg_replace_callback($pattern, function($matches) {
			$remplacement = '$object[\''.$matches[4].'\'][\''.((empty($matches[6]))? 'value' : $matches[6]).'\']';
			return $remplacement;
		}, $condition);
		$condition = 'return ('.$condition.');';

		// Loop
		$object_list = array();
		ORM::getORMInstance($object_name)->read($id_list, $object_list, $field_list);
		$crypt_object_id = '';
		foreach ($object_list as $object_id => $object)
		{
			// Verify qualification
			Security::crypt($object_id, $crypt_object_id);
			if ($qualification_required == true &&
				(
					!isset($_POST['qualification_id/'.$crypt_object_id]) ||
					empty($_POST['qualification_id/'.$crypt_object_id])
				))
			{
				$_SESSION['_ERROR_LIST']['Déplacemement'] = 'Le déplacement a échoué, la qualification est requise.';
				// @codeCoverageIgnoreStart
				if ($redirect)
				{
					UI::goBackWithoutBrutalExit();
				}
				// @codeCoverageIgnoreEnd
				return FALSE;
			}

			// Verify conditions
			if (!empty($constraints_string) && !eval($condition))
			{
				$_SESSION['_ERROR_LIST']['Déplacemement'] = 'Le déplacement a échoué, un objet ne répond pas aux critères demandés.';
				// @codeCoverageIgnoreStart
				if ($redirect)
				{
					UI::goBackWithoutBrutalExit();
				}
				// @codeCoverageIgnoreEnd
				return FALSE;
			}
		}
		return TRUE;
	}
	//.....................................................................
	public function move_token()
	{
		//---On recup le noeud d'origine
		$hORM = ORM::getORMInstance('node');
		$origin_node = null;
		$hORM->read($_GET['origin_node'],$origin_node);

		//---On recup le noeud de destination
		$destination_node = null;
		$hORM->read($_GET['destination_node'],$destination_node);

		$hWorkflowAction = new WorkflowAction();

		$id_list = array();

// 		//--- Vérification des contraintes du link
// 		if (isset(ORM::getObjectInstance('nodelink')->constraints))
// 		{
// 			ORM::getORMInstance('nodelink')->browse(
// 				$link_list,
// 				$num_link,
// 				array('constraints', 'constraints_qualification'),
// 				array(
// 					array('input_node',  '=', $_GET['origin_node']),
// 					array('output_node', '=', $_GET['destination_node'])
// 				)
// 			);
// 			$link = reset($link_list);
// 			if ((isset($link['constraints']) && !empty($link['constraints']['value'])) ||
// 				$link['constraints_qualification']['value'] === '1')
// 			{
// 				if (!self::checkConstraints($origin_node['object']['value'], $_POST['listing_selection'], $link['constraints']['value'], $link['constraints_qualification']['value']))
// 				{
// 					return FALSE;
// 				}
// 			}
// 		}
		
		foreach($_POST['listing_selection'] as $id=>$object_id)
		{
			if(isset($_POST['comment'])){
				$id_list[$object_id] = array('id'=>$object_id,
											 'commentaire'	  => $_POST['comment/'.$_POST['crypt/listing_selection'][$id]],
											 'qualification_id' => $_POST['qualification_id/'.$_POST['crypt/listing_selection'][$id]]
									   );
			}
			else{
				$id_list[$object_id] = array('id'=>$object_id,
											 'commentaire'	  => NULL,
											 'qualification_id' => NULL
									   );
			}
		}

		if(!$hWorkflowAction->moveTokenTo($id_list,
									  $origin_node['workflow_name']['value'],
									  $origin_node['node_name']['value'],
									  $destination_node['workflow_name']['value'],
									  $destination_node['node_name']['value']))
									  {
										/* Alerte par défaut en cas de non déplacement. */
										if (!isset($_SESSION['_ERROR_LIST']) && !Alert::containsErrors())
										{
											Alert::error('Déplacement impossible', 'Une contrainte n\'est pas respectée.');
										}
										UI::quitNBack();
									  }

		if(empty($_SESSION['_MESSAGE_LIST']) && !Alert::containsSuccess())
		{
			Alert::success('Déplacement effectué !', count($id_list) . ' élément(s) déplacé(s)');
		}

		UI::goBackWithoutBrutalExit();

		return TRUE;
	}

	/**
	 * Retrieve Get parameters and send them to WorkflowAction::moveTokenTo
	 * @return boolean
	 */
	public function moveTokenToNodeName()
	{
		$source = explode('.', $_GET['source']);
		$destination = explode('.', $_GET['destination']);
		$comment = (isset($_GET['comment'])?$_GET['comment']:'');
		$hWorkflowAction = new WorkflowAction();

		$id = array($_GET['primary_key']=>array('id' => $_GET['primary_key'],'commentaire'=>$comment));
		$hWorkflowAction->moveTokenTo(
									$id,
									$source[0],
									$source[1],
									$destination[0],
									$destination[1]);

		UI::goBackWithoutBrutalExit();

		return TRUE;
	}


	//.....................................................................
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		parent::edit($view,$data,$total_object_list,$template_name);

		if ($view=='form')
		{
			$workflow = $data['workflow'][key($data['workflow'])];

			Security::crypt($workflow['workflow_id']['value'],$crypt_workflow_id);

			$data['title'][0]['subtitle']['value'] = 'Workflow : '.$workflow['nom']['value'];

			$data['graphviz_schema'] = './index.php?action=workflow.displayschema&token='.$_SESSION['_TOKEN'].'&crypt/workflow_id='.$crypt_workflow_id;
		}
		else
		{
			$data['title'][0]['subtitle']['value'] = 'Gestion des Workflow';
		}

		return TRUE;
	}
	//.....................................................................
	public function displaySchema()
	{
		$this->processing($_GET['workflow_id']);

		//---Get workflow
		ORM::getORMInstance('workflow')->read($_GET['workflow_id'],$workflow);

		$node_list = array();
		ORM::getORMInstance('node')->browse($node_list,$num_node,NULL,array(array('workflow_id','=',$workflow['workflow_id']['value']), array('obsolete', '=', 0)), array( 'ordre' ) );

		$cluster_list = array();

		$file_content = "digraph Workflow {\n";
		$file_content.="	graph [];\n";
		$file_content.="	rankdir = \"TB\";\n";
		$file_content.="	ranksep = \"0.8\";\n";

		//---Process node
		$node_id_list = array_keys($node_list);

		if (!empty($node_id_list))
		{
			$query =	'select '.
							'node_id, '.
							'Group_Concat(Distinct(nom)) as profil_list '.
						'From '.
							'killi_node_rights, '.
							'killi_profil '.
						'Where '.
							'(node_id In ('.implode(',', $node_id_list).')) And '.
							'(killi_profil.killi_profil_id = killi_node_rights.profil_id) And '.
							'(killi_node_rights.allow = 1) '.
						'Group By node_id';
			$profil_node_list = array();
			$this->_hDB->db_select($query,$result,$num);
			while ($row = $result->fetch_assoc())
			{
				$profil_node_list[$row['node_id']] = $row['profil_list'];
			}
			$result->free();
		}

		foreach($node_list as $node_id=>$node)
		{
			//---On recup les droits
			$profil_list = (isset($profil_node_list[$node_id]))? $profil_node_list[$node_id] : '';
			$shape_type='box';

			switch($node['type_id']['value'])
			{
				//---Interface
				case(1):
				case(2):

					//---On recup la population du node
					ORM::getORMInstance('workflowtoken')->count($num_token,array(array('node_id', '=', $node_id)));

					//---Si noeud type erreur pointe sur le node
					if ($num_token == 0)
					{
						$bgcolor = '#777777';
						$color   = '#EEEEEE';
					}
					else
					{
						$bgcolor = '#CCCCCC';
						$color   = '#000000';
					}

					//---On recup tous les links
					$node_link_list = array();
					ORM::getORMInstance('nodelink')->browse($node_link_list, $num_link,array('traitement_echec'),array(array('hidden','=','0'),array('traitement_echec','=','1'),array('output_node','=',$node_id), array('input_node','in',array_keys($node_list))));
					$isError = (count($node_link_list) > 0);
					if ($isError && $num_token == 0)
					{
						$bgcolor = '#770000';
						$color   = '#EEEEEE';
					}
					elseif ($isError)
					{
						$bgcolor = '#CC0000';
						$color   = '#000000';
					}

					$shape_type='[shape=none,fontsize=10]';

					$label =	'<font face="sans-serif"><table BORDER="0" CELLBORDER="1" CELLSPACING="0" CELLPADDING="4">'.
									'<tr><td bgcolor="'.$bgcolor.'"><font color="'.$color.'">'.$node['etat']['value'].' ('.$node['object']['value'].')</font></td></tr>'.
									'<tr><td bgcolor="#FFFFFF">Quantité = '.$num_token.'</td></tr>'.
								'</table></font>';

					Security::crypt($node_id,$crypt_node_id);

					$url = "./index.php?action=".$node['object']['value'].".edit&amp;crypt/workflow_node_id=$crypt_node_id&amp;token=".$_SESSION['_TOKEN'];
					$tip = "Profils : $profil_list [$node_id] [".$node['ordre']['value']."]";

					break;

				//---Pt entree
				case(3):
					$shape_type='[shape=invhouse, color=red, fontsize=10]';
					$label=$node['etat']['value'];
					$url = "";
					$tip = '';
					break;

				//---Fin
				case(4):
					
					//---On recup la population du node
					ORM::getORMInstance('workflowtoken')->count($num_token,array(array('node_id', '=', $node_id)));

					if ($node['commande']['value']=="")
					{
						$shape_type='[shape=none,fontsize=10]';

						$label =	'<font face="sans-serif"><table border="0" cellborder="1" cellspacing="0" cellpadding="4">'.
										'<tr><td bgcolor="#C5FFC2">'.$node['etat']['value'].' ('.$node['type_id']['reference'].')</td></tr>'.
										'<tr><td>Quantité = '.$num_token.'</td></tr>'.
									'</table></font>';

						Security::crypt($node_id,$crypt_node_id);
						$url = "./index.php?action=".$node['object']['value'].".edit&amp;crypt/workflow_node_id=$crypt_node_id&amp;token=".$_SESSION['_TOKEN'];

						$tip = '';
					}

					break;

				//---Quantity
				case(5):
					$label = '';
					$url = '';
					$tip = '';
					if(!empty($node['commande']['value']))
					{
						//---On recup la quantité
						$raw = explode('::',$node['commande']['value']);
						$class  = $raw[0] . 'Method';
						$method = $raw[1];

						$hInstance = new $class();
						$hInstance->$method($quantity);

						$shape_type='[shape=none,fontsize=10]';

						$label="<table BORDER=\"0\" CELLBORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"4\">";
						$label.="<tr><td bgcolor=\"#DDDDFF\">".$node['etat']['value']." (".$node['type_id']['reference'].")</td></tr>";
						$label.="<tr><td>Quantité = $quantity</td></tr>";
						$label.="</table>";
					}
					break;

				//---Message
				case(6):
						$shape_type='[shape=cube, color=green,fontsize=10]';
						$label=$node['etat']['value'];
						$url = "";
						$tip = '';

					break;
			}

			//$file_content.="  node_$node_id [pin=true] $shape_type [tooltip=\"".$tip."\",fontsize=14,target=\"blank\",URL=\"$url\" ,label=<$label>;\n";

			$cluster_list[(int)$node['killi_workflow_node_group_id']['value']][] = "			node_$node_id [pin=true] $shape_type [tooltip=\"".$tip."\",fontsize=14,target=\"blank\",URL=\"$url\" ,label=<".$label.">];\n";
			//$cluster_list[0][] = "			node_$node_id [pin=true] $shape_type [tooltip=\"".$tip."\",fontsize=14,target=\"blank\",URL=\"$url\" ,label=<".$label.">];\n";
		}
		foreach($cluster_list as $cluster_id=>$cluster)
		{
			$label = '';
			$color = 'white';
			//---On recup le node group
			if ($cluster_id!=0)
			{
				$c_list = array();
				ORM::getORMInstance('nodegroup')->read(array($cluster_id),$c_list);
				$c = current($c_list);
				$label = $c['name']['value'];
				$color = $c['color']['value'];
			}

			$file_content .=	"		subgraph cluster_$cluster_id {\n".
								"			style=filled;\n".
								"			color=".$color.";\n".
								"			fontname=\"sans-serif\";\n".
								"			fontsize=25;\n".
								"			border=3;\n".
								"			label=\"".$label."\";\n";

			foreach($cluster as $node)
			{
				$file_content.= $node;
			}
			$file_content.="		}\n\n";
		}

		//---Process link
		$file_content.="	node [style=filled,color=white];\n";

		//---On recup tous les links
		$link_list = array();
		ORM::getORMInstance('nodelink')->browse($link_list, $num_link,NULL,array(array('hidden','=','0'),array('output_node','in',$node_id_list),array('input_node','in',$node_id_list)));

		$link_count_id_list = array();
		$output_nodes	   = array();

		foreach ($link_list as $link_id => $link)
		{
			if (empty($link['label']['value']))
			{
				$link_count_id_list[] = $link_id;
				$output_nodes[] = $link['output_node']['value'];
			}
		}

		$link_count_list = array();
		if (!empty($output_nodes))
		{
			$sql =	'Select L.link_id, Count(*) As num '.
					'From killi_workflow_token_log KWTL '.
					'Inner Join killi_node_link L On '.
						'KWTL.from_node_id = L.input_node And '.
						'KWTL.to_node_id = L.output_node '.
					'Where '.
						'L.link_id in ('.implode(',', $link_count_id_list).') '.
					'Group By L.link_id';
			$this->_hDB->db_select($sql,$result,$num);
			while ($row = $result->fetch_assoc())
			{
				$link_count_list[$row['link_id']] = $row['num'];
			}
			$result->free();
		}

		foreach($link_list as $link_id => $link)
		{
			$label = '';

			if (!empty($link['label']['value']))
			{
				$syle		= ($link['deplacement_manuel']['value']==1) ? 'dashed' : 'solid' ;
				$echec_color = ($link['traitement_echec']['value']==1) ? 'red' : 'black' ;

				$label="[color=".$echec_color.",style=".$syle.",label=\"".$link['label']['value']."\"]";
			}
			else //--- Qty
			{
				$qty = (isset($link_count_list[$link_id]))? $link_count_list[$link_id] : '0';

				$domain = array(array('to_node_id','=',$link['output_node']['value']),
								array('from_node_id','=',$link['input_node']['value']));

				Security::crypt(serialize($domain),$crypt_domain);

				//---Calculate AVG time
				$average = '';
				if ($link['display_average']['value']==1)
				{
					$query = 'select killi_workflow_token_log.workflow_token_id,killi_workflow_token_log.to_node_id,UNIX_TIMESTAMP(killi_workflow_token_log.date) as date from killi_workflow_token_log,killi_workflow_token_log as src where (killi_workflow_token_log.workflow_token_id=src.workflow_token_id) and (src.to_node_id='.$link['input_node']['value'].') order by killi_workflow_token_log.workflow_token_id,killi_workflow_token_log.date';
					$this->_hDB->db_select($query,$result,$num);

					$date_table = array();
					$previous_token_id=0;
					for ($i=0;$i<$num;$i++)
					{
						$row = $result->fetch_assoc();

						//---Si changement de token_id
						if ($row['workflow_token_id']!=$previous_token_id)
						{
							$previous_token_id = $row['workflow_token_id'];
							$previous_node_id  = $row['to_node_id'];
							$previous_date	 = $row['date'];
							continue;
						}
						else
						{
							if (($previous_node_id==$link['input_node']['value']) && ($row['to_node_id']==$link['output_node']['value']))
							{
								$date_table[] = array('from_date'=>$previous_date,'to_date'=>$row['date']);
							}

							$previous_node_id  = $row['to_node_id'];
						}
					}
					$result->free();

					//---On calcule la moyenne
					$sum = 0;
					foreach($date_table as $date_data)
					{
						$sum = $sum+$date_data['to_date'] - $date_data['from_date'];
					}

					if (count($date_table)>0)
						$average =  round(($sum/(60*60*24)) / count($date_table));
				}

				$syle		= ($link['deplacement_manuel']['value']==1) ? 'dashed' : 'solid' ;
				$echec_color = ($link['traitement_echec']['value']==1) ? 'red' : 'black' ;

				if (($link['display_average']['value']==1) && ($average!=''))
				{
					$label = "[color=".$echec_color.",style=".$syle.",penwidth=1,fontsize=8,target=\"blank\",labelURL=\"./index.php?action=workflowtokenlog.edit&amp;crypt/domain=".$crypt_domain."&amp;token=".$_SESSION['_TOKEN']."\",label=\"".$qty." (~$average j)\"]";
				}
				else
				{
					$label = "[color=".$echec_color.",style=".$syle.",penwidth=1,fontsize=8,target=\"blank\",labelURL=\"./index.php?action=workflowtokenlog.edit&amp;crypt/domain=".$crypt_domain."&amp;token=".$_SESSION['_TOKEN']."\",label=\"".$qty."\"]";
				}
			}

			$file_content .= "	node_".$link['input_node']['value']." -> node_".$link['output_node']['value']." $label;\n";
		}

		$file_content .=	"	overlap=false\n".
							"	fontsize=10;\n".
							"}\n";

		//---Files name
		$tmp_name='workflow_schema_'.uniqid();
		$txt='/tmp/'.$tmp_name.'.txt';
		$svg='/tmp/'.$tmp_name.'.svg';

		//---Tmp file
		file_put_contents($txt,$file_content);

		//---Generate schema
		system('dot -Tsvg -o '.$svg.' '.$txt);

		//---Display schema

		// @codeCoverageIgnoreStart
		if(!KILLI_SCRIPT)
		{
			header('Content-Type: image/svg+xml');
			echo file_get_contents($svg);
		}
		// @codeCoverageIgnoreEnd

		//---Cleanup
		system('rm '.$txt);
		system('rm '.$svg);

		return TRUE;
	}
	//.....................................................................
	public function processing($workflow_id)
	{
		//---On recup les noeuds
		$hORM = ORM::getORMInstance('node');
		$node_list = array();
		$hORM->browse($node_list,$num_node,array('commande','type_id'),array(array('workflow_id','=',$workflow_id)));

		//---Process each node
		foreach($node_list as $node_id=>$node)
		{
			if (empty($node['commande']['value']))
			{
				continue;
			}

			//---Stat point or command type
			if (!(($node['type_id']['value']==3) || ($node['type_id']['value']==2)))
			{
				continue;
			}

			$raw_commande = explode('::',$node['commande']['value']);

			$class  = $raw_commande[0].'Method';
			$method = $raw_commande[1];

			$hInstance = new $class();
			$hInstance->$method();
		}

		return TRUE;
	}
	//.....................................................................
	public function node_interface()
	{
		//---Get node
		$node = null;
		ORM::getORMInstance('node')->read($_GET['node_id'],$node);

		Security::crypt($_GET['node_id'],$crypt_node_id);

		header("Location: ./index.php?action=".$node['object']['value'].".edit&crypt/workflow_node_id=$crypt_node_id&token=".$_SESSION['_TOKEN']);

		return TRUE;
	}
}
