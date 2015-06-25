<?php

namespace Killi\Core\Application\Middleware;

/**
 *
 * @class  JsonRequestMiddleware
 * @Revision $Revision: 4563 $
 *
 */
use \Performance;
use \Closure;
use \Killi\Core\Application\Http\Request;

class JsonRequestMiddleware extends AbstractMiddleware
{
	/**
	 * @inherit
	 */
	public function handle(Request $request, Closure $next)
	{
		/**
		 * Crossdomain
		 */
		if(defined('ALLOW_CROSSDOMAIN') && ALLOW_CROSSDOMAIN == TRUE)
		{
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
		}

		// compteur interne de requêtes Curl
		$h=apache_request_headers();
		if(!isset($h['X-Killi-internalcounter']))
		{
			$h['X-Killi-internalcounter'] = 0;
		}

		$_SERVER['X-Killi-internalcounter']=$h['X-Killi-internalcounter'];
		unset($h);

		if($_SERVER['X-Killi-internalcounter'] >= CURL_INTERNALCOUNTER_LIMIT)
		{
			throw new Exception('X-Killi-internalcounter exceeded CURL_INTERNALCOUNTER_LIMIT : '.$_SERVER['X-Killi-internalcounter'].' >= '.CURL_INTERNALCOUNTER_LIMIT);
		}

		return $next($request);
	}
}
