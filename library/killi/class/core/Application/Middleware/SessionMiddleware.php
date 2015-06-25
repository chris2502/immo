<?php

namespace Killi\Core\Application\Middleware;

/**
 *
 * @class  SessionMiddleware
 * @Revision $Revision: 4599 $
 *
 */
use \Closure;
use \Killi\Core\Application\Http\Request;

class SessionMiddleware extends AbstractMiddleware
{
	/**
	 * @inherit
	 */
	public function handle(Request $request, Closure $next)
	{
		// TODO: Décommenter ça lorsque dbconfig disparaîtra !
		//session_name(md5($_SERVER['PHP_SELF']));
		//@session_start();

		//session_destroy();
		//---Generate token
		$_SESSION['_TOKEN'] = md5(session_id());

		$response = $next($request);

		/* Nettoyage des informations POST de la session. */
		if (isset($_SESSION['_POST']))
		{
			unset($_SESSION['_POST']);
		}

		return $response;
	}
}
