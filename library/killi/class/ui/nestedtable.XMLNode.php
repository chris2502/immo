<?php

/**
 * La base des composants <table> de la famille des
 * nested.
 * 
 * @author boutillon
 * 
 */
abstract class NestedTableXMLNode extends NestedXMLNode
{
	/**
	 * La profondeur d'affichage associé à cette node XML.
	 * 
	 * @var Int
	 */
	protected $_base_depth = 1;
	
	/**
	 * Le nombre de colonnes affichées par la table elle-même,
	 * sans le moindre enfant.
	 * 
	 * @var Int
	 */
	protected $_column_count = 0;
	
	/**
	 * La liste des sous-tables.
	 * 
	 * @var Array
	 */
	protected $_table_list = array();
	
	/**
	 * La liste de données à afficher par la table.
	 * ( <td> signifiant Table Data ) 
	 * 
	 * @var Array
	 */
	protected $_table_data_list = array();
	
	/**
	 * La table doit-elle s'afficher même vide.
	 * 
	 * @var Boolean
	 */
	protected $_render_empty = false;
	
	/**
	 * La fonction affichant le contenu du block.
	 * 
	 * @param	Array		$data_input		Le tableau de données à
	 * 										utiliser pour l'affichage.
	 */
	abstract protected function _render2(&$data_input);
	
	/**
	 * Affiche un enventuelle contenu devant
	 * apparaitre avant l'ouverture du premier enfant.
	 * 
	 */
	protected function _renderTop()
	{
		return TRUE;		
	}
	
	/**
	 * Ajoute un ou plusieurs éléments à la table.
	 * 
	 * @param Array		$node_list
	 */
	public function add($node_list)
	{
		if(!is_array($node_list))
		{
			$node_list = array($node_list);
		}
		
		foreach ($node_list as $node)
		{
			if (is_a($node, 'NestedTableXMLNode'))
			{
				$this->_table_list[] = $node;
			}
			elseif (is_a($node, 'NestedXMLNode'))
			{
				$this->_table_data_list[] = $node;
			}
		}
		return TRUE;
	}
	
	/**
	 * La fonction appelé à la fermeture de la Node XML.
	 * 
	 * Créer le div contenant l'arborescence et afficher le bloc ainsi que ses enfants.
	 */
	public function close()
	{	
		parent::close();
		$this->_column_count += count($this->_table_data_list);
		if ($this->_is_root == true)
		{
			$this->_css_list[] = 'depth'.$this->_max_depth;
			$this->_renderDiv();
			$this->_load('nestedlisting.js');
			$this->_load('nestedlisting.css');
			$this->_renderTop();
			$this->_render2($this->_data_list);
			echo '</div>';			
		}
		return TRUE;
	}

	protected function _renderDiv($extends = '')
	{
		echo '<div', $this->css_class($this->_css_list), $this->style(), ' id="', $this->id, '"';
		echo $extends;
		echo '>';
		return TRUE;
	}
}

