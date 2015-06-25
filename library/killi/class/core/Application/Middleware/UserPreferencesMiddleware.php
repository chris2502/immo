<?php

namespace Killi\Core\Application\Middleware;

/**
 * Classe permettant de gérer les préférences utilisateurs.
 *
 *
 * @class  UserPreferencesMiddleware
 * @Revision $Revision: 4563 $
 *
 */
use \Closure;
use \Killi\Core\Application\Http\Request;
use \Killi\Core\Application\Http\Response;

class UserPreferencesMiddleware extends AbstractMiddleware
{
	/**
	 * @inherit
	 */
	public function handle(Request $request, Closure $next)
	{
		//--- User preferences
		if (isset($_SESSION['_USER']) && !isset($_SESSION['_USER_PREFERENCES']))
		{
			// Recherche une entrée de préférences pour l'utilisateur courant.
			$pref_list = array();
			$user = new User();
			ORM::getORMInstance('userpreferences', TRUE)->browse($pref_list, $num_pref, NULL, array(array($user->primary_key, '=', $_SESSION['_USER']['killi_user_id']['value'])));
			unset($user);
			if ($num_pref == 1)
			{
				$_SESSION['_USER_PREFERENCES'] = reset($pref_list);
			}
			else
			{
				// Permet de ne pas faire le test à chaque chargement de page.
				// L'action "enregistrer" depuis l'interface des prefs écrase
				// le contenu de la variable de session.
				$_SESSION['_USER_PREFERENCES'] = array();
			}
		}

		global $search_view_num_records;
		if (isset($_SESSION['_USER_PREFERENCES']['items_per_page']['value']))
		{
			$search_view_num_records = $_SESSION['_USER_PREFERENCES']['items_per_page']['value'];
		}
		else
		{
			$search_view_num_records = SEARCH_VIEW_NUM_RECORDS;
		}

		/**
		*  Gestion du thème.
		*/
		// Thème applicatif par défaut
		$ui_theme = UI_THEME;

		//--- Thème de l'UI.
		if (isset($_SESSION['_USER_PREFERENCES']['ui_theme']))
		{
			// Défini par l'utilisateur
			$ui_theme = $_SESSION['_USER_PREFERENCES']['ui_theme']['value'];
		}

		UI::setTheme($ui_theme);

		return $next($request);
	}
}
