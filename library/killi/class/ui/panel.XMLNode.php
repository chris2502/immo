<?php

/**
 *  @class PageXMLNode
 *  @Revision $Revision: 4641 $
 *
 */

class PanelXMLNode extends XMLNode
{
	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		if($view != 'panel')
		{
			return false;
		}

		parent::__construct($structure, $parent, $view);
	}

	public function render($data_list, $view)
	{
		if($view != 'panel')
		{
			return false;
		}
		parent::render($data_list, $view);
	}

	public function open()
	{
		$this->_edition = TRUE;
		$raw	= explode('.', $_REQUEST['action']);
		$object = $raw[0];

		$action = $object.'.write';
		$login = $this->getNodeAttribute('login', false);
		$autocomplete = $this->getNodeAttribute('autocomplete', 1);
		$css_class = $this->getNodeAttribute('css_class', NULL);

		$class='';
		if (isset($css_class))
		{
			$class=' class="'.$css_class.'"';
		}

		$crypted_login = '';
		if ($login == 1 && isset($_SESSION['_USER']))
		{
			 Security::crypt($_SESSION['_USER']['login']['value'], $crypted_login);
			 $crypted_login = '&crypt/login='.$crypted_login;
		}
		$autocomplete_txt = '';
		if (!$autocomplete)
		{
			$autocomplete_txt = ' autocomplete="off"';
		}

		?>
		<form name="main_form" id="main_form"<?= $class ?> method="post" action="./index.php?action=<?= $action.$crypted_login ?>" enctype="multipart/form-data"<?= $autocomplete_txt ?>>
			<input type="hidden" name="form_data_checking" value="1"/>
			<input type="hidden" id="__token" name="token" value="<?= $_SESSION['_TOKEN'] ?>"/>

			<?php

		if(!empty($object))
		{
			echo '<input type="hidden" name="object" value="', $object, '"/>';
		}

		if (isset($_GET['inside_popup']))
		{
			?><input type="hidden" name="refresh_parent" value="1"/><?php
		}

		if (!empty($_GET['crypt/primary_key']) && !empty($object))
		{
			$hInstance = ORM::getObjectInstance($object);
			?><input type="hidden" id="crypt/<?= $hInstance->primary_key ?>" name="crypt/<?= $hInstance->primary_key ?>" value="<?= $_GET['crypt/primary_key'] ?>"/><?php
		}
	}
	//.....................................................................
	public function close()
	{
		?></form><?php
	}
}
