<?php

/**
 *  Gestionnaire d'exception.
 *
 *  @class ExceptionManager
 *  @Revision $Revision: 4336 $
 */

class ExceptionManager
{
	private static $_send_mail = true;

	//---------------------------------------------------------------------
	/**
	 * Active le gestionnaire
	 * @return boolean
	 */
	public static function enable()
	{
		error_reporting(E_ALL);

		set_exception_handler(array('ExceptionManager','exceptionHandler'));
		set_error_handler(array('ExceptionManager','errorHandler'));
		register_shutdown_function(array('ExceptionManager','fatalHandler'));

		return TRUE;
	}
	//---------------------------------------------------------------------
	/**
	 * Désactive le gestionnaire
	 * @return boolean
	 */
	public static function disable()
	{
		error_reporting(0);

		set_exception_handler(null);
		set_error_handler(function() {
			return false;
		});

		return TRUE;
	}
	//---------------------------------------------------------------------
	/**
	 * OLD
	 * @deprecated
	 */
	public static function dieOnError($die) {}
	//---------------------------------------------------------------------
	/**
	 * Indique au gestionnaire s'il doit envoyer des mails d'erreur en production
	 *
	 * @param boolean $send_mail
	 */
	public static function sendMail($send_mail)
	{
		self::$_send_mail = ($send_mail === TRUE);
	}
	//---------------------------------------------------------------------
	public static final function exceptionHandler(Exception $ex)
	{
		$data = array(
			'file'	 => $ex->getFile(),
			'line'	 => $ex->getLine(),
			'message'  => $ex->getMessage(),
			'trace'	=> $ex->getTraceAsString()
		);
		
		// erreur PHP
		if($ex instanceOf ErrorException)
		{
			$data['type'] = 'error';
			
			$errorlevels = array(
				32767 => 'E_ALL',
				16384 => 'E_USER_DEPRECATED',
				8192 => 'E_DEPRECATED',
				4096 => 'E_RECOVERABLE_ERROR',
				2048 => 'E_STRICT',
				1024 => 'E_USER_NOTICE',
				512 => 'E_USER_WARNING',
				256 => 'E_USER_ERROR',
				128 => 'E_COMPILE_WARNING',
				64 => 'E_COMPILE_ERROR',
				32 => 'E_CORE_WARNING',
				16 => 'E_CORE_ERROR',
				8 => 'E_NOTICE',
				4 => 'E_PARSE',
				2 => 'E_WARNING',
				1 => 'E_ERROR'
			);
			
			if(!array_key_exists($ex->getCode(), $errorlevels))
			{
				$data['err_type'] = 'Unknown error: '.$ex->getCode();
			}
			else
			{
				$data['err_type'] = 'Error: '.$errorlevels[$ex->getCode()];
			}
		}
		else
		{
			$data['type'] = 'critical';
			$data['err_type'] = 'Exception: '.get_class($ex);
		}

		if($ex instanceOf JSONException)
		{
			$data['type']='json';
		}
		else if($ex instanceOf UserException)
		{
			$data['type']='user';
			$data['user_message']=$ex->user_message.'<br/>Un technicien a été averti.<br/><br/><a href="javascript:history.back()">Page précédente</a>';
		}
		else if($ex instanceOf NonBlockingException)
		{
			$data['type']='nonblocking';
		}
	 	
	 	if(property_exists($ex, 'curl'))
		{
			$data['curl']=$ex->curl;
		}

		self::renderError($data);
		
		return TRUE;
	}
	//---------------------------------------------------------------------
	public static final function errorHandler($errno, $errstr, $errfile, $errline)
	{
		// on throw une nouvelle exception pour qu'un try catch puisse capturer l'erreur
		throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
	}
	//---------------------------------------------------------------------
	public static final function fatalHandler()
	{
		$error = error_get_last();
		
		if(empty($error) || $error['type'] != E_ERROR)
		{
			return TRUE;
		}
		
		self::exceptionHandler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
	}
	//---------------------------------------------------------------------
	protected static function renderError(array $data)
	{
		global $hDB, $start_memory, $start_time;

		// on ajoute les données communes
		$data = array_merge($data, array(
			'last_query'  => isset($hDB->last_query) ? $hDB->last_query : null,
			'user'		  => KILLI_SCRIPT ? (isset($_SERVER['USER']) ? $_SERVER['USER'] : $_SERVER['LOGNAME']) : (isset($_SESSION['_USER']['login']) ? $_SESSION['_USER']['login']['value'].' ('.$_SESSION['_USER']['killi_user_id']['value'].')' : NULL),
			'profils'	 => isset($_SESSION['_USER']) && isset($_SESSION['_USER']['profil_id']) ? implode(', ',$_SESSION['_USER']['profil_id']['value']) : null,
			'request'	  => $_REQUEST,
			'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (KILLI_SCRIPT ? implode(' ', $_SERVER['argv']) : null),
			'last_sql_error' => isset($hDB) ? $hDB->db_last_error() : null,
			'db_stat'	 => isset($hDB) ? $hDB->db_stat() : null,
			'mem_used'	 => sprintf("%1.2f",(memory_get_usage()-$start_memory)/(1024*1024)).'Mo ('.sprintf("%1.2f",memory_get_peak_usage()/(1024*1024)).'Mo max)',
			'ellapsed_time'	 => sprintf("%1.3f",microtime(true)-$start_time).'s',
			'server'	  => $_SERVER,
			'files'		  => $_FILES,
		));

		// bool
		$json = (isset($_GET['action']) && substr($_GET['action'],0,4)=='json');

		if(KILLI_SCRIPT)
		{
			if (!DISPLAY_ERRORS)
			{
				self::sendErrorMail($data);
			}
			self::logError($data);

			self::renderShellError($data);

			return TRUE;
		}

		switch ($data['type'])
		{
			// erreur JSON non critique (message de sortie)
			case 'json' :
				self::renderJSONError($data);
			break;

			// erreur utilisateur prévisible (bad url, mismatch etc)
			case 'user' :
				if (!DISPLAY_ERRORS)
				{
					self::sendErrorMail($data);
				}

				self::logError($data);

				if($json)
				{
					// en json, on affiche du json !
					self::renderJSONError($data);
				}
				else
				{
					// on affiche le message d'erreur proprement
					self::renderUserError($data);

					if (DISPLAY_ERRORS)
					{
						// on est en mode debug, on affiche aussi le popup
						self::renderPopupError($data);
					}
				}
			break;

			// Exception furtive non bloquante, envoie de mail uniquement
			case 'nonblocking' :
				if (!DISPLAY_ERRORS)
				{
					self::sendErrorMail($data);
				}

				self::logError($data);
			break;

			// erreur critique (Exception ou erreur PHP)
			case 'error' :
			case 'critical' :

				if (!DISPLAY_ERRORS)
				{
					self::sendErrorMail($data);
				}

				self::logError($data);

				if($json)
				{
					// en json, on affiche du json !
					self::renderJSONError($data);
				}
				else if (DISPLAY_ERRORS)
				{
					// on est en mode debug, on affiche le popup
					self::renderPopupError($data);
				}
				else
				{
					// en mode prod, on affiche la page d'erreur critique
					self::renderPageError($data);
				}
			break;
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function renderShellError(array $data)
	{
		$red 	= '01;31';
		$violet = '01;35';
		$yellow = '01;33';

		switch ($data['type'])
		{
			case 'nonblocking' :
				$color = $violet;
			break;

			case 'user' :
				$color = $yellow;
			break;

			default :
				$color = $red;
			break;

		}

		file_put_contents('php://stderr', "\033[" . $color . "m".$data['message'].PHP_EOL."\033[0m");

		if(DISPLAY_ERRORS)
		{
			// on dump les données pour debug
			file_put_contents('php://stderr', "\033[" . $color . "m".var_export($data,true).PHP_EOL."\033[0m");
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function renderPopupError(array $data)
	{
		switch($data['type'])
		{
			case 'user' :
				$popup_color='#880000';
			break;
			case 'error' :
				$popup_color='#9F4F00';
			break;
			case 'critical' :
				$popup_color='#880000';
			break;
		}

		$lines = self::renderFileLines($data);

		?>
			<!DOCTYPE html>
			<html>
				<head>
					<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

					<!-- JQUERY -->
					<link type="text/css" rel="stylesheet" href="<?= KILLI_DIR ?>/css/base/jquery.ui.all.css"/>

					<script src="<?= KILLI_DIR ?>/js/jquery/jquery.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.core.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.widget.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.mouse.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.draggable.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.position.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.resizable.js"></script>
					<script src="<?= KILLI_DIR ?>/js/jquery/ui/jquery.ui.dialog.js"></script>

					<!-- KILLI -->
					<link type="text/css" rel="stylesheet" href="<?= KILLI_DIR ?>/css/UI.css" />
				</head>

				<body>
					<div id="error_dialog" style="color: #FFFFFF; background-color: <?= $popup_color; ?>;">
						<br /><u><?= $data['err_type'] ?> :</u>
						<br /><br /> <?= htmlentities($data['message']); ?>

						<br /><br /><u>Where : </u>
						<br /><br /><?= htmlentities($data['file']); ?> (line <?= htmlentities($data['line']); ?>)

						<?php

						if(isset($data['curl']))
						{
							?>
							<br /><br /><u>Curl data :</u>
							<br /><br /> <?= nl2br(str_replace(' ','&nbsp;',str_replace("\t",'	',var_export($data['curl']->getInfos(),true)))); ?>
							<?php
						}

						?>
						<br /><br /><u>Trace :</u>
						<br /><br /> <?= nl2br($data['trace']); ?>

						<br /><br /><u>Last Query :</u>
						<br /><br /><?= htmlentities($data['last_query']); ?>

						<br /><br /><u>Last SQL Error :</u>
						<br /><br /><?= htmlentities($data['last_sql_error']); ?>

						<br /><br /><u>Code (<?= htmlentities($data['file']); ?>) :</u>
						<br /><br /><?= nl2br(str_replace(' ','&nbsp;',str_replace("\t",'	',$lines))); ?><br />

						<?php
							if(class_exists('Debug'))
							{
								$dataInException = Debug::$exceptionData;
								if(count($dataInException) > 0)
								{
									echo '<div style="font-size: 16px; color: #000; background-color: #FFF; margin-bottom: 20px;">';
									foreach($dataInException AS $_data)
									{
										echo $_data;
									}
									echo '</div>';
								}
							}
						?>
					</div>
					<script>
					$(document).ready(function(){
						$("#error_dialog").dialog({ height: 530,title: 'Exception',width: 800, modal: false });
					});
					</script>
				</body>
			</html>
		<?php

		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function renderJSONError(array $data)
	{
		$is_system = FALSE;
		if($data['profils'] !== NULL && defined('SYSTEM_PROFIL_ID') && SYSTEM_PROFIL_ID !== NULL)
		{
			$profil_id_list = explode( ', ', $data['profils'] );

			$is_system = in_array( SYSTEM_PROFIL_ID, $profil_id_list );
		}

		// on est en prod, c'est une erreur critique (ni JSONException ni NonBlockinException), et l'utilisateur n'a pas le profil SYSTEM
		if(!$is_system && $data['type'] != 'json' && $data['type'] != 'user' && !DISPLAY_ERRORS)
		{
			// on affiche pas le message d'erreur d'origine pour des raisons de sécurité
			$json_data = array('error'=>'Erreur interne');
		}
		else
		{
			// On affiche le message de l'exception
			$json_data = array('error' => $data['message']);

			if(DISPLAY_ERRORS)
			{
				// on dump les données pour debug
				$json_data['dump'] = $data;
			}
		}

		header('Content-type: application/json; charset=utf-8');

		echo json_encode($json_data);

		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function renderUserError(array $data)
	{
		// on change le mode pour UI
		$_GET['view']='form';

		// tentative de rendu, normalement la page n'a pas encore été générée (sinon header_already_sent va remonter)

		global $hUI;

		$hUI = new UI();
		$hUI->render(KILLI_DIR . '/template/error.xml', 0, array('user_message'=>$data['user_message'] ));

		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function renderPageError(array $data)
	{
		try
		{
			if(ob_get_level() >= 2)
			{
				ob_end_clean();
			}
		}
		catch (Exception $e) {}

		if (ERROR_PAGE_FILE!==NULL && file_exists(ERROR_PAGE_FILE))
		{
			echo file_get_contents(ERROR_PAGE_FILE);
		}
		else if(PUBLIC_ERROR_MESSAGE!==NULL)
		{
			echo PUBLIC_ERROR_MESSAGE;
		}
		else
		{
			echo 'Erreur critique.';
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function sendErrorMail(array $data)
	{
		if(!self::$_send_mail)
		{
			return TRUE;
		}

		$lines = self::renderFileLines($data);

		$message = "<h1>".$data['err_type']."</h1>";

		$message .= '<u>Requested page</u> : '.$data['request_uri']."\n\n";

		$trace_table = explode("\n", $data['trace']);

		if($data['type']=='user' || $data['type']=='nonblocking')
		{
			$message .= "L'utilisateur n'a pas eu d'erreur critique.\n\n";
		}

		$message .= "<u>Heure serveur</u> : ".date('H:i:s d/m/Y P')."\n\n";
		$message .= '<u>User</u> : '.$data['user']."\n\n";
		$message .= '<u>Profils</u> : '.$data['profils']."\n\n";
		$message .= '<u>Message</u> : '.$data['message']."\n\n";
		$message .= '<u>Memory used</u> : '.$data['mem_used']."\n\n";
		$message .= '<u>Ellapsed time</u> : '.$data['ellapsed_time']."\n\n";
		$message .= '<u>Where</u> : '.$data['file'].'('.$data['line'].')'."\n\n";
		$message .= '<u>Code</u> ('.$data['file'].') : '."\n\n".$lines."\n\n";

		if(isset($data['curl']))
		{
			$message.= '<u>Curl data</u> : '.var_export($data['curl']->getInfos(),true)."\n\n";
		}

		foreach ($trace_table as $idx => $trace_line)
		{
			if ($idx == 0)
			{
				$message .= '<u>Trace</u> : ';
			}
			else
			{
				$message .= '		';
			}
			$message .= $trace_line."\n";
		}
		$message .= "\n";

		$message .= '<u>Last Query</u> : '.$data['last_query']."\n\n";
		$message .= '<u>Last SQL Error</u> : '.$data['last_sql_error']."\n\n";
		$message .= '<u>SQL stats</u> : '.$data['db_stat']."\n\n";
		$message .= "<u>REQUEST</u> : ".var_export($data['request'],true)."\n\n";
		$message .= "<u>SERVER</u> : ".var_export($data['server'],true)."\n\n";
		$message .= "<u>FILES</u> : ".var_export($data['files'],true)."\n\n";

		$message = nl2br(str_replace(' ','&nbsp;',str_replace("\t",'	',$message)));

		switch ($data['type'])
		{
			case 'nonblocking' :
				$color = '#DBFFDB';
			break;

			case 'user' :
				$color = '#DDF0FF';
			break;

			default :
				$color = '#FFBDBD';
			break;

		}

		$message = '<html><body style="background-color:'.$color.';"><font style="font-size:10pt;" face="courier new, courier, monaco, monospace, sans-serif">' . $message .  '</font></body></html>';

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

		if (ERROR_EMAILS!==NULL)
		{
			$email_list = explode(',',ERROR_EMAILS);
			foreach($email_list as $email)
			{
				mail($email,ERROR_MAIL_SUBJECT,$message,$headers);
			}
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function logError(array $data)
	{
		try {
			$file = fopen(LOG_FILE,'a+');
			fwrite($file,date("[D d/m/Y H:i:s]")." [".$data['user']."] [".$data['file'].":".$data['line']."] [".$data['message']."]\n");
			fclose($file);
		}
		catch (Exception $e) {}
		
		return TRUE;
	}
	//---------------------------------------------------------------------
	protected static function renderFileLines(array $data)
	{
		$before = 8;
		$after  = 5;
		$min	= $data['line'] - $before;
		$max	= $data['line'] + $after;

		if($min<=0)
		{
			$min = 1;
		}

		$nNums  = strlen((string)$max);
		$cmd	= 'sed -n "'.$min.','.$max.'p" "'.$data['file'].'"';

		$output = array();
		exec($cmd, $output);

		$source = '';
		foreach ($output as $idx => $line)
		{
			$curLine = str_pad((string)($min + $idx), $nNums, ' ', STR_PAD_LEFT).': '.htmlentities($line,ENT_COMPAT,'UTF-8');
			if (($min + $idx) == $data['line'])
			{
				$source .= '<b><i>'.$curLine.'</i></b>';
			}
			else
			{
				$source .= $curLine;
			}
			$source .= "\n";
		}

		return $source;
	}
}
