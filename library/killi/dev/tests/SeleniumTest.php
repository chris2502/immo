<?php

class SeleniumTest extends Killi_UITestCase
{
	public static $seleneseDirectory = './tests/selenium/';

	public static $browsers = array(
		array(
			'name'    => 'Firefox on Linux',
			'browser' => '*firefox',
			'host'    => TEST_HOST,
			'port'    => 4444,
			'timeout' => 30000,
		));

    protected function setUp()
    {
	    $this->setBrowser('firefox');
		$this->setBrowserUrl(TEST_APP_URL);
    }
}
