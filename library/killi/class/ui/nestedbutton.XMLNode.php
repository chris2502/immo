<?php

/**
 * Composant correspondant à un boutton à afficher dans une <nestedtable>
 *
 * @Revision $Revision: 3741 $
 */
class NestedButtonXMLNode extends NestedFieldXMLNode
{
	/**
	 * L'action que doit appeler le script.
	 * 
	 * @var String
	 */
	protected $_action;
	
	/**
	 * L'index du tableau Data à utiliser en guise de clef pour le liens.
	 * 
	 * @var String
	 */
	protected $_key;
	
	/**
	 * La chaine de caractère à afficher par le bouton.
	 *
	 * @var String
	 */
	protected $_label = false;
	
	/**
	 * La taille de la fenetre popup à ouvrir, au format WxH.
	 *
	 * @var String
	 */
	protected $_size = false;

	/**
	* Une condition pour que le bouton soit enabled.
	*
	* @var String
	*/
	protected $_enabled_condition = true;

	/**
	* Une condition pour que le bouton n'ouvre pas de popup.
	*
	* @var String
	*/
	protected $_popup = true;

	/**
	 * Ajoute le rendu du composant au tableau $stack.
	 *
	 * @param	Int			$key		La clef primaire de l'élément à afficher. 
	 * @param	Array		$data		Le tableau de données à utiliser.
	 */
	public function render2(&$key, &$data)
	{
		if (!isset($data[$this->_attr]))
		{
			$data_node = array('value' => null);
		}
		else
		{
			$data_node = $data[$this->_attr];
		}

		$primary_key_arg = '';
		if($data_node['value'])
		{
			Security::crypt($data[$this->_key]['value'], $value_crypt);
			$primary_key_arg = 'crypt/primary_key=' . $value_crypt;
		}
		$view = empty($data[$this->_key]['value']) ? 'create' : 'form';
		$url = './index.php?view='.$view.'&action='.$this->_action.$this->_env($data).'&token='.$_SESSION['_TOKEN'].'&'.$primary_key_arg;

		echo '<td ', $this->_css($data), $this->style(), '>';
		if ($this->_popup)
		{
			$url .= '&inside_popup=1';
			echo '<button type="button" data-url="', $url, '" data-id="', $this->id, '"';
		}
		else
		{
			//echo '<button type="button" onclick="javascript:location.href=\'', $url, '\'" data-id="', $this->id, '"';
			echo '<button type="button" onclick="javascript:window.open(\'', $url, '\'); location.reload();" data-id="', $this->id, '"';
		}		
		if ($this->_size)
		{
			echo ' data-size="', $this->_size,'"';
		}
		if (!$this->_checkEnabled($data))
		{
			echo ' disabled="disabled"';
		}
		echo '>', $this->_label, '</button>';	
		echo '</td>';
		return true;
	}

	/**
	 * Affiche le rendu du Header du composant au tableau $stack.
	 */
	public function renderHeader()
	{
		echo '<th>',$this->_label,'</th>';
		return true;
	}
	
	/**
	 * La fonction appelé à l'ouverture de la node XML.
	 *
	 * Initialise le composant.
	 *
	 * @return Boolean
	 */
	public function open()
	{
		parent::open();
		$this->_label = $this->getNodeAttribute('string');
		$this->_action = $this->getNodeAttribute('action', $this->_object_name . '.edit');
		$this->_key = $this->getNodeAttribute('key', $this->_attr);
		$this->_size = $this->getNodeAttribute('size', false);
		$this->_popup = $this->getNodeAttribute('popup', 1);
		$this->_enabled_condition = $this->getNodeAttribute('enabled_condition', FALSE);
		return true;
	}

	/**
	* Check si le bouton doit etre enabled ou disabled.
	*
	*/
	protected function _checkEnabled($data)
	{
		if ($this->_enabled_condition === FALSE)
			return TRUE;
		if(!empty($this->_enabled_condition))
		{
			$condition = $this->_enabled_condition;
			/* Construction de la condition à générer en PHP. */
			// And && Or
			$pattern = array('(\bAND\b)', '(\bOR\b)', '(\band\b)', '(\bor\b)');
			$replacement = array('&&', '||', '&&', '||');
			$condition = preg_replace($pattern, $replacement, $condition);

			/* On remplace le cas de {variable} */
			$pattern = '((\{)(([a-zA-Z0-9_]+))(\}))';
			$replacement = '\$data[\'$3\'][\'value\']';

			$condition = 'return ('.preg_replace($pattern, $replacement, $condition).');';

			if (eval($condition))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return TRUE;
		}
	}
}
