<?php

/**
 *  @class Workflow_statusRenderFieldDefinition
 *  @Revision $Revision: 4515 $
 *
 */

class WorkflowstatusRenderFieldDefinition extends TextRenderFieldDefinition
{
	public function renderFilter($name, $selected_value)
	{
		$node_list = array();

		$hInstance	= ORM::getObjectInstance($this->node->getNodeAttribute('object', NULL, TRUE));
		$attr		= $this->node->getNodeAttribute('attribute');

		$hInstance->$attr->getFondamental($hInstance, $attr);

		$filters = array();
		$filters[] = array('workflow_name','=',$hInstance->$attr->object_relation);
		$filters[] = array('type_id','!=',NODE_TYPE_ENTRY_POINT);

		ORM::getORMInstance('node', FALSE, FALSE)->browse($node_list, $total, array('workflow_node_id','etat'), $filters);

		?><input type='hidden' name="<?= $name.'/op' ?>" value='='/><?php
		?><select id="search_<?= $this->node->id ?>" class="search_input" onchange="return trigger_search($('#search_<?= $this->node->id ?>'));" name="crypt/<?= $name ?>"><?php
			?><option></option><?php

			foreach ( $node_list as $node )
			{
				Security::crypt ( $node['workflow_node_id']['value'], $crypt_key );

				?><option <?= ($selected_value == $node['workflow_node_id']['value'] ? 'selected' : '') ?> value="<?= $crypt_key ?>"><?= htmlentities($node['etat']['value'], ENT_QUOTES, 'UTF-8') ?></option><?php
			}

		?></select><?php

		return TRUE;
	}
}
