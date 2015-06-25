<?php

/**
 *  @class NotebookXMLNode
 *  @Revision $Revision: 3925 $
 *
 */

class NotebookXMLNode extends XMLNode
{
	public function open()
	{
		?><div id="<?= $this->id; ?>"><?php
	}

	//.....................................................................
	public function close()
	{
		$count = 0;
		$parent = $this->getParent();
		if (!is_null($parent))
		{
			while($parent = $parent->getParent())
			{
				$count++;
			}
		}

		$key = md5($_GET['action'] . '_' . (isset($_GET['crypt/primary_key']) ? $_GET['crypt/primary_key'] : '') . '_' . $count);
		?><ul id="<?= $this->id; ?>_pages"><?php
		$pages_list = array();
		foreach($this->_childs as $child)
		{
			if(!$child->check_render_condition())
			{
				continue;
			}
			$pages_list[] = $child;
			$disabled	= $child->getNodeAttribute('disabled', '0') == '1';
			$string		= $child->getNodeAttribute('string', '');
			$string		= XMLNode::parseString($string, $this->_data_list, $this->_current_data);
			?><li><?php
				?><a id="<?= $child->id; ?>_btn" href="#<?= $disabled ? '' : $child->id; ?>"><span><?= $string ?></span></a><?php
			?></li><?php
		}
		?></ul></div><?php

		?><script>
		$("#<?= $this->id; ?>").prepend($("#<?= $this->id; ?>_pages"));
		$(document).ready(function () {
			$("#<?= $this->id; ?>").tabs({
				cookie: {
					name: 'notebook_cookie_<?= $key; ?>',
					expires: 1,
				}
			});

			<?php

			if(isset($_COOKIE['notebook_cookie_'.$key]) && isset($pages_list[$_COOKIE['notebook_cookie_'.$key]]) && ($pages_list[$_COOKIE['notebook_cookie_'.$key]]->getNodeAttribute('onopen', '#')!='#'))
			{
				?>$('#<?= $pages_list[$_COOKIE['notebook_cookie_'.$key]]->id ?>_btn').click();<?php
			}
			?>
		});

		<?php

		foreach($this->_childs as $page)
		{
			if(($event = $page->getNodeAttribute('onopen', '#'))=='#')
			{
				continue;
			}
			?>$('#<?= $page->id ?>_btn').click(function()
			{
				setTimeout("<?= $event; ?>",100);
			});<?php
		}
		?></script><?php
	}
}
