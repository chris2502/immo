-- Table "Killi_Commentaire" pour ajouter des commentaires et un titre court sur n'importe quel objet
-- (Utilisé par exemple sur les photos : titre = légende et descriptif = commentaire long)
-- Renomme aussi la table en killi_image_annotation.
-- $Revision$
-- $Author$
-- $Date$

CREATE TABLE IF NOT EXISTS `killi_commentaire` (
 `commentaire_id` int(11) NOT NULL AUTO_INCREMENT,
 `titre` tinytext,
 `descriptif` text,
 `object` tinytext NOT NULL,
 `object_id` int(11) NOT NULL,
 `date_creation` datetime DEFAULT NULL,
 `users_id` int(11) DEFAULT NULL,
 PRIMARY KEY (`commentaire_id`),
 KEY `users_id` (`users_id`)
);

