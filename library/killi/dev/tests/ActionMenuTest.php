<?php

/**
 *  @class ActionMenuTest
 *  @Revision $Revision: 4433 $
 *
 */

class ActionMenuTest extends Killi_TestCase
{

	/**
	 * @expectedException Exception
	 */
	public function testWrongForeignKey()
	{
		$hORM = ORM::getORMInstance('actionmenu');

		/* Création de menu. */
		$data = array('actionmenu_name' => 'actionmenu_name_test',
				'actionmenu_function' => 'actionmenu_function_test',
				'actionmenu_label' => 'actionmenu_label_test',
				'actionmenu_parent' => -1);

		$this->assertTrue($hORM->create($data, $id));
	}


	public function testValidOperation()
	{
		$hInstance = new ActionMenuMethod();
		$hProfilInstance = new ProfilMethod();

		$id_list = array();

		/* Création de menu. */
		$data1 = array('actionmenu_name' => 'actionmenu_name_test',
				'actionmenu_function' => 'actionmenu_function_test',
				'actionmenu_label' => 'actionmenu_label_test',
				'actionmenu_parent' => NULL);

		$this->assertTrue($hInstance->create($data1, $id1));
		$this->assertGreaterThan(0, $id1);

		$id_list[] = $id1;

		/* Création d'un sous menu. */
		$data2 = array('actionmenu_name' => 'actionmenu_name_test2',
				'actionmenu_function' => 'actionmenu_function_test2',
				'actionmenu_label' => 'actionmenu_label_test2',
				'actionmenu_parent' => $id1);

		$this->assertTrue($hInstance->create($data2, $id2));
		$this->assertGreaterThan(0, $id2);

		$id_list[] = $id2;

		/* Test getReferenceString */
		$references = array();
		$hInstance->getReferenceString($id_list, $references);
		$this->assertEquals(2, count($references));

		$this->assertNotEmpty($references[$id1]);
		$this->assertNotEmpty($references[$id2]);
		$this->assertNotEquals($references[$id1], $references[$id2]);

		/* Récupération de menu, vérification que le create a bien fait son boulot. */
		$hORM = ORM::getORMInstance('actionmenu');

		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total,
				array('actionmenu_name', 'actionmenu_label', 'actionmenu_function', 'actionmenu_parent'),
				array(array('actionmenu_id', 'in', $id_list))));

		$this->assertEquals(2, count($objects));

		$this->assertNotEmpty($objects[$id1]);
		$this->assertEquals('actionmenu_name_test', $objects[$id1]['actionmenu_name']['value']);
		$this->assertEquals('actionmenu_function_test', $objects[$id1]['actionmenu_function']['value']);
		$this->assertEquals('actionmenu_label_test', $objects[$id1]['actionmenu_label']['value']);
		$this->assertNull($objects[$id1]['actionmenu_parent']['value']);

		$this->assertNotEmpty($objects[$id2]);
		$this->assertEquals('actionmenu_name_test2', $objects[$id2]['actionmenu_name']['value']);
		$this->assertEquals('actionmenu_function_test2', $objects[$id2]['actionmenu_function']['value']);
		$this->assertEquals('actionmenu_label_test2', $objects[$id2]['actionmenu_label']['value']);
		$this->assertEquals($id1, $objects[$id2]['actionmenu_parent']['value']);


		// menu admin view = 1
		$_GET['primary_key'] = ADMIN_PROFIL_ID;
		$my_object_list = array();
		$hProfilInstance->getMenuList($my_object_list);

		$this->assertEquals('actionmenu_name_test', $my_object_list[0]['name']['value']);
		$this->assertEquals('actionmenu_label_test', $my_object_list[0]['nom']['value']);
		// sous menu
		$this->assertEquals('actionmenu_name_test2', $my_object_list[1]['name']['value']);
		$this->assertEquals('actionmenu_label_test / actionmenu_label_test2', $my_object_list[1]['nom']['value']);


		// check !view for other profil
		$_GET['primary_key'] = 0;
		$my_object_list = array();
		$hProfilInstance->getMenuList($my_object_list);

		for($k=1; $k<count($my_object_list); $k++)
		{

			if($my_object_list[$k]['name']['value'] == 'actionmenu_name_test'
			&& $my_object_list[$k]['nom']['value'] == 'actionmenu_label_test')
			{
			$this->assertEmpty($my_object_list[$k]['view']['value']);
			}

			if($my_object_list[$k]['name']['value'] == 'actionmenu_name_test2'
					&& $my_object_list[$k]['nom']['value'] == 'actionmenu_label_test / actionmenu_label_test2')
			{
				$this->assertEmpty($my_object_list[$k]['view']['value']);
			}
		}


		/* Suppression de menu. */
		$this->assertTrue($hInstance->unlink($id2));
		$this->assertTrue($hInstance->unlink($id1));

		/* Vérification que le fils a été supprimé. */
		$objects_id = array();
		$this->assertTrue($hORM->search($objects_id, $total, array(array('actionmenu_id', '=', $id2))));
		$this->assertEquals(0, count($objects_id));
	}

}

