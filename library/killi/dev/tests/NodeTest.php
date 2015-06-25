<?php

/**
 *  @class NodeTest
 *  @Revision $Revision: 2736 $
 *
 */

class NodeTest extends Killi_TestCase
{
	/**
	 * @public
	 * @dataProvider HydrateOKNode
	 */
	public function testsetPopulation($node_id, $etat, $worfklow_id, $type_id, $commande, $node_name, $interface, $object, $ordre, $killi_workflow_node_group_id)
	{
		$node_list = array();

		$node = array(
				'workflow_node_id'=> $node_id,
				'etat' => $etat,
				'workflow_id'=>$worfklow_id,
				'type_id'=>$type_id,
				'commande'=>$commande,
				'node_name'=>$node_name,
				'interface'=>$interface,
				'object'=>$object,
				'ordre'=>$ordre,
				'killi_workflow_node_group_id'=>$killi_workflow_node_group_id
				);

		$node_list[] = $node;

		$node_populate = NodeMethod::setPopulation($node_list);

		$this->assertTrue($node_populate, 'setPopulation return false');
	}

	public static function main()
	{
		return new NodeTest('main');
	}

	public function setUp()
	{
		/* Définition du profil de l'utilisateur. */
		$_SESSION['_USER']['profil_id']['value'] = array(ADMIN_PROFIL_ID);
		parent::setUp();
	}

	/**
	 * @public
	 */
	public function HydrateOKNode()
	{
		return array(
						array('18', 'Point de départ', '1', '3', 'Verticalisation::start_point', 'start_point', '', '', '1', NULL),
						array('2', 'Commandable', '1', '1',      '',      'commandable', 'verticalisation.commandable.xml', 'adresse', '2', NULL)
					);
	}

	/**
	 * @public
	 * @return multitype:multitype:string
	 */
	public function HydrateNOKNode()
	{
		return array(
						array('18', 'Point de départ', '1', '3', 'Verticalisation::start_point', '', '', '', false)
		);
	}

	/**
	 * @public
	 * @provider HydrateOKNode
	 */
	public function testRemove_qualification()
	{
// 		$hORM = ORM::getORMInstance('nodequalification');
// 		$hORM->unlink($wf_id);
	}

}