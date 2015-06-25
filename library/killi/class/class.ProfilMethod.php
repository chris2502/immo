<?php

function sortWFNodes($a, $b)
{
	$cmp = strcasecmp($a['workflow_id']['reference'], $b['workflow_id']['reference']);
	if ($cmp != 0)
	{
		return ($cmp > 0);
	}
	else
	{
		$cmp = strcasecmp($a['etat']['value'], $b['etat']['value']);
		return (($cmp == 0)? 0 : ($cmp > 0));
	}
}
function objectListSort($a, $b)
{
	$cmp = strcasecmp(Autoload::getClassNamespace(ORM::$_objects[$a]['className']), Autoload::getClassNamespace(ORM::$_objects[$b]['className']));
	if ($cmp != 0)
	{
		return ($cmp > 0);
	}
	else
	{
		$ca = ORM::$_objects[$a]['className'];
		$cb = ORM::$_objects[$b]['className'];
		if ($ca == $cb)
		{
			return 0;
		}
		return ((strcasecmp($ca, $cb) < 0)? -1 : 1);
	}
}

abstract class KilliProfilMethod extends Common
{
	public function getObjectList(&$datasrc)
	{
		// droits du profil
		Rights::getCreateDeleteRightsFromProfilIDByObject(array($_GET['primary_key']), $object_rights);

		// liste des objets
		$object_list = ORM::getDeclaredObjectsList();
		usort($object_list, 'objectListSort');

		foreach($object_list as $object_name)
		{
			if(!ORM::$_objects[$object_name]['rights'])
			{
				continue;
			}

			$mobject = array();

			$ns = Autoload::getClassNamespace($object_name);
			
			$instance = ORM::getObjectInstance($object_name, false);

			$mobject['nom']['value']	 = ORM::$_objects[$object_name]['className'];
			$mobject['nom']['reference'] = $ns != null ? $ns.' / '. substr(ORM::$_objects[$object_name]['className'],strlen($ns)) : ORM::$_objects[$object_name]['className'];
			$mobject['nom']['html']	     = $mobject['nom']['reference'] . (isset($instance->json) ? ' <img title = "Objet distant, certains droits sont verrouillés." class="tooltip_link" src="'.KILLI_DIR.'images/network.png" style="height:16px;width:16px;vertical-align: sub;"/>' : '');
			$mobject['create']['value']  = $object_rights[$object_name]['create'];
			$mobject['delete']['value']  = $object_rights[$object_name]['delete'];
			$mobject['view']['value']    = $object_rights[$object_name]['view'];

			$mobject['create']['editable'] = Rights::rightIsEditable($object_name,'create');
			$mobject['delete']['editable'] = Rights::rightIsEditable($object_name,'delete');
			$mobject['view']['editable']   = Rights::rightIsEditable($object_name,'view');

			// les admins voient tout
			if ($_GET['primary_key']==ADMIN_PROFIL_ID)
			{
				$mobject['view']['editable'] = FALSE;
			}

			// le profil lecture ne modifie rien
			if(READONLY_PROFIL_ID!==NULL && READONLY_PROFIL_ID==$_GET['primary_key'])
			{
				$mobject['create']['editable'] = FALSE;
				$mobject['delete']['editable'] = FALSE;
			}

			$datasrc[] = $mobject;
		}
	}
	//.....................................................................
	public function getMenuList(&$datasrc)
	{
		ORM::getORMInstance('actionmenu')->browse($actionmenu_list,$total_record,array('actionmenu_parent','actionmenu_label','actionmenu_name','actionmenu_function'),null,array('actionmenu_parent','actionmenu_label'));

		// arborescence
		$my_menu_list=array();
		foreach($actionmenu_list as $actionmenu)
		{
			self::buildMenu($my_menu_list, array(
				'name'=>$actionmenu['actionmenu_name']['value'],
				'label'=>$actionmenu['actionmenu_label']['value'],
				'action'=>$actionmenu['actionmenu_function']['value'],
				'children'=>array(),
				'parent'=>$actionmenu['actionmenu_parent']['value']
			), $actionmenu['actionmenu_id']['value'], $actionmenu['actionmenu_parent']['value']);
		}

		// template liste
		self::buildMenuList($datasrc,$my_menu_list,$my_menu_list);

		// pas admin, donc calcul des droits
		if (ADMIN_PROFIL_ID!=$_GET['primary_key'])
		{
			$this->_hDB->db_select('select actionmenu_id, killi_actionmenu_rights.view from killi_actionmenu_rights where profil_id='.$_GET['primary_key'],$result);

			$actionmenu_rights_list = array();
			while($row=$result->fetch_array())
			{
				$actionmenu_rights_list[$row['actionmenu_id']] = ($row['view']==1); // bool
			}
			$result->free();

			foreach($datasrc as &$menu)
			{
				$menu['view']['value']	= ((READONLY_PROFIL_ID!==NULL && $_GET['primary_key']==READONLY_PROFIL_ID && !isset($actionmenu_rights_list[$menu['actionmenu_id']['value']])) || $menu['name']['value']==null || (isset($actionmenu_rights_list[$menu['actionmenu_id']['value']]) && $actionmenu_rights_list[$menu['actionmenu_id']['value']])); // bool
				$menu['view']['editable'] = $menu['name']['value']!=null;
			}
		}

	}
	//.....................................................................
	public function getUserList(&$datasrc, &$total, $limit, $offset)
	{
		ORM::getORMInstance('user',true)->browse($datasrc, $total, array('nom_complet', 'actif'), array(array('profil_id','=',$_GET['primary_key'])), null, $offset, $limit);
	}
	//.....................................................................
	public function getNodeList(&$datasrc)
	{
		ORM::getORMInstance('node')->browse($datasrc, $total);

		//---On recup les droits des noeuds
		$rights_list = array();
		$query = "select node_id,allow from killi_node_rights where profil_id=".$_GET['primary_key'];
		$this->_hDB->db_select($query, $result, $numrows);

		for ($i=0;$i<$numrows;$i++)
		{
			$row = $result->fetch_assoc();
			$rights_list[$row['node_id']] = $row['allow'];
		}
		$result->free();

		$not_editable_node_list = array();
		foreach ($datasrc as $node_id=>$node)
		{
			$new_node = array();
			foreach($node as $key=>$value)
			{
				$new_node[$key] = $value;
				$new_node[$key]['editable']	= False;
				$new_node['allow']['editable'] = True;
			}

			if (isset($rights_list[$node_id]))
			{
				$new_node['allow']['value'] = $rights_list[$node_id];
			}
			else
			{
				$new_node['allow']['value'] = False;
			}

			if ($_GET['primary_key']==ADMIN_PROFIL_ID)
			{
				$new_node['allow']['value'] = TRUE;
				$new_node['allow']['editable'] = FALSE;
			}

			$not_editable_node_list[$node_id] = $new_node;
		}

		usort($not_editable_node_list, 'sortWFNodes');
		$datasrc = $not_editable_node_list;
	}
	//.....................................................................
	public function getLinkList(&$datasrc)
	{
		$datasrc = array();
		ORM::getORMInstance('NodeLink')->browse($datasrc, $num_rows, array('link_id', 'input_node', 'output_node'));
		foreach ($datasrc as $id => $data)
		{
			$datasrc[$id]['move'] = array('value' => false, 'editable' => true);
		}

		$link_rights_list;
		ORM::getORMInstance('LinkRights')->browse($link_rights_list, $num_rows, array('move'), array(
			array('killi_profil_id', '=', $_GET['primary_key']),
			array('move', '=', 1)
		));
		foreach ($link_rights_list as $link_rights)
		{
			$datasrc[$link_rights['link_id']['value']]['move']['value'] = true;
		}
		return TRUE;
	}
	//.....................................................................
	public function write($data)
	{
		parent::write($data);

		foreach($data as $key=>$value)
		{
			$value = $value ? '1':'0';

			if (mb_substr($key,0,7)==="object/")
			{
				list($null, $attr, $object_name) = explode("/",$key);

				$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_objects_rights
						  set object_name=\"".Security::secure($object_name)."\",
						  profil_id=\"".Security::secure($data['killi_profil_id'])."\",
						  `$attr`=\"".$value."\"
						  on duplicate key update `$attr`=\"".$value."\"");
			}
			elseif(mb_substr($key,0,11)==="menurights/")
			{
				if($data['killi_profil_id']==ADMIN_PROFIL_ID)
				{
					continue;
				}

				list($null, $attr, $id) = explode("/",$key);

				$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_actionmenu_rights
						  set `$attr`=\"".$value."\",
						  actionmenu_id=\"".Security::secure($id)."\",
						  profil_id=\"".Security::secure($data['killi_profil_id'])."\"
						  on duplicate key update `$attr`=\"".$value."\"");
			}
			elseif(mb_substr($key,0,11)==="node/allow/")
			{
				if($data['killi_profil_id']==ADMIN_PROFIL_ID)
				{
					continue;
				}

				list($null, $null, $node_id) = explode("/",$key);

				$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_node_rights
						  set `node_id`=\"".Security::secure($node_id)."\",
						  allow=".$value.",
						  profil_id=\"".Security::secure($data['killi_profil_id'])."\"
						  on duplicate key update allow=".$value);
			}
			if (mb_substr($key,0,11)==="linkrights/")
			{
				$raw = explode("/",$key);
				if ($raw[1] == 'move')
				{
					$query = "INSERT INTO ".RIGHTS_DATABASE.".killi_link_rights
						  SET link_id=\"".Security::secure($raw[2])."\",
						  killi_profil_id=\"".Security::secure($data['killi_profil_id'])."\",
						  move=\"".Security::secure($value)."\"
						  ON DUPLICATE KEY UPDATE move = \"".Security::secure($value)."\"";
					$this->_hDB->db_execute($query, $affected_rows);
				}
			}
		}
		return TRUE;
	}
	//.....................................................................
	public function create($data,&$id,$ignore_duplicate=false)
	{
		$copy_profil_id= null;
		if(isset($data['copy_of']) && !empty($data['copy_of']))
		{
			$copy_profil_id = $data['copy_of'];

			unset($data['copy_of']);
		}

		parent::create($data,$id,$ignore_duplicate);

		if($copy_profil_id!=null)
		{
			$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_objects_rights (object_name, profil_id, `create`, `delete`, `view`)
						SELECT
							object_name,
							".$id." as profil_id,
							`create`,
							`delete`,
							`view`
						FROM killi_objects_rights
						WHERE killi_objects_rights.profil_id = ".$copy_profil_id);

			$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_actionmenu_rights (actionmenu_id, profil_id, `view`)
						SELECT
							actionmenu_id,
							".$id." as profil_id,
							`view`
						FROM killi_actionmenu_rights
						WHERE killi_actionmenu_rights.profil_id = ".$copy_profil_id);

			$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_node_rights (node_id, profil_id, `allow`)
						SELECT
							node_id,
							".$id." as profil_id,
							`allow`
						FROM killi_node_rights
						WHERE killi_node_rights.profil_id = ".$copy_profil_id);

			$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_attributes_rights (object_name, attribute_name, profil_id, `read`, `write`)
						SELECT
							object_name,
							attribute_name,
							".$id." as profil_id,
							`read`,
							`write`
						FROM killi_attributes_rights
						WHERE killi_attributes_rights.profil_id = ".$copy_profil_id);
		}

		return TRUE;
	}
	//.....................................................................
	//
	// PRIVATES METHODES
	//
	//.....................................................................
	private static function findParentMenu($item, $my_menu_list)
	{
		foreach($my_menu_list as $id=>$menu)
		{
			if($item['parent']==$id || ($menu = self::findParentMenu($item, $menu['children'])))
			{
				return $menu;
			}
		}
		return false;
	}
	//.....................................................................
	private static function getFilDAriane($item, $my_menu_list)
	{
		if($item['parent'])
		{
			$parent=self::findParentMenu($item, $my_menu_list);

			return self::getFilDAriane($parent, $my_menu_list).$parent['label'].' / ';
		}
	}
	//.....................................................................
	private static function buildMenuList(&$menu_list, $children, $my_menu_list)
	{
		foreach($children as $id_item=>$item)
		{
			$menu_list[]=array(
				'actionmenu_id'=>array(
					'value'=>$id_item
				),
				'nom'=>array(
					'value'=>self::getFilDAriane($item, $my_menu_list).$item['label']
				),
				'view'=>array(
					'value'=>true,
					'editable'=>false
				),
				'name'=>array(
					'value'=>$item['name']
				)
			);

			self::buildMenuList($menu_list, $item['children'], $my_menu_list);
		}
	}
	//.....................................................................
	private static function buildMenu(&$menu_list, $item, $id_item, $parent_item)
	{
		if(!$parent_item)
		{
			$menu_list[$id_item]=$item;
			return;
		}

		foreach($menu_list as $id=>&$menu)
		{
			if($parent_item==$id)
			{
				$menu['children'][$id_item]=$item;
				return;
			}

			self::buildMenu($menu['children'], $item, $id_item, $parent_item);
		}
	}
}
