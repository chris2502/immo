<?php

namespace Killi\Core\Logger;

/**
 *
 * @class FileLogger
 * @Revision $Revision: 4576 $
 *
 */

use Psr\Log\AbstractLogger;

class FileLogger extends AbstractLogger
{
	protected $filename;
	protected $file_descriptor;

	public function __construct($filename)
	{
		$this->filename = $filename;
		$this->file_descriptor = fopen($filename, 'a+');
	}

	public function __destruct()
	{
		fclose($this->file_descriptor);
	}

	public function log($level, $message, array $context = array())
	{
		$context_suite = join("\n", $context);

		$print  = date('[D d/m/Y H:i:s]');
		$print .= ' ['.$level.'] ';
		$print .= $message;
		if(!empty($context_suite))
		{
			$print .= ' :'."\n".$context_suite;
		}
		$print .= "\n";

		fwrite($this->file_descriptor, $print);
		return TRUE;
	}
}
