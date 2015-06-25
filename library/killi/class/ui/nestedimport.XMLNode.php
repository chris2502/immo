<?php
/**
 * Composant d'interface pour Killi.
 *
 * Afiche une liste imbriquée permettant l'importation d'élément à partir d'une liste <nestedexport>.
 *
 * La node <nestedselect> DOIT contenir une <nestedlist> décrivant les objets attendus, et contenant
 * les mêmes champs que le liste d'export.
 *
 * @author boutillon
 *
 */
class NestedImportXMLNode extends NestedListingXMLNode
{	
	/**
	 * Les rendus du composant doivent-ils être selectionnés à l'affichage initiale.
	 * 
	 * @var Boolean
	 */
	protected $_checked;	
	
	/**
	 * Dans le cas de nestedimport, on force l'affichage des tableaux 
	 * vides afin de pouvoir remplir ceux-ci avec le JS. 
	 * 
	 */
	public function close()
	{
		if ($this->_parent)
		{
			$this->_parent->_select = 1;
		}
		foreach ($this->_table_list as $block) 
		{
			$block->_render_empty = true;			
		}
		parent::close();
		return TRUE;
	}
	
	/**
	 * Fonction d'entrée du composant, configure celui-ci
	 *
	 * @return boolean
	 */
	public function open() {
		parent::open();
		
		$this->_checked = $this->_getInheritedAttribute('checked', false);
		$this->_css_list[] = 'nestedimport';
		$this->_column_count = 1;
		return TRUE;
	}
}
