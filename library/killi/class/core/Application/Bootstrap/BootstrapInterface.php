<?php

namespace Killi\Core\Application\Bootstrap;

/**
 *
 * @class  BootstrapInterface
 * @Revision $Revision: 4527 $
 *
 */

use \Killi\Core\Application\Application;

interface BootstrapInterface
{
	/**
	 * Run the bootstrap
	 *
	 * @param Application $app
	 */
	public function bootstrap(Application $app);
}
