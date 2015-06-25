<?php

/**
 *  @class CreateXMLNode
 *  @Revision $Revision: 4084 $
 *
 */

class CreateXMLNode extends XMLNode
{
	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		if($view != 'create')
		{
			return false;
		}
		parent::__construct($structure, $parent, $view);
	}

	public function render($data_list, $view)
	{
		if($view != 'create')
		{
			return false;
		}
		parent::render($data_list, $view);
	}

	public function open()
	{
		$this->_edition = TRUE;

		$css_class	= $this->getNodeAttribute('css_class', NULL);

		if (isset($this->attributes['onload']))
		{
			?>
			<script>
			$(document).ready(function(){
				<?= $this->attributes['onload'] ?>
			});
			</script>

			<?php
		}

		if(!isset($this->attributes['object']))
		{
			throw new Exception("L'attribut object de <create> n'est pas defini");
		}

		$action = isset($this->attributes['action']) ? $this->attributes['action'] : $this->attributes['object'] . '.create';
		?>
		<form name="main_form" id="main_form" method="post" action="./index.php?action=<?= $action ?>" <?php if ($css_class!=NULL){ echo 'class="'.$css_class.'"'; } ?> enctype="multipart/form-data">
		<input type="hidden" name="form_data_checking" value="1"/>
		<input type="hidden" id="__token" name="token" value="<?= $_SESSION['_TOKEN'] ?>"/>
		<input type="hidden" name="object" value="<?= $this->attributes['object'] ?>"/>
		<?php

		//----Si dans un popup
		if (isset($_GET['input_name']))
		{
			?>
			<input type="hidden" value="1" name="inside_popup">
			<?php
		}
	}
	//.....................................................................
	public function close()
	{
		?>
			</form>
		<?php
	}
}
