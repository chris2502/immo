<?php

abstract class KilliNodeLinkMethod extends Common
{
	static public function setFullReference(&$node_link_list)
	{
		self::checkAttributesDependencies('NodeLink', $node_link_list, array('input_workflow_name', 'input_node_name', 'output_workflow_name', 'output_node_name'));
		foreach ($node_link_list as $node_link_id => $node_link)
		{
			$node_link_list[$node_link_id]['full_reference'] = array('value' => $node_link['input_workflow_name']['value'].'::'.$node_link['input_node_name']['value']
			.' To '.$node_link['output_workflow_name']['value'].'::'.$node_link['output_node_name']['value']);
		}
		return TRUE;
	}

	public function edit($view,&$data,&$total_object_list,&$template=NULL)
	{
		if (defined('USE_LINK_RIGHTS') && USE_LINK_RIGHTS)
		{
			$template = '../'.KILLI_DIR.'/template/nodelink_w_rights.xml';
		}
		parent::edit($view,$data,$total_object_list,$template);
	}
}

