<?php

/**
 *  @class HeaderXMLNode
 *  @Revision $Revision: 4688 $
 *
 */

class HeaderXMLNode extends XMLNode
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

		//---Si dans un popup
		if (isset($_GET['input_name']) || (isset($_GET['inside_popup']) && $_GET['inside_popup'] == 1))
		{
			return FALSE;
		}

		return TRUE;
	}
	//.....................................................................
	public function open()
	{
		if (isset($_SESSION['_USER']))
		{
			?><table class="header ui-widget-header ui-state-hover"><?php
				?><tr><?php
					?><td><?php

					?><span class="header-bloc header-message" style='padding:5px'><?php echo HEADER_MESSAGE; ?></span><?php

					if(HEADER_DESCRIPTION!=='')
					{
						?><span class="header-separator" style='padding:5px'>&#9632;</span><?php
						?><span class="header-bloc header-description" style='font-style : italic;font-size: 11px;'><?= HEADER_DESCRIPTION ?></span><?php
					}

					if(DISPLAY_ERRORS)
					{
						?><span class="header-separator" style="padding:5px">&#9632;</span><?php
						?><span class="header-bloc red_head">Mode développement</span><?php
						
						?><span class="header-separator" style="padding:5px">&#9632;</span><?php
						?><span class="header-bloc red_head">KILLI <?= KILLI_VERSION ?></span><?php
					}

					?></td><?php
					?><td style="text-align:right;"><?php
					?><span class="header-bloc header-username" style='font-weight:bold'><?php

					if(array_key_exists('prenom', $_SESSION['_USER']))
					{
						echo $_SESSION['_USER']['prenom']['value'].' '.$_SESSION['_USER']['nom']['value'].' ('.$_SESSION['_USER']['login']['value'].')';
					}
					else
					{
						echo $_SESSION['_USER']['login']['value'];
					}

					?> on <?php echo RIGHTS_DATABASE; ?></span><?php

					if ((isset($_SESSION['_USER']['original_profil_list']) &&
						(count($_SESSION['_USER']['original_profil_list']) > 1 ||
						(in_array(ADMIN_PROFIL_ID, $_SESSION['_USER']['original_profil_list'])))) ||
						count($_SESSION['_USER']['profil_id']['value']) > 1 ||
						(in_array(ADMIN_PROFIL_ID, $_SESSION['_USER']['profil_id']['value']))):

					?><span class="header-separator" style='padding:5px'>&#9632;</span><?php

					global $hDB;
					//--- Détermination de la liste originale des profils
					$profil_list	= array();
					$profil_id_list = $_SESSION['_USER']['profil_id']['value'];
					if (isset($_SESSION['_USER']['original_profil_list']))
					{
						//--- Cette valeur est créée à partir du moment où l'utilisateur
						//--- commence à changer de profil.
						$profil_id_list = $_SESSION['_USER']['original_profil_list'];
					}
					//--- Le domaine du profil ne doit pas être utilisé ici
					//--- (pour les profils bas level n'ayant pas accès à un éventuel profil haut level).
					$sql =  'Select '.
								'killi_profil_id, '.
								'nom '.
							'From killi_profil ';
					//--- Les admins ont accès à tous les profils
					if (!in_array(ADMIN_PROFIL_ID, $profil_id_list))
					{
						$sql .= 'Where killi_profil_id In ('.implode(',', $profil_id_list).') ';
					}
					$sql .= 'Order By nom ';
					$hDB->db_select($sql, $result);
					while (($row = $result->fetch_assoc()))
					{
						$profil_list[$row['killi_profil_id']] = $row['nom'];
					}
					$result->free();
					//--- Si plus d'un profil courant, cela veut dire que l'on a réinitialisé le sélecteur.
					$all_profiles = (count($_SESSION['_USER']['profil_id']['value']) > 1);
					//--- Un profil courant, cette valeur ne prend son sens que si l'user a changé de profil.
					$current_profil = reset($_SESSION['_USER']['profil_id']['value']);

					?><form class="header-bloc header-profile-selector" style='display:inline-block' name="profile_selector" action="./index.php?action=user.select_profil&token=<?php echo $_SESSION['_TOKEN']; ?>" method="post"><?php
						?><select onchange="document.profile_selector.submit();" style="width:200px;" name="profil_id"><?php
							?><option value="*"<?php if ($all_profiles): echo ' selected'; endif; ?>>Tous les profils</option><?php

							// Pour les admins avec plusieurs profils, nous afficherons les profils qu'ils ne possèdent pas en gris.

							foreach ($profil_list as $profile_id => $profile):

							?><option value="<?php echo $profile_id; ?>"<?php if (!$all_profiles && $current_profil == $profile_id): echo ' selected'; endif; if(!in_array($profile_id, $profil_id_list)): echo ' style="background-color:#ccc;"'; endif;?>><?php echo $profile; ?></option><?php

							endforeach;
						?></select></form><?php

					endif;

					?><span class="header-separator" style='padding:5px'>&#9632;</span><?php

					try
					{
						$notification_list = array();
						ORM::getORMInstance('notificationuser')->search($notification_list, $total, array(array('killi_user_id','=', $_SESSION['_USER']['killi_user_id']['value']),array('killi_notification_read','=',0)));

						if(!empty($notification_list))
						{
							?><a class='blink' href='./index.php?action=notification.edit&token=<?php echo $_SESSION['_TOKEN']; ?>'><?= count($notification_list) ?> message<?= count($notification_list)>1?'s':'' ?> non lu<?= count($notification_list)>1?'s':'' ?></a><?php
						}
						else
						{
							?><a class="header-bloc header-notif" style='text-decoration:underline' href='./index.php?action=notification.edit&token=<?php echo $_SESSION['_TOKEN']; ?>'>Messagerie</a><?php
						}
					}
					catch (Exception $e) {}

					?><span class="header-separator" style='padding:5px'>&#9632;</span><?php
						?><a class="header-bloc header-account" style="text-decoration:underline;" href="./index.php?action=userpreferences.edit&token=<?php echo $_SESSION['_TOKEN']; ?>">Compte</a><?php
					?><span class="header-separator" style='padding:5px'>&#9632;</span><?php

						$redirect='';
						if($_SERVER['QUERY_STRING']!='')
						{
							$redirect='&redirect_url='.urlencode(preg_replace('/&token=([^&]+)/', '', $_SERVER['QUERY_STRING']));
						}


						?><a class="header-bloc header-logout" style='padding-right:5px;font-weight:bold;text-decoration:underline' href="./index.php?action=user.disconnect&token=<?php echo $_SESSION['_TOKEN'].$redirect; ?>">Déconnexion</a><?php
					
						if(defined('USE_ORIGINAL_USER') && USE_ORIGINAL_USER && isset($_SESSION['_USER']) && isset($_SESSION['_ORIGINAL_USER']) && $_SESSION['_USER']['killi_user_id']['value'] != $_SESSION['_ORIGINAL_USER']['killi_user_id']['value'])
						{
							?><span class="header-separator" style='padding:5px'>&#9632;</span><?php
							?><a class="header-bloc header-originaluser" style="text-decoration:underline;" href="./index.php?action=user.backtooriginaluser&token=<?php echo $_SESSION['_TOKEN']; ?>">Revenir en tant que <?php echo $_SESSION['_ORIGINAL_USER']['login']['value'] ?></a><?php
						}
						
					?></td></tr></table><?php
		}
		else
		{

			?><table class="header ui-widget-header ui-state-hover"><?php
				?><tr><?php
					?><td><?php

			echo HEADER_MESSAGE;

   			if(HEADER_DESCRIPTION!=='')
			{
				?><span class="header-separator" style="padding:5px">&#9632;</span><?php
				?><span style='font-style : italic;font-size: 11px;'><?= HEADER_DESCRIPTION ?></span><?php
			}

			if(DISPLAY_ERRORS)
			{
				?><span class="header-separator" style="padding:5px">&#9632;</span><?php
				?><span style="color:red">Mode développement activé</span><?php

				if (file_exists('./.svn/entries'))
				{
					$svn = File('./.svn/entries');

					if(isset($svn[3]))
					{
						?><span class="header-separator" style="padding:5px">&#9632;</span><?php
						?><span style="color:red">Applicatif r<?= $svn[3] ?></span><?php
					}
				}

				if (file_exists(KILLI_DIR . '/.svn/entries'))
				{
					$svn = File(KILLI_DIR . '/.svn/entries');

					if(isset($svn[3]))
					{
						?><span class="header-separator" style="padding:5px">&#9632;</span><?php
						?><span style="color:red">Killi r<?= $svn[3] ?></span><?php
					}
				}
			}

			?></td></tr></table><?php
		}
	}
}
