<?php

/*
 * La classe stream permet de détecter l'écriture dans un stream (handler d'un
 * fopen, d'un fsocketopen, etc)
 */
class FileStream
{
	private $fp = NULL;
	/*
	 * Ouverture du stream, obligatoire
	 */
	function stream_open ($path, $mode, $options, &$opened_path)
	{
		$this->fp = tmpfile();
		
		return true;
	}
	
	/*
	 * Lorsque quelque chose est écrit dans la stream
	 */
	public function stream_write ($data)
	{
		return fwrite($this->fp, $data);
	}
	
	/*
	 * Lorsque des headers sont écrits dans le stream
	 */
	public static function HandleHeaderLine ($curl, $header_line)
	{
		// obligatoire !
		return strlen($header_line);
	}
	
	public function stream_eof ()
	{
		return feof($this->fp);
	}
	
	public function stream_read ($length)
	{
		return fread($this->fp, $length);
	}
	
	public function stream_close ()
	{
		return fclose($this->fp);
	}
	
	public function stream_gets ($length)
	{
		return fgets($this->fp, $length);
	}
	
	public function stream_seek ($offset)
	{
		return fseek($this->fp, $offset);
	}
}
