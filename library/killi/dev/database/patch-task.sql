--
-- Structure de la table `killi_task`
--

CREATE TABLE `killi_task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_action_id` int(10) unsigned NOT NULL,
  `object` varchar(64) NOT NULL,
  `object_id` int(10) unsigned NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `users_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`task_id`),
  KEY `task_action_id` (`task_action_id`,`object`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déclencheurs `killi_task`
--
DROP TRIGGER IF EXISTS `tgr_task_insert`;
DELIMITER //
CREATE TRIGGER `tgr_task_insert` BEFORE INSERT ON `killi_task`
 FOR EACH ROW BEGIN
IF ISNULL( @users_id ) THEN
SET @users_id = 0 ;
END IF ;
SET NEW.users_id=@users_id ;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `killi_task_action`
--

CREATE TABLE `killi_task_action` (
  `task_action_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_name` varchar(255) NOT NULL,
  `task_internal_name` varchar(200) NOT NULL,
  PRIMARY KEY (`task_action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Contraintes pour la table `killi_task`
--
ALTER TABLE `killi_task`
  ADD CONSTRAINT `killi_task_fk_1` FOREIGN KEY (`task_action_id`) REFERENCES `killi_task_action` (`task_action_id`);

-- --------------------------------------------------------

INSERT INTO `killi_workflow` (`nom`, `workflow_name`) VALUES ('Tâche', 'task');

SELECT workflow_id INTO @task_wf_id FROM killi_workflow WHERE workflow_name='task';

INSERT INTO `killi_workflow_node` (`etat`, `workflow_id`, `type_id`, `commande`, `node_name`, `interface`, `object`, `ordre`, `killi_workflow_node_group_id`) VALUES
('Point de départ', @task_wf_id, 3, NULL, 'start_point', NULL, 'task', 1, NULL),
('En attente de traitement', @task_wf_id, 1, NULL, 'tache_en_attente', 'task.enattente.xml', 'task', 2, NULL),
('En cours', @task_wf_id, 1, NULL, 'tache_en_cours', 'task.encours.xml', 'task', 3, NULL),
('Annulée', @task_wf_id, 1, NULL, 'tache_annulee', 'task.annulee.xml', 'task', 3, NULL),
('Traitée', @task_wf_id, 1, NULL, 'tache_traitee', 'task.traitee.xml', 'task', 4, NULL),
('Échouée', @task_wf_id, 1, NULL, 'tache_echouee', 'task.echouee.xml', 'task', 4, NULL);

SELECT workflow_node_id INTO @task_sp FROM `killi_workflow_node` WHERE workflow_id=@task_wf_id AND node_name='start_point';
SELECT workflow_node_id INTO @task_ea FROM `killi_workflow_node` WHERE workflow_id=@task_wf_id AND node_name='tache_en_attente';
SELECT workflow_node_id INTO @task_ec FROM `killi_workflow_node` WHERE workflow_id=@task_wf_id AND node_name='tache_en_cours';
SELECT workflow_node_id INTO @task_an FROM `killi_workflow_node` WHERE workflow_id=@task_wf_id AND node_name='tache_annulee';
SELECT workflow_node_id INTO @task_tr FROM `killi_workflow_node` WHERE workflow_id=@task_wf_id AND node_name='tache_traitee';
SELECT workflow_node_id INTO @task_ech FROM `killi_workflow_node` WHERE workflow_id=@task_wf_id AND node_name='tache_echouee';

INSERT INTO `killi_node_link` (`input_node`, `output_node`, `traitement_echec`, `label`, `display_average`, `deplacement_manuel`, `constraints`, `constraints_label`) VALUES
(@task_sp, @task_ea, 0, NULL, 0, 0, NULL, NULL),
(@task_ea, @task_ec, 0, NULL, 0, 0, NULL, NULL),
(@task_ea, @task_an, 1, NULL, 0, 0, NULL, NULL),
(@task_ec, @task_ea, 0, NULL, 0, 0, NULL, NULL),
(@task_ec, @task_tr, 0, NULL, 0, 0, NULL, NULL),
(@task_ec, @task_ech, 1, NULL, 0, 0, NULL, NULL);