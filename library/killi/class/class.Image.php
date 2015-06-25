<?php

/**
 *  @class Image
 *  @Revision $Revision: 8028 $
 *
 */

abstract class KilliImage extends KilliDocument
{
	/**
	 * Liste des type MIME d'image
	 * @var array
	 */
	public static $image_mime_list = array(
		'jpeg' => 'image/jpeg',
		'jpg'  => 'image/jpg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
		'webp' => 'image/webp',
		'wbmp' => 'image/vnd.wap.wbmp'
	);
	//---------------------------------------------------------------------
	public function setDomain()
	{
		parent::setDomain();
		$this->object_domain[] = array('mime_type','in', self::$image_mime_list);
	}
	//---------------------------------------------------------------------
	public function __construct()
	{
		parent::__construct();
		
		// Modification de type : Enum au lieu de Text
		$this->mime_type = EnumFieldDefinition::create(array_combine(self::$image_mime_list, self::$image_mime_list))
			->setLabel($this->mime_type->name)
			->setEditable($this->mime_type->editable)
			->setExtractCSV($this->mime_type->extract_csv);

		$this->annotations = One2manyFieldDefinition::create('ImageAnnotation', 'image_id')
				->setLabel('Annotations');
		
		$this->comment_id  = One2oneFieldDefinition::create('DocumentCommentaire', 'object_id');
		
		$this->label       = RelatedFieldDefinition::create('comment_id', 'titre')->setLabel('Légende')
				->setEditable(TRUE);
		
		$this->comment     = RelatedFieldDefinition::create('comment_id', 'descriptif')->setLabel('Commentaire')
				->setEditable(TRUE);
		
	}
}
