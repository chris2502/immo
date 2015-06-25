<?php

namespace Killi\Core\Application;

/**
 *
 * @class Router
 * @Revision $Revision: 4563 $
 *
 */

use \Killi\Core\Application\Http\Request;
use \Killi\Core\Application\Http\Response;

class Router
{
	/**
	 * Application instance
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Instanciate new router.
	 *
	 * @param Application $app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Dispatch to the right controller.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function dispatch(Request $request)
	{
		/**
		*  Détection et paramétrage de l'environnement
		*/
		Route::init();
		Route::in();

		/**
		 *
		 */
		//---Si deja auth
		if (isset($_SESSION['_USER']) && !isset($_GET['action']))
		{
			header('Location: ./index.php?action='.HOME_PAGE.'&token='.$_SESSION['_TOKEN']);
			exit(0);
		}

		//---Refresh parent
		if (isset($_POST['refresh_parent']))
		{
			$_SESSION['refresh_parent']=1;
		}

		//---Population noeud worflow => ajout des champs qualification & commentaire correspondant
		if( isset( $_GET[ 'workflow_node_id'] ) )
		{
			$attributes = array();
			ORM::getORMInstance('node')->read( $_GET[ 'workflow_node_id'], $attributes ,array('object')) ;

			if( isset( $attributes[ 'object'][ 'value' ] ) )
			{
				ORM::setWorkflowAttributes($attributes[ 'object'][ 'value' ]);
			}
		}

		/**
		* Décomposition de l'action.
		*/
		$module = Route::getController();
		$object = Route::getObject();
		$method = Route::getMethod();

		/**
		* Vérification du profil.
		*/
		Debug::info($_SESSION, 'SESSION');
		Debug::info($_POST, 'POST');
		Debug::info($_GET, 'GET');

		//---Stat log
		if(LOG_USER_ACTION === TRUE)
		{
			KilliUserMethod::log_action();
		}

		Route::dispatch();
		Route::out();
		return new Response();
	}
}
