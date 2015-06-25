<?php

/**
 *  @class FTP
 *  @Revision $Revision: 4139 $
 */

class FTP
{
	private $_hostname;
	private $_username;
	private $_password;

	protected $_connection;
	protected $_passive;
	protected $_cwd;

	public function __construct($hostname, $username, $password, $passive_mode = TRUE)
	{
		$this->_hostname = $hostname;
		$this->_username = $username;
		$this->_password = $password;
		$this->_passive = $passive_mode;
		$this->_connect();
		$this->_cwd = '/';
	}

	private function _connect()
	{
		$this->_connection = ftp_connect($this->_hostname);
		if(!$this->_connection)
		{
			throw new Exception('FTP CONNECT FAILED !');
		}

		$login_result = ftp_login($this->_connection, $this->_username, $this->_password);
		if(!$login_result)
		{
			throw new Exception('FTP CONNECT FAILED !');
		}

		ftp_pasv($this->_connection, $this->_passive);
	}

	protected function checkConnection()
	{
		if(ftp_nlist($this->_connection, '.') === FALSE)
		{
			$this->_connect();
		}
	}

	public function __destruct()
	{
		ftp_close($this->_connection);
	}

	// Only absolute path
	protected function cd($dir)
	{
		$this->checkConnection();

		if(ftp_chdir($this->_connection, $dir) === FALSE)
		{
			throw new Exception('ftp_chdir FAILED !');
		}
		$this->_cwd = $dir;
	}

	public function ls($dir = '.', $filter = NULL)
	{
		if($dir != '.')
		{
			$old_dir = $this->_cwd;
			$this->cd($dir);
		}

		$data = ftp_nlist($this->_connection, '.');
		if($data === FALSE)
		{
			throw new Exception('ftp_nlist FAILED ! Unable to access : ' . $dir);
		}

		if($filter != NULL)
		{
			$result = array();

			foreach($data AS $file)
			{
				if(strpos($file, $filter) !== FALSE)
				{
					$result[] = $file;
				}
			}
		}
		else
		{
			$result = $data;
		}

		if($dir != '.')
		{
			$this->cd($old_dir);
		}
		return $result;
	}

	public function get($dst_file, $src_file)
	{
		$this->checkConnection();

		if(!ftp_get($this->_connection, $dst_file, $src_file, FTP_BINARY))
		{
			throw new Exception('ftp_get FAILED !');
		}
		return TRUE;
	}

	public function put($dst_file, $src_file)
	{
		$this->checkConnection();

		if(!ftp_put($this->_connection, $dst_file, $src_file, FTP_BINARY))
		{
			throw new Exception('ftp_put FAILED !');
		}
		return TRUE;
	}

	/* TODO: Création récursive et non plantage lorsque le dossier existe déjà. */
	public function mkdir($dir)
	{
		$this->checkConnection();

		$cd = dirname($dir);
		$file = basename($dir);

		$old_dir = $this->_cwd;
		$this->cd($cd);

		$mk = ftp_mkdir($this->_connection, $file);
		if($mk !== FALSE)
		{
			throw new Exception('ftp_mkdir FAILED !');
		}
		$this->cd($old_dir);

		return TRUE;
	}

	public function move($from, $to)
	{
		$filename = basename($from);

		if(!ftp_rename($this->_connection, $from,  $to . '/' . $filename))
		{
			throw new Exception('ftp_rename FAILED !');
		}
		return TRUE;
	}

	public function rename($from, $to)
	{
		if(!ftp_rename($this->_connection, $from,  $to))
		{
			throw new Exception('ftp_rename FAILED !');
		}
		return TRUE;
	}
}
