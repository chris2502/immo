SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS admin_code_type; -- heritage dev
DROP TABLE IF EXISTS admin_code; -- heritage dev

DROP TABLE IF EXISTS killi_jeton;
DROP TABLE IF EXISTS killi_type_jeton;

CREATE TABLE `killi_type_jeton` (
  `type_jeton_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `object` varchar(64) NOT NULL,
  `method` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`type_jeton_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE TABLE `killi_jeton` (
  `jeton_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `destinataire_id` int(10) UNSIGNED NOT NULL,
  `actif` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `type_jeton_id` int(10) UNSIGNED NOT NULL,
  `killi_user_id` int(10) UNSIGNED NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL,
  `object_id` INT(10) UNSIGNED DEFAULT NULL ,
  `end_time` datetime DEFAULT NULL ,
  PRIMARY KEY (`jeton_id`),
  UNIQUE (`code`),
  INDEX `fk_jeton_type_jeton1_idx` (`type_jeton_id` ASC) ,
  CONSTRAINT `fk_jeton_type_jeton1`
    FOREIGN KEY (`type_jeton_id` )
    REFERENCES `killi_type_jeton` (`type_jeton_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE TABLE `killi_jeton_log` (
  `jeton_log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `jeton_id` int(10) UNSIGNED NOT NULL,
  `killi_user_id` int(10) UNSIGNED NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `code` varchar(255) NOT NULL,
  `actif` tinyint(1) UNSIGNED NOT NULL,
  `object` varchar(64) NOT NULL,
  `method` varchar(255) DEFAULT NULL,
  `object_id` INT(10) UNSIGNED DEFAULT NULL ,
  `end_time` datetime DEFAULT NULL ,
  PRIMARY KEY (`jeton_log_id`),
  INDEX `fk_jeton_log_jeton1_idx` (`jeton_id` ASC) ,
  CONSTRAINT `fk_jeton_log_jeton1`
    FOREIGN KEY (`jeton_id` )
    REFERENCES `killi_jeton` (`jeton_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
