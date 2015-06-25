<?php

/**
 *  @class AccordionXMLNode
 *  @Revision $Revision: 2381 $
 *
 */

class AccordionXMLNode extends XMLNode
{
	public function open()
	{
		?>
			<div id="<?= $this->id; ?>">
		<?php
	}
	//.....................................................................
	public function close()
	{
		?>
			</div>
			<script>
			$("#<?= $this->id; ?>").accordion({
				cookie:{},
				heightStyle: "content"
			});
			</script>
		<?php
	}
}
