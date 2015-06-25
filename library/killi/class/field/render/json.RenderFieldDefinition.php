<?php

/**
 *  @class SerializedRenderFieldDefinition
 *  @Revision $Revision: 4279 $
 *
 */

class JSONRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		if($value['value'] === NULL)
		{
			?><div></div><?php

			return TRUE;
		}

		?><div id="<?= $this->node->id ?>" <?= $this->node->css_class() ?> <?= $this->node->style() ?>><pre><?php

		var_export($value['value']);

		?></pre></div><?php
	}
	
	public function renderInput($value, $input_name, $field_attributes)
	{
		if($value['value'] == NULL)
		{
			$value['value'] = "{}";
		}
		
		if(is_string($value['value']))
		{
			$value['value'] = json_decode($value['value']);
		}
		
		?>
		<div id='<?= $this->node->id ?>_jsoneditor'></div>
		<input type='hidden' name='<?= $input_name?>' id='<?= $this->node->id ?>_jsoneditor_value'/>
		
		<script>
			var value = <?= json_encode($value['value'], JSON_UNESCAPED_UNICODE) ?>;
	    	$('#<?= $this->node->id ?>_jsoneditor').jsonEditor(value,
	    	{
		    	change: function(data)
		    	{
			    	$('#<?= $this->node->id ?>_jsoneditor_value').val(JSON_stringify(data));
			    }
	    	});
	    	
	    	$('#<?= $this->node->id ?>_jsoneditor_value').val(JSON_stringify(value));

	    	function JSON_stringify(s)
	    	{
	    	   return JSON.stringify(s).replace(/[\u007f-\uffff]/g,
	    	      function(c) { 
	    	        return '\\u'+('0000'+c.charCodeAt(0).toString(16)).slice(-4);
	    	      }
	    	   );
	    	}
	    </script>
	    
		<?php
	}
}
