SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Structure de la table `killi_application`
--

CREATE TABLE IF NOT EXISTS `killi_application` (
`killi_application_id` int(10) unsigned NOT NULL,
  `nom` varchar(45) NOT NULL,
  `app_token` varchar(64) NOT NULL,
  `app_secret` varchar(64) NOT NULL,
  `redirect_uri` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `killi_application_token`
--

CREATE TABLE IF NOT EXISTS `killi_application_token` (
`killi_application_token_id` int(10) unsigned NOT NULL,
  `token_type_id` smallint(5) unsigned NOT NULL,
  `killi_application_id` int(10) unsigned NOT NULL,
  `killi_user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `validity_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `killi_application`
--
ALTER TABLE `killi_application`
 ADD PRIMARY KEY (`killi_application_id`), ADD UNIQUE KEY `app_token` (`app_token`);

--
-- Index pour la table `killi_application_token`
--
ALTER TABLE `killi_application_token`
 ADD PRIMARY KEY (`killi_application_token_id`), ADD UNIQUE KEY `token_type_id` (`token_type_id`,`killi_application_id`,`killi_user_id`), ADD KEY `killi_application_id` (`killi_application_id`), ADD KEY `killi_user_id` (`killi_user_id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `killi_application`
--
ALTER TABLE `killi_application`
MODIFY `killi_application_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `killi_application_token`
--
ALTER TABLE `killi_application_token`
MODIFY `killi_application_token_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `killi_application_token`
--
ALTER TABLE `killi_application_token`
ADD CONSTRAINT `killi_application_token_ibfk_1` FOREIGN KEY (`killi_application_id`) REFERENCES `killi_application` (`killi_application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `killi_application_token_ibfk_2` FOREIGN KEY (`killi_user_id`) REFERENCES `killi_user` (`killi_user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
