<?php

/**
 *
 *  @class CSVReader
 *  @Revision $Revision: 4139 $
 *
 */

class CSVReader
{
	protected $file_handler = NULL;
	protected $file_name = NULL;
	protected $file_path = NULL;
	protected $file_dir = NULL;
	protected $header = NULL;
	protected $total_line = NULL;
	protected $separator = NULL;
	public $auto_unlink = FALSE;
	//.....................................................................
	/**
	* Lecteur de CSV
	* @param string $file
	* @throws Exception
	*/
	public function __construct($file, $separator = ';')
	{
		$pathinfo = pathinfo ( $file );

		$this->separator = $separator;
		$this->file_path = $file;
		$this->file_name = $pathinfo ['basename'];
		$this->file_dir = $pathinfo ['dirname'];
		$this->file_handler = fopen ( $this->file_path, 'r' );

		if ($this->file_handler === FALSE)
		{
			throw new Exception ( 'Unable to open file : ' . $this->file_path );
		}

		$this->getHeader ();
		$this->readLine ();
	}
	//.....................................................................
	public function __destruct()
	{
		if ($this->file_handler !== NULL)
		{
			fclose ( $this->file_handler );
		}

		if($this->auto_unlink === TRUE)
		{
			unlink($this->file_path);
		}
	}
	//.....................................................................
	/**
	* Nombre de lignes dans le CSV
	* @return number
	*/
	public function getTotalLines()
	{
		if ($this->total_line === NULL)
		{
			list ( $this->total_line, $foo ) = explode ( " ", exec ( 'wc -l ' . escapeshellarg ( $this->file_path ) ) );
		}

		return $this->total_line - 1;
	}
	//.....................................................................
	/**
	* Retourne le nom du fichier
	* @return string
	*/
	public function getFileName()
	{
		return $this->file_name;
	}
	//.....................................................................
	/**
	* Retourne le chemin d'acces du fichier
	* @return string
	*/
	public function getFilePath()
	{
		return $this->file_path;
	}
	//.....................................................................
	/**
	* Retourne le dossier du fichier
	* @return string
	*/
	public function getFileDir()
	{
		return $this->file_dir;
	}
	//.....................................................................
	/**
	 * Retourne le contenu du fichier
	 * @return string
	 */
	 public function getFileContent()
	 {
	 	return file_get_contents($this->file_path);
	 }
	//.....................................................................
	/**
	 * Vide le contenu du fichier
	 */
	 public function clear()
	 {
	 	ftruncate($this->file_handler, 0);
	 	$this->seek ( 0 );
	 }
	//.....................................................................
	/**
	* Retourne la prochaine ligne du CSV
	* NULL si le curseur est à la fin du fichier
	* @return multitype:array|NULL
	* @throws Exception
	*/
	public function readLine($i = NULL)
	{
		$line = array ();
		$raw = $this->getLine ($i);

		if ($raw === FALSE)
		{
			return NULL;
		}

		foreach ( $raw as $i => $data )
		{
			if (! isset ( $this->header [$i] ))
			{
				throw new Exception ( sprintf('Unkown column %d (header has %d columns)', ($i+1),count($this->header) ) );
			}
			$line [$this->header [$i]] = trim ( $data );
		}

		unset($raw);

		if(count($line) != count($this->header))
		{
			throw new Exception ( sprintf('Column count does not match with header : %d<=>%d', count($line),count($this->header) ) );
		}

		return $line;
	}
	//.....................................................................
	/**
	* Retourne les en-têtes
	* @return array
	*/
	public function getHeader()
	{
		if ($this->header === NULL)
		{
			$offset = $this->offset ();
			$this->seek ( 0 );
			$raw = $this->getLine ();
			$this->header=array();
			foreach ( $raw as $data )
			{
				$this->header[] = trim ( $data );
			}
			$this->seek ( $offset );

			unset($raw);

			// fix bom
			$this->header[0] = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '', $this->header[0]);
		}
		return $this->header;
	}
	//.....................................................................
	//
	// PRIVATES
	//
	//.....................................................................
	private function offset()
	{
		return ftell ( $this->file_handler );
	}
	//.....................................................................
	private function seek($curor)
	{
		return fseek ( $this->file_handler, $curor );
	}
	//.....................................................................
	private function getLine($i = NULL)
	{
		if ($this->file_handler === NULL)
		{
			throw new Exception ( 'File not opened !' );
		}

		if($i === NULL)
		{
			$raw = fgetcsv ( $this->file_handler, 0, $this->separator );
		}
		else
		{
			exec("sed -n ".((int)$i+1)."p ".escapeshellarg($this->file_path), $raw_csv);

			$raw = str_getcsv($raw_csv[0], $this->separator);
		}

		if ($raw === NULL)
		{
			throw new Exception ( 'Invalid file handle !' );
		}

		if (! is_array ( $raw ) && $raw !== FALSE)
		{
			throw new Exception ( 'Unable to read line !' );
		}

		return $raw;
	}
}
