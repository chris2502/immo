<?php

/**
 *  @class IntRenderFieldDefinition
 *  @Revision $Revision: 4362 $
 *
 */

class IntRenderFieldDefinition extends TextRenderFieldDefinition
{
	public $html5_type = 'number';

	public function renderFilter($name, $selected_value)
	{
		$operators = array('=', '&gt;', '&lt;', '&gt;=', '&lt;=', '!=');
		$selected_operator = (isset($_POST[$name.'/op']) ? htmlentities($_POST[$name.'/op']) : NULL);

		?><table class='ui-filter ui-filter-int'><?php
			?><tr><?php
				?><td><?php

					?><select name="<?= $name.'/op' ?>"><?php
					foreach($operators as $operator)
					{
						?><option value="<?= $operator; ?>" <?= ($selected_operator == $operator ? ' selected' : ''); ?>><?= $operator; ?></option><?php
					}
					?></select><?php

					?><input class="search_input" value="<?= htmlentities($selected_value,ENT_COMPAT,'UTF-8') ?>" name="<?= $name ?>" id="search_<?= $this->node->id ?>" type="number"><?php
				?></td><?php

			if($selected_value != '')
			{
				?><td class='ui-filter-clear'><?php
					?><img onclick="$('#search_<?= $this->node->id ?>').val('');trigger_search($('#search_<?= $this->node->id ?>'));" src='library/killi/images/delete.png' /><?php
				?></td><?php
			}

			?></tr><?php
		?></table><?php

		return TRUE;
	}
}
