<?php

namespace Killi\Core\Application\Bootstrap;

/**
 *
 * @class  HandleExceptions
 * @Revision $Revision: 4527 $
 *
 */

use \ExceptionManager;
use \Killi\Core\Application\Application;

class HandleExceptions implements BootstrapInterface
{
	public function bootstrap(Application $app)
	{
		ExceptionManager::enable();
	}
}
