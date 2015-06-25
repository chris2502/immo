<?php

/**
 *
 *  @class Mailer
 *  @Revision $Revision: 4663 $
 *
 */

class Mailer
{
	/**
	 * Liste des emails qui seront envoyés si aucune exception ou erreurs ont été levées.
	 * @type array
	 */
	public static $email_instance_list;

	protected static $_enabled = TRUE;

	protected $_mailer_instance = NULL;

	protected $_users_id_list = array();

	protected $_subject = NULL;
	protected $_title = NULL;
	protected $_message = NULL;

	protected $_attachements = array();
	protected $_renderer = NULL;
	protected $_html_content = NULL;

	public function __construct()
	{
		$this->_mailer_instance = new PHPMailer();
		$this->_renderer = new RenderTextMail();

		$this->_mailer_instance->SMTPDebug = FALSE;
		$this->_mailer_instance->isSMTP();
		$this->_mailer_instance->Host = 'localhost';
		$this->_mailer_instance->CharSet = 'UTF-8';
		$this->_mailer_instance->SMTPDebug = FALSE;

		$this->_mailer_instance->setFrom(APP_MAIL_FROM, 'No reply');

		if (defined('SMTP_HOST'))
		{
			$this->_mailer_instance->Host = SMTP_HOST;
		}
		//$this->_mailer_instance->setLanguage('fr', '/optional/path/to/language/directory/');

		if (defined('MAILER_DEBUG'))
		{
			$this->_mailer_instance->Debugoutput = 'html';
		}
	}

	public static function SMTPDebug($bool)
	{
		$this->_mailer_instance->SMTPDebug = $bool;
		return TRUE;
	}

	public static function setEnable($bool)
	{
		self::$_enabled = $bool;
		return TRUE;
	}

	public static function isEnable()
	{
		return self::$_enabled == TRUE;
	}

	public function setRenderer($renderer)
	{
		$this->_renderer = $renderer;
		return $this;
	}

	public function isHTML($bool)
	{
		$this->_mailer_instance->isHTML($bool);
		if ($bool)
		{
			$this->setRenderer(new RenderHTMLMail());
		}
		return $this;
	}

	public function setFrom($email, $name = NULL)
	{
		$this->_mailer_instance->setFrom($email, $name);
		return $this;
	}

	public function setReplyTo($email, $name = NULL)
	{
		$this->_mailer_instance->addReplyTo($email, $name);
		return $this;
	}

	public function setMail($email, $name = NULL)
	{
		if (defined('MAILER_DEBUG'))
		{
			return $this;
		}
		$this->_mailer_instance->addAddress($email, $name);
		return $this;
	}

	public function setMailCc($email, $name = NULL)
	{
		if (defined('MAILER_DEBUG'))
		{
			return $this;
		}
		$this->_mailer_instance->addCC($email, $name);
		return $this;
	}

	public function setMailCci($email, $name = NULL)
	{
		if (defined('MAILER_DEBUG'))
		{
			return $this;
		}
		$this->_mailer_instance->addBCC($email, $name);
		return $this;
	}

	public function setUser($user_id)
	{
		$this->_users_id_list['to'][$user_id] = $user_id;
		return $this;
	}

	public function setUserCc($user_id)
	{
		$this->_users_id_list['cc'][$user_id] = $user_id;
		return $this;
	}

	public function setUserCci($user_id)
	{
		$this->_users_id_list['bcc'][$user_id] = $user_id;
		return $this;
	}

	public function setSubject($subject)
	{
		$this->_subject = $subject;
		return $this;
	}

	public function setTitle($title)
	{
		$this->_title = $title;
		return $this;
	}

	public function setMessage($message)
	{
		$this->_message = $message;
		return $this;
	}

	public function setDocument($document_id_list)
	{
		if (is_int($document_id_list))
		{
			$document_id_list = array($document_id_list);
		}

		$hORM = ORM::getORMInstance('document');
		$document_list = array();
		$hORM->browse(
			$document_list,
			$total,
			array('file_name', 'hr_name', 'mime_type'),
			array(array('document_id', 'IN', $document_id_list))
		);

		$this->_attachements = $document_list;

		return $this;
	}

	protected function _setMailByUserId()
	{
		foreach ($this->_users_id_list as $type => $user_data)
		{
			$id = reset($user_data);
			$user_id_list[$id] = $id;
		}

		$hORM = ORM::getORMInstance('user');
		$user_list = array();
		$hORM->read(
			$user_id_list,
			$user_list,
			array('nom', 'prenom', 'mail')
		);

		foreach ($this->_users_id_list as $type => $user_data)
		{
			$id = reset($user_data);

			$email = $user_list[$id]['mail']['value'];
			$name = $user_list[$id]['prenom']['value'] . ' ' . $user_list[$id]['nom']['value'];

			switch ($type)
			{
				case 'to':
					$this->_mailer_instance->addAddress($email, $name);
					break;

				case 'cc':
					$this->_mailer_instance->addCC($email, $name);
					break;

				case 'bcc':
					$this->_mailer_instance->addBCC($email, $name);
					break;

				default:
					throw new Exception("Mail Users setup divided universe by zero.", 1);
					break;
			}
		}

		return TRUE;
	}

	public function send()
	{
		if (!self::$_enabled)
		{
			return TRUE;
		}

		if (!defined('MAILER_DEBUG') && !empty($this->_users_id_list))
		{
			$this->_setMailByUserId();
		}

		if (defined('MAILER_DEBUG'))
		{
			$sender = explode(',', MAILER_DEBUG);

			if (isset($sender[1]))
			{
				$this->_mailer_instance->addAddress($sender[0], $sender[1]);
			}
			else
			{
				$this->_mailer_instance->addAddress($sender[0]);
			}
		}

		/**
		 * On defini les destinataires cachés
		 */
		if(defined('CCI_OF_ALL_EMAILS'))
		{
			$mails_cci = explode(',',CCI_OF_ALL_EMAILS);
			foreach ($mails_cci as $v)
			{
				$this->setMailCci($v);
			}
		}

		$this->_mailer_instance->Subject = $this->_subject;

		$this->_mailer_instance->Body = $this->_renderer->setTitle($this->_title)->setContent($this->_message)->render();
		$this->_mailer_instance->IsHTML(true);

		/**
		 * Pièce jointes.
		 */
		foreach ($this->_attachements as $document)
		{
			$this->_mailer_instance->AddAttachment($document['file_name']['value'], $document['hr_name']['value']);
		}

		self::$email_instance_list[] = $this->_mailer_instance;

		return TRUE;
	}

	public static function commit()
	{
		Debug::log('Mailer commit');

		if (!empty(Mailer::$email_instance_list))
		{
			foreach (Mailer::$email_instance_list as $email_instance)
			{
				$result = $email_instance->Send();
				if (!$result)
				{
					throw new Exception('Une erreur s\'est produite dans l\'envoi du mail : ' . $email_instance->ErrorInfo, 1);
				}
			}
			Mailer::$email_instance_list = NULL;
		}
	}

	public function getHTMLContent()
	{
		$content = $this->_renderer->setTitle($this->_title)->setContent($this->_message)->render()."\r\n\r\n";

		return $content;
	}

	public function getContent()
	{
		if (!isset($this->_html_content))
		{
			return 'getContent() must be call after send().';
		}
		return $this->_html_content;
	}

}
