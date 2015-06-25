<?php
/**
 * Composant d'interface pour Killi.
 *
 * Afiche une liste imbriquée permettant la selection d'en ensemble d'éléments et de sous-éléments. Utilise une 
 * syntax identique à <nestedlisting>.
 *
 * La node <nestedselect> peut contenir en enfants :
 * - d'autres <nestedselect>	: 	Fonctionnement normal.
 * - des <nestedlisting>		: 	Si le parent de la liste est sélectioné, tous les élément de la liste le sont également.
 *
 * La récupération des données se fait par la méthode POST, la fonction static
 * getSelection() permettant une récupération et un décryptage facilité des données.
 *
 * Nécessite nestedlisting.js et nestedlisting.css
 *
 * @author boutillon
 *
 */
class NestedSelectXMLNode extends NestedListingXMLNode
{		

	protected $_with_form;
	protected $_with_counter;
	protected $_send;
	protected $_cancel;
	protected $_action;

	/**
	 * Créer l'élément formulaire du composant, dans lequel se trouve le bouton
	 * de soumission de la sélection.
	 */
	protected function _renderTop()
	{
		$env = $this->_env($this->_data_list);
		$aEnv = explode('&',$env);
		foreach ($aEnv as $key => $strEnv)
		{
			if ($strEnv == '')
				continue;
			$aTab = explode('=', $strEnv);
			echo '<input type="hidden" name="'.$aTab[0].'" value="'.$aTab[1].'" />';
		}
		if ($this->_with_counter == 1)
		{
			echo '<div>';
			echo '<label for="send_'.$this->id.'">';	
			echo $this->_makeDescriptionString($this->getDescription());
			echo '</label>';
			echo '</div>';
		}
		return TRUE;
	}
	
	protected function _makeDescriptionString($description)
	{
		$string = '<span data-object="'.$description->obj.'">'.$description->desc.': <strong>0</strong> / <em>0</em></span>';
		foreach ($description->childs as $child)
		{
			$string .= '&nbsp;';
			$string .= $this->_makeDescriptionString($child);
		}		
		return $string;
	}

	/**
	 * Affiche le tag d'ouverture de la table.
	 *
	 */
	protected function _renderDiv($extend = '')
	{
		$extend = ' data-send="'.$this->_send.'"';
		if ($this->_cancel && $this->_cancel  != 1)
		{
			$extend .= ' data-cancel="'.$this->_cancel.'"';
		}
		parent::_renderDiv($extend);
		return TRUE;
	}
	
	/**
	 * Execute les traimente necessaire à la création de l'objet,
	 * mais avant la laisons des enfants à celui-ci. 
	 */
	public function open()
	{
		$this->_with_counter = $this->getNodeAttribute('with_counter',1);
		$this->_send = $this->getNodeAttribute('send');

		$this->_column_count = 2;
		$this->_select = 1;		
		parent::open();		
		$this->_css_list[] = 'nestedselect';
		return TRUE;
	}
}

