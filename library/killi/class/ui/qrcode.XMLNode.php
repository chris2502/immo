<?php

/**
 *  @class QRCodeXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class QRCodeXMLNode extends XMLNode
{
	public function open()
	{
		$object	= $this->getNodeAttribute('object');
		$attribute = $this->getNodeAttribute('attribute');
		$color	 = $this->getNodeAttribute('color', 'black');
		$width	 = $this->getNodeAttribute('width', '100');
		$height	= $this->getNodeAttribute('height', $width);
		$align	 = $this->getNodeAttribute('align', 'left');
		$display   = ($this->getNodeAttribute('display_on_empty', '0') == '1');
		$pk		= $_GET['primary_key'];
		$id		= $this->id;

		$hInstance = ORM::getObjectInstance($object);

		$attribute_list   = explode(',', $attribute);
		$values_list	  = array();
		$required_fields  = array();
		$values_are_empty = true;
		foreach ($attribute_list as $attr)
		{
			$required_fields[] = $hInstance->$attr->name;
			if (substr($hInstance->$attr->type, 0, 8) == 'many2one')
			{
				$value = $this->_data_list[$object][$pk][$attr]['reference'];
			}
			else
			{
				$value = $this->_data_list[$object][$pk][$attr]['value'];
			}
			if (!empty($value))
			{
				$values_are_empty = false;
			}
			$values_list[] = $value;
		}

		$errmsg = $this->getNodeAttribute('error_message', 'Les champs nécessaires ('.implode(', ', $required_fields).') pour générer le QRcode sont vides.');
?>
<div style="text-align:<?php echo $align; ?>;" id="__qr_<?php echo $id; ?>"></div>
<?php if ($display || !$values_are_empty): ?>
<script type="text/javascript">
$(document).ready(function(){
	$('#__qr_<?php echo $id; ?>').qrcode({
		width  : <?php echo $width; ?>,
		height : <?php echo $height; ?>,
		color  : '<?php echo $color; ?>',
		text   : '<?php echo implode('\n', $values_list); ?>'
	});
});
</script>
<?php elseif (!$display && $values_are_empty): ?>
<div style="border: 1px solid #f88;padding:2px;"><?php echo $errmsg; ?></div>
<?php endif;
	}
}