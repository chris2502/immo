<?php

namespace Killi\Core\Application\Middleware;

/**
 *
 * @class  AbstractMiddleware
 * @Revision $Revision: 4539 $
 *
 */
use \Closure;
use \Killi\Core\Application\Application;
use \Killi\Core\Application\Http\Request;

abstract class AbstractMiddleware
{
	/**
	 * Application
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Instancie le middleware.
	 *
	 * @param Application $app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Exécute le middleware et passe au suivant
	 *
	 * @param Request $request
	 */
	public abstract function handle(Request $request, Closure $next);

}
