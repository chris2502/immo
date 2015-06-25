
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `killi_process`
--

CREATE TABLE IF NOT EXISTS `killi_process` (
`process_id` int(10) unsigned NOT NULL,
  `module_depart_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(64) NOT NULL,
  `internal_name` varchar(255) NOT NULL,
  `actif` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `killi_process_module`
--

CREATE TABLE IF NOT EXISTS `killi_process_module` (
`module_id` int(10) unsigned NOT NULL,
  `process_id` int(10) unsigned NOT NULL,
  `class_id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `data` longtext,
  `x` int(10) DEFAULT NULL,
  `y` int(10) DEFAULT NULL,
  `delai` int(10) unsigned DEFAULT NULL COMMENT 'Délai maximum en heure !',
  `visibility_user` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `visibility_profile` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `visibility_company` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `killi_process_module_class`
--

CREATE TABLE IF NOT EXISTS `killi_process_module_class` (
`class_id` int(10) unsigned NOT NULL,
  `class_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `desc` text COLLATE utf8_bin,
  `fa_icon` varchar(64) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `killi_process_token`
--

CREATE TABLE IF NOT EXISTS `killi_process_token` (
`token_id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned NOT NULL,
  `killi_user_id` int(10) DEFAULT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `killi_process_token_data`
--

CREATE TABLE IF NOT EXISTS `killi_process_token_data` (
  `token_id` int(10) unsigned NOT NULL,
  `data` longtext COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `killi_process_transition`
--

CREATE TABLE IF NOT EXISTS `killi_process_transition` (
`transition_id` int(10) unsigned NOT NULL,
  `module_depart_id` int(10) unsigned NOT NULL,
  `module_arrivee_id` int(10) unsigned NOT NULL,
  `answer_key` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `killi_process`
--
ALTER TABLE `killi_process`
 ADD PRIMARY KEY (`process_id`), ADD KEY `fk_process_module1` (`module_depart_id`);

--
-- Index pour la table `killi_process_module`
--
ALTER TABLE `killi_process_module`
 ADD PRIMARY KEY (`module_id`), ADD KEY `fk_killi_process_module_killi_process_module_class_idx` (`class_id`), ADD KEY `process_id` (`process_id`);

--
-- Index pour la table `killi_process_module_class`
--
ALTER TABLE `killi_process_module_class`
 ADD PRIMARY KEY (`class_id`);

--
-- Index pour la table `killi_process_token`
--
ALTER TABLE `killi_process_token`
 ADD PRIMARY KEY (`token_id`), ADD KEY `fk_token_module1` (`module_id`);

--
-- Index pour la table `killi_process_token_data`
--
ALTER TABLE `killi_process_token_data`
 ADD UNIQUE KEY `fk_table1_killi_process_token1` (`token_id`);

--
-- Index pour la table `killi_process_transition`
--
ALTER TABLE `killi_process_transition`
 ADD PRIMARY KEY (`transition_id`), ADD UNIQUE KEY `module_depart_id` (`module_depart_id`,`module_arrivee_id`,`answer_key`), ADD KEY `fk_module_has_module_module1` (`module_arrivee_id`), ADD KEY `fk_module_has_module_module` (`module_depart_id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `killi_process`
--
ALTER TABLE `killi_process`
MODIFY `process_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `killi_process_module`
--
ALTER TABLE `killi_process_module`
MODIFY `module_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `killi_process_module_class`
--
ALTER TABLE `killi_process_module_class`
MODIFY `class_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `killi_process_token`
--
ALTER TABLE `killi_process_token`
MODIFY `token_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `killi_process_transition`
--
ALTER TABLE `killi_process_transition`
MODIFY `transition_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `killi_process`
--
ALTER TABLE `killi_process`
ADD CONSTRAINT `fk_process_module1` FOREIGN KEY (`module_depart_id`) REFERENCES `killi_process_module` (`module_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Contraintes pour la table `killi_process_module`
--
ALTER TABLE `killi_process_module`
ADD CONSTRAINT `fk_killi_process_module_killi_process_module_class` FOREIGN KEY (`class_id`) REFERENCES `killi_process_module_class` (`class_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `killi_process_module_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `killi_process` (`process_id`);

--
-- Contraintes pour la table `killi_process_token`
--
ALTER TABLE `killi_process_token`
ADD CONSTRAINT `fk_token_module1` FOREIGN KEY (`module_id`) REFERENCES `killi_process_module` (`module_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Contraintes pour la table `killi_process_token_data`
--
ALTER TABLE `killi_process_token_data`
ADD CONSTRAINT `fk_table1_killi_process_token1` FOREIGN KEY (`token_id`) REFERENCES `killi_process_token` (`token_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Contraintes pour la table `killi_process_transition`
--
ALTER TABLE `killi_process_transition`
ADD CONSTRAINT `fk_module_has_module_module` FOREIGN KEY (`module_depart_id`) REFERENCES `killi_process_module` (`module_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_module_has_module_module1` FOREIGN KEY (`module_arrivee_id`) REFERENCES `killi_process_module` (`module_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
