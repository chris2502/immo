<?php

namespace Killi\Core\Application\Middleware;

/**
 * Middleware permettant de tracer et debugger.
 *
 * @class  DebugTraceMiddleware
 * @Revision $Revision: 4563 $
 *
 */
use \Performance;
use \Closure;
use \Killi\Core\Application\Http\Request;

class DebugTraceMiddleware extends AbstractMiddleware
{
	/**
	 * @inherit
	 */
	public function handle(Request $request, Closure $next)
	{
		if(FIREPHP_ENABLE)
		{
			ob_start();
		}

		Performance::start();

		if(CHECK_SECURITY_ISSUE && (isset($_GET['primary_key']) || isset($_POST['primary_key'])))
		{
			throw new Exception('Faille de sécurité détectée : La clé primaire est transférée en clair !!!');
		}

		/**
		 * Exécution de la suite.
		 */
		$response = $next($request);

		/**
		 * Action post exécution.
		 */
		Performance::stop();

		/**
		 * Éléments de debug pour les développeurs.
		 */
		//---Debug
		if(DISPLAY_ERRORS && UI::isRendered())
		{
			$header_ok=false;
			foreach(headers_list() as $header)
			{
				$header_info=preg_split('/\s*:\s*/',$header);

				if($header_info[0]=='Content-type' && strncmp($header_info[1], 'text/html',9) == 0)
				{
					$header_ok=true;
					break;
				}
			}

			if(PROFILER && function_exists('xhprof_enable'))
			{
				$xhprof_data = xhprof_disable();

				$XHPROF_ROOT = "/var/www/xhprof/";
				include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
				include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

				$xhprof_runs = new XHProfRuns_Default();
				$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");

				if($header_ok)
				{
					echo '<a href="http://localhost/xhprof/xhprof_html/index.php?run=', $run_id, '&source=xhprof_testing">Données du Profiler</a>', "\n", '<br><br>';
				}
			}

			Debug::firephp_end();

			if($header_ok && !defined('NOTRACE')) // ni json, ni svg
			{
				DbLayer::trace_memory_leak();
				DbLayer::trace_duplicate_queries();
				DbLayer::trace_slow_queries();
				ORM::trace_no_fields();
				Debug::debug_end();
				Performance::printList();
				//DbLayer::trace_queries();
				ORM::traceObjectLoader();
				if (class_exists('KilliCurl')) {
					KilliCurl::renderHistory();
				}
			}
		}

		return $response;
	}
}
