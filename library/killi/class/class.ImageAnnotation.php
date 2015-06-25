<?php

/**
 *  @class ImageAnnotation
 *  @version $Revision: 4214 $
 *  @author $Author$
 */
class KilliImageAnnotation
{
	public $description	= 'AnnotationImage' ;
	public $table		= 'killi_image_annotation';
	public $database	= DBSI_DATABASE;
	public $primary_key	= 'annotation_id' ;
	public $log			= FALSE;
	public $filters		= array();
	public $reference	= 'annotation_texte';

	//---------------------------------------------------------------------
	function __construct()
	{
		$this->annotation_id = PrimaryFieldDefinition::create();

		$this->image_id = Many2oneFieldDefinition::create('Image')->setLabel('Image');

		$this->annotation_texte = TextFieldDefinition::create()->setLabel('Annotation');

		$this->coord_Ax = NumericFieldDefinition::create()->setLabel('A(x)');
		$this->coord_Ay = NumericFieldDefinition::create()->setLabel('A(y)');
		$this->coord_Bx = NumericFieldDefinition::create()->setLabel('B(x)');
		$this->coord_By = NumericFieldDefinition::create()->setLabel('B(y)');

	}
}
