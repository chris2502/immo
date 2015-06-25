<?php

namespace Killi\Core\Application\Http;

/**
 * Classe de base contenant les informations sur la requête.
 *
 * @class  Request
 * @Revision $Revision: 4563 $
 *
 */

class Request
{
	/**
	 * Instance de la requête en cours.
	 *
	 * @var Request
	 */
	private static $instance = NULL;

	/**
	 * Variables du header
	 *
	 * @var array
	 */
	private $_headers = array();

	/**
	 * Variables du serveur
	 *
	 * @var array
	 */
	private $_servers = array();

	/**
	 * Constructeur interne de la requête.
	 */
	private function __construct()
	{
		$this->_headers = getallheaders();
		foreach($this->_headers AS &$h)
		{
			$h = strtolower($h);
		}
		unset($h);

		foreach($_SERVER AS $key => $value)
		{
			$key = strtolower($key);
			$this->_servers[$key] = $value;
		}
	}

	/**
	 * Récupère tout les paramètres de la requête et retourne un objet Request.
	 *
	 * @return Request
	 */
	public static function capture()
	{
		self::$instance = new Request();

		return self::$instance;
	}

	/**
	 * Retourne l'instance de la requête en cours.
	 *
	 * @return Request
	 */
	public static function instance()
	{
		return self::$instance;
	}

	/**
	 * Retourne la méthode HTTP utilisée.
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->server('REQUEST_METHOD');
	}

	/**
	 * Retourne le protocole utilisé.
	 *
	 * @return string
	 */
	public function getProtocol()
	{
		return $this->isSecure() ? 'https' : 'http';
	}

	/**
	 * Retourne le nom d'hôte.
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->server('SERVER_NAME', 'localhost');
	}

	/**
	 * Retourne le port utilisé
	 *
	 * @return string
	 */
	public function getPort()
	{
		return $this->server('SERVER_PORT', '80');
	}

	/**
	 * Retourne le nom d'utilisateur (dans le cas d'une authentification BASIC).
	 *
	 * @return string
	 */
	public function getUser()
	{
		return $this->server('PHP_AUTH_USER');
	}

	/**
	 * Retourne le mot de passe de l'utilisateur (dans le cas d'une authentification BASIC).
	 *
	 * @return string
	 */
	public function getPassword()
	{
		return $this->server('PHP_AUTH_PW');
	}

	/**
	 * Retourne le couple user:password de l'authentification BASIC.
	 *
	 * @return string
	 */
	public function getUserPassword()
	{
		$user = $this->getUser();
		$pass = $this->getPassword();

		if(empty($pass))
		{
			return $user;
		}

		return $user . ':' . $pass;
	}

	/**
	 * Retourne l'URL de l'hôte.
	 *
	 * @return string
	 */
	public function getHostURL()
	{
		return $this->getProtocol() . '://' . $this->getHost();
	}

	/**
	 * Retourne l'URL de base du script.
	 *
	 * @return string
	 */
	public function getBaseURL()
	{
		$filename = basename($this->server('SCRIPT_FILENAME', ''));

		/* Cas d'un appel direct */
		if(basename($this->server('SCRIPT_NAME', '')) === $filename)
		{
			$base_url = $filename;
		}
		else
		/* Cas du bootstrap */
		if(basename($this->server('PHP_SELF', '')) == $filename)
		{
			$base_url = $filename;
		}
		/* Cas classique */
		else
		{
			$self = $this->server('PHP_SELF', '');
			$raw = explode('/', trim($filename, '/'));
			$raw = array_reverse($raw);
			$i = 0;
			$total = count($raw);
			$base_url = '';
			do
			{
				$t = $raw[$i];
				$base_url = '/' . $t . $base_url;
				$i++;
			} while($i < $total && ($pos = strpos($path, $base_url) !== FALSE) && $pos != 0);
		}

		return rtrim($base_url, '/');
	}

	/**
	 * Retourne l'URL racine de l'application.
	 *
	 * @return string
	 */
	public function getRoot()
	{
		return rtrim($this->getHostURL() . '/' . $this->getBaseURL(), '/');
	}

	/**
	 * Retourne l'URI complète de la requête.
	 *
	 * @return string
	 */
	public function getURI()
	{
		return $this->server('REQUEST_URI', '');
	}

	/**
	 * Retourne l'URL de la requête (sans la suite de paramètres).
	 *
	 * @return string
	 */
	public function getURL()
	{
		return rtrim(preg_replace('/\?.*/', '', $this->getURI()), '/');
	}

	/**
	 * Retourne l'URL complète de la requête.
	 *
	 * @return string
	 */
	public function getFullURL()
	{
		return $this->getRoot() . '/' . $this->getURI();
	}

	/**
	 * Retourne le chemin de la requête.
	 *
	 * @return string
	 */
	public function getPath()
	{
		$base_url = $this->getBaseURL();
		$uri = $this->getURI();

		if($uri === NULL)
		{
			return '/';
		}

		$path = '/';

		/* On vire les paramètres de la requête. */
		if($pos = strpos($uri, '?'))
		{
			$uri = substr($uri, 0, $pos);
		}

		if($base_url === NULL)
		{
			return $base_url;
		}
		else
		if($path = substr($uri, strlen($base_url)) === FALSE)
		{
			return '/';
		}

		return $path;
	}

	/**
	 * Retourne vrai si la requête est une requête AJAX.
	 *
	 * @return bool
	 */
	public function isAjax()
	{
		return $this->header('X-Requested-With', '') == 'XMLHttpRequest';
	}

	/**
	 * Retourne vrai si la requête est sécurisée (HTTPS).
	 *
	 * @return bool
	 */
	public function isSecure()
	{
		return strtolower($this->server('HTTPS')) == 'on' || $this->server('HTTPS') == 1;
	}

	/**
	 * Retourne l'adresse IP du client.
	 *
	 * @return string
	 */
	public function getClientIP()
	{
		return $this->server('REMOTE_ADDR', '');
	}

	/**
	 * Retourne vrai si le paramètre de la requête est présent.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function exists($key)
	{
		return isset($_REQUEST[$key]);
	}

	/**
	 * Retourne vrai si le paramètre de la requête est présent et non vide.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return isset($_REQUEST[$key]) && $_REQUEST[$key] !== '';
	}

	/**
	 * Retourne la valeur d'un paramètre. Si le paramètre est vide, la valeur par défaut est retournée.
	 *
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function input($key = null, $default = null)
	{
		if(!empty($_POST[$key]))
		{
			return $_POST[$key];
		}

		if(!empty($_GET[$key]))
		{
			return $_GET[$key];
		}

		return $default;
	}

	/**
	 * Retourne la valeur présente dans le header. Si la valeur est vide, la valeur par défaut est retournée.
	 *
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function header($key = null, $default = null)
	{
		$key = strtolower($key);
		if(!empty($this->_headers[$key]))
		{
			return $this->_headers[$key];
		}

		return $default;
	}

	/**
	 * Retourne la valeur d'une variable serveur. Si la valeur est vide, la valeur par défaut est retournée.
	 *
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function server($key = null, $default = null)
	{
		$key = strtolower($key);
		if(!empty($this->_servers[$key]))
		{
			return $this->_servers[$key];
		}

		return $default;
	}

	/**
	 * Retourne vrai si la requête est une requête JSON.
	 *
	 * @return bool
	 */
	public function isJson()
	{
		return strpos($this->header('CONTENT_TYPE'), '/json') !== FALSE;
	}

	/**
	 * Retourne vrai si la requête accepte comme réponse principale le format JSON.
	 *
	 * @return bool
	 */
	public function wantsJson()
	{
		$accept_content = $this->server('HTTP_ACCEPT', '');
		$acceptable = explode(',', $accept_content);
		return isset($acceptable[0]) && $acceptable[0] == 'application/json';
	}

	/**
	 * Retourne vrai si un fichier est présent avec la clé défini en paramètre.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasFile($key)
	{

	}

	protected function isValidFile($file)
	{

	}
}
