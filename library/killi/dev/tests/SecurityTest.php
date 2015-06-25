<?php

/**
 *  @class SecurityTest
 *  @Revision $Revision: 4516 $
 *
 */

class SecurityTest extends Killi_TestCase
{
	private $hSecurity = null;

	public function __construct()
	{
		$this->hSecurity = new Security();
	}

	public function testCrypt()
	{
		$nb_test=0;
		while($nb_test != 1000)
		{
			$value_to_crypt = $this->generate_value_to_crypt();

			$this->assertTrue($this->hSecurity  ->crypt($value_to_crypt, $crypted_value));
			$this->assertTrue(Security			::crypt($value_to_crypt, $crypted_value2));

			$nb_test++;
		}

		$nb_test=0;
		while($nb_test != 1000)
		{
			$value_to_crypt = rand(1, 99999999999999999999);

			$this->assertTrue($this->hSecurity  ->crypt($value_to_crypt, $crypted_value));
			$this->assertTrue(Security			::crypt($value_to_crypt, $crypted_value2));

			$nb_test++;
		}

	}

	public function testEmptyCrypt()
	{
		$value_to_crypt = '';

		$this->assertTrue($this->hSecurity  ->crypt($value_to_crypt, $crypted_value));
		$this->assertTrue(Security			::crypt($value_to_crypt, $crypted_value2));

		$this->assertEquals('', $crypted_value);
		$this->assertEquals('', $crypted_value2);
	}

	public function testEmptyDeCrypt()
	{
		$value_to_decrypt = '';

		$this->assertTrue($this->hSecurity  ->decrypt($value_to_decrypt, $decrypted_value));
		$this->assertTrue(Security			::decrypt($value_to_decrypt, $decrypted_value2));

		$this->assertEquals($decrypted_value, $decrypted_value2);
		$this->assertEquals('', $decrypted_value);
		$this->assertEquals('', $decrypted_value2);
	}

	private function generate_value_to_crypt()
	{
		$passchar = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789";
		srand(microtime(true));

		$tmp_pass = "";
		for ($i=0; $i<8;$i++)
		{
			$tmp_pass .= $passchar[ rand()%strlen($passchar)];
		}

		return $tmp_pass;
	}

	public function testOldDeCrypt()
	{
		$this->assertTrue($this->hSecurity  ->decrypt('a3eb0b29f12efbbe93b8c888f96b2b0c', $decrypted_value));
		$this->assertTrue(Security			::decrypt('a3eb0b29f12efbbe93b8c888f96b2b0c', $decrypted_value2));

		$this->assertEquals($decrypted_value, $decrypted_value2);
		$this->assertEquals(351, $decrypted_value);

	}

	public function testOldComplexeCryptNDecrypt()
	{
		$array_to_crypt = array(array('a'=>'b'), 11=>array(22=>33), 'c');

		$serialized_to_crypt = serialize($array_to_crypt);

		$this->assertTrue(Security::crypt($serialized_to_crypt, $crypted_value));
		$this->assertTrue(Security::decrypt($crypted_value, $decrypted_value));

		$deserialized_value = unserialize($decrypted_value);

		$this->assertEquals('b', $deserialized_value[0]['a']);
		$this->assertEquals(array(22=>33), $deserialized_value[11]);
	}

	public function testDeCrypt()
	{
		$nb_test=0;
		while($nb_test != 1000)
		{
			$value_to_crypt = $this->generate_value_to_crypt();

			$this->assertTrue($this->hSecurity  ->crypt($value_to_crypt, $crypted_value));
			$this->assertTrue(Security			::crypt($value_to_crypt, $crypted_value2));
			$this->assertTrue(Security			::crypt($value_to_crypt, $crypted_value3));

			$this->assertTrue($this->hSecurity  ->decrypt($crypted_value, $decrypted_value));
			$this->assertTrue(Security			::decrypt($crypted_value2, $decrypted_value2));

			$this->assertEquals($value_to_crypt, $decrypted_value);
			$this->assertEquals($value_to_crypt, $decrypted_value2);

			$nb_test++;

		}

		$nb_test=0;
		while($nb_test != 1000)
		{
			$value_to_crypt = rand(1, 99999999999999999999);

			$this->assertTrue($this->hSecurity  ->crypt($value_to_crypt, $crypted_value));
			$this->assertTrue(Security			::crypt($value_to_crypt, $crypted_value2));

			$this->assertTrue($this->hSecurity  ->decrypt($crypted_value, $decrypted_value));
			$this->assertTrue(Security			::decrypt($crypted_value2, $decrypted_value2));

			$this->assertEquals($decrypted_value, $decrypted_value2);
			$this->assertEquals($value_to_crypt, $decrypted_value);
			$this->assertEquals($value_to_crypt, $decrypted_value2);

			$nb_test++;

		}
	}

	public function testSecure()
	{
		$secured_value = Security::secure("='(àçè_à##à  \n\"'(é\"");

		$this->assertEquals('=\\\'(àçè_à##à  \n\"\\\'(é\"', $secured_value);

	}

	public function testcryptNDecryptNULL()
	{
		$value_to_crypt = null;

		$this->assertTrue(Security::crypt($value_to_crypt, $crypted_value));
		$this->assertEquals($crypted_value, '');

		$this->assertTrue(Security::decrypt($crypted_value, $decrypted_value));
		$this->assertEquals($decrypted_value, '');

	}

	public function testcryptNDecryptEmpty()
	{
		$value_to_crypt = '';

		$this->assertTrue(Security::crypt($value_to_crypt, $crypted_value));
		$this->assertEquals($crypted_value, '');

		$this->assertTrue(Security::decrypt($crypted_value, $decrypted_value));
		$this->assertEquals($decrypted_value, '');

	}

	public function testcryptNDecryptZero()
	{
		$value_to_crypt = 0;

		$this->assertTrue(Security::crypt($value_to_crypt, $crypted_value));
		$this->assertNotEquals($crypted_value, '');

		$this->assertTrue(Security::decrypt($crypted_value, $decrypted_value));
		$this->assertEquals($decrypted_value, 0);

	}

	public function testcryptRecursive()
	{
		$value_to_crypt = array(1, 2, array(3, 4, array(5)), 6);

		$this->assertTrue(Security::crypt($value_to_crypt, $crypted_value));

		$this->assertTrue(Security::decrypt($crypted_value, $decrypted_value));

		$this->assertEquals($decrypted_value[0], 1);
		$this->assertEquals($decrypted_value[1], 2);
		$this->assertEquals($decrypted_value[2][0], 3);
		$this->assertEquals($decrypted_value[2][1], 4);
		$this->assertEquals($decrypted_value[2][2][0], 5);
		$this->assertEquals($decrypted_value[3], 6);
	}

    public function testCryptAccents()
    {
        $value_to_crypt = "château d'eau";

		$this->assertTrue(Security::crypt($value_to_crypt, $crypted_value));
		$this->assertEquals($crypted_value, 'AV,mxBdWBBAQARQGAkQ_');

		$this->assertTrue(Security::decrypt($crypted_value, $decrypted_value));
		$this->assertEquals($decrypted_value, $value_to_crypt);

    }
}
