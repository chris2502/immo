-- MySQL dump 10.13 Distrib 5.6.21, for debian-linux-gnu (x86_64)
--
-- Host: localhost Database: killi
-- ------------------------------------------------------
-- Server version	5.6.21-1~dotdeb.1

-- set names latin1;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS=0;

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
 `document_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `document_type_id` int(11) unsigned NOT NULL,
 `etat_document_id` tinyint(1) unsigned NOT NULL DEFAULT '1',
 `object` varchar(64) NOT NULL,
 `object_id` int(11) unsigned DEFAULT NULL,
 `mime_type` varchar(255) NOT NULL,
 `size` int(10) unsigned NOT NULL DEFAULT '0',
 `file_name` varchar(255) DEFAULT NULL,
 `hr_name` varchar(255) DEFAULT NULL,
 `users_id` int(11) DEFAULT NULL,
 `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `used_as_padlock` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`document_id`),
 KEY `document_type_id` (`document_type_id`),
 KEY `object` (`object`),
 KEY `object_id` (`object_id`),
 KEY `used_as_padlock` (`used_as_padlock`),
 KEY `document_ibfk_3` (`users_id`),
 KEY `document_ibfk_2` (`etat_document_id`),
 CONSTRAINT `document_ibfk_2` FOREIGN KEY (`etat_document_id`) REFERENCES `etat_document` (`etat_document_id`),
 CONSTRAINT `document_ibfk_3` FOREIGN KEY (`users_id`) REFERENCES `killi_user` (`killi_user_id`),
 CONSTRAINT `document_ibfk_4` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`document_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `document`
--

--
-- Table structure for table `document_type`
--

CREATE TABLE `document_type` (
 `document_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `name` varchar(64) NOT NULL,
 `rulename` varchar(64) NOT NULL,
 `object` varchar(64) NOT NULL,
 `obsolete` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`document_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `document_type`
--

--
-- Table structure for table `etat_document`
--

CREATE TABLE `etat_document` (
 `etat_document_id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
 `nom` varchar(32) DEFAULT NULL,
 PRIMARY KEY (`etat_document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `etat_document`
--

INSERT INTO `etat_document` VALUES (1,'Non vérifié'),(2,'Conforme'),(3,'Non conforme');

--
-- Table structure for table `killi_actionmenu`
--

CREATE TABLE `killi_actionmenu` (
 `actionmenu_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `actionmenu_name` varchar(32) DEFAULT NULL,
 `actionmenu_function` varchar(64) NOT NULL DEFAULT 'edit',
 `actionmenu_label` varchar(64) NOT NULL,
 `actionmenu_parent` int(11) unsigned DEFAULT NULL,
 PRIMARY KEY (`actionmenu_id`),
 KEY `menu_parent` (`actionmenu_parent`),
 CONSTRAINT `killi_actionmenu_ibfk_1` FOREIGN KEY (`actionmenu_parent`) REFERENCES `killi_actionmenu` (`actionmenu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_actionmenu`
--

INSERT INTO `killi_actionmenu` VALUES (1,NULL,'edit','Administration',NULL),(2,'actionmenu','edit','Menus d\'action',1),(3,'user','edit','Utilisateurs',1),(4,'profil','edit','Profils',1),(6,NULL,'edit','Référentiel d\'objet',NULL),(7,'workflow','edit','Workflows',1),(8,'node','edit','Noeuds',7),(9,'nodelink','edit','Liens des noeuds',7),(10,'workflowtokenlog','edit','Historique des tokens',7),(11,'nodequalification','edit','Qualifications des noeuds',7),(12,'document','edit','Base documentaire',1);

--
-- Table structure for table `killi_actionmenu_rights`
--

CREATE TABLE `killi_actionmenu_rights` (
 `actionmenu_right_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `actionmenu_id` int(11) unsigned NOT NULL,
 `view` tinyint(1) NOT NULL DEFAULT '0',
 `profil_id` int(11) NOT NULL,
 PRIMARY KEY (`actionmenu_right_id`),
 UNIQUE KEY `actionmenu_id` (`actionmenu_id`,`profil_id`),
 KEY `profil_id` (`profil_id`),
 KEY `actionmenu_id_2` (`actionmenu_id`),
 CONSTRAINT `fk_killi_actionmenu_rights1` FOREIGN KEY (`profil_id`) REFERENCES `killi_profil` (`killi_profil_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `fk_killi_actionmenu_rights2` FOREIGN KEY (`actionmenu_id`) REFERENCES `killi_actionmenu` (`actionmenu_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_actionmenu_rights`
--

--
-- Table structure for table `killi_android_app_error`
--

CREATE TABLE `killi_android_app_error` (
 `android_app_error_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
 `date_err_client` timestamp NULL DEFAULT NULL,
 `date_err_server` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `app_name` varchar(50) NOT NULL,
 `device_data` varchar(100) NOT NULL,
 `err_type` varchar(100) NOT NULL,
 `err_cause` varchar(200) NOT NULL,
 `err_msg` varchar(255) NOT NULL,
 `err_backtrace` varchar(1000) NOT NULL,
 `alert_is_sended` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 si alerte mail faite',
 PRIMARY KEY (`android_app_error_id`),
 KEY `date_err` (`date_err_client`),
 KEY `app_name` (`app_name`),
 KEY `device_data` (`device_data`),
 KEY `err_type` (`err_type`),
 KEY `err_cause` (`err_cause`),
 KEY `err_msg` (`err_msg`),
 KEY `alert_is_sended` (`alert_is_sended`),
 KEY `date_err_server` (`date_err_server`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_android_app_error`
--

--
-- Table structure for table `killi_application`
--

CREATE TABLE `killi_application` (
 `killi_application_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `nom` varchar(45) NOT NULL,
 `app_token` varchar(64) NOT NULL,
 `app_secret` varchar(64) NOT NULL,
 `redirect_uri` varchar(255) NOT NULL,
 `active` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`killi_application_id`),
 UNIQUE KEY `app_token` (`app_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_application`
--

--
-- Table structure for table `killi_application_token`
--

CREATE TABLE `killi_application_token` (
 `killi_application_token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `token_type_id` smallint(5) unsigned NOT NULL,
 `killi_application_id` int(10) unsigned NOT NULL,
 `killi_user_id` int(11) NOT NULL,
 `token` varchar(64) NOT NULL,
 `validity_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`killi_application_token_id`),
 UNIQUE KEY `token_type_id` (`token_type_id`,`killi_application_id`,`killi_user_id`),
 KEY `killi_application_id` (`killi_application_id`),
 KEY `killi_user_id` (`killi_user_id`),
 CONSTRAINT `killi_application_token_ibfk_1` FOREIGN KEY (`killi_application_id`) REFERENCES `killi_application` (`killi_application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `killi_application_token_ibfk_2` FOREIGN KEY (`killi_user_id`) REFERENCES `killi_user` (`killi_user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_application_token`
--

--
-- Table structure for table `killi_attributes_rights`
--

CREATE TABLE `killi_attributes_rights` (
 `attribute_right_id` int(11) NOT NULL AUTO_INCREMENT,
 `object_name` varchar(64) NOT NULL,
 `attribute_name` varchar(64) NOT NULL,
 `read` tinyint(1) NOT NULL DEFAULT '0',
 `write` tinyint(1) NOT NULL DEFAULT '0',
 `profil_id` int(11) NOT NULL,
 PRIMARY KEY (`attribute_right_id`),
 UNIQUE KEY `object_name` (`object_name`,`attribute_name`,`profil_id`),
 KEY `profil_id` (`profil_id`),
 CONSTRAINT `killi_attributes_rights_ibfk_1` FOREIGN KEY (`profil_id`) REFERENCES `killi_profil` (`killi_profil_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_attributes_rights`
--

--
-- Table structure for table `killi_certificat`
--

CREATE TABLE `killi_certificat` (
 `id_certificat` int(11) NOT NULL AUTO_INCREMENT,
 `killi_user_id` int(11) NOT NULL,
 `duree` int(11) NOT NULL,
 PRIMARY KEY (`id_certificat`),
 KEY `killi_user_id` (`killi_user_id`),
 CONSTRAINT `killi_certificat_ibfk_1` FOREIGN KEY (`killi_user_id`) REFERENCES `killi_user` (`killi_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_certificat`
--

--
-- Table structure for table `killi_link_rights`
--

CREATE TABLE `killi_link_rights` (
 `link_rights_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `link_id` int(11) NOT NULL,
 `killi_profil_id` int(11) NOT NULL,
 `move` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`link_rights_id`),
 UNIQUE KEY `link_id` (`link_id`,`killi_profil_id`),
 KEY `killi_profil_id` (`killi_profil_id`),
 CONSTRAINT `killi_link_rights_ibfk_1` FOREIGN KEY (`killi_profil_id`) REFERENCES `killi_profil` (`killi_profil_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `killi_link_rights_ibfk_2` FOREIGN KEY (`link_id`) REFERENCES `killi_node_link` (`link_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_link_rights`
--

--
-- Table structure for table `killi_macro_filter`
--

CREATE TABLE `killi_macro_filter` (
 `killi_macro_filter_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `view_name` varchar(64) NOT NULL,
 `filter` text NOT NULL,
 `descript` varchar(64) NOT NULL,
 PRIMARY KEY (`killi_macro_filter_id`),
 UNIQUE KEY `view_2` (`view_name`,`descript`),
 KEY `view` (`view_name`),
 KEY `description` (`descript`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_macro_filter`
--

--
-- Table structure for table `killi_node_link`
--

CREATE TABLE `killi_node_link` (
 `link_id` int(11) NOT NULL AUTO_INCREMENT,
 `output_node` int(11) NOT NULL,
 `input_node` int(11) NOT NULL,
 `traitement_echec` tinyint(1) NOT NULL DEFAULT '0',
 `label` varchar(32) DEFAULT NULL,
 `display_average` tinyint(1) NOT NULL DEFAULT '0',
 `deplacement_manuel` tinyint(1) NOT NULL DEFAULT '1',
 `constraints` longtext default null,
 `constraints_label` longtext default null,
 `constraints_padlock` longtext default null,
 `constraints_qualification` tinyint(1) NOT NULL DEFAULT '0',
 `mandatory_comment` tinyint(1) NOT NULL DEFAULT '0',
 `hidden` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`link_id`),
 KEY `output_node` (`output_node`,`input_node`),
 KEY `input_node` (`input_node`),
 KEY `output_node_2` (`output_node`),
 KEY `deplacement_manuel` (`deplacement_manuel`),
 CONSTRAINT `killi_node_link_ibfk_1` FOREIGN KEY (`output_node`) REFERENCES `killi_workflow_node` (`workflow_node_id`),
 CONSTRAINT `killi_node_link_ibfk_2` FOREIGN KEY (`input_node`) REFERENCES `killi_workflow_node` (`workflow_node_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_node_link`
--

INSERT INTO `killi_node_link` VALUES
(1,2,1,0,NULL,0,0,NULL,NULL,NULL,0,0,0),
(2,3,2,0,NULL,0,0,NULL,NULL,NULL,0,0,0),
(3,4,2,1,NULL,0,0,NULL,NULL,NULL,0,0,0),
(4,2,3,0,NULL,0,0,NULL,NULL,NULL,0,0,0),
(5,5,3,0,NULL,0,0,NULL,NULL,NULL,0,0,0),
(6,6,3,1,NULL,0,0,NULL,NULL,NULL,0,0,0);

--
-- Table structure for table `killi_node_qualification`
--

CREATE TABLE `killi_node_qualification` (
 `qualification_id` int(11) NOT NULL AUTO_INCREMENT,
 `node_id` int(11) DEFAULT NULL,
 `nom` varchar(64) NOT NULL,
 `obsolete` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`qualification_id`),
 KEY `id` (`node_id`),
 CONSTRAINT `killi_node_qualification_ibfk_1` FOREIGN KEY (`node_id`) REFERENCES `killi_workflow_node` (`workflow_node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_node_qualification`
--

--
-- Table structure for table `killi_node_rights`
--

CREATE TABLE `killi_node_rights` (
 `node_right_id` int(11) NOT NULL AUTO_INCREMENT,
 `node_id` int(11) NOT NULL,
 `profil_id` int(11) NOT NULL,
 `allow` tinyint(1) NOT NULL,
 PRIMARY KEY (`node_right_id`),
 UNIQUE KEY `node_id` (`node_id`,`profil_id`),
 KEY `profil_id` (`profil_id`),
 KEY `allow` (`allow`),
 CONSTRAINT `killi_node_rights_ibfk_1` FOREIGN KEY (`profil_id`) REFERENCES `killi_profil` (`killi_profil_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `killi_node_rights_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `killi_workflow_node` (`workflow_node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_node_rights`
--

--
-- Table structure for table `killi_node_type`
--

CREATE TABLE `killi_node_type` (
 `node_type_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(64) NOT NULL,
 PRIMARY KEY (`node_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_node_type`
--

INSERT INTO `killi_node_type` VALUES (1,'Interface'),
(2,'Script'),
(3,'Point d\'entrée'),
(4,'Fin'),
(5,'Quantité'),
(6,'Message');

--
-- Table structure for table `killi_notification`
--

CREATE TABLE `killi_notification` (
 `killi_notification_id` int(11) NOT NULL AUTO_INCREMENT,
 `subject` varchar(128) NOT NULL,
 `message` longtext,
 `killi_priority_id` int(11) NOT NULL,
 `notification_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`killi_notification_id`),
 KEY `killi_priority_id` (`killi_priority_id`),
 CONSTRAINT `killi_notification_ibfk_3` FOREIGN KEY (`killi_priority_id`) REFERENCES `killi_priority` (`killi_priority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_notification`
--

--
-- Table structure for table `killi_notification_user`
--

CREATE TABLE `killi_notification_user` (
 `killi_notification_user_id` int(11) NOT NULL AUTO_INCREMENT,
 `killi_notification_id` int(11) NOT NULL,
 `killi_user_id` int(11) NOT NULL,
 `killi_notification_read` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`killi_notification_user_id`),
 UNIQUE KEY `killi_notification_couple` (`killi_notification_id`,`killi_user_id`),
 KEY `killi_notification_id` (`killi_notification_id`),
 KEY `killi_user_id` (`killi_user_id`),
 KEY `killi_notification_read` (`killi_notification_read`),
 CONSTRAINT `killi_notification_user_ibfk_1` FOREIGN KEY (`killi_notification_id`) REFERENCES `killi_notification` (`killi_notification_id`),
 CONSTRAINT `killi_notification_user_ibfk_2` FOREIGN KEY (`killi_user_id`) REFERENCES `killi_user` (`killi_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_notification_user`
--

--
-- Table structure for table `killi_objects_rights`
--

CREATE TABLE `killi_objects_rights` (
 `object_right_id` int(11) NOT NULL AUTO_INCREMENT,
 `object_name` varchar(64) NOT NULL,
 `create` tinyint(1) NOT NULL DEFAULT '0',
 `delete` tinyint(1) NOT NULL DEFAULT '0',
 `profil_id` int(11) NOT NULL,
 `view` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`object_right_id`),
 UNIQUE KEY `object_name` (`object_name`,`profil_id`),
 KEY `profil_id` (`profil_id`),
 CONSTRAINT `killi_objects_rights_ibfk_1` FOREIGN KEY (`profil_id`) REFERENCES `killi_profil` (`killi_profil_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_objects_rights`
--

--
-- Table structure for table `killi_parametric`
--

CREATE TABLE `killi_parametric` (
 `parametric_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
 `parametric_type_id` smallint(5) unsigned NOT NULL,
 `parametric_name` varchar(45) NOT NULL,
 `parametric_description` longtext,
 `parametric_datatype` enum('integer','string') NOT NULL,
 `parametric_value` blob NOT NULL,
 PRIMARY KEY (`parametric_id`),
 KEY `parametric_type_id` (`parametric_type_id`),
 CONSTRAINT `killi_parametric_ibfk_1` FOREIGN KEY (`parametric_type_id`) REFERENCES `killi_type_parametric` (`type_parametric_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_parametric`
--

--
-- Table structure for table `killi_priority`
--

CREATE TABLE `killi_priority` (
 `killi_priority_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(32) NOT NULL,
 PRIMARY KEY (`killi_priority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_priority`
--

INSERT INTO `killi_priority` VALUES (1,'Basse'),(2,'Moyenne'),(3,'Urgente');

--
-- Table structure for table `killi_process`
--

CREATE TABLE `killi_process` (
 `process_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `module_depart_id` int(10) unsigned DEFAULT NULL,
 `name` varchar(64) NOT NULL,
 `internal_name` varchar(255) NOT NULL,
 `actif` tinyint(1) unsigned NOT NULL DEFAULT '0',
 PRIMARY KEY (`process_id`),
 KEY `fk_process_module1` (`module_depart_id`),
 CONSTRAINT `fk_process_module1` FOREIGN KEY (`module_depart_id`) REFERENCES `killi_process_module` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_process`
--

--
-- Table structure for table `killi_process_module`
--

CREATE TABLE `killi_process_module` (
 `module_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `process_id` int(10) unsigned NOT NULL,
 `class_id` int(10) unsigned NOT NULL,
 `name` varchar(255) DEFAULT NULL,
 `data` longtext,
 `x` int(10) DEFAULT NULL,
 `y` int(10) DEFAULT NULL,
 `delai` int(10) unsigned DEFAULT NULL COMMENT 'Délai maximum en heure !',
 `visibility_user` tinyint(1) unsigned NOT NULL DEFAULT '0',
 `visibility_profile` tinyint(1) unsigned NOT NULL DEFAULT '0',
 `visibility_company` tinyint(1) unsigned NOT NULL DEFAULT '0',
 PRIMARY KEY (`module_id`),
 KEY `fk_killi_process_module_killi_process_module_class_idx` (`class_id`),
 KEY `process_id` (`process_id`),
 CONSTRAINT `fk_killi_process_module_killi_process_module_class` FOREIGN KEY (`class_id`) REFERENCES `killi_process_module_class` (`class_id`),
 CONSTRAINT `killi_process_module_ibfk_1` FOREIGN KEY (`process_id`) REFERENCES `killi_process` (`process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_process_module`
--

--
-- Table structure for table `killi_process_module_class`
--

CREATE TABLE `killi_process_module_class` (
 `class_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `class_name` varchar(64) NOT NULL,
 `name` varchar(255) NOT NULL,
 `desc` longtext default null,
 `fa_icon` varchar(64) DEFAULT NULL,
 PRIMARY KEY (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_process_module_class`
--

--
-- Table structure for table `killi_process_token`
--

CREATE TABLE `killi_process_token` (
 `token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `module_id` int(10) unsigned NOT NULL,
 `killi_user_id` int(10) DEFAULT NULL,
 `date` datetime NOT NULL,
 PRIMARY KEY (`token_id`),
 KEY `fk_token_module1` (`module_id`),
 KEY `fk_token_user_id1` (`killi_user_id`),
 CONSTRAINT `fk_token_module1` FOREIGN KEY (`module_id`) REFERENCES `killi_process_module` (`module_id`),
 CONSTRAINT `killi_process_token_ibfk_1` FOREIGN KEY (`killi_user_id`) REFERENCES `killi_user` (`killi_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_process_token`
--

--
-- Table structure for table `killi_process_token_data`
--

CREATE TABLE `killi_process_token_data` (
 `token_id` int(10) unsigned NOT NULL,
 `data` longtext,
 UNIQUE KEY `fk_table1_killi_process_token1` (`token_id`),
 CONSTRAINT `fk_table1_killi_process_token1` FOREIGN KEY (`token_id`) REFERENCES `killi_process_token` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_process_token_data`
--

--
-- Table structure for table `killi_process_transition`
--

CREATE TABLE `killi_process_transition` (
 `transition_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `module_depart_id` int(10) unsigned NOT NULL,
 `module_arrivee_id` int(10) unsigned NOT NULL,
 `answer_key` varchar(64) DEFAULT NULL,
 PRIMARY KEY (`transition_id`),
 UNIQUE KEY `module_depart_id` (`module_depart_id`,`module_arrivee_id`,`answer_key`),
 KEY `fk_module_has_module_module1` (`module_arrivee_id`),
 KEY `fk_module_has_module_module` (`module_depart_id`),
 CONSTRAINT `fk_module_has_module_module` FOREIGN KEY (`module_depart_id`) REFERENCES `killi_process_module` (`module_id`),
 CONSTRAINT `fk_module_has_module_module1` FOREIGN KEY (`module_arrivee_id`) REFERENCES `killi_process_module` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_process_transition`
--

--
-- Table structure for table `killi_profil`
--

CREATE TABLE `killi_profil` (
 `killi_profil_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(32) NOT NULL,
 PRIMARY KEY (`killi_profil_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_profil`
--

INSERT INTO `killi_profil` VALUES (1,'Administrateur'),(2,'Lecture seule'),(3,'SYSTEM');

--
-- Table structure for table `killi_query_type`
--

CREATE TABLE `killi_query_type` (
 `query_type_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(64) NOT NULL,
 PRIMARY KEY (`query_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_query_type`
--

--
-- Table structure for table `killi_stats_category`
--

CREATE TABLE `killi_stats_category` (
 `stats_cat_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(64) NOT NULL,
 `stats_cat_name` varchar(64) NOT NULL,
 `ordre` tinyint(1) unsigned NOT NULL DEFAULT '0',
 PRIMARY KEY (`stats_cat_id`),
 KEY `stats_cat_name` (`stats_cat_name`),
 KEY `ordre` (`ordre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_stats_category`
--

--
-- Table structure for table `killi_stats_query`
--

CREATE TABLE `killi_stats_query` (
 `query_id` int(11) NOT NULL AUTO_INCREMENT,
 `cat_id` int(11) NOT NULL,
 `type_id` int(11) NOT NULL,
 `parent_id` int(11) DEFAULT NULL,
 `nom` varchar(64) NOT NULL,
 `query_name` varchar(64) NOT NULL,
 `query_sql` longtext,
 `object` varchar(64) DEFAULT NULL,
 `param_object` varchar(64) DEFAULT NULL,
 `param_domain` varchar(128) DEFAULT NULL,
 `ordre` tinyint(1) unsigned DEFAULT NULL,
 PRIMARY KEY (`query_id`),
 KEY `cat_id` (`cat_id`),
 KEY `type_id` (`type_id`),
 KEY `parent_id` (`parent_id`),
 KEY `object` (`object`),
 KEY `query_name` (`query_name`),
 KEY `ordre` (`ordre`),
 CONSTRAINT `killi_stats_query_fk1` FOREIGN KEY (`cat_id`) REFERENCES `killi_stats_category` (`stats_cat_id`),
 CONSTRAINT `killi_stats_query_fk2` FOREIGN KEY (`type_id`) REFERENCES `killi_query_type` (`query_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_stats_query`
--

--
-- Table structure for table `killi_stats_report`
--

CREATE TABLE `killi_stats_report` (
 `report_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(64) NOT NULL,
 `report_name` varchar(64) NOT NULL,
 KEY `report_id` (`report_id`),
 KEY `report_name` (`report_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_stats_report`
--

--
-- Table structure for table `killi_stats_value`
--

CREATE TABLE `killi_stats_value` (
 `value_id` int(11) NOT NULL AUTO_INCREMENT,
 `report_date` datetime NOT NULL,
 `query_id` int(11) NOT NULL,
 `value` int(11) DEFAULT NULL,
 `param_value` int(11) DEFAULT NULL,
 PRIMARY KEY (`value_id`),
 KEY `query_id` (`query_id`),
 CONSTRAINT `killi_stats_value_fk1` FOREIGN KEY (`query_id`) REFERENCES `killi_stats_query` (`query_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_stats_value`
--

--
-- Table structure for table `killi_task`
--

CREATE TABLE `killi_task` (
 `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `task_action_id` int(10) unsigned NOT NULL,
 `object` varchar(64) NOT NULL,
 `object_id` int(10) unsigned NOT NULL,
 `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `users_id` int(11) DEFAULT NULL,
 PRIMARY KEY (`task_id`),
 KEY `task_action_id` (`task_action_id`,`object`,`object_id`),
 KEY `users_id` (`users_id`),
 CONSTRAINT `killi_task_fk_1` FOREIGN KEY (`task_action_id`) REFERENCES `killi_task_action` (`task_action_id`),
 CONSTRAINT `killi_task_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `killi_user` (`killi_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_task`
--

DELIMITER ;;
 CREATE TRIGGER `tgr_task_insert` BEFORE INSERT ON `killi_task`
 FOR EACH ROW BEGIN
IF ISNULL( @users_id ) THEN
SET @users_id = 0 ;
END IF ;
SET NEW.users_id=@users_id ;
END ;;
DELIMITER ;

--
-- Table structure for table `killi_task_action`
--

CREATE TABLE `killi_task_action` (
 `task_action_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `task_name` varchar(255) NOT NULL,
 `task_internal_name` varchar(200) NOT NULL,
 PRIMARY KEY (`task_action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_task_action`
--

--
-- Table structure for table `killi_type_parametric`
--

CREATE TABLE `killi_type_parametric` (
 `type_parametric_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
 `type_parametric_name` varchar(45) NOT NULL,
 PRIMARY KEY (`type_parametric_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_type_parametric`
--

--
-- Table structure for table `killi_user`
--

CREATE TABLE `killi_user` (
 `killi_user_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(24) NOT NULL,
 `prenom` varchar(24) NOT NULL,
 `mail` varchar(128) NOT NULL,
 `actif` tinyint(1) NOT NULL DEFAULT '0',
 `certificat_duree` smallint(3) NOT NULL,
 `certificat_date_envoi` datetime DEFAULT NULL,
 `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `login` varchar(32) NOT NULL,
 `password` varchar(40) NOT NULL,
 `last_connection` datetime DEFAULT NULL,
 PRIMARY KEY (`killi_user_id`),
 UNIQUE KEY `login` (`login`),
 KEY `actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_user`
--

--
-- Table structure for table `killi_user_log`
--

CREATE TABLE `killi_user_log` (
 `killi_user_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `killi_user_id` int(10) unsigned NOT NULL,
 `action` varchar(128) NOT NULL,
 `type_view` varchar(16) DEFAULT NULL,
 `date_log` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `pk` int(10) unsigned DEFAULT NULL,
 `ipv4` int(9) unsigned NOT NULL,
 PRIMARY KEY (`killi_user_log_id`),
 KEY `killi_user_id` (`killi_user_id`,`action`),
 KEY `type_view` (`type_view`),
 KEY `pk` (`pk`),
 KEY `ipv4` (`ipv4`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_user_log`
--

--
-- Table structure for table `killi_user_preferences`
--

CREATE TABLE `killi_user_preferences` (
 `killi_user_id` int(11) NOT NULL,
 `ui_theme` varchar(24) DEFAULT NULL,
 `items_per_page` tinyint(3) unsigned NOT NULL DEFAULT '200',
 `unlocked_header` tinyint(1) NOT NULL DEFAULT '1',
 PRIMARY KEY (`killi_user_id`),
 CONSTRAINT `killi_user_preferences_ibfk_1` FOREIGN KEY (`killi_user_id`) REFERENCES `killi_user` (`killi_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_user_preferences`
--

--
-- Table structure for table `killi_user_profil`
--

CREATE TABLE `killi_user_profil` (
 `user_profil_id` int(11) NOT NULL AUTO_INCREMENT,
 `killi_user_id` int(11) NOT NULL,
 `killi_profil_id` int(11) NOT NULL,
 PRIMARY KEY (`user_profil_id`),
 UNIQUE KEY `killi_user_id` (`killi_user_id`,`killi_profil_id`),
 KEY `killi_profil_id` (`killi_profil_id`),
 KEY `killi_user_id_2` (`killi_user_id`),
 CONSTRAINT `killi_user_profil_ibfk_1` FOREIGN KEY (`killi_user_id`) REFERENCES `killi_user` (`killi_user_id`),
 CONSTRAINT `killi_user_profil_ibfk_2` FOREIGN KEY (`killi_profil_id`) REFERENCES `killi_profil` (`killi_profil_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_user_profil`
--

--
-- Table structure for table `killi_workflow`
--

CREATE TABLE `killi_workflow` (
 `workflow_id` int(11) NOT NULL AUTO_INCREMENT,
 `nom` varchar(64) NOT NULL,
 `workflow_name` varchar(32) NOT NULL,
 `obsolete` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`workflow_id`),
 KEY `workflow_id` (`workflow_id`),
 KEY `workflow_name` (`workflow_name`),
 KEY `obsolete` (`obsolete`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_workflow`
--

INSERT INTO `killi_workflow` VALUES (1,'Tâches','task',0);

--
-- Table structure for table `killi_workflow_node`
--

CREATE TABLE `killi_workflow_node` (
 `workflow_node_id` int(11) NOT NULL AUTO_INCREMENT,
 `etat` varchar(64) NOT NULL,
 `workflow_id` int(11) NOT NULL,
 `type_id` int(11) NOT NULL,
 `commande` varchar(255) DEFAULT NULL,
 `node_name` varchar(32) NOT NULL,
 `interface` varchar(64) DEFAULT NULL,
 `object` varchar(64) NOT NULL,
 `ordre` tinyint(1) DEFAULT '0',
 `killi_workflow_node_group_id` int(11) DEFAULT NULL,
 PRIMARY KEY (`workflow_node_id`),
 KEY `type_id` (`type_id`),
 KEY `workflow_id` (`workflow_id`),
 KEY `ordre` (`ordre`),
 KEY `node_name` (`node_name`),
 KEY `killi_workflow_node_group_id` (`killi_workflow_node_group_id`),
 CONSTRAINT `killi_workflow_node_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `killi_node_type` (`node_type_id`),
 CONSTRAINT `killi_workflow_node_ibfk_3` FOREIGN KEY (`workflow_id`) REFERENCES `killi_workflow` (`workflow_id`),
 CONSTRAINT `killi_workflow_node_ibfk_4` FOREIGN KEY (`killi_workflow_node_group_id`) REFERENCES `killi_workflow_node_group` (`killi_workflow_node_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_workflow_node`
--

INSERT INTO `killi_workflow_node` VALUES (1,'Point de départ',1,3,NULL,'start_point',NULL,'task',1,NULL),(2,'En attente de traitement',1,1,NULL,'tache_en_attente','task.enattente.xml','task',2,NULL),(3,'En cours',1,1,NULL,'tache_en_cours','task.encours.xml','task',3,NULL),(4,'Annulée',1,1,NULL,'tache_annulee','task.annulee.xml','task',3,NULL),(5,'Traitée',1,1,NULL,'tache_traitee','task.traitee.xml','task',4,NULL),(6,'Échouée',1,1,NULL,'tache_echouee','task.echouee.xml','task',4,NULL);

--
-- Table structure for table `killi_workflow_node_group`
--

CREATE TABLE `killi_workflow_node_group` (
 `killi_workflow_node_group_id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(32) NOT NULL,
 `color` enum('lightgrey','lightpink','lightblue') NOT NULL,
 PRIMARY KEY (`killi_workflow_node_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_workflow_node_group`
--

--
-- Table structure for table `killi_workflow_token`
--

CREATE TABLE `killi_workflow_token` (
 `workflow_token_id` int(11) NOT NULL AUTO_INCREMENT,
 `node_id` int(11) NOT NULL,
 `id` int(11) DEFAULT NULL,
 `commentaire` longtext,
 `qualification_id` int(11) DEFAULT NULL,
 `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (`workflow_token_id`),
 UNIQUE KEY `node_id` (`node_id`,`id`),
 KEY `id` (`id`),
 KEY `qualification_id` (`qualification_id`),
 CONSTRAINT `killi_workflow_token_ibfk_1` FOREIGN KEY (`node_id`) REFERENCES `killi_workflow_node` (`workflow_node_id`),
 CONSTRAINT `killi_workflow_tokenfk_1` FOREIGN KEY (`qualification_id`) REFERENCES `killi_node_qualification` (`qualification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_workflow_token`
--

DELIMITER ;;
 CREATE TRIGGER `tgr_workflow_token_insert` AFTER INSERT ON `killi_workflow_token`
 FOR EACH ROW BEGIN
INSERT INTO killi_workflow_token_log SET commentaire=new.commentaire, qualification_id=new.qualification_id, workflow_token_id=new.workflow_token_id,from_node_id=NULL, to_node_id=new.node_id, id=new.id,`date`=NOW(),user_id=@users_id ;
END ;;
DELIMITER ;

DELIMITER ;;
 CREATE TRIGGER `tgr_workflow_token_update` BEFORE UPDATE ON `killi_workflow_token`
 FOR EACH ROW BEGIN
INSERT INTO killi_workflow_token_log SET commentaire=new.commentaire, qualification_id=new.qualification_id, workflow_token_id=NEW.workflow_token_id,from_node_id=OLD.node_id, to_node_id=NEW.node_id, id=NEW.id,`date`=NOW(),user_id=@users_id ;
END ;;
DELIMITER ;

--
-- Table structure for table `killi_workflow_token_log`
--

CREATE TABLE `killi_workflow_token_log` (
 `workflow_token_log_id` int(11) NOT NULL AUTO_INCREMENT,
 `workflow_token_id` int(11) NOT NULL,
 `to_node_id` int(11) DEFAULT NULL,
 `id` int(11) DEFAULT NULL,
 `commentaire` longtext,
 `qualification_id` int(11) DEFAULT NULL,
 `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `user_id` int(11) DEFAULT NULL,
 `from_node_id` int(11) DEFAULT NULL,
 PRIMARY KEY (`workflow_token_log_id`),
 KEY `node_id` (`to_node_id`,`id`),
 KEY `user_id` (`user_id`),
 KEY `workflow_token_id` (`workflow_token_id`),
 KEY `qualification_id` (`qualification_id`),
 KEY `from_node_id` (`from_node_id`),
 KEY `to_node_id` (`to_node_id`),
 CONSTRAINT `killi_workflow_token_log_ibfk_2` FOREIGN KEY (`to_node_id`) REFERENCES `killi_workflow_node` (`workflow_node_id`) ON DELETE SET NULL ON UPDATE CASCADE,
 CONSTRAINT `killi_workflow_token_log_ibfk_3` FOREIGN KEY (`from_node_id`) REFERENCES `killi_workflow_node` (`workflow_node_id`) ON DELETE SET NULL ON UPDATE CASCADE,
 CONSTRAINT `killi_workflow_token_log_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `killi_user` (`killi_user_id`),
 CONSTRAINT `killi_workflow_token_log_ibfk_5` FOREIGN KEY (`qualification_id`) REFERENCES `killi_node_qualification` (`qualification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `killi_workflow_token_log`
--

SET FOREIGN_KEY_CHECKS=1;

-- Dump completed on 2014-12-01 17:51:34
