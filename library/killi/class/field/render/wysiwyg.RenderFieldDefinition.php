<?php

/**
 *  @class TextareaRenderFieldDefinition
 *  @Revision $Revision: 3847 $
 *
 */

class WysiwygRenderFieldDefinition extends RenderFieldDefinition
{
	private $_images = true;

	public function renderValue($value, $input_name, $field_attributes)
	{
		$node_id       = $this->node->getNodeAttribute('id', $this->node->id);
		$default_value = $value['value'];

		?>
		<iframe id="<?= $node_id ?>_iframe" src="about:blank" style='width: 100%;height: 500px;border: none;'></iframe>
		
		<script type="text/javascript">
			var doc = document.getElementById('<?= $node_id ?>_iframe').contentWindow.document;
			doc.open();
			doc.write(<?= json_encode($default_value) ?>);
			doc.close();
		</script>
		<?php
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$node_id       = $this->node->getNodeAttribute('id', $this->node->id);
		$object        = $this->node->getNodeAttribute('object', ATTRIBUTE_HAS_NO_DEFAULT_VALUE, TRUE);
		$this->_images = ($this->node->getNodeAttribute('images_render', '1') == '1');
?>

<?php 
if (!defined('WYSIWYG_INCLUDED')) 
{
	define('WYSIWYG_INCLUDED', true);
?>
<script src="<?=KILLI_DIR?>js/ckeditor/ckeditor.js"></script>
<script src="<?=KILLI_DIR?>js/ckeditor/adapters/jquery.js"></script>
<script src="<?=KILLI_DIR?>js/ckeditor/ck_killi.js"></script>
<?php
}

?>

<textarea id="<?=$node_id?>" name="<?=$input_name?>" object="<?=$object?>"><?=$value['value']?></textarea>

<?php
if ($this->_images)
{
?>
<!-- Image insertion part -->
<button type="button" id="btn_addimage_<?=$node_id?>" style="width:160px;">Ajouter image</button>
<!-- End image insertion -->
<?php
}
?>

<script type="text/javascript">
$(document).ready(function(){
	// $('#<?=$this->node->id?>').jqte();
	$('#<?=$node_id?>').ckeditor();

	<?php
	if ($this->_images)
	{
	?>

	$('#btn_addimage_<?=$node_id?>').click(function(){
		addImageToWysiwyg('<?=$node_id?>');
	});

	<?php
	}
	?>

	CKEDITOR.instances['<?=$node_id?>'].on('change', function() {
		CKEDITOR.instances['<?=$node_id?>'].updateElement()
	});
});
</script>

		<?php
	}
}
