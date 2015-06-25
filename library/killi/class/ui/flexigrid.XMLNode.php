<?php

/**
 *  @class FlexigridXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class FlexigridXMLNode extends XMLNode
{
	public $columns = array();
	public $search_elements = array();
	public $buttons = array();

	public function open()
	{

	}

	public function close()
	{
		$method						= $this->getNodeAttribute('method');
		$flexigrid_object			= $this->getNodeAttribute('object');
		$flexigrid_key				= $this->getNodeAttribute('key');
		$flexigrid_title			= $this->getNodeAttribute('title', '');
		$flexigrid_height			= $this->getNodeAttribute('height', 'auto');
		$flexigrid_width			= $this->getNodeAttribute('width', '');
		$flexigrid_nombre_lignes 	= $this->getNodeAttribute('nbline', '10');
		$flexigrid_rowclick 		= $this->getNodeAttribute('rowclick', '');

		$hInstance = ORM::getObjectInstance($flexigrid_object);

		$tmp = explode('.', $method);
		if(count($tmp) != 2)
			throw new XmlTemplateErrorException('flexigrid tag method need to be formatted like \'object.method\'. Current value : ' . $method);
		$object = $tmp[0];
		$flexigrid_method = $tmp[1];

		$jsonUrl='./index.php?action='.$object.'.flexigrid';//.$method;
		foreach($_GET as $key=>$value)
		{
			if ((!isset($_GET['crypt/'.$key])) && ($key!='action') && ($key!='view') && ($key!='mode'))
			{
				$jsonUrl.='&'.$key.'='.$value;
			}
		}

		?>
		<div id="<?= $this->id ?>_container" <?= !empty($flexigrid_width) ? 'style="width: ' . $flexigrid_width . ';"' : '' ?>>
			<table class="flexiUpdate" id="<?= $this->id ?>"></table>
		</div>
		<script type="text/javascript">

		<?php
		$col_desc = '';
		if(!empty($flexigrid_rowclick))
		{
			$col_desc = ', process: rowClick_' . $this->id;
			?>
			function rowClick_<?= $this->id ?>(celDiv, id)
			{
				$(celDiv).click(
					  function() {
					   <?= $flexigrid_rowclick?>(celDiv, id);
					  });
			}
			<?php
		}
		?>
		$("#<?= $this->id ?>").flexigrid({
			url: '<?= $jsonUrl ?>',
			dataType: 'json',
			colModel: [
				 {display: '<?= $flexigrid_key ?>', name: '<?= $flexigrid_key ?>', hide: true},
		<?php

		// Génération des colonnes
		foreach($this->columns AS $col_id => $column)
		{
			?>
				{display: '<?= $column['name'] ?>', name: '<?= $column['attribute'] ?>' <?= !empty($column['width']) ? ', width: \''. $column['width'] . '\'' : '' ?>, sortable: <?= ($column['sortable'] == '1') ? 'true' : 'false' ?>, align: '<?= $column['align'] ?>' <?= $col_desc ?>},
			<?php
		}

		?>
		],
		<?php
		if(count($this->search_elements) > 0)
		{
			?>
			searchitems : [
						<?php
						foreach ($this->search_elements as $key => $value)
						{
							?>
							{display: '<?= $key ?>', name : '<?= $value ?>'},
							<?php
						}
						?>
			],
			<?php
		}
		if(count($this->buttons) > 0)
		{
			?>
			buttons : [
						<?php
						foreach ($this->buttons as $key => $button)
						{
							?>
								{name: '<?= $button['name'] ?>', bclass: '<?= $button['class'] ?>', onpress:<?= $button['onpress'] ?>},
								{separator: true},
							<?php
						}
						?>
			],
			<?php
		}
		?>
		sortorder: 'asc',
		usepager: true,
		title: '<?= addslashes($flexigrid_title) ?>',
		useRp: true,
		rp: '<?= $flexigrid_nombre_lignes ?>',
		showTableToggleBtn: false,
		<?= !empty($flexigrid_width) ? 'width: \'' . $flexigrid_width . '\',' : '' ?>
		height: '<?= $flexigrid_height  ?>',
		singleSelect: true,
		showToggleBtn: false,
		resizable: false,
		rpOptions: [5, 10, 15, 20, 30, 50],
		autoload: false,
		params: [{name: 'object', value: '<?= $flexigrid_object ?>'}, {name: 'key', value: '<?= $flexigrid_key ?>'}, {name: 'method', value: '<?= $flexigrid_method ?>'}],
		onError: function(XMLHttpRequest, textStatus, errorThrown) {
			if(textStatus == 'parsererror')
			{
				//location.reload();
				alert('Données renvoyées incorrect : ' + textStatus);
			}
			else
			{
				alert('Erreur lors de la récupération des informations : ' + textStatus);
			}
		},
		onSuccess: function() {
				var grid = $("#<?= $this->id ?>").parent().parent();
				var headers = grid.find(".hDiv thead th");
				var row = grid.find(".bDiv tbody tr:first");
				var drags = grid.find("div.cDrag div");
				if (row.length >= 1) {
					var cells = row.find("td");
					var offsetAccumulator = 0;
					headers.each(function(i) {
						if(headers.eq(i).css("display") != "none")
						{
							var headerWidth = $(this).width();
							var bodyWidth = cells.eq(i).width();
							var realWidth = (bodyWidth > headerWidth ? bodyWidth : headerWidth) - 10;

							$(this).width(realWidth);
							$(this).find("div").width(realWidth);
							cells.eq(i).width(realWidth);
							cells.eq(i).find("div").width(realWidth);

							var drag = drags.eq(i-1);
							var dragPos = drag.position();
							var offset = (realWidth - headerWidth) + 10;
							var newPos = realWidth + offsetAccumulator + 10;
							offsetAccumulator += realWidth + 12;
							drag.css("left", newPos);
						}
					});
				}
			}
		});
		$("#<?= $this->id ?>").Updatable({callback: updateFlexiAttribute});
		$(document).ready(function() {
		<?php
		if($this->getParent()->name == 'page')
		{
			?>
				$('#<?= $this->getParent()->id ?>').onShow(function() {
					$("#<?= $this->id ?>").flexReload();
				});
			<?php
		}
		else
		{
			?>
				$("#<?= $this->id ?>").flexReload();
			<?php
		}
		?>
		});
		</script>
		<?php
		return TRUE;
	}
}

