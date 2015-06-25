<?php

namespace Killi\Core\Application;

/**
 *
 * @class Stack
 * @Revision $Revision: 4539 $
 *
 */
use \Closure;

class Stack
{
	/**
	 * Instance de l'application
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Objet passé à tout les éléments de la pile.
	 *
	 * @var mixed
	 */
	protected $passable;

	/**
	 * Éléments de la pile d'éxécution.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Méthode éxécuter sur chaque éléments de la pile.
	 *
	 * @var string
	 */
	protected $method = 'handle';

	/**
	 * Construction de la pile.
	 *
	 * @param Application $app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Définition de l'objet passé à tout les éléments de la pile.
	 *
	 * @param mixed $passable
	 */
	public function send($passable)
	{
		$this->passable = $passable;
		return $this;
	}

	/**
	 * Définition des éléments de la pile.
	 *
	 * @param array $items
	 */
	public function stack(array $items)
	{
		$this->items = $items;
		return $this;
	}

	/**
	 * Définition de la méthode éxécutée sur chaque éléments de la pile.
	 *
	 * @param string $method
	 */
	public function via($method)
	{
		$this->method = $method;
	}

	/**
	 * Cloture éxécuté en dernier lors du parcours le plus profond de la pile.
	 *
	 * @param Closure $destination
	 */
	public function endBy(Closure $destination)
	{
		$first = $this->getInit($destination);
		$reversed = array_reverse($this->items);

		return call_user_func(
				array_reduce($reversed, $this->getNext(), $first),
				$this->passable);
	}

	/**
	 * Récupère une cloture correspondant au prochain élément éxécuté de la pile.
	 *
	 * @return Closure
	 */
	protected function getNext()
	{
		return function($stack, $pipe) {
			return function($passable) use ($stack, $pipe) {
				if($pipe instanceof Closure)
				{
					return call_user_func($pipe, $passable, $stack);
				}
				else
				{
					return $pipe->{$this->method}($passable, $stack);
				}
			};
		};
	}

	/**
	 * Retourne la première cloture éxécutée lors du lancement du pipeline.
	 *
	 * @return Closure
	 */
	protected function getInit(Closure $end)
	{
		return function() use ($end) {
			return call_user_func($end, $this->passable);
		};
	}
}
