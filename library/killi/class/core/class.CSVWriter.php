<?php

/**
 *
 *  @class CSVWriter
 *  @Revision $Revision: 4139 $
 *
 */

class CSVWriter extends CSVReader
{

	/**
	 * Generateur de CSV
	 * @param string $file
	 * @param array $header
	 * @throws Exception
	 */
	public function __construct($file, array $header, $separator = ';')
	{
		$this->file_path = $file;
		$this->file_handler = fopen ( $this->file_path, 'c+' );

		if ($this->file_handler === FALSE)
		{
			throw new Exception ( 'Unable to open file : ' . $this->file_path );
		}

		$pathinfo = pathinfo ( $file );

		$this->separator = $separator;
		$this->file_name = $pathinfo ['basename'];
		$this->file_dir = $pathinfo ['dirname'];

		if(($t = $this->getTotalLines() )> 0)
		{
			$this->getHeader ();

			$i=0;
			while($i <= $t)
			{
				$this->readLine();
				$i++;
			}
		}
		else
		{
			$this->writeLine ( $header );
			$this->header = $header;
		}
	}
	//.....................................................................
	/**
	* Ajoute une ligne dans le CSV
	* @param array $line
	*/
	public function writeLine(array $line)
	{
		if($this->header !== NULL && count($line) != count($this->header))
		{
			throw new Exception ( 'Unable to write CSV line : column count does not match ! ('.count($this->header).' column header, '.count($line) .' column in line)' );
		}

		fputcsv ( $this->file_handler, $line, $this->separator);

		$this->total_line ++;
	}
}
