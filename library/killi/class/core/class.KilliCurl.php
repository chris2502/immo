<?php

/**
 *  Le requeteur Curl de Killi.
 *
 *  @class KilliCurl
 *  @Revision $Revision: 4600 $
 *
 */

class KilliCurl
{

	/**
	 * Le nombre de requêtes Curl effectuées.
	 *
	 * @var int
	 */
	static public $queries_number = 0;

	/**
	 * Le temps passé à effectuer les requêtes Curls.
	 *
	 * @var int
	 */
	static public $time = 0;

	/**
	 * L'historique des requêtes Curl effectuées.
	 *
	 * @var Array
	 */
	static protected $_history = array();

	/**
	 * La fonction doit-elle lancer une exception si elle trouve un membre "error"
	 * dans le retour de la requête.
	 *
	 * @var boolean
	 */
	protected $_throw;

	/**
	 * Le résultat attendu a t'il la forme d'une chaine JSON.
	 */
	protected $_expect_json;

	/**
	 * L'url à appeler.
	 *
	 * @var string
	 */
	protected $_url;
	protected $_login;
	protected $_password;
	protected $_certificate;
	protected $_certificate_pwd;

	protected $_stream = NULL;
	protected $_post;
	protected $_sent_post;
	protected $_json;

	protected $_last_error;
	protected $_last_result;

	/**
	 * Le constructeur de la classe.
	 */
	public function __construct($url = null, $throw = true) {
		$this->_throw = $throw;
		$this->_last_error = array();
		$this->_last_result = null;
		$this->_expect_json = true;
		// @codeCoverageIgnoreStart
		if(!function_exists('curl_init'))
		{
			$this->_last_error[] = 'Appel à l\'extension PHPCurl sans avoir installé celle-ci.';
			$this->_throw();
			return;
		}
		// @codeCoverageIgnoreEnd
		$this->_url = $url;
		$this->_json = array();
		$this->_post = array();
	}

	/**
	 * Appelé par le gestionnaire d'Exception
	 * Ajouter dans le tableau de retour les infos souhaitées
	 * @return multitype:string Ambigous <multitype:, unknown>
	 */
	public function getInfos()
	{
		return array(
			'url'=>$this->_url,
			'post'=>$this->_sent_post
		);
	}

	public function setUser($login, $password)
	{
		$this->_login = $login;
		$this->_password = $password;
		return $this;
	}

	public function setSSL($file, $password)
	{
		$this->_certificate = $file;
		$this->_certificate_pwd = $password;
		return $this;
	}

	public function setPost($key, $value)
	{
		$this->_post[$key] = $value;
		return $this;
	}
	
	public function setStream($class)
	{
		$this->_stream = $class; 
		$this->setJson(false);
		
		return $this;
	}
	
	public function addFile($key, $file_path)
	{
		return $this->setPost ( $key, new CurlFile ( $file_path) );
	}

	public function setJson($expect)
	{
		$this->_expect_json = $expect;
		return $this;
	}

	public function __set($name, $value)
	{
		$this->_json[$name] = $value;
		return;
	}

	public function __get($name)
	{
		if (isset($this->_json[$name]))
		{
			return $this->_json[$name];
		}
		else
		{
			return null;
		}
	}

	public function getErrors()
	{
		return $this->_last_error;
	}

	protected function _throw()
	{
		if($this->_throw)
		{
			$e = new CurlException(implode(PHP_EOL, $this->_last_error));
			$e->curl = $this;
			throw $e;
		}
		return FALSE;
	}

	public function request($url = null, $json = null)
	{
		$result = $this->requestNoData($url, $json);
		if ($result !== FALSE && $this->_expect_json)
		{
			if (empty($result))
			{
				$this->_last_error[] = 'Empty Curl result, maybe the script died';
				$this->_throw();
				$result = FALSE;
			}
			elseif (isset($result['data']))
			{
				$result = $result['data'];
			}
			else
			{
				$this->_last_error[] = 'Bad Curl result, return must have the property "data"';
				$this->_throw();
				$result = FALSE;
			}
		}
		return $result;
	}

	/**
	 * Effectue la requête Curl.
	 */
	public function requestNoData($url = null, $json = null)
	{
		$this->_last_error = array();

		if (empty($url))
		{
			$url = $this->_url;
		}
		if (empty($json))
		{
			$json = $this->_json;
		}
		if (empty($url))
		{
			$this->_last_error[] = 'No Url';
		}

		$post = array();
		if (isset($this->_post['data']) && !empty($json))
		{
			$this->_last_error[] = 'Définition de données à la clef data du $_POST en plus de données JSON.';
		}

		if (!empty($this->_post))
		{
			foreach ($this->_post as $key => $value)
			{
				$post[$key] = $value;
			}
		}
		if (!empty($json))
		{
			$post['data'] = json_encode($json);
		}
		
		if (!empty($this->_login))
        {
        	$post['user/login'] = $this->_login;
        	
        	Security::crypt($this->_password, $post['crypt/user/password']);
        }

		$ch = curl_init();

		if(!isset($_SERVER['X-Killi-internalcounter']))
		{
			$_SERVER['X-Killi-internalcounter'] = 0;
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-Killi-internalcounter: '.($_SERVER['X-Killi-internalcounter']+1)
	    ));
		
		if (!empty($post))
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		if($this->_stream)
		{
			stream_wrapper_register("memory", $this->_stream);
			$this->stream_fp = fopen("memory://".md5(uniqid('stream',TRUE)), "a");
			
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FILE, $this->stream_fp);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this->_stream, "HandleHeaderLine") );
		}
		else
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		}

		$this->_sent_post = $post;

		if (!empty($this->_certificate) && !empty($this->_certificate_pwd))
		{
			curl_setopt($ch, CURLOPT_SSLCERT, $this->_certificate);
			curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->_certificate_pwd);
			curl_setopt($ch, CURLOPT_SSLVERSION, 1); // Pb sur a6dxf si non présent (serveur différent).
		}
		elseif (!empty($this->_certificate))
		{
			$this->_last_error[] = 'Certificat sans password.';
		}
		elseif (!empty($this->_certificate_pwd))
		{
			$this->_last_error[] = 'Password de certificat sans certificat.';
		}

		if (!empty($this->_login))
		{
			$basic_auth = base64_encode($this->_login.':'.$this->_password);
			curl_setopt($ch, CURLOPT_USERPWD, $basic_auth);
		}
		elseif (!empty($this->_password))
		{
			$this->_last_error[] = 'Password utilisateur sans login.';
		}

		if (empty($this->_last_error))
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_URL, $url);
			return $this->_execute($ch);
		}
		$this->_throw();
		return FALSE;
	}

	/**
	 * Execute l'appel curl, et lance des exceptions en cas d'erreur.
	 *
	 * @param	curl		$ch			Un handle vers la requête Curl à executer.
	 * @return	Array					[0]: true / false, la fonction s'est-elle finie normalement.
	 * 									[1]: les données retournées par Curl.
	 */
	protected function _execute($ch) {
		global $start_time;

		self::$queries_number++;
		if(self::$queries_number == 50)
		{
			//new NonBlockingException(self::$queries_number.' appels CURL !');
		}

		if(isset($_SERVER['REQUEST_URI']))
		{
			curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
		}
		
		$curl_start_time = microtime(true);
		$this->_last_result = curl_exec($ch);
		$curl_duration = ( microtime(true) - $curl_start_time);
		if (DISPLAY_ERRORS)
		{
			$this->_recordRequest();
		}
		self::$time += $curl_duration;
		$start_time += $curl_duration;

		$error = curl_error($ch);
		curl_close($ch);
		
		if($this->_stream)
		{
			return $this->stream_fp;
		}
		
		if($error)
		{
			$this->_last_error[] = $error;
			$this->_throw();
			return FALSE;
		}
		if ($this->_expect_json)
		{
			$json = json_decode($this->_last_result, true);
			// @codeCoverageIgnoreStart
			$json_err = json_last_error();
			if ($json_err != JSON_ERROR_NONE)
			{
				switch ($json_err)
				{
					case JSON_ERROR_DEPTH:
						$this->_last_error[] = 'Json Decode : Maximum stack depth exceeded';
						break;
					case JSON_ERROR_STATE_MISMATCH:
						$this->_last_error[] = 'Json Decode : Underflow or the modes mismatch';
						break;
					case JSON_ERROR_CTRL_CHAR:
						$this->_last_error[] = 'Json Decode : Unexpected control character found';
						break;
					case JSON_ERROR_SYNTAX:
						$this->_last_error[] = 'Json Decode : Syntax error, malformed JSON'.(DISPLAY_ERRORS ? ' : '.var_export($this->_last_result,true):'');
						break;
					case JSON_ERROR_UTF8:
						$this->_last_error[] = 'Json Decode : Malformed UTF-8 characters, possibly incorrectly encoded';
						break;
					default:
						$this->_last_error[] = 'Json Decode : Unknown error';
						break;
				}
				$this->_throw();
				return FALSE;
			}
			// @codeCoverageIgnoreEnd
			elseif(isset($json['authentification']))
			{
				$this->_last_error[] = 'Remote error : '.var_export($json['authentification'],true);
				$this->_throw();
				return FALSE;
			}
			elseif(isset($json['error']))
			{
				$this->_last_error[] = 'Remote error : '.var_export($json['error'],true).(DISPLAY_ERRORS && isset($json['dump']) ? PHP_EOL.PHP_EOL.var_export($json['dump'],true) : NULL);
				$this->_throw();
				return FALSE;
			}
			return $json;
		}
		return $this->_last_result;
	}

	/**
	 * Affiche l'historique des requêtes Curl.
	 *
	 *  @codeCoverageIgnoreStart
	 */
	static public function renderHistory() {
		echo '<h3>Requêtes Curls:</h3>';
		if (!empty(self::$_history))
		{
			foreach(self::$_history as $h)
			{
				echo '<div class="curl-request"><p class="killicurl_history"><strong>'.$h->url.'</strong>';
				if (!empty($h->post))
				{
					echo ' Post : '.json_encode($h->post).'';
				}
				if (!empty($h->json))
				{
					echo ' Json : '.json_encode($h->json).'';
				}
				echo '</p>';
				echo '<ul style="margin: 5px 10px;" class="killicurl_trace">';
				echo '<li><strong>As '.$h->auth.'</strong></li>';
				foreach ($h->trace as $trace)
				{
					$file = isset($trace['file']) ? $trace['file'] : 'callback';
					$line = isset($trace['line']) ? $trace['line'] : '';
					echo '<li>#' . $file . ' ('. $line. ') <strong>'.(isset($trace['class']) ? $trace['class'].'->':'').$trace['function'].'</strong></li>';
				}
				echo '</ul></div>';
			}
			echo "<script type=\"text/javascript\">
				$('ul.killicurl_trace').hide();
				$('p.killicurl_history').click(function () {
					$(this).closest('div').find('ul.killicurl_trace').toggle();
				});
			</script>";
		}
		else
		{
			echo '<p>Aucunes requêtes n\'a été effectuée.</p>';
		}
	}
	// @codeCoverageIgnoreEnd

	/**
	 * Enregistre les détails d'une requête si le define DISPLAY_ERRORS
	 * est activé.
	 *
	 * @param JsonRequest		$JsRequest		La requête à stocker.
	 * @return boolean
	 */
	protected function _recordRequest() {
		$h = (object) array();
		$h->auth = $this->_login.':'.$this->_password;
		$h->url = $this->_url;
		$h->post = $this->_post;
		$h->json = $this->_json;
		$h->size = strlen($this->_last_result);
		$h->trace = array();
		foreach(debug_backtrace(false) as $key => $trace)
		{
			if (isset($trace['file']) && $trace['file'] == __FILE__)
			{
				continue;
			}
			$h->trace[] = $trace;
		}

		self::$_history[] = $h;
		return true;
	}

	public function __toString()
	{
		if (is_string($this->_last_result))
		{
			return $this->_last_result;
		}
		return '';
	}
}
