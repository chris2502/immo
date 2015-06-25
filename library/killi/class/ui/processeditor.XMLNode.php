<?php

/**
 *  @class ProcessEditorXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class ProcessEditorXMLNode extends XMLNode
{
	protected $_process_id;
	protected $_module_list;

	public function open()
	{
		if (!isset($_GET['primary_key']))
		{
			throw new Exception("Impossible de déterminer l'ID du process", 1);
			exit(0);
		}

		$this->_process_id = $_GET['primary_key'];

		$hORM = ORM::getORMInstance('typemodule');
		$hORM->browse(
			$this->_module_list,
			$total
		);

	}

	public function close()
	{
		$this->renderCss();
		$this->renderEditor();
		$this->renderScript();
	}

	protected function renderEditor()
	{
?>
<table class="separator"><tbody><tr><td>Editeur</td></tr></tbody></table>
<table id="process-editor">
<tr>
	<td colspan="2" class="process-tools"><?php

	foreach ($this->_module_list as $m_id => $m_data)
	{
		$fa_icon = 'fa-cogs';
		if (!empty($m_data['fa_icon']['value']))
		{
			$fa_icon = 'fa-'.$m_data['fa_icon']['value'];
		}
		?>
		<div class="module-tool module-item" data-classname="<?php echo $m_data['class_name']['value']; ?>" title="<?php echo $m_data['desc']['value']; ?>">
			<i class="fa <?php echo $fa_icon; ?>"></i>
			<div class="desc"><?php echo $m_data['name']['value']; ?></div>
		</div>
		<?php

	}
	
	?></td>
	<td colspan="2" class="process-workspace" width="100%">
		
	</td>
	
</tr>
</table>
<?php
	}

	protected function renderScript()
	{
?>
<script>

	var process = {
		process_id: '<?php Security::crypt($this->_process_id, $crypt_process_id); echo $crypt_process_id; ?>',
		init: function()
		{
			$('#process-editor').tooltip();

			$('.process-workspace').droppable(
			{
				drop: function(event, ui)
				{
					process.module.drop(event, ui);
				},
				activeClass: "drop-workspace-hover",
				hoverClass: "drop-workspace-active"
			});

			$('.module-tool').each(function()
			{
				process.module.setDraggable($(this));
			});
		}
	}

	process.module = {
		module_id: 0,
		count: 0,
		setDraggable: function(div)
		{
			div.draggable(
			{
				start: function(event, ui)
				{
				},
				revert: function (droppableObj)
				{
					if (droppableObj === false)
					{
						return true;
					}
					else
					{
						return false;
					}
				},
				handle: '.drag-handle',
				helper: 'clone'
			});
		},
		drop: function(event, ui)
		{
			workspaceWidth = $('.process-workspace').width();
			helperXpos = ui.helper.position().left - $('.process-workspace').position().left;
			helperYpos = ui.helper.position().top - $('.process-workspace').position().top + 16;
			
			if (helperXpos < 0 || helperYpos < 0)
			{
				return true;
			}

			helperXpos = helperXpos + (ui.helper.width() / 2);
			helperXpercent = Math.round(helperXpos * 100 / workspaceWidth);

			var send_data = {};
			send_data['process_id'] = process.process_id;
			send_data['class_name'] = ui.helper.attr('data-classname');
			send_data['x'] = helperXpercent;
			send_data['y'] = helperYpos;

			process.module.save(send_data);
			process.module.count++;

			module_html = '<div class="module-workspace module-item module-item-'+process.module.count+'" data-moduleid="'+process.module.module_id+'" data-posxpercent="'+helperXpercent+'" data-classname="FormModule" title="Écran de formulaire">';
			module_html += ui.draggable.html();
			module_html += '</div>';

			$('.process-workspace').append(module_html);

			var module_div = $('.module-item-'+process.module.count);

			module_div.css('left', helperXpos);
			module_div.css('top', helperYpos);

			process.module.setNameEdit(module_div.children('.desc'));
		},
		save: function(save_data)
		{
			$.ajax({
				type: "POST",
				url: './index.php?action=process.ajaxSaveModule'+add_token(),
				data: save_data,
				dataType: 'json',
				success: function(data)
				{
					process.module.module_id = data.module_id;
				},
				error: function(xhr,err)
				{
					alert("Une erreur est survenue.");
				}
			});
		},
		setNameEdit: function(text_div)
		{
			text_div.click(function(){
				process.module.editName($(this));
			});
		},
		editName:function(text_div)
		{
			text = text_div.html();
			text_html  = '<form action="">';
			text_html += '<input name="text" value="'+text+'">';
			text_html += '</form>';

			text_div.html(text_html);			
		}
	}

	$(document).ready(function(){
		process.init();
	});

</script>
<?php
	}

	protected function renderCss()
	{
?>
<style>

	.cl { clear: both; }
	#process-editor { border: 1px solid #CCC; width: 100%; border-collapse:  -moz-user-select: -moz-none; -webkit-user-select: none; -khtml-user-select: none; -o-user-select: none; user-select: none; }
	
	#process-editor .process-tools { background-color: #EEE; padding: 5px 20px 20px 20px; border-right: 1px solid #DDD; }
	#process-editor .module-tool { margin-top: 15px; cursor:hand; }

	#process-editor .module-item { text-align: center; background-color: white; border: 1px solid #CCC; border-radius: 6px; padding: 10px; cursor:grab; cursor:-moz-grab; cursor:-webkit-grab; }
	#process-editor .module-item .fa { font-size: 30px; }
	#process-editor .module-item .desc { font-size: 12px; width: 60px; padding-top: 5px; }
	#process-editor .module-item.ui-draggable-dragging { opacity: .5; box-shadow: 0 2px 1px rgba(0,0,0,.3); z-index: 9999; }

	#process-editor .module-workspace { position: absolute; } 

	#process-editor .process-workspace { position: relative; }
	#process-editor .process-workspace.drop-workspace-hover { background-color: #dff0d8; }

</style>
<?php
	}
}
