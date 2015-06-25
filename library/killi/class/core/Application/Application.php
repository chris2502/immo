<?php

namespace Killi\Core\Application;

/**
 * Classe de base d'une application Killi
 *
 * @class  Application
 * @Revision $Revision: 4539 $
 *
 */

use \Killi\Core\Application\Http\Request;
use \Killi\Core\Application\Http\Response;

use \Killi\Core\Application\Bootstrap\BootstrapInterface;
use \Killi\Core\Application\Middleware\AbstractMiddleware;

class Application
{
	/**
	 * Killi Version
	 *
	 * @var string
	 */
	const VERSION = KILLI_VERSION;

	/**
	 * Répertoire de base de l'application
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * Tableau d'instances d'éléments nécessaires au noyau de l'application.
	 *
	 * @var array
	 */
	protected $instances = array();

	/**
	 * Tableau d'instances des middlewares exécuter au lancement de l'application.
	 *
	 * @var array
	 */
	protected $middlewares = array();

	/**
	 * Instance du routeur.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Instancie une nouvelle application Killi
	 *
	 * @param string $basePath
	 * @return void
	 */
	public function __construct($basePath)
	{
		$this->setBasePath($basePath);
		$this->instances['app'] = $this;

		$this->router = new Router($this);
	}

	/**
	 * Retourne la version de l'application.
	 *
	 * @return string
	 */
	 public function version()
	 {
		return static::VERSION;
	 }

	 /**
	  * Défini le répertoire de base de l'application.
	  *
	  * @param string $basePath
	  * @return $this
	  */
	 public function setBasePath($basePath)
	 {
		$this->basePath = $basePath;

		return $this;
	 }

	 /**
	  * Retourne le répertoire de base de l'application.
	  *
	  * @return string
	  */
	 public function basePath()
	 {
		return $this->basePath;
	 }

	 /**
	  * Détermine si l'application est lancé en mode console.
	  *
	  * @return bool
	  */
	 public function runningInConsole()
	 {
		return php_sapi_name() == 'cli';
	 }

	/**
	 * Détermine si l'application est lancé via les tests unitaires.
	 *
	 * @return bool
	 */
	public function runningInTests()
	{
		return defined('TEST_MODE') && TEST_MODE == TRUE;
	}

	/**
	 * Détermine si l'application est en mode maintenance.
	 *
	 * @return bool
	 */
	public function isDownForMaintenance()
	{
		return defined('MAINTENANCE') && MAINTENANCE == TRUE;
	}

	/**
	 * Enregistre une instance dans l'application.
	 *
	 * @param string $name
	 * @param mixed  $instance
	 * @return void
	 */
	public function instance($name, $instance)
	{
		$this->instances[$name] = $instance;
	}

	/**
	 * Éxécution des classes d'initialisations de l'application
	 *
	 * @param array $bootstraps
	 * @return void
	 */
	public function runBootstraps(array $bootstraps)
	{
		foreach($bootstraps AS $bootstrap)
		{
			$b = new $bootstrap();

			if(!($b instanceof BootstrapInterface))
			{
				throw new Exception('Erreur de classe d\'initialisation !');
			}

			$b->bootstrap($this);
		}
	}

	/**
	 * Définition des middlewares.
	 *
	 * @param array $middlewares
	 */
	public function setMiddlewares(array $middlewares)
	{
		foreach($middlewares AS $m)
		{
			$middleware = new $m($this);
			if(!($middleware instanceof AbstractMiddleware))
			{
				throw new Exception('Erreur de classe middleware !');
			}
			$this->middlewares[] = $middleware;
		}
	}

	/**
	 * Démarrage de l'application.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function start(Request $request)
	{
		/* Sauvegarde de l'instance de la requête dans la classe applicative. */
		$this->instance('request', $request);

		/* Exécution des middlewares (Pipeline). */
		$stack = new Stack($this);

		return $stack->send($request)
				/* Exécution des middlewares. */
				->stack($this->middlewares)

				/* Dispatch au routeur. */
				->endBy(function($request) {
					return $this->router->dispatch($request);
				});
	}

	/**
	 * Arrêt de l'application.
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function stop(Request $request, Response $response)
	{
		return;
	}
}
