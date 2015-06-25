<?php
class KilliImageMethod extends Common
{
	const THUMB_EXT        = '.thumb';
	const THUMB_MAX_WIDTH  = 120;
	const THUMB_MAX_HEIGHT = 90;
	const DEFAULT_QUALITY  = 75;
	
	public function preWrite($object_id, $original_data, &$data)
	{
		parent::preWrite($object_id, $original_data, $original_data);
		
		$data_commentaire = array(
			'titre'      => $original_data['label'],
			'descriptif' => $original_data['comment'],
		);
		
		$commentaire_id_list = array();
		$hCommentaire = ORM::getORMInstance('DocumentCommentaire');
		$hCommentaire->search($commentaire_id_list, $total_record, array(array('object_id', '=', $object_id)));
		if (count($commentaire_id_list) > 0)
		{
			ORM::getORMInstance('DocumentCommentaire')->write(reset($commentaire_id_list), $data_commentaire);
		}
		else{
			$data_commentaire['object']        = 'document';
			$data_commentaire['object_id']     = $object_id;
			$data_commentaire['users_id']      = KilliUserMethod::getUserId();
			$data_commentaire['date_creation'] = date('Y-m-d H:i:s');
			ORM::getORMInstance('DocumentCommentaire')->create($data_commentaire, $commentaire_id);
		}

		$data = $original_data;
		unset($data['label'], $data['comment']);
		
		return TRUE;
	}
	
	/**
	 * Affiche l'image demandée par le paramètre GET "crypt/path" ou "crypt/primary_key"
	 * en ayant redimensionnée l'image selon les paramètres GET (tous optionnels) :
	 * - w  : largeur (<b>w</b>idth)
	 * - h  : hauteur (<b>h</b>eight)
	 * - mw : largeur max. (<b>m</b>ax. <b>w</b>idth)
	 * - mw : hauteur max. (<b>m</b>ax.<b>h</b>eight)
	 * - q  : qualité, de 0 à 100 (<b>q</b>uality)
	 * @throws Exception
	 * @return boolean
	 */
	public function show($view)
	{
		// Paramètres
		
		$realfile  = isset($_GET['file_name'])    ? $_GET['file_name']    : NULL;
		$image_id  = isset($_GET['primary_key'])  ? $_GET['primary_key']  : NULL;
		$maxWidth  = isset($_GET['mw']) ? (int)$_GET['mw'] : ($view=='thumb' ? self::THUMB_MAX_WIDTH : NULL);
		$maxHeight = isset($_GET['mh']) ? (int)$_GET['mh'] : ($view=='thumb' ? self::THUMB_MAX_WIDTH : NULL);
		$quality   = isset($_GET['q'])  ? (int)$_GET['q']  : 100;
	
		
		if ($maxWidth < 0 || $maxHeight < 0 || $quality < 0)
		{
			throw new Exception('Erreur dans la validité des paramètres');
		}
		
		// Récupération du chemin d'accès vers le fichier
		
		$create_date = time();
		$mime_type = 'image/jpeg';
		if (! empty($realfile))
		{
			$create_date = filectime($realfile);
			$mime_type   = mime_content_type($realfile);
		}
		elseif(! empty($image_id))
		{
			ORM::getORMInstance('image')->read($image_id, $image, array('file_name', 'date_creation', 'mime_type'));
			if (!empty($image))
			{
				$realfile    = $image['file_name']['value'];
				$create_date = $image['date_creation']['value'];
				$mime_type   = $image['mime_type']['value'];
			}
		}
		else
		{
			throw new Exception('Impossible de déterminer l\'image à afficher: ni paramètre file_name ni paramètre primary_key');
		}

		// Cas du thumbnail 
		
		if($view=='thumb')
		{
			$thumbfile = $realfile.self::THUMB_EXT;
			if (! file_exists($thumbfile))
			{
				self::createThumb($realfile, NULL, NULL, $thumbfile);
			}
			$realfile = $thumbfile;
		}
		
		if(! is_readable($realfile))
		{
			throw new Exception('Document "'.$realfile.'" is not available.');
		}
		
		// Si rien de modifié...
		
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH']==md5(filemtime($realfile)))
		{
			header('HTTP/1.0 304 Not Modified');
			die();
		}
		
		
		// Envoi l'image
		
		$expires = $create_date + 60 * 60 * 24 * 365; // 1 an
		
		header('Content-Type: ' . $mime_type);
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: max-age='.(60 * 60 * 24 * 365).', must-revalidate');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
		header('Last-modified: ' . gmdate('D, d M Y H:i:s', filemtime($realfile)) . ' GMT');
		header('ETag: ' . md5(filemtime($realfile)));
		
		if ($view=='thumb' || (empty($maxHeight) && empty($maxWidth) && $quality==100)) // thumbnail, ou rien à modifier
		{
			readfile($realfile);
		}
		else
		{
			echo self::getContent($realfile, $maxWidth, $maxHeight, $quality); // modifier taille / qualité
		}
		
		exit(0);

	}

	
	/**
	 * Retourne le contenu binaire de l'image (comme file_get_contents), en ayant préalablement effectué le redimensionnement demandées
	 * @param string $path      Chemin d'accès vers le fichier image
	 * @param int    $maxWidth  Largeur maximum
	 * @param int    $maxHeight Hauteur maximum
	 * @param int    $quality   Qualité (0..100)
	 * @param string $thumbfile Fichier thumbnail.
	 * @throws Exception
	 * @return Les données binaires de l'image
	 */
	public static function getContent($path, $maxWidth=NULL, $maxHeight=NULL, $quality=self::DEFAULT_QUALITY, $thumbfile=NULL)
	{
		// Contrôles
		
		if (empty($path))
		{
			throw new Exception('Impossible de déterminer l\'image à afficher: $path vide');
		}
		if (! file_exists($path))
		{
			// throw new Exception('Le fichier "'.$path.'" n\'existe pas');
			$image = imagecreatetruecolor(300, 225);
			//imageantialias($image, TRUE);
			$gray     = imagecolorallocate($image, 250, 250, 250);
			$redlight = imagecolorallocate($image, 255, 128, 128);
			$reddark  = imagecolorallocate($image, 200, 0, 0);
			$black    = imagecolorallocate($image, 0, 0, 0);
			imagefill($image, 0, 0, $gray);
			imageline($image, 0, 225, 300, 0, $redlight);
			imageline($image, 0, 0, 300, 225, $redlight);
			imagerectangle($image, 0, 0, 299, 224, $black);
			imagestring($image, 5, 75, 25, "IMAGE NON TROUVEE", $reddark);
			@ob_start();
			imagepng($image);
			imagedestroy($image);
			return @ob_get_clean();
		}

		// Si rien à modifier : retourner simplement l'image
		
		if (empty($maxWidth) && empty($maxHeight) && $quality==self::DEFAULT_QUALITY)
		{
			return file_get_contents($path);
		}
		
		// Dimension de l'image
		
		list($src_width, $src_height) = getimagesize($path);
		list($width, $height) = self::computeDim($src_width, $src_height, $maxWidth, $maxHeight);

		// Si le thumbnail existe déjà et est à la bonne taille : le retourner simplement
		
		if (!empty($thumbfile) && file_exists($thumbfile))
		{
			list($thumb_width, $thumb_height) = getimagesize($thumbfile);
			if ($thumb_width == $width && $thumb_height == $height)
			{
				return file_get_contents($thumbfile);
			}
		}
		
		// --- Redimensionnement et écriture
		
		$writefunc = NULL;
		$src_image = self::imagecreatefromfile($path, $writefunc);
		$new_image = imagecreatetruecolor($width, $height);
		$black     = imagecolorallocate($new_image, 0, 0, 0);
		imagecolortransparent($new_image, $black);
		imagecopyresampled($new_image, $src_image, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
		imagedestroy($src_image);
		
		if ($writefunc == 'imagepng')
		{
			$quality = 9*($quality/100); // Quality pour un PNG : 0..9 (au lieu de 0..100)
		}
		
		// Pas de thumbnail : retourne seulement l'image redimensionnée
		
		if (empty($thumbfile))
		{
			@ob_start();
			$writefunc($new_image, NULL, $quality);
			return @ob_get_clean();
		}
		// Thumbnail : enregistre d'abord l'image
		else
		{
			$writefunc($new_image, $thumbfile, $quality);
			return file_get_contents($thumbfile);
		}
	}

	
	/**
	 * Création d'un fichier thumbnail
	 * @param string $path       Image source
	 * @param int    $maxWidth   Largeur max du thumbnail
	 * @param int    $maxHeight  Hauteur max du thumbnail
	 * @return boolean
	 */
	public static function createThumb($path, $maxWidth=self::THUMB_MAX_WIDTH, $maxHeight=self::THUMB_MAX_HEIGHT, $thumbfile=NULL)
	{
		if(!is_file($path))
		{
			return FALSE;
		}

		if (empty($thumbfile))
		{
			$thumbfile = $path.self::THUMB_EXT;
		}
	
		/*
		if (file_exists($thumbnail))
		{
			return TRUE;
		}
		*/
		
		if (empty($maxWidth))  $maxWidth  = self::THUMB_MAX_WIDTH;
		if (empty($maxHeight)) $maxHeight = self::THUMB_MAX_HEIGHT;
			
		list($src_width, $src_height) = getimagesize($path);
		$width  = $src_width;
		$height = $src_height;
		list($width, $height) = self::computeDim($src_width, $src_height, $maxWidth, $maxHeight);

		$source = self::imagecreatefromfile($path);
		$destination = imagecreatetruecolor($width, $height);
		imagecopyresampled ($destination, $source, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
		imagedestroy($source);
		imagejpeg($destination, $thumbfile);
		imagedestroy($destination);
		
		return file_exists($thumbfile);
	}
	
	
	public function ajaxRotate($data)
	{
		$document = array();
		ORM::getORMInstance('image')->read($_POST['document_id'], $document, array('file_name','mime_type','hr_name','date_creation'));
		
		if(!empty($document))
		{
			$realfile = $document['file_name']['value'];

			if(is_readable($realfile))
			{
				$writefunc = NULL;
				$source = self::imagecreatefromfile($realfile, $writefunc);
				$rotate = imagerotate($source, $_POST['delta']*90, 0);
				$writefunc($rotate, $realfile);
				imagedestroy($source);
				imagedestroy($rotate);
				self::createThumb($realfile);
				die('DONE');
			}
			else
			{
				throw new Exception('Document is not available.');
			}
		}
		else
		{
			throw new Exception('Document does not exists.');
		}
	}
	
	
	/**
	 * Calcule de la dimension finale de l'image
	 * @param int $src_width   Largeur source
	 * @param int $src_heigth  Hauteur source
	 * @param int $maxWidth    Largeur max
	 * @param int $maxHeight   Hauteur max
	 * @return multitype:number  array(largeur finale, hauteur finale)
	 */
	private static function computeDim($src_width, $src_heigth, $maxWidth, $maxHeight)
	{
		// Calcule maxWidth et maxHeight si l'un des deux est vide.
		
		if ($maxWidth > 0 && $maxHeight <= 0)
		{
			$maxHeight = ($maxWidth/$src_width) * $src_height;
		}
		elseif($maxHeight > 0 && $maxWidth <= 0)
		{
			$maxWidth = ($maxHeight/$src_height) * $src_width;
		}
		
		$width  = $src_width;
		$height = $src_heigth;
		
		// Limite les dimensions
		
		if ($maxWidth > 0 && $width > $maxWidth)
		{
			$height = $height*($maxWidth/$width);
			$width  = $maxWidth;
		}
		if ($maxHeight > 0 && $height > $maxHeight)
		{
			$width  = $width*($maxHeight/$height);
			$height = $maxHeight;
		}
		
		return array((int)$width, (int)$height);
	}
	
	
	/**
	 * Créer une ressource image
	 * @param string  $filename   Chemin d'accès au fichier image
	 * @param string& $writefunc  Le nom de la fonction permettant de sauvegarder l'image (imagegif(), imagejpeg(), etc.)
	 * @throws Exception
	 * @return resource
	 */
	private static function imagecreatefromfile($filename, &$writefunc=NULL)
	{
		$mime = image_type_to_mime_type(exif_imagetype($filename));
	
		if (empty($mime) || ! substr($mime, 0, 6) == 'image/')
		{
			throw new Exception('Le fichier  "'.$filename.'" n\'est pas une image');
		}
	
		$writefunc = 'imagejpeg'; // Par défaut : Ecrire en JPEG
		
		switch ($mime)
		{
			case 'image/gif':
				$writefunc = 'imagegif';
				return imagecreatefromgif($filename);
				break;
	
			case 'image/jpg':
			case 'image/jpeg':
				$writefunc = 'imagejpeg';
				return imagecreatefromjpeg($filename);
				break;
	
			case 'image/png':
				$writefunc = 'imagepng';
				return imagecreatefrompng($filename);
				break;
	
			case 'image/webp':
				$writefunc = 'imagewebp';
				return imagecreatefromwebp($filename);
				break;
	
			case 'image/vnd.wap.wbmp':
				$writefunc = 'imagewbmp';
				return imagecreatefromwbmp($filename);
				break;
	
			default:
				throw new Exception('Format d\'image inconnu');
				return NULL;
				break;
		}

		return NULL;
	}
	
}
