<?php

namespace Killi\Core\Application\Middleware;

/**
 *
 * @class  ObjectRightsMiddleware
 * @Revision $Revision: 4680 $
 *
 */
use \Closure;
use \Killi\Core\Application\Http\Request;

class ObjectRightsMiddleware extends AbstractMiddleware
{
	/**
	 * @inherit
	 */
	public function handle(Request $request, Closure $next)
	{
		if(defined('CAS_SERVER_URL'))
		{
			Rights::lockObjectRights('user','create',false);
			Rights::lockObjectRights('user','delete',false);
		}

		if(isset($_SESSION['_USER']) && empty($_SESSION['_USER']['profil_id']['value']) && (!isset($_GET['action']) || ($_GET['action'] != 'user.disconnect' && $_GET['action'] != 'user.cas_auth')))
		{
			if(defined('CAS_SERVER_URL'))
			{
				if ((isset($_GET['action'])) && (strncmp($_GET['action'],'json.',5)==0))
				{
					throw new JSONException("Votre compte utilisateur ne dispose pas de profil.");
				}
				else
				{
					JetonMethod::fakeAuth($_SESSION['_USER']['killi_user_id']['value']);
					
					$_SESSION['_ERROR_LIST']['Erreur de connexion'] = "Votre compte utilisateur ne dispose pas de profil.";
					global $hUI;
					$hUI = new UI();

					if (file_exists('./template/error.xml'))
					{
						$hUI->render('./template/error.xml', 1, array());
					}
					else
					{
						$hUI->render('./library/killi/template/error.xml', 1, array());
					}
					exit(0);
				}
			}

			session_unset();
			session_destroy();
			session_start();
			if ((isset($_GET['action'])) && (strncmp($_GET['action'],'json.',5)==0))
			{
				throw new JSONException("Votre compte utilisateur ne dispose pas de profil.");
			}
			else
			{
				JetonMethod::fakeAuth($_SESSION['_USER']['killi_user_id']['value']);
				
				$_SESSION['_ERROR_LIST']['Erreur de connexion'] = "Votre compte utilisateur ne dispose pas de profil.";
				UI::quitNBack();
			}
		}

		return $next($request);
	}
}
