<?php

/**
 *  @class QuicksearchXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class QuicksearchXMLNode extends XMLNode
{

	public function open()
	{
		$object	= $this->getNodeAttribute('object');
		$attribute = $this->getNodeAttribute('attribute');
		$auth_view = explode(',', $this->getNodeAttribute('authorized_views', 'panel,form'));

		if (!in_array($this->_view, $auth_view))
		{
			return TRUE;
		}

		$hInstance = ORM::getObjectInstance($object);
		if (!isset($hInstance->$attribute))
		{
			throw new Exception('Attribute '.$attribute.' does\'nt exist in '.$object);
		}

		$supported_types_list = array(
			'int',
			'text',
			'hexacle'
		);
		$attribute_type = $hInstance->$attribute->type;
		$attribute_name = $hInstance->$attribute->name;

		if (!in_array($attribute_type, $supported_types_list))
		{
			throw new Exception('Field type '.$attribute_type.' is not yet supported in quicksearch.');
		}
?>

<div style="text-align:right; margin-right: 2px; padding-bottom: 2px;">
	<?=$attribute_name?> : <input id="quicksearch_field_<?=$this->id?>" type="text" style="background-image:url('<?=KILLI_DIR?>/images/gtk-find.png'); background-repeat: no-repeat; background-position: 100% 50%; padding-right: 18px;" />
</div>

<?php
	}
	//.....................................................................
	public function close()
	{
		$object = $this->getNodeAttribute('object');
		$attribute = $this->getNodeAttribute('attribute');
?>

<script type="text/javascript">
$(document).ready(function(){
	$('#quicksearch_field_<?=$this->id?>').keyup(function(event){
		if (event.keyCode == 13)
		{
			$.ajax({
				async:	true,
				url:	  './index.php?action=<?=$object?>.quickSearch' + add_token(),
				data:	 'attribute/<?=$attribute?>=' + $('#quicksearch_field_<?=$this->id?>').val(),
				dataType: 'json',
				success:  function(response) {
					if (response.error)
					{
						$('#dialog<?=$this->id?>').html(response.error);
						$('#dialog<?=$this->id?>').dialog({
							closeOnEscape: false,
							bgiframe: true,
							modal: true,
							buttons: {
								'Bummer': function() {
									$(this).dialog('close');
								}
							}
						});
					}
					else if (response.url)
					{
						if (response.message)
						{
							$('#dialog<?=$this->id?>').html(response.message);
							$('#dialog<?=$this->id?>').dialog({buttons: {}});
						}
						window.location = response.url;
					}
				}
			});
		}
	});
});
</script>
<div id="dialog<?=$this->id?>" title="Information" style="display:none;"></div>

<?php
	}
}
