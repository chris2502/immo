<?php

/**
 *  @class ConstraintsTest
 *  @Revision $Revision: 2736 $
 *
 */

class ConstraintsTest extends Killi_TestCase
{

	public static function main()
	{
		return new ConstraintsTest('main');
	}

	/**
	 * @dataProvider validSizeProvider
	 */
	public function testValidCheckSize($value, $min, $max)
	{
		$this->assertTrue(Constraints::checkSize($value, $min, $max, $error));
	}

    public function validSizeProvider()
    {
    	return array(
    		array(1, 0, 10),
    	);
    }

    /**
     * @dataProvider wrongSizeProvider
     */
    public function testWrongCheckSize($value, $min, $max)
    {
    	$this->assertFalse(Constraints::checkSize($value, $min, $max, $error));
    	$this->assertNotNull($error);
    }

    public function wrongSizeProvider()
    {
    	return array(
    			array(0, 0, 0),
    	);
    }

}
