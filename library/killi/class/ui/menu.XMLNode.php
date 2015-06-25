<?php

/**
 *  @class MenuXMLNode
 *  @Revision $Revision: 4583 $
 *
 */

class MenuXMLNode extends XMLNode
{
	public function check_render_condition()
	{
		if(!parent::check_render_condition())
		{
			return FALSE;
		}
		
		//---Si mode create
		if (isset($_GET['view']) && $_GET['view']=='create' && (!isset($_GET['inside_popup']) || (isset($_GET['inside_popup']) && $_GET['inside_popup'] == 1)))
		{
			return FALSE;
		}

		//---SI dans un popup
		if (isset($_GET['input_name']) || (isset($_GET['inside_popup']) && $_GET['inside_popup'] == 1))
		{
			return FALSE;
		}
		
		return TRUE;
	}
	//.....................................................................
	public function open()
	{
		$menu_list = array();
		self::getActionmenuListFromProfilID($menu_list);

		?><div id='main_menu' class="kmenu"><?php
			?><ul class='ui-menubar ui-widget-header ui-helper-clearfix'><?php
			
				// bouton home
				?><li class='ui-menubar-item'><a style='cursor:pointer !important' href='index.php?action=<?= HOME_PAGE ?>&amp;token=<?= $_SESSION['_TOKEN'] ?>' class='menu_home_button ui-button ui-widget ui-button-text-only ui-menubar-link'><span class="ui-button-text"></span></a></li><?php

				self::renderRecursiveMenu($menu_list);

			?></ul><?php
		?></div><?php
		
		?><script>
			$(document).ready(function(){
				$('.kmenu > ul > li').hover(function()
				{
					$(this).find('> a').addClass('ui-state-hover');
				},
				function ()
				{
					$(this).find('> a').removeClass("ui-state-hover");
			    });

				$('.kmenu_sub_menu > li').hover(function()
				{
					$(this).find('> a').addClass('ui-state-focus');
				},
				function ()
				{
					$(this).find('> a').removeClass("ui-state-focus");
				});
			});
		</script><?php
	}
	//.....................................................................
	private static function getActionmenuListFromProfilID(array &$my_menu_list)
	{
		if (!isset($_SESSION['_USER']) || empty($_SESSION['_USER']['profil_id']['value']))
		{
			return;
		}

		$read_only_request='';
		if(READONLY_PROFIL_ID!==NULL && in_array(READONLY_PROFIL_ID,$_SESSION['_USER']['profil_id']['value']))
		{
			$read_only_request=' or kmr.view is null';
		}

		global $hDB;

		if (in_array(ADMIN_PROFIL_ID,$_SESSION['_USER']['profil_id']['value']))
		{
			$query = "select * from ".RIGHTS_DATABASE.".killi_actionmenu order by actionmenu_parent, actionmenu_label";
		}
		else
		{
			$query = "select km.* FROM ".RIGHTS_DATABASE.".killi_actionmenu km left join
				".RIGHTS_DATABASE.".killi_actionmenu_rights kmr on km.actionmenu_id=kmr.actionmenu_id
				where ((profil_id in (".join(',',$_SESSION['_USER']['profil_id']['value']).") and kmr.view=1)".$read_only_request.") or km.actionmenu_name is null group by km.actionmenu_id order by actionmenu_parent, actionmenu_label";
		}

		$hDB->db_select($query, $result);

		while($row = $result->fetch_assoc())
		{
			self::buildMenu($my_menu_list, array(
				'name'=>substr($row['actionmenu_function'],0,11)=='javascript:' ? 'javascript' : $row['actionmenu_name'],
				'label'=>$row['actionmenu_label'],
				'action'=>$row['actionmenu_function'],
				'children'=>array()
			), $row['actionmenu_id'], $row['actionmenu_parent']);
		}
		$result->free();

		ksort($my_menu_list); // menu pincipaux triés par id, pas par label

		// compte le nombre d'enfant
		self::indexMenu($my_menu_list);

		// cleanup des menus sans enfants
		self::cleanupMenu($my_menu_list);
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
	//.....................................................................
	private static function indexMenu(&$menu_list)
	{
		$count=0;

		foreach($menu_list as &$menu)
		{
			if($menu['name'])
			{
				$count++;
			}

			$count+=$menu['count']=self::indexMenu($menu['children']);
		}

		return $count;
	}
	//.....................................................................
	private static function cleanupMenu(&$menu_list)
	{
		foreach($menu_list as $id=>&$menu)
		{
			if(!$menu['name'] && $menu['count']==0)
			{
				unset($menu_list[$id]);
				continue;
			}

			self::cleanupMenu($menu['children']);
		}
	}
	//.....................................................................
	private static function renderRecursiveMenu($menu_list, $sub = false)
	{
		foreach($menu_list as $menu)
		{
			if(empty($menu['children']) && !$menu['name'])
			{
				continue;
			}

			?><li class='<?= (!$sub ? 'ui-menubar-item' : 'ui-menu-item') ?> kmenu_child'><?php
			
			$href = NULL;
			if(substr($menu['action'],0,11)=='javascript:')
			{
				// lien javascript
				$href = $menu['action'];
			}
			else if($menu['name'])
			{
				$href = './index.php?action='.$menu['name'] . '.' . $menu['action'].'&amp;token='.$_SESSION['_TOKEN'];
			}

			?><a class='<?= (!$sub ? 'ui-button ui-widget ui-button-text-only ui-menubar-link' : 'ui-corner-all') ?>' <?= ($href ? 'style="cursor:pointer !important" href="'.$href.'"':'') ?>"><?php

			if(!$sub)
			{
				// label des menus en tête
				?><span class="ui-button-text"><?php
			}
			
			if(!empty($menu['children']) && $sub)
			{
				// fleche du sous-menu
				?><span class="ui-menu-icon ui-icon ui-icon-carat-1-e"></span><?php
			}
			
			echo $menu['label'];
			
			if(!$sub)
			{
				?></span><?php
			}
			
			?></a><?php

			// recursif
			if(!empty($menu['children']))
			{
				?><ul class='ui-menu ui-widget ui-widget-content ui-corner-all kmenu_sub_menu'><?php
					self::renderRecursiveMenu($menu['children'], TRUE);
				?></ul><?php
			}
			?></li><?php
		}
	}
}
