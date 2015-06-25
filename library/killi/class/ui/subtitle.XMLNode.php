<?php


class SubTitleXMLNode extends XMLNode
{
	public function open()
	{
		$title	= $this->getNodeAttribute('string', '');
		$title	= XMLNode::parseString($title, $this->_data_list, $this->_current_data);
		?>
		<div id="<?= $this->id ?>" class="ui-widget ui-helper-clearfix">
			<!--<ul id="stack_container" class="stack_container ui-helper-reset ui-helper-clearfix">-->
				<h2 class="ui-widget-header"><?php echo $title; ?></h2>
		</div>
		<?php
	}
}
