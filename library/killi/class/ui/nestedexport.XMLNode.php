<?php

/**
 * Composant d'interface pour Killi.
 *
 * Afiche une liste imbriquée permettant l'exportation d'élément vers une liste <nestedimport>.
 *
 * Peut se trouver dans des nestedlisting, et utilise les même arguments 
 * que <nestedlisting>, mais doit de plus pouvoir trouver l'attribut suivant :
 *
 * - target = "NestedImportRoot.NestedListingObject"		L'attribut contient, séparé par un '.' :
 * 			NestedImportRoot								L'ID du <nestedimport> ciblé.
 * 			NestedListingObject								Le nom de l'objet traité par le <nestedlisting> membre du <nestedimport>
 * 															dans lequel on veut injecter nos enregistrements.
 *
 * @author boutillon
 *
 */
class NestedExportXMLNode extends NestedListingXMLNode
{
	/**
	 * Le nombe de colonnes affichées par la liste, sans le moindre enfant
	 * ( Block ou Inline ).
	 * 
	 * @var Int
	 */
	protected $_column_count = 2;
	
	/**
	 * La cible d'exportation.
	 * 
	 * @var String
	 */
	protected $_to = false;
	
	/**
	 * Affiche le tag d'ouverture de la table.
	 * 
	 * Si la liste est la racine de l'arborescence, alors
	 * ses rêgles CSS sont appliqué au div de celle plutôt qu'à
	 * la table.
	 */
	protected function _renderTableOpening($extend = '')
	{
		$extend = ' data-export-to="'.$this->_to[0].'" data-export-as="'.$this->_to[1].'"';
		parent::_renderTableOpening($extend);
		return TRUE;
	}
		
	/**
	 * Fonction d'entrée du composant, configure celui-ci
	 *
	 * @return boolean
	 */
	public function open() {
		parent::open();
		$this->_to = $this->_getInheritedAttribute('to', true);
		$this->_to = explode('.', $this->_to);
		$this->_css_list[] = 'nestedexport';
		return TRUE;
	}
}
