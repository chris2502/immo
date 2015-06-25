<?php

/**
 *  @class ProcessTokenMethod
 *  @Revision $Revision: 3960 $
 *
 */

class KilliProcessTokenMethod extends Common
{
	public function edit($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		parent::edit($view,$data,$total_object_list,$template_name);

		if($view == 'form')
		{
			$pk = $_GET['primary_key'];
			$token = $data['processtoken'][$pk];

			/**
			 * Vérification du droit d'édition.
			 */

			$module_id = $token['module_id']['value'];
			$hORM = ORM::getORMInstance('processmodule');
			$module = array();
			$hORM->read($module_id, $module, array('visibility_user', 'visibility_profile', 'visibility_company'));

			/**
			 * Restriction la plus forte. Le process n'est visible que par l'utilisateur qui a le token.
			 */
			if($module['visibility_user']['value'])
			{
				if($token['killi_user_id']['value'] != $_SESSION['_USER']['killi_user_id']['value'])
				{
					Alert::warning('Ce process est destiné à ' . $token['killi_user_id']['reference'] . ' !', 'Vous ne pouvez pas éditer les informations présentes.');
				}
			}

			if($module['visibility_profile']['value'])
			{
				/**
				 * Si l'utilisateur a au moins un profil de l'autre.
				 */
				$user = array();
				$hORM = ORM::getORMInstance('user');
				$hORM->read($token['killi_user_id']['value'], $user, array('profil_id'));

				$rights = false;
				foreach($_SESSION['_USER']['profil_id']['value'] AS $p_id)
				{
					if(in_array($p_id, $user['profil_id']['value']))
					{
						$rights = true;
						break;
					}
				}

				if(!$rights)
				{
					$hORM = ORM::getORMInstance('profil');
					$profils = array();
					$hORM->read($user['profil_id']['value'], $profils, array('nom'));
					$profil_list = array();
					foreach($profils AS $p_id => $p)
					{
						$profil_list[$p_id] = $p['nom']['value'];
					}
					Alert::warning('Ce process est destiné à un profil particulier !', 'Vous devez disposer de l\'un des profils suivant pour pouvoir éditer les informations présentes : ' . join(', ', $profil_list));
				}
			}

			if($module['visibility_company']['value'])
			{
				$user = array();
				$hORM = ORM::getORMInstance('user');
				$hORM->read($token['killi_user_id']['value'], $user, array('entreprise_id'));
				if($user['entreprise_id']['value'] != $_SESSION['_USER']['entreprise_id']['value'])
				{
					Alert::warning('Ce process est destiné à un utilisateur d\'une autre entreprise !', 'Vous ne pouvez pas éditer les informations présentes.');
				}
			}
		}

		return TRUE;
	}

	/**
	 * Action éxécuter lors des actions utilisateurs sur le déroulement du process.
	 */
	public function write($data)
	{
		if(empty($_POST['token_id']))
		{
			UI::quitNBack();
		}

		$module = ModuleFactory::getModule($_POST['token_id']);

		global $hDB;
		$way = 0;
		if(isset($_POST['submit']))
		{
			if($_POST['submit'] == 'following')
			{
				if($module->onNext())
				{
					$hDB->db_commit();
				}
			}
			if($_POST['submit'] == 'preceding')
			{
				if($module->onPrev())
				{
					$hDB->db_commit();
				}
			}
			if($_POST['submit'] == 'terminate')
			{
				UI::quitNBack('processtoken.edit', TRUE);
			}
		}
		UI::quitNBack();
		return TRUE;
	}
}
