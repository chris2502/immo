-- Modifie le type des champs de coordonnées en FLOAT pour enregistrer des positions RELATIVES
-- et calculer dynamiquement la position et la taille de la zone à afficher
-- Renomme aussi la table en killi_image_annotation.
-- $Revision$
-- $Author$
-- $Date$


SET NAMES utf8;


DELIMITER //

CREATE PROCEDURE TableImageAnnotation()
BEGIN

	DECLARE tableexists INT;

	SET tableexists = EXISTS(SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = 'ftth_v3_17' AND table_name LIKE 'z_image_annotation');

	IF tableexists > 0

	THEN
		ALTER TABLE `z_image_annotation` CHANGE `coord_Ax` `coord_Ax` FLOAT NULL DEFAULT '0',
		CHANGE `coord_Ay` `coord_Ay` FLOAT NULL DEFAULT '0',
		CHANGE `coord_Bx` `coord_Bx` FLOAT NULL DEFAULT '0',
		CHANGE `coord_By` `coord_By` FLOAT NULL DEFAULT '0';

		RENAME TABLE `ftth_v3_17`.`z_image_annotation` TO `ftth_v3_17`.`killi_image_annotation`;

	ELSE

		CREATE TABLE IF NOT EXISTS `killi_image_annotation` (
		 `annotation_id` int(11) NOT NULL AUTO_INCREMENT,
		 `image_id` varchar(255) NOT NULL,
		 `annotation_texte` varchar(255) DEFAULT 'field empty',
		 `coord_Ax` float DEFAULT '0',
		 `coord_Ay` float DEFAULT '0',
		 `coord_Bx` float DEFAULT '0',
		 `coord_By` float DEFAULT '0',
		 PRIMARY KEY (`annotation_id`)
		);

	END IF;

END //

DELIMITER ;

CALL TableImageAnnotation();

DROP PROCEDURE TableImageAnnotation;

