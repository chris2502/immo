<?php

/**
 *  @class BoxXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class BoxXMLNode extends XMLNode
{
	public function open()
	{
		?><table id="<?= $this->id; ?>" style="border-spacing: 0px;width: 100%;" class="box_container"><?php
			?><thead><?php
				?><tr class="ui-widget-header ui-state-hover" style='height:20px'><?php
					?><th class="box_header leftcorner" style="width: 4px;"></th><?php
					?><th class="box_header leftcorner_alternate" style="width: 1px;"></th><?php
					?><th class="box_header leftcorner" style="width: 2px;"></th><?php
					?><th class="box_header leftcorner_alternate" style="width: 1px;"></th><?php
					?><th class="box_header leftcorner" style="width: 1px;"></th><?php
					?><th class="box_header" style="border-bottom: solid 1px #BBBBBB;"><?= $this->getNodeAttribute('string', ''); ?></th><?php
				?></tr><?php
				?></thead><?php
				?><tbody><?php
				?><tr><?php
					?><td style="height: 2px;" colspan="6"></td><?php
				?></tr><?php
				?><tr><?php
					?><td colspan="6"><?php
	}
	//.....................................................................
	public function close()
	{
					?></td><?php
				?></tr><?php
			?></tbody><?php
		?></table><?php
	}
}
