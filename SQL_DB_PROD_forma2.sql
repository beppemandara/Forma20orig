-- phpMyAdmin SQL Dump
-- version 4.3.11.1
-- http://www.phpmyadmin.net
--
-- Host: prd-formazione-vdb02.formazione.csi.it:3306
-- Generation Time: Giu 05, 2020 alle 15:50
-- Versione del server: 5.6.15-log
-- PHP Version: 5.4.45

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `forma2`
--

DELIMITER $$
--
-- Funzioni
--
CREATE DEFINER=`forma2`@`%` FUNCTION `ak_f2_get_budget_validato`(`par_anno_formativo` INT, `par_direzioneid` INT, `par_tipo_pianificazione` VARCHAR(10), `par_euro_giorni` VARCHAR(10)) RETURNS float
    READS SQL DATA
    DETERMINISTIC
BEGIN
  DECLARE ret_val float; 
	DECLARE costo_tot float;
	DECLARE durata_tot float;
	
		select sum(p.costo), sum(p.durata)
		into costo_tot,durata_tot
		from mdl_f2_prenotati p, mdl_f2_anagrafica_corsi ac
			where p.isdeleted = 0 and p.orgid in (SELECT id FROM mdl_org where visible = 1 and (path like concat('%/',par_direzioneid,'/%') or id = par_direzioneid))
				and p.anno = par_anno_formativo and p.validato_dir = 1
				and ac.tipo_budget in (select tb.id from mdl_f2_tipo_pianificazione tb where tb.stato = 'a' and (lower(tb.descrizione) like concat('%',lower(par_tipo_pianificazione),'%')))
			and ac.courseid = p.courseid;
  CASE
		WHEN UPPER(par_euro_giorni) = 'EURO' THEN
				set ret_val = costo_tot;
		WHEN UPPER(par_euro_giorni) = 'GIORNI' THEN
			set ret_val = durata_tot;
		ELSE
			set ret_val = 0.0;
  END CASE;
	RETURN ret_val;
END$$

CREATE DEFINER=`forma2`@`%` FUNCTION `ak_f2_report_get_date_svolgimento`(`par_ftfsessionid` INT) RETURNS varchar(255) CHARSET latin1
    READS SQL DATA
    DETERMINISTIC
BEGIN
	DECLARE done INT DEFAULT FALSE;
  DECLARE date_svolg VARCHAR(255) DEFAULT ''; 
  DECLARE var_timestart_ts INT(20);
  DECLARE var_timestart_dt VARCHAR(255) DEFAULT '';
  DECLARE var_timeend_ts INT(20);
	DECLARE cursore CURSOR 
		FOR SELECT timestart,timefinish FROM mdl_facetoface_sessions_dates where sessionid = par_ftfsessionid order by timestart asc;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
	OPEN cursore;
	read_loop: LOOP 
		FETCH cursore INTO var_timestart_ts, var_timeend_ts;
		if done THEN 
			LEAVE read_loop;
		END IF;
		IF var_timestart_ts > 0 THEN
			SET var_timestart_dt = FROM_UNIXTIME(var_timestart_ts,'%d/%m/%Y');
			IF INSTR (date_svolg,var_timestart_dt) = 0 THEN
				SET date_svolg = concat(date_svolg,', ',var_timestart_dt);
			END IF;
			SET var_timestart_ts = var_timestart_ts + 86400;
			WHILE var_timestart_ts < var_timeend_ts DO
				SET var_timestart_dt = FROM_UNIXTIME(var_timestart_ts,'%d/%m/%Y');
				IF INSTR (date_svolg,var_timestart_dt) = 0 THEN
					SET date_svolg = concat(date_svolg,', ',var_timestart_dt);
				END IF;
				SET var_timestart_ts = var_timestart_ts + 86400;
			END WHILE;
			SET var_timestart_dt = FROM_UNIXTIME(var_timeend_ts,'%d/%m/%Y');
			IF INSTR (date_svolg,var_timestart_dt) = 0 THEN
				SET date_svolg = concat(date_svolg,', ',var_timestart_dt);
			END IF;
		END IF;
	END LOOP;
	CLOSE cursore;
	SET date_svolg = TRIM(LEADING ', ' FROM date_svolg);
  RETURN date_svolg;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assign`
--

CREATE TABLE IF NOT EXISTS `mdl_assign` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `alwaysshowdescription` tinyint(2) NOT NULL DEFAULT '0',
  `nosubmissions` tinyint(2) NOT NULL DEFAULT '0',
  `submissiondrafts` tinyint(2) NOT NULL DEFAULT '0',
  `sendnotifications` tinyint(2) NOT NULL DEFAULT '0',
  `sendlatenotifications` tinyint(2) NOT NULL DEFAULT '0',
  `duedate` bigint(10) NOT NULL DEFAULT '0',
  `allowsubmissionsfromdate` bigint(10) NOT NULL DEFAULT '0',
  `grade` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `requiresubmissionstatement` tinyint(2) NOT NULL DEFAULT '0',
  `completionsubmit` tinyint(2) NOT NULL DEFAULT '0',
  `cutoffdate` bigint(10) NOT NULL DEFAULT '0',
  `teamsubmission` tinyint(2) NOT NULL DEFAULT '0',
  `requireallteammemberssubmit` tinyint(2) NOT NULL DEFAULT '0',
  `teamsubmissiongroupingid` bigint(10) NOT NULL DEFAULT '0',
  `blindmarking` tinyint(2) NOT NULL DEFAULT '0',
  `revealidentities` tinyint(2) NOT NULL DEFAULT '0',
  `attemptreopenmethod` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `maxattempts` mediumint(6) NOT NULL DEFAULT '-1',
  `markingworkflow` tinyint(2) NOT NULL DEFAULT '0',
  `markingallocation` tinyint(2) NOT NULL DEFAULT '0',
  `sendstudentnotifications` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table saves information about an instance of mod_assign';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignfeedback_comments`
--

CREATE TABLE IF NOT EXISTS `mdl_assignfeedback_comments` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `grade` bigint(10) NOT NULL DEFAULT '0',
  `commenttext` longtext COLLATE utf8_unicode_ci,
  `commentformat` smallint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Text feedback for submitted assignments';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignfeedback_editpdf_annot`
--

CREATE TABLE IF NOT EXISTS `mdl_assignfeedback_editpdf_annot` (
  `id` bigint(10) NOT NULL,
  `gradeid` bigint(10) NOT NULL DEFAULT '0',
  `pageno` bigint(10) NOT NULL DEFAULT '0',
  `x` bigint(10) DEFAULT '0',
  `y` bigint(10) DEFAULT '0',
  `endx` bigint(10) DEFAULT '0',
  `endy` bigint(10) DEFAULT '0',
  `path` longtext COLLATE utf8_unicode_ci,
  `type` varchar(10) COLLATE utf8_unicode_ci DEFAULT 'line',
  `colour` varchar(10) COLLATE utf8_unicode_ci DEFAULT 'black',
  `draft` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='stores annotations added to pdfs submitted by students';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignfeedback_editpdf_cmnt`
--

CREATE TABLE IF NOT EXISTS `mdl_assignfeedback_editpdf_cmnt` (
  `id` bigint(10) NOT NULL,
  `gradeid` bigint(10) NOT NULL DEFAULT '0',
  `x` bigint(10) DEFAULT '0',
  `y` bigint(10) DEFAULT '0',
  `width` bigint(10) DEFAULT '120',
  `rawtext` longtext COLLATE utf8_unicode_ci,
  `pageno` bigint(10) NOT NULL DEFAULT '0',
  `colour` varchar(10) COLLATE utf8_unicode_ci DEFAULT 'black',
  `draft` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores comments added to pdfs';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignfeedback_editpdf_quick`
--

CREATE TABLE IF NOT EXISTS `mdl_assignfeedback_editpdf_quick` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `rawtext` longtext COLLATE utf8_unicode_ci NOT NULL,
  `width` bigint(10) NOT NULL DEFAULT '120',
  `colour` varchar(10) COLLATE utf8_unicode_ci DEFAULT 'yellow'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores teacher specified quicklist comments';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignfeedback_file`
--

CREATE TABLE IF NOT EXISTS `mdl_assignfeedback_file` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `grade` bigint(10) NOT NULL DEFAULT '0',
  `numfiles` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores info about the number of files submitted by a student';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignment`
--

CREATE TABLE IF NOT EXISTS `mdl_assignment` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `assignmenttype` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `resubmit` tinyint(2) NOT NULL DEFAULT '0',
  `preventlate` tinyint(2) NOT NULL DEFAULT '0',
  `emailteachers` tinyint(2) NOT NULL DEFAULT '0',
  `var1` bigint(10) DEFAULT '0',
  `var2` bigint(10) DEFAULT '0',
  `var3` bigint(10) DEFAULT '0',
  `var4` bigint(10) DEFAULT '0',
  `var5` bigint(10) DEFAULT '0',
  `maxbytes` bigint(10) NOT NULL DEFAULT '100000',
  `timedue` bigint(10) NOT NULL DEFAULT '0',
  `timeavailable` bigint(10) NOT NULL DEFAULT '0',
  `grade` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines assignments';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignment_submissions`
--

CREATE TABLE IF NOT EXISTS `mdl_assignment_submissions` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `numfiles` bigint(10) NOT NULL DEFAULT '0',
  `data1` longtext COLLATE utf8_unicode_ci,
  `data2` longtext COLLATE utf8_unicode_ci,
  `grade` bigint(11) NOT NULL DEFAULT '0',
  `submissioncomment` longtext COLLATE utf8_unicode_ci NOT NULL,
  `format` smallint(4) NOT NULL DEFAULT '0',
  `teacher` bigint(10) NOT NULL DEFAULT '0',
  `timemarked` bigint(10) NOT NULL DEFAULT '0',
  `mailed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about submitted assignments';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignment_upgrade`
--

CREATE TABLE IF NOT EXISTS `mdl_assignment_upgrade` (
  `id` bigint(10) NOT NULL,
  `oldcmid` bigint(10) NOT NULL DEFAULT '0',
  `oldinstance` bigint(10) NOT NULL DEFAULT '0',
  `newcmid` bigint(10) NOT NULL DEFAULT '0',
  `newinstance` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignsubmission_file`
--

CREATE TABLE IF NOT EXISTS `mdl_assignsubmission_file` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `submission` bigint(10) NOT NULL DEFAULT '0',
  `numfiles` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about file submissions for assignments';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assignsubmission_onlinetext`
--

CREATE TABLE IF NOT EXISTS `mdl_assignsubmission_onlinetext` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `submission` bigint(10) NOT NULL DEFAULT '0',
  `onlinetext` longtext COLLATE utf8_unicode_ci,
  `onlineformat` smallint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about onlinetext submission';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assign_grades`
--

CREATE TABLE IF NOT EXISTS `mdl_assign_grades` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `grader` bigint(10) NOT NULL DEFAULT '0',
  `grade` decimal(10,5) DEFAULT '0.00000',
  `attemptnumber` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Grading information about a single assignment submission.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assign_plugin_config`
--

CREATE TABLE IF NOT EXISTS `mdl_assign_plugin_config` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `plugin` varchar(28) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `subtype` varchar(28) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(28) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Config data for an instance of a plugin in an assignment.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assign_submission`
--

CREATE TABLE IF NOT EXISTS `mdl_assign_submission` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `status` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `attemptnumber` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table keeps information about student interactions with';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assign_user_flags`
--

CREATE TABLE IF NOT EXISTS `mdl_assign_user_flags` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `locked` bigint(10) NOT NULL DEFAULT '0',
  `mailed` smallint(4) NOT NULL DEFAULT '0',
  `extensionduedate` bigint(10) NOT NULL DEFAULT '0',
  `workflowstate` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `allocatedmarker` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='List of flags that can be set for a single user in a single ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_assign_user_mapping`
--

CREATE TABLE IF NOT EXISTS `mdl_assign_user_mapping` (
  `id` bigint(10) NOT NULL,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Map an assignment specific id number to a user';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_backup_controllers`
--

CREATE TABLE IF NOT EXISTS `mdl_backup_controllers` (
  `id` bigint(10) NOT NULL,
  `backupid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `operation` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'backup',
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemid` bigint(10) NOT NULL,
  `format` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `interactive` smallint(4) NOT NULL,
  `purpose` smallint(4) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `status` smallint(4) NOT NULL,
  `execution` smallint(4) NOT NULL,
  `executiontime` bigint(10) NOT NULL,
  `checksum` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `controller` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=708 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To store the backup_controllers as they are used';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_backup_courses`
--

CREATE TABLE IF NOT EXISTS `mdl_backup_courses` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `laststarttime` bigint(10) NOT NULL DEFAULT '0',
  `lastendtime` bigint(10) NOT NULL DEFAULT '0',
  `laststatus` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '5',
  `nextstarttime` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To store every course backup status';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_backup_logs`
--

CREATE TABLE IF NOT EXISTS `mdl_backup_logs` (
  `id` bigint(10) NOT NULL,
  `backupid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `loglevel` smallint(4) NOT NULL,
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `timecreated` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To store all the logs from backup and restore operations (by';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge`
--

CREATE TABLE IF NOT EXISTS `mdl_badge` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `usercreated` bigint(10) NOT NULL,
  `usermodified` bigint(10) NOT NULL,
  `issuername` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `issuerurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `issuercontact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expiredate` bigint(10) DEFAULT NULL,
  `expireperiod` bigint(10) DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `courseid` bigint(10) DEFAULT NULL,
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `messagesubject` longtext COLLATE utf8_unicode_ci NOT NULL,
  `attachment` tinyint(1) NOT NULL DEFAULT '1',
  `notification` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `nextcron` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge_backpack`
--

CREATE TABLE IF NOT EXISTS `mdl_badge_backpack` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `backpackurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `backpackuid` bigint(10) NOT NULL,
  `autosync` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge_criteria`
--

CREATE TABLE IF NOT EXISTS `mdl_badge_criteria` (
  `id` bigint(10) NOT NULL,
  `badgeid` bigint(10) NOT NULL DEFAULT '0',
  `criteriatype` bigint(10) DEFAULT NULL,
  `method` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge_criteria_met`
--

CREATE TABLE IF NOT EXISTS `mdl_badge_criteria_met` (
  `id` bigint(10) NOT NULL,
  `issuedid` bigint(10) DEFAULT NULL,
  `critid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `datemet` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge_criteria_param`
--

CREATE TABLE IF NOT EXISTS `mdl_badge_criteria_param` (
  `id` bigint(10) NOT NULL,
  `critid` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge_external`
--

CREATE TABLE IF NOT EXISTS `mdl_badge_external` (
  `id` bigint(10) NOT NULL,
  `backpackid` bigint(10) NOT NULL,
  `collectionid` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge_issued`
--

CREATE TABLE IF NOT EXISTS `mdl_badge_issued` (
  `id` bigint(10) NOT NULL,
  `badgeid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `uniquehash` longtext COLLATE utf8_unicode_ci NOT NULL,
  `dateissued` bigint(10) NOT NULL DEFAULT '0',
  `dateexpire` bigint(10) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `issuernotified` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_badge_manual_award`
--

CREATE TABLE IF NOT EXISTS `mdl_badge_manual_award` (
  `id` bigint(10) NOT NULL,
  `badgeid` bigint(10) NOT NULL,
  `recipientid` bigint(10) NOT NULL,
  `issuerid` bigint(10) NOT NULL,
  `issuerrole` bigint(10) NOT NULL,
  `datemet` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block`
--

CREATE TABLE IF NOT EXISTS `mdl_block` (
  `id` bigint(10) NOT NULL,
  `name` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cron` bigint(10) NOT NULL DEFAULT '0',
  `lastcron` bigint(10) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='contains all installed blocks';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_community`
--

CREATE TABLE IF NOT EXISTS `mdl_block_community` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `coursename` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `coursedescription` longtext COLLATE utf8_unicode_ci,
  `courseurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `imageurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Community block';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_f2_gestione_risorse`
--

CREATE TABLE IF NOT EXISTS `mdl_block_f2_gestione_risorse` (
  `id` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Default comment for block_f2_gestione_risorse, please edit m';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_formindbudget`
--

CREATE TABLE IF NOT EXISTS `mdl_block_formindbudget` (
  `id` bigint(10) NOT NULL,
  `anno` smallint(4) NOT NULL,
  `budget` decimal(20,2) NOT NULL DEFAULT '0.00',
  `inseritoda` bigint(10) DEFAULT NULL,
  `datainserimento` bigint(20) DEFAULT NULL,
  `modificatoda` bigint(10) DEFAULT NULL,
  `datamodifica` bigint(20) DEFAULT NULL,
  `note` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tabella di gestione del budget';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_formindbudget_log`
--

CREATE TABLE IF NOT EXISTS `mdl_block_formindbudget_log` (
  `id` bigint(10) NOT NULL,
  `azione` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` bigint(20) DEFAULT NULL,
  `msg` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tabella di log';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_formindbudget_storico`
--

CREATE TABLE IF NOT EXISTS `mdl_block_formindbudget_storico` (
  `id` bigint(10) NOT NULL,
  `annoriferimento` smallint(4) NOT NULL,
  `valorebudget` decimal(20,2) DEFAULT '0.00',
  `inseritoda` bigint(10) NOT NULL,
  `datainserimento` bigint(20) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='storico';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_instances`
--

CREATE TABLE IF NOT EXISTS `mdl_block_instances` (
  `id` bigint(10) NOT NULL,
  `blockname` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `parentcontextid` bigint(10) NOT NULL,
  `showinsubcontexts` smallint(4) NOT NULL,
  `pagetypepattern` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `subpagepattern` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `defaultregion` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `defaultweight` bigint(10) NOT NULL,
  `configdata` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=3387 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table stores block instances. The type of block this is';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_positions`
--

CREATE TABLE IF NOT EXISTS `mdl_block_positions` (
  `id` bigint(10) NOT NULL,
  `blockinstanceid` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `pagetype` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `subpage` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `visible` smallint(4) NOT NULL,
  `region` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `weight` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the position of a sticky block_instance on a another ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_recent_activity`
--

CREATE TABLE IF NOT EXISTS `mdl_block_recent_activity` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `cmid` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `action` tinyint(1) NOT NULL,
  `modname` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2545 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_block_rss_client`
--

CREATE TABLE IF NOT EXISTS `mdl_block_rss_client` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `title` longtext COLLATE utf8_unicode_ci NOT NULL,
  `preferredtitle` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `shared` tinyint(2) NOT NULL DEFAULT '0',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Remote news feed information. Contains the news feed id, the';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_blog_association`
--

CREATE TABLE IF NOT EXISTS `mdl_blog_association` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `blogid` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Associations of blog entries with courses and module instanc';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_blog_external`
--

CREATE TABLE IF NOT EXISTS `mdl_blog_external` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `url` longtext COLLATE utf8_unicode_ci NOT NULL,
  `filtertags` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `failedlastsync` tinyint(1) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) DEFAULT NULL,
  `timefetched` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='External blog links used for RSS copying of blog entries to ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_book`
--

CREATE TABLE IF NOT EXISTS `mdl_book` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `numbering` smallint(4) NOT NULL DEFAULT '0',
  `customtitles` tinyint(2) NOT NULL DEFAULT '0',
  `revision` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines book';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_book_chapters`
--

CREATE TABLE IF NOT EXISTS `mdl_book_chapters` (
  `id` bigint(10) NOT NULL,
  `bookid` bigint(10) NOT NULL DEFAULT '0',
  `pagenum` bigint(10) NOT NULL DEFAULT '0',
  `subchapter` bigint(10) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `contentformat` smallint(4) NOT NULL DEFAULT '0',
  `hidden` tinyint(2) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `importsrc` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines book_chapters';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_cache_filters`
--

CREATE TABLE IF NOT EXISTS `mdl_cache_filters` (
  `id` bigint(10) NOT NULL,
  `filter` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `version` bigint(10) NOT NULL DEFAULT '0',
  `md5key` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rawtext` longtext COLLATE utf8_unicode_ci NOT NULL,
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='For keeping information about cached data';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_cache_flags`
--

CREATE TABLE IF NOT EXISTS `mdl_cache_flags` (
  `id` bigint(10) NOT NULL,
  `flagtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `expiry` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=58607 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Cache of time-sensitive flags';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_capabilities`
--

CREATE TABLE IF NOT EXISTS `mdl_capabilities` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `captype` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contextlevel` bigint(10) NOT NULL DEFAULT '0',
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `riskbitmask` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=627 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='this defines all capabilities';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_certificate`
--

CREATE TABLE IF NOT EXISTS `mdl_certificate` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `emailteachers` tinyint(1) NOT NULL DEFAULT '0',
  `emailothers` longtext COLLATE utf8_unicode_ci,
  `savecert` tinyint(1) NOT NULL DEFAULT '0',
  `reportcert` tinyint(1) NOT NULL DEFAULT '0',
  `delivery` smallint(3) NOT NULL DEFAULT '0',
  `requiredtime` bigint(10) NOT NULL DEFAULT '0',
  `certificatetype` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `orientation` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `borderstyle` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `bordercolor` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `printwmark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `printdate` bigint(10) NOT NULL DEFAULT '0',
  `datefmt` bigint(10) NOT NULL DEFAULT '0',
  `printnumber` tinyint(1) NOT NULL DEFAULT '0',
  `printgrade` bigint(10) NOT NULL DEFAULT '0',
  `gradefmt` bigint(10) NOT NULL DEFAULT '0',
  `printoutcome` bigint(10) NOT NULL DEFAULT '0',
  `printhours` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `printteacher` bigint(10) NOT NULL DEFAULT '0',
  `customtext` longtext COLLATE utf8_unicode_ci,
  `printsignature` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `printseal` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines certificates';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_certificate_issues`
--

CREATE TABLE IF NOT EXISTS `mdl_certificate_issues` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `certificateid` bigint(10) NOT NULL DEFAULT '0',
  `code` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1076 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about issued certificates';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_chat`
--

CREATE TABLE IF NOT EXISTS `mdl_chat` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `keepdays` bigint(11) NOT NULL DEFAULT '0',
  `studentlogs` smallint(4) NOT NULL DEFAULT '0',
  `chattime` bigint(10) NOT NULL DEFAULT '0',
  `schedule` smallint(4) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each of these is a chat room';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_chat_messages`
--

CREATE TABLE IF NOT EXISTS `mdl_chat_messages` (
  `id` bigint(10) NOT NULL,
  `chatid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores all the actual chat messages';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_chat_messages_current`
--

CREATE TABLE IF NOT EXISTS `mdl_chat_messages_current` (
  `id` bigint(10) NOT NULL,
  `chatid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores current session';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_chat_users`
--

CREATE TABLE IF NOT EXISTS `mdl_chat_users` (
  `id` bigint(10) NOT NULL,
  `chatid` bigint(11) NOT NULL DEFAULT '0',
  `userid` bigint(11) NOT NULL DEFAULT '0',
  `groupid` bigint(11) NOT NULL DEFAULT '0',
  `version` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `firstping` bigint(10) NOT NULL DEFAULT '0',
  `lastping` bigint(10) NOT NULL DEFAULT '0',
  `lastmessageping` bigint(10) NOT NULL DEFAULT '0',
  `sid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `course` bigint(10) NOT NULL DEFAULT '0',
  `lang` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Keeps track of which users are in which chat rooms';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_choice`
--

CREATE TABLE IF NOT EXISTS `mdl_choice` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `publish` tinyint(2) NOT NULL DEFAULT '0',
  `showresults` tinyint(2) NOT NULL DEFAULT '0',
  `display` smallint(4) NOT NULL DEFAULT '0',
  `allowupdate` tinyint(2) NOT NULL DEFAULT '0',
  `showunanswered` tinyint(2) NOT NULL DEFAULT '0',
  `limitanswers` tinyint(2) NOT NULL DEFAULT '0',
  `timeopen` bigint(10) NOT NULL DEFAULT '0',
  `timeclose` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `completionsubmit` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Available choices are stored here';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_choice_answers`
--

CREATE TABLE IF NOT EXISTS `mdl_choice_answers` (
  `id` bigint(10) NOT NULL,
  `choiceid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `optionid` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='choices performed by users';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_choice_options`
--

CREATE TABLE IF NOT EXISTS `mdl_choice_options` (
  `id` bigint(10) NOT NULL,
  `choiceid` bigint(10) NOT NULL DEFAULT '0',
  `text` longtext COLLATE utf8_unicode_ci,
  `maxanswers` bigint(10) DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='available options to choice';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_cohort`
--

CREATE TABLE IF NOT EXISTS `mdl_cohort` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `name` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `idnumber` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL,
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each record represents one cohort (aka site-wide group).';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_cohort_members`
--

CREATE TABLE IF NOT EXISTS `mdl_cohort_members` (
  `id` bigint(10) NOT NULL,
  `cohortid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timeadded` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=20249 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Link a user to a cohort.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_comments`
--

CREATE TABLE IF NOT EXISTS `mdl_comments` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `commentarea` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemid` bigint(10) NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `format` tinyint(2) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='moodle comments module';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_config`
--

CREATE TABLE IF NOT EXISTS `mdl_config` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2137 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Moodle configuration variables';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_config_log`
--

CREATE TABLE IF NOT EXISTS `mdl_config_log` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `plugin` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci,
  `oldvalue` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=1718 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Changes done in server configuration through admin UI';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_config_plugins`
--

CREATE TABLE IF NOT EXISTS `mdl_config_plugins` (
  `id` bigint(10) NOT NULL,
  `plugin` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'core',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1503 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Moodle modules and plugins configuration variables';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_context`
--

CREATE TABLE IF NOT EXISTS `mdl_context` (
  `id` bigint(10) NOT NULL,
  `contextlevel` bigint(10) NOT NULL DEFAULT '0',
  `instanceid` bigint(10) NOT NULL DEFAULT '0',
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `depth` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=15198 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='one of these must be set';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_context_temp`
--

CREATE TABLE IF NOT EXISTS `mdl_context_temp` (
  `id` bigint(10) NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `depth` tinyint(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Used by build_context_path() in upgrade and cron to keep con';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_controlli_log`
--

CREATE TABLE IF NOT EXISTS `mdl_controlli_log` (
  `id` bigint(10) NOT NULL,
  `file` varchar(50) DEFAULT NULL,
  `funzione` varchar(100) DEFAULT NULL,
  `msg` varchar(500) DEFAULT NULL,
  `data` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5302 DEFAULT CHARSET=utf8 COMMENT='Tabella di log per i controlli effettuati su forma20';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course`
--

CREATE TABLE IF NOT EXISTS `mdl_course` (
  `id` bigint(10) NOT NULL,
  `category` bigint(10) NOT NULL DEFAULT '0',
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `fullname` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `idnumber` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `summary` longtext COLLATE utf8_unicode_ci,
  `summaryformat` tinyint(2) NOT NULL DEFAULT '0',
  `format` varchar(21) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'topics',
  `showgrades` tinyint(2) NOT NULL DEFAULT '1',
  `modinfo` longtext COLLATE utf8_unicode_ci,
  `newsitems` mediumint(5) NOT NULL DEFAULT '1',
  `startdate` bigint(10) NOT NULL DEFAULT '0',
  `numsections` mediumint(5) unsigned NOT NULL DEFAULT '1',
  `marker` bigint(10) NOT NULL DEFAULT '0',
  `maxbytes` bigint(10) NOT NULL DEFAULT '0',
  `legacyfiles` smallint(4) NOT NULL DEFAULT '0',
  `showreports` smallint(4) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `visibleold` tinyint(1) NOT NULL DEFAULT '1',
  `hiddensections` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `groupmode` smallint(4) NOT NULL DEFAULT '0',
  `groupmodeforce` smallint(4) NOT NULL DEFAULT '0',
  `defaultgroupingid` bigint(10) NOT NULL DEFAULT '0',
  `lang` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `theme` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `requested` tinyint(1) NOT NULL DEFAULT '0',
  `enablecompletion` tinyint(1) NOT NULL DEFAULT '0',
  `completionnotify` tinyint(1) NOT NULL DEFAULT '0',
  `cacherev` bigint(10) NOT NULL DEFAULT '0',
  `calendartype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=194882 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Central course table';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_categories`
--

CREATE TABLE IF NOT EXISTS `mdl_course_categories` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `idnumber` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0',
  `parent` bigint(10) NOT NULL DEFAULT '0',
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `coursecount` bigint(10) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `visibleold` tinyint(1) NOT NULL DEFAULT '1',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `depth` bigint(10) NOT NULL DEFAULT '0',
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `theme` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Course categories';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_completions`
--

CREATE TABLE IF NOT EXISTS `mdl_course_completions` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `course` bigint(10) NOT NULL DEFAULT '0',
  `timeenrolled` bigint(10) NOT NULL DEFAULT '0',
  `timestarted` bigint(10) NOT NULL DEFAULT '0',
  `timecompleted` bigint(10) DEFAULT NULL,
  `reaggregate` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=103417 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Course completion records';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_completion_aggr_methd`
--

CREATE TABLE IF NOT EXISTS `mdl_course_completion_aggr_methd` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `criteriatype` bigint(20) DEFAULT NULL,
  `method` tinyint(1) NOT NULL DEFAULT '0',
  `value` decimal(10,5) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=405 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Course completion aggregation methods for criteria';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_completion_criteria`
--

CREATE TABLE IF NOT EXISTS `mdl_course_completion_criteria` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `criteriatype` bigint(20) NOT NULL DEFAULT '0',
  `module` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `moduleinstance` bigint(10) DEFAULT NULL,
  `courseinstance` bigint(10) DEFAULT NULL,
  `enrolperiod` bigint(10) DEFAULT NULL,
  `timeend` bigint(10) DEFAULT NULL,
  `gradepass` decimal(10,5) DEFAULT NULL,
  `role` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=325 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Course completion criteria';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_completion_crit_compl`
--

CREATE TABLE IF NOT EXISTS `mdl_course_completion_crit_compl` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `course` bigint(10) NOT NULL DEFAULT '0',
  `criteriaid` bigint(10) NOT NULL DEFAULT '0',
  `gradefinal` decimal(10,5) DEFAULT NULL,
  `unenroled` bigint(10) DEFAULT NULL,
  `timecompleted` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=87782 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Course completion user records';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_format_options`
--

CREATE TABLE IF NOT EXISTS `mdl_course_format_options` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `format` varchar(21) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sectionid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=4221 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_modules`
--

CREATE TABLE IF NOT EXISTS `mdl_course_modules` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `module` bigint(10) NOT NULL DEFAULT '0',
  `instance` bigint(10) NOT NULL DEFAULT '0',
  `section` bigint(10) NOT NULL DEFAULT '0',
  `idnumber` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `added` bigint(10) NOT NULL DEFAULT '0',
  `score` smallint(4) NOT NULL DEFAULT '0',
  `indent` mediumint(5) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `visibleold` tinyint(1) NOT NULL DEFAULT '1',
  `groupmode` smallint(4) NOT NULL DEFAULT '0',
  `groupingid` bigint(10) NOT NULL DEFAULT '0',
  `groupmembersonly` smallint(4) NOT NULL DEFAULT '0',
  `completion` tinyint(1) NOT NULL DEFAULT '0',
  `completiongradeitemnumber` bigint(10) DEFAULT NULL,
  `completionview` tinyint(1) NOT NULL DEFAULT '0',
  `completionexpected` bigint(10) NOT NULL DEFAULT '0',
  `showdescription` tinyint(1) NOT NULL DEFAULT '0',
  `availability` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=5047 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='course_modules table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_modules_completion`
--

CREATE TABLE IF NOT EXISTS `mdl_course_modules_completion` (
  `id` bigint(10) NOT NULL,
  `coursemoduleid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `completionstate` tinyint(1) NOT NULL,
  `viewed` tinyint(1) DEFAULT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=316514 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the completion state (completed or not completed, etc';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_published`
--

CREATE TABLE IF NOT EXISTS `mdl_course_published` (
  `id` bigint(10) NOT NULL,
  `huburl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `courseid` bigint(10) NOT NULL,
  `timepublished` bigint(10) NOT NULL,
  `enrollable` tinyint(1) NOT NULL DEFAULT '1',
  `hubcourseid` bigint(10) NOT NULL,
  `status` tinyint(1) DEFAULT '0',
  `timechecked` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Information about how and when an local courses were publish';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_request`
--

CREATE TABLE IF NOT EXISTS `mdl_course_request` (
  `id` bigint(10) NOT NULL,
  `fullname` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `summary` longtext COLLATE utf8_unicode_ci NOT NULL,
  `summaryformat` tinyint(2) NOT NULL DEFAULT '0',
  `category` bigint(10) NOT NULL DEFAULT '0',
  `reason` longtext COLLATE utf8_unicode_ci NOT NULL,
  `requester` bigint(10) NOT NULL DEFAULT '0',
  `password` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='course requests';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_course_sections`
--

CREATE TABLE IF NOT EXISTS `mdl_course_sections` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `section` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `summary` longtext COLLATE utf8_unicode_ci,
  `summaryformat` tinyint(2) NOT NULL DEFAULT '0',
  `sequence` longtext COLLATE utf8_unicode_ci,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `availability` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=4273 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='to define the sections for each course';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmark`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmark` (
  `id` bigint(10) NOT NULL,
  `platform` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `durata` decimal(6,3) DEFAULT NULL,
  `startraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stopraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaddr` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onlines` int(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarkdettagliprove`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarkdettagliprove` (
  `id` bigint(20) NOT NULL,
  `idsessione` int(3) NOT NULL,
  `idprova` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggiocalc` int(11) NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1calc` int(11) NOT NULL,
  `limit2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit2calc` int(11) NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=107913 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarkprove`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarkprove` (
  `id` bigint(20) NOT NULL,
  `idsessione` int(3) NOT NULL,
  `idprova` bigint(10) NOT NULL,
  `platform` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `durata` decimal(6,3) DEFAULT NULL,
  `duratacalc` int(11) DEFAULT NULL,
  `startraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stopraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaddr` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onlines` int(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7709 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarkraw_v0`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarkraw_v0` (
  `id` bigint(10) NOT NULL,
  `platform` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `durata` int(11) DEFAULT NULL,
  `startraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stopraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaddr` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onlines` int(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarkraw_v1`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarkraw_v1` (
  `id` bigint(10) NOT NULL,
  `platform` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `durata` int(11) DEFAULT NULL,
  `startraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stopraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaddr` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onlines` int(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarksessioni`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarksessioni` (
  `id` bigint(10) NOT NULL,
  `platform` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `vm` int(1) DEFAULT NULL,
  `vcpu` int(11) DEFAULT NULL,
  `vram` int(11) DEFAULT NULL,
  `so` text COLLATE utf8mb4_unicode_ci,
  `ws` text COLLATE utf8mb4_unicode_ci,
  `langdev` text COLLATE utf8mb4_unicode_ci,
  `moodle` text COLLATE utf8mb4_unicode_ci,
  `build` text COLLATE utf8mb4_unicode_ci,
  `titolo` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarktest`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarktest` (
  `id` bigint(10) NOT NULL,
  `numtest` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=16017 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarktestraw_v0`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarktestraw_v0` (
  `id` bigint(10) NOT NULL,
  `numtest` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` int(11) NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` int(11) NOT NULL,
  `limit2` int(11) NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=45977 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarktestraw_v1`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarktestraw_v1` (
  `id` bigint(10) NOT NULL,
  `numtest` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` int(11) NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` int(11) NOT NULL,
  `limit2` int(11) NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=45921 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarktest_bis`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarktest_bis` (
  `idsessione` int(3) DEFAULT NULL,
  `numtest` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarktest_v0`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarktest_v0` (
  `id` bigint(10) NOT NULL,
  `numtest` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=45977 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarktest_v1`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarktest_v1` (
  `id` bigint(10) NOT NULL,
  `idsessione` int(3) DEFAULT NULL,
  `numtest` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=45921 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmarktest_v1_bis`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmarktest_v1_bis` (
  `idsessione` int(3) DEFAULT NULL,
  `numtest` bigint(10) NOT NULL,
  `nametest` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stop` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `esito` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmark_v0`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmark_v0` (
  `id` bigint(10) NOT NULL,
  `platform` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `durata` decimal(6,3) DEFAULT NULL,
  `startraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stopraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaddr` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onlines` int(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_csibenchmark_v1`
--

CREATE TABLE IF NOT EXISTS `mdl_csibenchmark_v1` (
  `id` bigint(10) NOT NULL,
  `platform` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `durata` decimal(6,3) DEFAULT NULL,
  `startraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stopraw` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ipaddr` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onlines` int(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_data`
--

CREATE TABLE IF NOT EXISTS `mdl_data` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `comments` smallint(4) NOT NULL DEFAULT '0',
  `timeavailablefrom` bigint(10) NOT NULL DEFAULT '0',
  `timeavailableto` bigint(10) NOT NULL DEFAULT '0',
  `timeviewfrom` bigint(10) NOT NULL DEFAULT '0',
  `timeviewto` bigint(10) NOT NULL DEFAULT '0',
  `requiredentries` int(8) NOT NULL DEFAULT '0',
  `requiredentriestoview` int(8) NOT NULL DEFAULT '0',
  `maxentries` int(8) NOT NULL DEFAULT '0',
  `rssarticles` smallint(4) NOT NULL DEFAULT '0',
  `singletemplate` longtext COLLATE utf8_unicode_ci,
  `listtemplate` longtext COLLATE utf8_unicode_ci,
  `listtemplateheader` longtext COLLATE utf8_unicode_ci,
  `listtemplatefooter` longtext COLLATE utf8_unicode_ci,
  `addtemplate` longtext COLLATE utf8_unicode_ci,
  `rsstemplate` longtext COLLATE utf8_unicode_ci,
  `rsstitletemplate` longtext COLLATE utf8_unicode_ci,
  `csstemplate` longtext COLLATE utf8_unicode_ci,
  `jstemplate` longtext COLLATE utf8_unicode_ci,
  `asearchtemplate` longtext COLLATE utf8_unicode_ci,
  `approval` smallint(4) NOT NULL DEFAULT '0',
  `scale` bigint(10) NOT NULL DEFAULT '0',
  `assessed` bigint(10) NOT NULL DEFAULT '0',
  `assesstimestart` bigint(10) NOT NULL DEFAULT '0',
  `assesstimefinish` bigint(10) NOT NULL DEFAULT '0',
  `defaultsort` bigint(10) NOT NULL DEFAULT '0',
  `defaultsortdir` smallint(4) NOT NULL DEFAULT '0',
  `editany` smallint(4) NOT NULL DEFAULT '0',
  `notification` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='all database activities';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_data_content`
--

CREATE TABLE IF NOT EXISTS `mdl_data_content` (
  `id` bigint(10) NOT NULL,
  `fieldid` bigint(10) NOT NULL DEFAULT '0',
  `recordid` bigint(10) NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8_unicode_ci,
  `content1` longtext COLLATE utf8_unicode_ci,
  `content2` longtext COLLATE utf8_unicode_ci,
  `content3` longtext COLLATE utf8_unicode_ci,
  `content4` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='the content introduced in each record/fields';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_data_fields`
--

CREATE TABLE IF NOT EXISTS `mdl_data_fields` (
  `id` bigint(10) NOT NULL,
  `dataid` bigint(10) NOT NULL DEFAULT '0',
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `param1` longtext COLLATE utf8_unicode_ci,
  `param2` longtext COLLATE utf8_unicode_ci,
  `param3` longtext COLLATE utf8_unicode_ci,
  `param4` longtext COLLATE utf8_unicode_ci,
  `param5` longtext COLLATE utf8_unicode_ci,
  `param6` longtext COLLATE utf8_unicode_ci,
  `param7` longtext COLLATE utf8_unicode_ci,
  `param8` longtext COLLATE utf8_unicode_ci,
  `param9` longtext COLLATE utf8_unicode_ci,
  `param10` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='every field available';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_data_records`
--

CREATE TABLE IF NOT EXISTS `mdl_data_records` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `dataid` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `approved` smallint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='every record introduced';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_eml_php_log_query`
--

CREATE TABLE IF NOT EXISTS `mdl_eml_php_log_query` (
  `id` bigint(10) NOT NULL,
  `id_elab` bigint(10) DEFAULT NULL,
  `funzione` varchar(100) DEFAULT NULL,
  `query` varchar(250) DEFAULT NULL,
  `data` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tabella di log per query procedure batch Forma20';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_enrol`
--

CREATE TABLE IF NOT EXISTS `mdl_enrol` (
  `id` bigint(10) NOT NULL,
  `enrol` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `status` bigint(10) NOT NULL DEFAULT '0',
  `courseid` bigint(10) NOT NULL,
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enrolperiod` bigint(10) DEFAULT '0',
  `enrolstartdate` bigint(10) DEFAULT '0',
  `enrolenddate` bigint(10) DEFAULT '0',
  `expirynotify` tinyint(1) DEFAULT '0',
  `expirythreshold` bigint(10) DEFAULT '0',
  `notifyall` tinyint(1) DEFAULT '0',
  `password` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cost` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `roleid` bigint(10) DEFAULT '0',
  `customint1` bigint(10) DEFAULT NULL,
  `customint2` bigint(10) DEFAULT NULL,
  `customint3` bigint(10) DEFAULT NULL,
  `customint4` bigint(10) DEFAULT NULL,
  `customint5` bigint(10) DEFAULT NULL,
  `customint6` bigint(10) DEFAULT NULL,
  `customint7` bigint(10) DEFAULT NULL,
  `customint8` bigint(10) DEFAULT NULL,
  `customchar1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `customchar2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `customchar3` varchar(1333) COLLATE utf8_unicode_ci DEFAULT NULL,
  `customdec1` decimal(12,7) DEFAULT NULL,
  `customdec2` decimal(12,7) DEFAULT NULL,
  `customtext1` longtext COLLATE utf8_unicode_ci,
  `customtext2` longtext COLLATE utf8_unicode_ci,
  `customtext3` longtext COLLATE utf8_unicode_ci,
  `customtext4` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2228 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Instances of enrolment plugins used in courses, fields marke';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_enrol_flatfile`
--

CREATE TABLE IF NOT EXISTS `mdl_enrol_flatfile` (
  `id` bigint(10) NOT NULL,
  `action` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `roleid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `timestart` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='enrol_flatfile table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_enrol_paypal`
--

CREATE TABLE IF NOT EXISTS `mdl_enrol_paypal` (
  `id` bigint(10) NOT NULL,
  `business` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `receiver_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `receiver_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `item_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `instanceid` bigint(10) NOT NULL DEFAULT '0',
  `memo` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tax` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `option_name1` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `option_selection1_x` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `option_name2` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `option_selection2_x` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `payment_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pending_reason` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `reason_code` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `txn_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `parent_txn_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `payment_type` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timeupdated` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='Holds all known information about PayPal transactions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_event`
--

CREATE TABLE IF NOT EXISTS `mdl_event` (
  `id` bigint(10) NOT NULL,
  `name` longtext COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `format` smallint(4) NOT NULL DEFAULT '0',
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `repeatid` bigint(10) NOT NULL DEFAULT '0',
  `modulename` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `instance` bigint(10) NOT NULL DEFAULT '0',
  `eventtype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timestart` bigint(10) NOT NULL DEFAULT '0',
  `timeduration` bigint(10) NOT NULL DEFAULT '0',
  `visible` smallint(4) NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sequence` bigint(10) NOT NULL DEFAULT '1',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `subscriptionid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=310764 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='For everything with a time associated to it';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_events_handlers`
--

CREATE TABLE IF NOT EXISTS `mdl_events_handlers` (
  `id` bigint(10) NOT NULL,
  `eventname` varchar(166) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `component` varchar(166) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `handlerfile` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `handlerfunction` longtext COLLATE utf8_unicode_ci,
  `schedule` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` bigint(10) NOT NULL DEFAULT '0',
  `internal` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table is for storing which components requests what typ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_events_queue`
--

CREATE TABLE IF NOT EXISTS `mdl_events_queue` (
  `id` bigint(10) NOT NULL,
  `eventdata` longtext COLLATE utf8_unicode_ci NOT NULL,
  `stackdump` longtext COLLATE utf8_unicode_ci,
  `userid` bigint(10) DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table is for storing queued events. It stores only one ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_events_queue_handlers`
--

CREATE TABLE IF NOT EXISTS `mdl_events_queue_handlers` (
  `id` bigint(10) NOT NULL,
  `queuedeventid` bigint(10) NOT NULL,
  `handlerid` bigint(10) NOT NULL,
  `status` bigint(10) DEFAULT NULL,
  `errormessage` longtext COLLATE utf8_unicode_ci,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This is the list of queued handlers for processing. The even';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_event_subscriptions`
--

CREATE TABLE IF NOT EXISTS `mdl_event_subscriptions` (
  `id` bigint(10) NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `eventtype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pollinterval` bigint(10) NOT NULL DEFAULT '0',
  `lastupdated` bigint(10) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_external_functions`
--

CREATE TABLE IF NOT EXISTS `mdl_external_functions` (
  `id` bigint(10) NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `classname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `methodname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `classpath` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `capabilities` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='list of all external functions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_external_services`
--

CREATE TABLE IF NOT EXISTS `mdl_external_services` (
  `id` bigint(10) NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL,
  `requiredcapability` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `restrictedusers` tinyint(1) NOT NULL,
  `component` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloadfiles` tinyint(1) NOT NULL DEFAULT '0',
  `uploadfiles` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='built in and custom external services';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_external_services_functions`
--

CREATE TABLE IF NOT EXISTS `mdl_external_services_functions` (
  `id` bigint(10) NOT NULL,
  `externalserviceid` bigint(10) NOT NULL,
  `functionname` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='lists functions available in each service group';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_external_services_users`
--

CREATE TABLE IF NOT EXISTS `mdl_external_services_users` (
  `id` bigint(10) NOT NULL,
  `externalserviceid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `iprestriction` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `validuntil` bigint(10) DEFAULT NULL,
  `timecreated` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='users allowed to use services with restricted users flag';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_external_tokens`
--

CREATE TABLE IF NOT EXISTS `mdl_external_tokens` (
  `id` bigint(10) NOT NULL,
  `token` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tokentype` smallint(4) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `externalserviceid` bigint(10) NOT NULL,
  `sid` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contextid` bigint(10) NOT NULL,
  `creatorid` bigint(20) NOT NULL DEFAULT '1',
  `iprestriction` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `validuntil` bigint(10) DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL,
  `lastaccess` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Security tokens for accessing of external services';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_af`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_af` (
  `id` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_af table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_anagrafica_corsi`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_anagrafica_corsi` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `cf` float(7,2) NOT NULL,
  `course_type` smallint(3) NOT NULL,
  `tipo_budget` smallint(3) DEFAULT '0',
  `af` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `subaf` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `to_x` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `flag_dir_scuola` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'S',
  `id_dir_scuola` bigint(10) DEFAULT NULL,
  `te` smallint(3) NOT NULL,
  `sf` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `orario` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `viaente` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `localita` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `anno` bigint(11) DEFAULT '0',
  `note` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `determina` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `costo` float(9,2) DEFAULT '0.00',
  `durata` float(5,2) DEFAULT '0.00',
  `num_min_all` smallint(3) DEFAULT '0',
  `num_norm_all` smallint(3) DEFAULT '0',
  `num_max_all` smallint(3) DEFAULT '0',
  `dir_proponente` smallint(3) DEFAULT '0',
  `timemodified` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usermodified` smallint(3) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=702 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_b`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_b` (
  `id` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_b table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsiind`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsiind` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `codice_fiscale` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `partita_iva` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orgfk` bigint(10) DEFAULT NULL,
  `storico` bigint(10) DEFAULT '0',
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiario_pagamento` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cassa_economale` varchar(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `stato_determina` bigint(10) DEFAULT '0',
  `titolo` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `costo` float NOT NULL,
  `area_formativa` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `tipologia_organizzativa` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `tipo` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `durata` float(11,2) DEFAULT NULL,
  `ente` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `via` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `training` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_archiviazione` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
  `localita` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sotto_area_formativa` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio` bigint(20) NOT NULL,
  `credito_formativo` float(11,2) DEFAULT NULL,
  `modello_email` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_regionale` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `segmento_formativo` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tipo_pianificazione` varchar(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `blocked` bigint(11) DEFAULT '0',
  `id_determine` bigint(20) DEFAULT '0',
  `codice_creditore` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_invio_mail` bigint(20) DEFAULT NULL,
  `offerta_speciale` varchar(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `note_offerta` varchar(255) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2728 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsiind_anno_finanziario`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsiind_anno_finanziario` (
  `id` bigint(10) NOT NULL,
  `id_corsiind` bigint(10) NOT NULL,
  `data` datetime NOT NULL,
  `anno` int(4) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=834 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='tabella di raccordo fra anno finanziario e corsi individuali con determina';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsiind_log`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsiind_log` (
  `id` bigint(10) NOT NULL,
  `msg` varchar(250) DEFAULT NULL,
  `data` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3915 DEFAULT CHARSET=utf8 COMMENT='tabella di log Corsi individuali';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsiind_prot`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsiind_prot` (
  `id` bigint(10) NOT NULL,
  `id_corsiind` bigint(10) NOT NULL,
  `data` datetime NOT NULL,
  `prot` char(50) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='tabella di raccordo fra protocollo e corsi individuali senza determina';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsiind_senza_spesa`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsiind_senza_spesa` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `codice_fiscale` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `partita_iva` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orgfk` bigint(10) DEFAULT NULL,
  `storico` bigint(10) DEFAULT '0',
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiario_pagamento` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cassa_economale` varchar(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `stato_determina` bigint(10) DEFAULT '0',
  `titolo` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `costo` float NOT NULL,
  `area_formativa` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `tipologia_organizzativa` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `tipo` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `durata` float(11,2) DEFAULT NULL,
  `ente` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `via` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `training` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_archiviazione` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
  `localita` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sotto_area_formativa` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio` bigint(20) NOT NULL,
  `credito_formativo` float(11,2) DEFAULT NULL,
  `modello_email` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_regionale` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `segmento_formativo` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tipo_pianificazione` varchar(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `blocked` bigint(11) DEFAULT '0',
  `id_determine` bigint(20) DEFAULT '0',
  `codice_creditore` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_invio_mail` bigint(20) DEFAULT NULL,
  `prot` char(50) COLLATE utf8_unicode_ci DEFAULT '-',
  `data_prot` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1397 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsiind_senza_spesa_query_log`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsiind_senza_spesa_query_log` (
  `id` bigint(10) NOT NULL,
  `data` datetime NOT NULL,
  `msg` longtext COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='tabella di log per le query dei corsi individuali senza determina';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsi_coorti_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsi_coorti_map` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `coorteid` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3493 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_corsi_sedi_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_corsi_sedi_map` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `sedeid` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=1983 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_course_org_mapping`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_course_org_mapping` (
  `id` int(20) NOT NULL,
  `courseid` int(20) NOT NULL,
  `orgid` int(20) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1022 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_csi_pent_gruppi_report`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_csi_pent_gruppi_report` (
  `id` bigint(10) NOT NULL,
  `codice` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `descrizione` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella di definizione dei gruppi report di Forma20';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_csi_pent_menu_report`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_csi_pent_menu_report` (
  `id` bigint(10) NOT NULL,
  `codice` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `descrizione` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `attiva` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella di definizione delle voci nel menu Report di Forma20';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_csi_pent_param`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_csi_pent_param` (
  `id` bigint(10) NOT NULL,
  `nome` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella dei parametri usati dai report di pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_csi_pent_param_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_csi_pent_param_map` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_param` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella che lega i report ai suoi parametri';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_csi_pent_report`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_csi_pent_report` (
  `id` bigint(10) NOT NULL,
  `id_menu_report` bigint(10) NOT NULL,
  `nome_report` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nome_file_pentaho` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `formato_default` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `posizione_in_elenco_report` tinyint(1) NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1',
  `numero_esecuzioni` int(11) NOT NULL DEFAULT '0',
  `data_ultima_esecuzione` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella di definizione dei report pentaho attivabili da Forma20';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_csi_pent_role_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_csi_pent_role_map` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_role` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella che collega i report di pentaho e i ruoli di moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_determine`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_determine` (
  `id` bigint(20) NOT NULL,
  `codice_determina` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_provvisorio_determina` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `note` longtext COLLATE utf8_unicode_ci NOT NULL,
  `data_determina` bigint(20) DEFAULT NULL,
  `numero_protocollo` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_protocollo` bigint(20) DEFAULT NULL,
  `anno_esercizio_finanziario` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=549 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_edizioni_postiris_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_edizioni_postiris_map` (
  `id` bigint(10) NOT NULL,
  `sessionid` bigint(10) NOT NULL,
  `direzioneid` bigint(10) NOT NULL,
  `npostiassegnati` smallint(3) NOT NULL DEFAULT '0',
  `nposticonsumati` smallint(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3664 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_edizioni_postiris_map table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_edz_pianificate_corsi_prg`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_edz_pianificate_corsi_prg` (
  `id` bigint(10) NOT NULL,
  `anno_pianificazione` smallint(4) NOT NULL,
  `codice_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sede` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `edizione` smallint(3) NOT NULL,
  `anno_svolgimento` smallint(4) NOT NULL,
  `sessione_svolgimento` smallint(3) NOT NULL,
  `edizione_svolgimento` smallint(3) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5720 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_edz_pianificate_corsi_prg table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_fi_partialbudget`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_fi_partialbudget` (
  `id` bigint(11) NOT NULL,
  `anno` smallint(4) NOT NULL,
  `orgfk` bigint(11) NOT NULL,
  `tipo` smallint(4) NOT NULL,
  `money_bdgt` double NOT NULL,
  `lstupd` longtext COLLATE utf8_unicode_ci,
  `usrname` varchar(90) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_forma2riforma_log`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_forma2riforma_log` (
  `id` int(11) NOT NULL,
  `shortname` varchar(100) CHARACTER SET utf8 NOT NULL,
  `data_ora` bigint(20) NOT NULL,
  `codice` int(11) NOT NULL,
  `descrizione` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_forma2riforma_mapping`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_forma2riforma_mapping` (
  `id` int(11) NOT NULL,
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `id_riforma` bigint(20) NOT NULL,
  `id_forma20` bigint(20) NOT NULL,
  `perc_x_cfv` float NOT NULL,
  `va_default` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio` bigint(20) NOT NULL,
  `stato` int(11) NOT NULL,
  `nota` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_forma2riforma_partecipazioni`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_forma2riforma_partecipazioni` (
  `id` int(11) NOT NULL,
  `id_mapping` int(11) NOT NULL,
  `matricola` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `id_user_Riforma` bigint(20) NOT NULL,
  `cognome_Riforma` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `nome_Riforma` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `id_scorm_Riforma` bigint(20) NOT NULL,
  `punteggio_Riforma` float NOT NULL,
  `id_user_Forma` bigint(20) DEFAULT NULL,
  `cognome_Forma` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nome_Forma` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_fiscale_Forma` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sesso_Forma` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_Forma` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `categoria_Forma` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ap_Forma` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cod_settore_Forma` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `settore_Forma` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cod_direzione_Forma` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `direzione_Forma` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stato` int(11) NOT NULL,
  `nota` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_formatore`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_formatore` (
  `id` bigint(10) NOT NULL,
  `piva` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tstudio` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `dettstudio` varchar(765) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prof` varchar(765) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ente` varchar(765) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tipodoc` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `categoria` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lstupd` bigint(10) NOT NULL,
  `usrid` bigint(10) NOT NULL,
  `cf` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=823 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_formatore table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_formsubaf_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_formsubaf_map` (
  `id` bigint(20) NOT NULL,
  `formid` bigint(10) NOT NULL,
  `subafid` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lstupd` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=605 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_formsubaf_map table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_fornitori`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_fornitori` (
  `id` bigint(10) NOT NULL,
  `id_org` bigint(11) NOT NULL DEFAULT '-1',
  `denominazione` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cognome` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` longtext COLLATE utf8_unicode_ci,
  `partita_iva` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_fiscale` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `codice_creditore` varchar(300) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tipo_formazione` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stato` tinyint(1) DEFAULT NULL,
  `nota` longtext COLLATE utf8_unicode_ci,
  `indirizzo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cap` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `citta` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `provincia` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `paese` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `preferiti` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='f2_fornitori table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_forzature`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_forzature` (
  `id` bigint(10) NOT NULL,
  `codice_fiscale` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `cohort_fk` bigint(10) NOT NULL,
  `orgfk_direzione` bigint(10) NOT NULL,
  `matricola` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `cognome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `sesso` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `qualifica` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `ap` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `e_mail` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `cod_direzione` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `direzione` varchar(205) COLLATE utf8_unicode_ci NOT NULL,
  `cod_settore` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `settore` varchar(205) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_fine` bigint(20) NOT NULL,
  `nota` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_gest_codpart`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_gest_codpart` (
  `id` bigint(11) NOT NULL,
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_gest_codpart table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_notif_corso`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_notif_corso` (
  `id` bigint(11) NOT NULL,
  `id_corso` bigint(11) NOT NULL,
  `id_edizione` bigint(11) DEFAULT NULL,
  `id_notif_templates` bigint(11) NOT NULL,
  `id_tipo_notif` mediumint(6) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=346 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_notif_corso table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_notif_templates`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_notif_templates` (
  `id` bigint(10) NOT NULL,
  `title` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `subject` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` longtext COLLATE utf8_unicode_ci,
  `id_tipo_notif` bigint(11) NOT NULL,
  `stato` smallint(4) NOT NULL,
  `lstupd` longtext COLLATE utf8_unicode_ci,
  `usrname` varchar(90) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `canale` tinyint(2) NOT NULL DEFAULT '0',
  `predefinito` tinyint(2) DEFAULT '0',
  `attachment` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_notif_templates table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_notif_template_log`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_notif_template_log` (
  `id` bigint(10) NOT NULL,
  `sessionid` bigint(11) NOT NULL DEFAULT '0',
  `useridfrom` bigint(11) NOT NULL DEFAULT '0',
  `useridto` bigint(11) NOT NULL DEFAULT '0',
  `mailfrom` longtext COLLATE utf8_unicode_ci,
  `mailto` longtext COLLATE utf8_unicode_ci,
  `mailcc` longtext COLLATE utf8_unicode_ci,
  `mailbcc` longtext COLLATE utf8_unicode_ci,
  `subject` longtext COLLATE utf8_unicode_ci,
  `message` longtext COLLATE utf8_unicode_ci,
  `attachment` longtext COLLATE utf8_unicode_ci NOT NULL,
  `format` smallint(3) NOT NULL DEFAULT '1',
  `time` bigint(10) DEFAULT NULL,
  `mailtemplate` mediumint(5) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3031 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_notif_template_log table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_notif_template_mailqueue`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_notif_template_mailqueue` (
  `id` bigint(10) NOT NULL,
  `sessionid` bigint(11) NOT NULL DEFAULT '0',
  `useridfrom` bigint(11) NOT NULL DEFAULT '0',
  `useridto` bigint(11) NOT NULL DEFAULT '0',
  `mailfrom` longtext COLLATE utf8_unicode_ci,
  `mailto` longtext COLLATE utf8_unicode_ci,
  `mailcc` longtext COLLATE utf8_unicode_ci,
  `mailbcc` longtext COLLATE utf8_unicode_ci,
  `subject` longtext COLLATE utf8_unicode_ci,
  `message` longtext COLLATE utf8_unicode_ci,
  `attachment` longtext COLLATE utf8_unicode_ci NOT NULL,
  `format` smallint(3) NOT NULL DEFAULT '1',
  `time` bigint(10) DEFAULT NULL,
  `mailtemplate` mediumint(5) NOT NULL DEFAULT '0',
  `skip` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=689 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_notif_template_mailqueue table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_notif_tipo`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_notif_tipo` (
  `id` mediumint(6) NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `segnaposto` varchar(800) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_notif_tipo table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_org_budget`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_org_budget` (
  `id` bigint(11) NOT NULL,
  `anno` smallint(4) NOT NULL,
  `orgfk` bigint(11) NOT NULL,
  `tipo` smallint(4) NOT NULL,
  `money_bdgt` double NOT NULL,
  `days_bdgt` double NOT NULL,
  `lstupd` longtext,
  `usrname` varchar(90) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=1343 DEFAULT CHARSET=utf8 COMMENT='f2_org_budget table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_parametri`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_parametri` (
  `id` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `val_int` bigint(20) DEFAULT NULL,
  `val_float` decimal(17,2) DEFAULT NULL,
  `val_char` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `val_date` datetime DEFAULT NULL,
  `obbligatorio` smallint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_parametri table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_partecipazioni`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_partecipazioni` (
  `id` bigint(11) NOT NULL,
  `codpart` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `descrpart` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) DEFAULT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_partecipazioni table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_partialbdgt`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_partialbdgt` (
  `id` bigint(11) NOT NULL,
  `anno` bigint(11) NOT NULL,
  `orgfk` bigint(11) NOT NULL,
  `settori` bigint(11) NOT NULL DEFAULT '0',
  `dirigenti` bigint(11) NOT NULL DEFAULT '0',
  `personale` bigint(11) NOT NULL DEFAULT '0',
  `ap_poa` double NOT NULL DEFAULT '0',
  `totb` double NOT NULL DEFAULT '0',
  `criterioa` double NOT NULL DEFAULT '0',
  `criteriob` double NOT NULL DEFAULT '0',
  `criterioc` double NOT NULL DEFAULT '0',
  `criteriod` double NOT NULL DEFAULT '0',
  `coefficiente` double NOT NULL DEFAULT '0',
  `lstupd` longtext COLLATE utf8_unicode_ci,
  `usrname` varchar(90) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `modificato` tinyint(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_partialbdgt table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_pd`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_pd` (
  `id` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_pd table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_piani_di_studio`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_piani_di_studio` (
  `id` bigint(11) NOT NULL,
  `qualifica` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sf` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `crediti_richiesti` decimal(8,2) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=270 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_piani_di_studio table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `mdl_f2_posiz_econom_qualifica`
--
CREATE TABLE IF NOT EXISTS `mdl_f2_posiz_econom_qualifica` (
`id` bigint(10) unsigned
,`codqual` varchar(2)
,`ap` varchar(2)
,`macrocategory` varchar(4)
,`descrizione` varchar(250)
,`cohortid` bigint(11)
);

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_prenotati`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_prenotati` (
  `id` bigint(20) NOT NULL,
  `anno` smallint(4) NOT NULL,
  `courseid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `orgid` bigint(10) NOT NULL,
  `data_prenotazione` bigint(10) NOT NULL,
  `validato_sett` tinyint(1) NOT NULL DEFAULT '0',
  `cf` decimal(5,2) NOT NULL DEFAULT '0.00',
  `sfid` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `costo` decimal(17,2) NOT NULL DEFAULT '0.00',
  `durata` decimal(5,2) NOT NULL DEFAULT '0.00',
  `lstupd` bigint(10) NOT NULL,
  `usrname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sede` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `validato_dir` tinyint(1) NOT NULL DEFAULT '0',
  `val_sett_by` bigint(20) DEFAULT NULL,
  `val_sett_dt` bigint(10) DEFAULT NULL,
  `val_dir_by` bigint(20) DEFAULT NULL,
  `val_dir_dt` bigint(10) DEFAULT NULL,
  `isdeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=429 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_prenotati table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho` (
  `id` bigint(10) NOT NULL,
  `nome` longtext COLLATE utf8_unicode_ci NOT NULL,
  `full_path` longtext COLLATE utf8_unicode_ci NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1',
  `extra_param` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella di definizione dei report generati tramite pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_formind`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_formind` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `full_path` longtext NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1',
  `extra_param` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='tabella di definizione dei report formazione individuale generati tramite pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param` (
  `id` bigint(10) NOT NULL,
  `nome` longtext COLLATE utf8_unicode_ci NOT NULL,
  `default_value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella dei parametri usati dai report di pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_formind`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_formind` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `default_value` longtext
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella dei parametri usati dai report di pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_map` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_report_param` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT COMMENT='tabella che lega i report ai suoi parametri';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_map_formind`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_map_formind` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_report_param` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella che lega i report formazione individuale ai suoi parametri';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_map_partecipazione`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_map_partecipazione` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_report_param` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella che lega i report formazione individuale ai suoi parametri';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_map_questionari`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_map_questionari` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_report_param` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella che lega i report formazione individuale ai suoi parametri';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_map_stat`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_map_stat` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_report_param` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT COMMENT='tabella che lega i report ai suoi parametri';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_map_visual_on_line`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_map_visual_on_line` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_report_param` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella che lega i report formazione individuale ai suoi parametri';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_partecipazione`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_partecipazione` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `default_value` longtext
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella dei parametri usati dai report di pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_questionari`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_questionari` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `default_value` longtext
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella dei parametri usati dai report di pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_stat`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_stat` (
  `id` bigint(10) NOT NULL,
  `nome` longtext COLLATE utf8_unicode_ci NOT NULL,
  `default_value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT COMMENT='tabella dei parametri usati dai report di pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_param_visual_on_line`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_param_visual_on_line` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `default_value` longtext
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella dei parametri usati dai report di pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_partecipazione`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_partecipazione` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `full_path` longtext NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1',
  `extra_param` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella di definizione dei report statistici generati tramite pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_questionari`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_questionari` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `full_path` longtext NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1',
  `extra_param` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella di definizione dei report statistici generati tramite pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_role_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_role_map` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_role` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella che collega i report di pentaho e i ruoli di moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_role_map_formind`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_role_map_formind` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_role` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella che collega i report formazione individuale di pentaho e i ruoli di moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_role_map_partecipazione`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_role_map_partecipazione` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_role` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella che collega i report formazione individuale di pentaho e i ruoli di moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_role_map_questionari`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_role_map_questionari` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_role` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT COMMENT='tabella che collega i report di pentaho e i ruoli di moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_role_map_stat`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_role_map_stat` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_role` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT COMMENT='tabella che collega i report di pentaho e i ruoli di moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_role_map_visual_on_line`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_role_map_visual_on_line` (
  `id` bigint(10) NOT NULL,
  `id_report` bigint(10) NOT NULL,
  `id_role` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT COMMENT='tabella che collega i report di pentaho e i ruoli di moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_stat`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_stat` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `full_path` longtext NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1',
  `extra_param` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='tabella di definizione dei report statistici generati tramite pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_report_pentaho_visual_on_line`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_report_pentaho_visual_on_line` (
  `id` bigint(10) NOT NULL,
  `nome` longtext NOT NULL,
  `full_path` longtext NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT '1',
  `extra_param` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='tabella di definizione dei report statistici generati tramite pentaho';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_saf`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_saf` (
  `id` bigint(20) NOT NULL,
  `af` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sub` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_saf table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_scheda_progetto`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_scheda_progetto` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `sede_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `destinatari` longtext COLLATE utf8_unicode_ci NOT NULL,
  `accesso` longtext COLLATE utf8_unicode_ci NOT NULL,
  `obiettivi` longtext COLLATE utf8_unicode_ci NOT NULL,
  `pfa` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pfb` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pfc` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pfd` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pfdir` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pue` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `met1` smallint(3) NOT NULL,
  `met2` smallint(3) NOT NULL,
  `met3` smallint(3) NOT NULL,
  `monitoraggio` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `valutazione` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `apprendimento` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ricaduta` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `first` smallint(4) DEFAULT NULL,
  `last` smallint(4) DEFAULT NULL,
  `rev` tinyint(2) DEFAULT '0',
  `dispense_vigenti` longtext COLLATE utf8_unicode_ci NOT NULL,
  `contenuti` longtext COLLATE utf8_unicode_ci NOT NULL,
  `a` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'QN',
  `timemodified` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `usermodified` smallint(3) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=358 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_sedi`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_sedi` (
  `id` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_sedi table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_sessioni`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_sessioni` (
  `id` bigint(10) NOT NULL,
  `anno` smallint(4) NOT NULL,
  `numero` smallint(3) NOT NULL,
  `data_inizio` bigint(10) NOT NULL,
  `data_fine` bigint(10) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `percentuale_corsi` decimal(6,3) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_sessioni table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_sf`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_sf` (
  `id` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_sf table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_sf_af_map`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_sf_af_map` (
  `id` bigint(11) NOT NULL,
  `sf` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `af` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_sf_af_map table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_stati_funz`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_stati_funz` (
  `id` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `aperto` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` smallint(4) DEFAULT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_stati_funz table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_stati_validazione`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_stati_validazione` (
  `id` bigint(20) NOT NULL,
  `orgid` bigint(20) NOT NULL,
  `stato_validaz_sett` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A',
  `stato_validaz_dir` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A',
  `anno` smallint(4) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_stati_validazione table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_storico_corsi`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_storico_corsi` (
  `id` bigint(11) NOT NULL,
  `matricola` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `cognome` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `nome` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `sesso` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `categoria` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ap` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `e_mail` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cod_direzione` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `direzione` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cod_settore` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `settore` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `edizione` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codcorso` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tipo_corso` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sessione` tinyint(1) DEFAULT NULL,
  `data_inizio` bigint(10) NOT NULL DEFAULT '0',
  `sede_corso` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `localita` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codcitta` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prot` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
  `costo` decimal(13,2) DEFAULT NULL,
  `af` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `to_x` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `tipo` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sirp` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sirpdata` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `periodo` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orario` varchar(65) COLLATE utf8_unicode_ci DEFAULT NULL,
  `titolo` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `durata` decimal(5,2) DEFAULT NULL,
  `scuola_ente` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `presenza` decimal(5,2) NOT NULL,
  `delibera` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `determina` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codpart` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `descrpart` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `servizio` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sub_af` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cfa` decimal(5,2) DEFAULT NULL,
  `cfv` decimal(5,2) DEFAULT NULL,
  `va` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cf` decimal(5,2) DEFAULT NULL,
  `te` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ac` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sf` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lstupd` bigint(10) NOT NULL DEFAULT '0',
  `data_inizio_dt` date DEFAULT NULL,
  `id_record_sumtotal` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=379406 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Trigger `mdl_f2_storico_corsi`
--
DELIMITER $$
CREATE TRIGGER `insert_data_inizio_dt` BEFORE INSERT ON `mdl_f2_storico_corsi`
 FOR EACH ROW BEGIN
SET new.data_inizio_dt =  DATE_FORMAT(FROM_UNIXTIME(NEW.data_inizio), '%Y-%m-%d');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_data_inizio_dt` BEFORE UPDATE ON `mdl_f2_storico_corsi`
 FOR EACH ROW BEGIN
SET new.data_inizio_dt =  DATE_FORMAT(FROM_UNIXTIME(NEW.data_inizio), '%Y-%m-%d');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_subaf`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_subaf` (
  `id` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_subaf table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_te`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_te` (
  `id` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_te table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_tipo`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_tipo` (
  `id` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_tipo table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_tipo_pianificazione`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_tipo_pianificazione` (
  `id` tinyint(2) NOT NULL,
  `descrizione` varchar(100) NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='f2_tipo_pianificazione table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_totali_crediti`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_totali_crediti` (
  `id` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cf_necessari` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_totali_crediti table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_to_x`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_to_x` (
  `id` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_to_x table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_va`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_va` (
  `id` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `descrizione` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `progr_displ` bigint(11) NOT NULL,
  `stato` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='f2_va table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_f2_webi_piani_di_studio`
--

CREATE TABLE IF NOT EXISTS `mdl_f2_webi_piani_di_studio` (
  `matricola` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cognome` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codice_categoria` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `descrizione_categoria` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_pk_dominio` bigint(20) DEFAULT NULL,
  `org_cd_dominio` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_name_dominio` varchar(225) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_pk_dominio_direzione` bigint(20) DEFAULT NULL,
  `org_cd_dominio_direzione` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_name_dominio_direzione` varchar(225) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prima_data_inizio_elab` date DEFAULT NULL,
  `prima_data_fine_elab` date DEFAULT NULL,
  `prima_perc_compl_piano` float(8,2) DEFAULT NULL,
  `punti_prima_data` int(1) DEFAULT NULL,
  `primo_sf_tot_ric` float(8,2) DEFAULT NULL,
  `primo_sf1_ric` float(8,2) DEFAULT NULL,
  `primo_sf2_ric` float(8,2) DEFAULT NULL,
  `primo_sfj_ric` float(8,2) DEFAULT NULL,
  `primo_sf_tot_ott` float(8,2) DEFAULT NULL,
  `primo_sf1_ott` float(8,2) DEFAULT NULL,
  `primo_sf2_ott` float(8,2) DEFAULT NULL,
  `primo_sfj_ott` float(8,2) DEFAULT NULL,
  `primo_sf_tot_util` float(8,2) DEFAULT NULL,
  `primo_sf1_util` float(8,2) DEFAULT NULL,
  `primo_sf2_util` float(8,2) DEFAULT NULL,
  `primo_sfj_util` float(8,2) DEFAULT NULL,
  `seconda_data_inizio_elab` date DEFAULT NULL,
  `seconda_data_fine_elab` date DEFAULT NULL,
  `seconda_perc_compl_piano` float(8,2) DEFAULT NULL,
  `punti_seconda_data` int(1) DEFAULT NULL,
  `secondo_sf_tot_ric` float(8,2) DEFAULT NULL,
  `secondo_sf1_ric` float(8,2) DEFAULT NULL,
  `secondo_sf2_ric` float(8,2) DEFAULT NULL,
  `secondo_sfj_ric` float(8,2) DEFAULT NULL,
  `secondo_sf_tot_ott` float(8,2) DEFAULT NULL,
  `secondo_sf1_ott` float(8,2) DEFAULT NULL,
  `secondo_sf2_ott` float(8,2) DEFAULT NULL,
  `secondo_sfj_ott` float(8,2) DEFAULT NULL,
  `secondo_sf_tot_util` float(8,2) DEFAULT NULL,
  `secondo_sf1_util` float(8,2) DEFAULT NULL,
  `secondo_sf2_util` float(8,2) DEFAULT NULL,
  `secondo_sfj_util` float(8,2) DEFAULT NULL,
  `data_elaborazione` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella webi';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` tinyint(2) NOT NULL DEFAULT '0',
  `thirdparty` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `thirdpartywaitlist` tinyint(1) NOT NULL DEFAULT '0',
  `display` bigint(10) NOT NULL DEFAULT '0',
  `confirmationsubject` longtext COLLATE utf8_unicode_ci,
  `confirmationinstrmngr` longtext COLLATE utf8_unicode_ci,
  `confirmationmessage` longtext COLLATE utf8_unicode_ci,
  `waitlistedsubject` longtext COLLATE utf8_unicode_ci,
  `waitlistedmessage` longtext COLLATE utf8_unicode_ci,
  `cancellationsubject` longtext COLLATE utf8_unicode_ci,
  `cancellationinstrmngr` longtext COLLATE utf8_unicode_ci,
  `cancellationmessage` longtext COLLATE utf8_unicode_ci,
  `remindersubject` longtext COLLATE utf8_unicode_ci,
  `reminderinstrmngr` longtext COLLATE utf8_unicode_ci,
  `remindermessage` longtext COLLATE utf8_unicode_ci,
  `reminderperiod` bigint(10) NOT NULL DEFAULT '0',
  `requestsubject` longtext COLLATE utf8_unicode_ci,
  `requestinstrmngr` longtext COLLATE utf8_unicode_ci,
  `requestmessage` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(20) NOT NULL DEFAULT '0',
  `timemodified` bigint(20) NOT NULL DEFAULT '0',
  `shortname` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `showoncalendar` tinyint(1) NOT NULL DEFAULT '1',
  `approvalreqd` tinyint(1) NOT NULL DEFAULT '0',
  `f2session` bigint(10) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=644 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='Each facetoface activity has an entry here';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_notice`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_notice` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Site-wide notices shown on the Training Calendar';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_notice_data`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_notice_data` (
  `id` bigint(10) NOT NULL,
  `fieldid` bigint(10) NOT NULL DEFAULT '0',
  `noticeid` bigint(10) NOT NULL DEFAULT '0',
  `data` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Custom field filters for site notices';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_sessions`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_sessions` (
  `id` bigint(10) NOT NULL,
  `facetoface` bigint(10) NOT NULL DEFAULT '0',
  `capacity` bigint(10) NOT NULL DEFAULT '0',
  `allowoverbook` tinyint(1) NOT NULL DEFAULT '0',
  `details` longtext COLLATE utf8_unicode_ci,
  `datetimeknown` tinyint(1) NOT NULL DEFAULT '0',
  `duration` bigint(10) DEFAULT NULL,
  `normalcost` bigint(10) NOT NULL DEFAULT '0',
  `discountcost` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(20) NOT NULL DEFAULT '0',
  `timemodified` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1221 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A given facetoface activity may be given at different times ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_sessions_dates`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_sessions_dates` (
  `id` bigint(10) NOT NULL,
  `sessionid` bigint(10) NOT NULL DEFAULT '0',
  `timestart` bigint(20) NOT NULL DEFAULT '0',
  `timefinish` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2609 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The dates and times for each session.  Sessions can be set o';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_sessions_docenti`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_sessions_docenti` (
  `id` bigint(10) NOT NULL,
  `sessionid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3101 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The teachers for each session.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_session_data`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_session_data` (
  `id` bigint(10) NOT NULL,
  `fieldid` bigint(10) NOT NULL DEFAULT '0',
  `sessionid` bigint(10) NOT NULL DEFAULT '0',
  `data` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=8567 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Contents of custom info fields for Face-to-face session';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_session_field`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_session_field` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` bigint(10) NOT NULL DEFAULT '0',
  `possiblevalues` longtext COLLATE utf8_unicode_ci,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `defaultvalue` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isfilter` tinyint(1) NOT NULL DEFAULT '1',
  `showinsummary` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Definitions of custom info fields for Face-to-face session';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_session_roles`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_session_roles` (
  `id` bigint(10) NOT NULL,
  `sessionid` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Users with a trainer role in a facetoface session';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_signups`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_signups` (
  `id` bigint(10) NOT NULL,
  `sessionid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `mailedreminder` bigint(10) NOT NULL,
  `discountcode` longtext COLLATE utf8_unicode_ci,
  `notificationtype` bigint(10) NOT NULL,
  `f2_send_notif` smallint(4) DEFAULT '0',
  `f2_note` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=62939 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='User/session signups';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_facetoface_signups_status`
--

CREATE TABLE IF NOT EXISTS `mdl_facetoface_signups_status` (
  `id` bigint(10) NOT NULL,
  `signupid` bigint(10) NOT NULL,
  `statuscode` bigint(10) NOT NULL,
  `superceded` tinyint(1) NOT NULL,
  `grade` decimal(10,5) DEFAULT NULL,
  `note` longtext COLLATE utf8_unicode_ci,
  `advice` longtext COLLATE utf8_unicode_ci,
  `createdby` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `f2_substituted` tinyint(1) DEFAULT '0',
  `f2_user_changes` bigint(20) DEFAULT NULL,
  `presenza` decimal(4,2) DEFAULT NULL,
  `va` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stores` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=132941 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='User/session signup status';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `anonymous` tinyint(1) NOT NULL DEFAULT '1',
  `email_notification` tinyint(1) NOT NULL DEFAULT '1',
  `multiple_submit` tinyint(1) NOT NULL DEFAULT '1',
  `autonumbering` tinyint(1) NOT NULL DEFAULT '1',
  `site_after_submit` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `page_after_submit` longtext COLLATE utf8_unicode_ci NOT NULL,
  `page_after_submitformat` tinyint(2) NOT NULL DEFAULT '0',
  `publish_stats` tinyint(1) NOT NULL DEFAULT '0',
  `timeopen` bigint(10) NOT NULL DEFAULT '0',
  `timeclose` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `completionsubmit` tinyint(1) NOT NULL DEFAULT '0',
  `consenti_compilazione_utenti` smallint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=1423 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='all feedbacks';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_completed`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_completed` (
  `id` bigint(10) NOT NULL,
  `feedback` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `random_response` bigint(10) NOT NULL DEFAULT '0',
  `anonymous_response` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=20512 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='filled out feedback';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_completedtmp`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_completedtmp` (
  `id` bigint(10) NOT NULL,
  `feedback` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `guestid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `random_response` bigint(10) NOT NULL DEFAULT '0',
  `anonymous_response` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=14981 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='filled out feedback';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_completed_session`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_completed_session` (
  `id` bigint(10) NOT NULL,
  `feedback` bigint(10) NOT NULL,
  `completed` bigint(10) NOT NULL,
  `session` bigint(10) NOT NULL,
  `user` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=20512 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_item`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_item` (
  `id` bigint(10) NOT NULL,
  `feedback` bigint(10) NOT NULL DEFAULT '0',
  `template` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `presentation` longtext COLLATE utf8_unicode_ci NOT NULL,
  `typ` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hasvalue` tinyint(1) NOT NULL DEFAULT '0',
  `position` smallint(3) NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `dependitem` bigint(10) NOT NULL DEFAULT '0',
  `dependvalue` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `options` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `teacher_item` bigint(10) NOT NULL DEFAULT '0',
  `teacherid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=56540 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='feedback_items';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_sitecourse_map`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_sitecourse_map` (
  `id` bigint(10) NOT NULL,
  `feedbackid` bigint(10) NOT NULL DEFAULT '0',
  `courseid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='feedback sitecourse map';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_template`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_template` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `ispublic` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='templates of feedbackstructures';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_tracking`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_tracking` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `feedback` bigint(10) NOT NULL DEFAULT '0',
  `completed` bigint(10) NOT NULL DEFAULT '0',
  `tmp_completed` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=20512 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='feedback trackingdata';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_value`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_value` (
  `id` bigint(10) NOT NULL,
  `course_id` bigint(10) NOT NULL DEFAULT '0',
  `item` bigint(10) NOT NULL DEFAULT '0',
  `completed` bigint(10) NOT NULL DEFAULT '0',
  `tmp_completed` bigint(10) NOT NULL DEFAULT '0',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=499254 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='values of the completeds';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_feedback_valuetmp`
--

CREATE TABLE IF NOT EXISTS `mdl_feedback_valuetmp` (
  `id` bigint(10) NOT NULL,
  `course_id` bigint(10) NOT NULL DEFAULT '0',
  `item` bigint(10) NOT NULL DEFAULT '0',
  `completed` bigint(10) NOT NULL DEFAULT '0',
  `tmp_completed` bigint(10) NOT NULL DEFAULT '0',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=350324 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='values of the completedstmp';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_files`
--

CREATE TABLE IF NOT EXISTS `mdl_files` (
  `id` bigint(10) NOT NULL,
  `contenthash` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pathnamehash` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contextid` bigint(10) NOT NULL,
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `filearea` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemid` bigint(10) NOT NULL,
  `filepath` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `userid` bigint(10) DEFAULT NULL,
  `filesize` bigint(10) NOT NULL,
  `mimetype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` bigint(10) NOT NULL DEFAULT '0',
  `source` longtext COLLATE utf8_unicode_ci,
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `license` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `referencefileid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=230371 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='description of files, content is stored in sha1 file pool';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_files_reference`
--

CREATE TABLE IF NOT EXISTS `mdl_files_reference` (
  `id` bigint(10) NOT NULL,
  `repositoryid` bigint(10) NOT NULL,
  `lastsync` bigint(10) DEFAULT NULL,
  `reference` longtext COLLATE utf8_unicode_ci,
  `referencehash` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_filter_active`
--

CREATE TABLE IF NOT EXISTS `mdl_filter_active` (
  `id` bigint(10) NOT NULL,
  `filter` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contextid` bigint(10) NOT NULL,
  `active` smallint(4) NOT NULL,
  `sortorder` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores information about which filters are active in which c';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_filter_config`
--

CREATE TABLE IF NOT EXISTS `mdl_filter_config` (
  `id` bigint(10) NOT NULL,
  `filter` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contextid` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores per-context configuration settings for filters which ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_folder`
--

CREATE TABLE IF NOT EXISTS `mdl_folder` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `revision` bigint(10) NOT NULL DEFAULT '0',
  `showexpanded` tinyint(1) NOT NULL DEFAULT '1',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `display` smallint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='each record is one folder resource';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_format_grid_icon`
--

CREATE TABLE IF NOT EXISTS `mdl_format_grid_icon` (
  `id` bigint(10) NOT NULL,
  `image` longtext COLLATE utf8_unicode_ci,
  `sectionid` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `displayedimageindex` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1363 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Icon images for each topic, used by the grid course format';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_format_grid_summary`
--

CREATE TABLE IF NOT EXISTS `mdl_format_grid_summary` (
  `id` bigint(10) NOT NULL,
  `showsummary` tinyint(1) NOT NULL,
  `courseid` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=251 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A table to hold a single flag on whether to show section 0 a';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum`
--

CREATE TABLE IF NOT EXISTS `mdl_forum` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'general',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `assessed` bigint(10) NOT NULL DEFAULT '0',
  `assesstimestart` bigint(10) NOT NULL DEFAULT '0',
  `assesstimefinish` bigint(10) NOT NULL DEFAULT '0',
  `scale` bigint(10) NOT NULL DEFAULT '0',
  `maxbytes` bigint(10) NOT NULL DEFAULT '0',
  `maxattachments` bigint(10) NOT NULL DEFAULT '1',
  `forcesubscribe` tinyint(1) NOT NULL DEFAULT '0',
  `trackingtype` tinyint(2) NOT NULL DEFAULT '1',
  `rsstype` tinyint(2) NOT NULL DEFAULT '0',
  `rssarticles` tinyint(2) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `warnafter` bigint(10) NOT NULL DEFAULT '0',
  `blockafter` bigint(10) NOT NULL DEFAULT '0',
  `blockperiod` bigint(10) NOT NULL DEFAULT '0',
  `completiondiscussions` int(9) NOT NULL DEFAULT '0',
  `completionreplies` int(9) NOT NULL DEFAULT '0',
  `completionposts` int(9) NOT NULL DEFAULT '0',
  `displaywordcount` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=273 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Forums contain and structure discussion';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum_digests`
--

CREATE TABLE IF NOT EXISTS `mdl_forum_digests` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `forum` bigint(10) NOT NULL,
  `maildigest` tinyint(1) NOT NULL DEFAULT '-1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum_discussions`
--

CREATE TABLE IF NOT EXISTS `mdl_forum_discussions` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `forum` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `firstpost` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '-1',
  `assessed` tinyint(1) NOT NULL DEFAULT '1',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `usermodified` bigint(10) NOT NULL DEFAULT '0',
  `timestart` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Forums are composed of discussions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum_posts`
--

CREATE TABLE IF NOT EXISTS `mdl_forum_posts` (
  `id` bigint(10) NOT NULL,
  `discussion` bigint(10) NOT NULL DEFAULT '0',
  `parent` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `created` bigint(10) NOT NULL DEFAULT '0',
  `modified` bigint(10) NOT NULL DEFAULT '0',
  `mailed` tinyint(2) NOT NULL DEFAULT '0',
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `messageformat` tinyint(2) NOT NULL DEFAULT '0',
  `messagetrust` tinyint(2) NOT NULL DEFAULT '0',
  `attachment` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `totalscore` smallint(4) NOT NULL DEFAULT '0',
  `mailnow` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=297 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='All posts are stored in this table';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum_queue`
--

CREATE TABLE IF NOT EXISTS `mdl_forum_queue` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `discussionid` bigint(10) NOT NULL DEFAULT '0',
  `postid` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='For keeping track of posts that will be mailed in digest for';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum_read`
--

CREATE TABLE IF NOT EXISTS `mdl_forum_read` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `forumid` bigint(10) NOT NULL DEFAULT '0',
  `discussionid` bigint(10) NOT NULL DEFAULT '0',
  `postid` bigint(10) NOT NULL DEFAULT '0',
  `firstread` bigint(10) NOT NULL DEFAULT '0',
  `lastread` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tracks each users read posts';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum_subscriptions`
--

CREATE TABLE IF NOT EXISTS `mdl_forum_subscriptions` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `forum` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1752 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Keeps track of who is subscribed to what forum';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_forum_track_prefs`
--

CREATE TABLE IF NOT EXISTS `mdl_forum_track_prefs` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `forumid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tracks each users untracked forums';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game`
--

CREATE TABLE IF NOT EXISTS `mdl_game` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `course` bigint(10) DEFAULT NULL,
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) DEFAULT '0',
  `sourcemodule` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timeopen` bigint(10) DEFAULT '0',
  `timeclose` bigint(10) NOT NULL DEFAULT '0',
  `quizid` bigint(10) DEFAULT NULL,
  `glossaryid` bigint(10) DEFAULT NULL,
  `glossarycategoryid` bigint(10) DEFAULT NULL,
  `questioncategoryid` bigint(10) DEFAULT NULL,
  `bookid` bigint(10) DEFAULT NULL,
  `gamekind` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `param1` bigint(10) DEFAULT NULL,
  `param2` bigint(10) DEFAULT NULL,
  `param3` bigint(10) DEFAULT NULL,
  `param4` bigint(10) DEFAULT NULL,
  `param5` bigint(10) DEFAULT NULL,
  `param6` bigint(10) DEFAULT NULL,
  `param7` bigint(10) DEFAULT NULL,
  `param8` bigint(10) DEFAULT NULL,
  `param9` longtext COLLATE utf8_unicode_ci,
  `param10` bigint(10) DEFAULT NULL,
  `shuffle` tinyint(2) DEFAULT '1',
  `timemodified` bigint(10) DEFAULT NULL,
  `gameinputid` bigint(10) DEFAULT NULL,
  `toptext` longtext COLLATE utf8_unicode_ci,
  `bottomtext` longtext COLLATE utf8_unicode_ci,
  `grademethod` tinyint(2) DEFAULT NULL,
  `grade` bigint(10) DEFAULT NULL,
  `decimalpoints` tinyint(2) DEFAULT NULL,
  `popup` smallint(4) DEFAULT NULL,
  `review` bigint(10) DEFAULT NULL,
  `attempts` bigint(10) DEFAULT NULL,
  `glossaryid2` bigint(10) DEFAULT NULL,
  `glossarycategoryid2` bigint(10) DEFAULT NULL,
  `language` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subcategories` tinyint(1) DEFAULT NULL,
  `maxattempts` smallint(3) DEFAULT NULL,
  `userlanguage` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `disablesummarize` tinyint(1) DEFAULT '0',
  `glossaryonlyapproved` tinyint(1) DEFAULT '0',
  `completionattemptsexhausted` tinyint(1) DEFAULT '0',
  `completionpass` tinyint(1) DEFAULT '0',
  `highscore` tinyint(2) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_attempts`
--

CREATE TABLE IF NOT EXISTS `mdl_game_attempts` (
  `id` bigint(10) NOT NULL,
  `gameid` bigint(10) DEFAULT NULL,
  `userid` bigint(10) DEFAULT NULL,
  `timestart` bigint(10) NOT NULL,
  `timefinish` bigint(10) NOT NULL,
  `timelastattempt` bigint(10) DEFAULT NULL,
  `preview` tinyint(1) DEFAULT NULL,
  `attempt` bigint(10) DEFAULT NULL,
  `score` double DEFAULT NULL,
  `attempts` bigint(10) DEFAULT NULL,
  `language` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_attempts';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_bookquiz`
--

CREATE TABLE IF NOT EXISTS `mdl_game_bookquiz` (
  `id` bigint(10) NOT NULL,
  `lastchapterid` varchar(81) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_bookquiz';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_bookquiz_chapters`
--

CREATE TABLE IF NOT EXISTS `mdl_game_bookquiz_chapters` (
  `id` bigint(10) NOT NULL,
  `attemptid` bigint(10) NOT NULL,
  `chapterid` varchar(81) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_bookquiz_chapters';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_bookquiz_questions`
--

CREATE TABLE IF NOT EXISTS `mdl_game_bookquiz_questions` (
  `id` bigint(10) NOT NULL,
  `gameid` bigint(10) DEFAULT NULL,
  `chapterid` bigint(10) DEFAULT NULL,
  `questioncategoryid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_bookquiz';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_cross`
--

CREATE TABLE IF NOT EXISTS `mdl_game_cross` (
  `id` bigint(10) NOT NULL,
  `usedcols` smallint(3) DEFAULT '0',
  `usedrows` smallint(3) DEFAULT '0',
  `cols` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rows` smallint(3) DEFAULT NULL,
  `words` smallint(3) DEFAULT NULL,
  `wordsall` bigint(10) DEFAULT NULL,
  `createscore` double DEFAULT '0',
  `createtries` bigint(10) DEFAULT NULL,
  `createtimelimit` bigint(10) DEFAULT NULL,
  `createconnectors` bigint(10) DEFAULT NULL,
  `createfilleds` bigint(10) DEFAULT NULL,
  `createspaces` bigint(10) DEFAULT NULL,
  `triesplay` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_cross';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_cryptex`
--

CREATE TABLE IF NOT EXISTS `mdl_game_cryptex` (
  `id` bigint(10) NOT NULL,
  `letters` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_cryptex';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_export_html`
--

CREATE TABLE IF NOT EXISTS `mdl_game_export_html` (
  `id` bigint(10) NOT NULL,
  `filename` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checkbutton` tinyint(2) DEFAULT NULL,
  `printbutton` tinyint(2) DEFAULT NULL,
  `inputsize` smallint(3) DEFAULT NULL,
  `maxpicturewidth` bigint(10) DEFAULT NULL,
  `maxpictureheight` bigint(10) DEFAULT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_export_html';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_export_javame`
--

CREATE TABLE IF NOT EXISTS `mdl_game_export_javame` (
  `id` bigint(10) NOT NULL,
  `filename` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `createdby` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `vendor` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `maxpicturewidth` bigint(10) DEFAULT NULL,
  `maxpictureheight` bigint(10) DEFAULT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_export_javame';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_grades`
--

CREATE TABLE IF NOT EXISTS `mdl_game_grades` (
  `id` bigint(10) NOT NULL,
  `gameid` bigint(10) DEFAULT NULL,
  `userid` bigint(10) DEFAULT NULL,
  `score` double NOT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_grades';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_hangman`
--

CREATE TABLE IF NOT EXISTS `mdl_game_hangman` (
  `id` bigint(10) NOT NULL,
  `queryid` bigint(10) DEFAULT NULL,
  `letters` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `allletters` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `try` smallint(4) DEFAULT NULL,
  `maxtries` smallint(4) DEFAULT NULL,
  `finishedword` smallint(4) DEFAULT NULL,
  `corrects` smallint(4) DEFAULT NULL,
  `iscorrect` tinyint(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_hangman';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_hiddenpicture`
--

CREATE TABLE IF NOT EXISTS `mdl_game_hiddenpicture` (
  `id` bigint(10) NOT NULL,
  `correct` smallint(4) DEFAULT '0',
  `wrong` smallint(4) DEFAULT '0',
  `found` smallint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_hiddenpicture';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_millionaire`
--

CREATE TABLE IF NOT EXISTS `mdl_game_millionaire` (
  `id` bigint(10) NOT NULL,
  `queryid` bigint(10) DEFAULT NULL,
  `state` tinyint(2) NOT NULL DEFAULT '0',
  `level` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_millionaire';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_queries`
--

CREATE TABLE IF NOT EXISTS `mdl_game_queries` (
  `id` bigint(10) NOT NULL,
  `attemptid` bigint(10) DEFAULT NULL,
  `gamekind` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gameid` bigint(10) DEFAULT NULL,
  `userid` bigint(10) DEFAULT NULL,
  `sourcemodule` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `questionid` bigint(10) DEFAULT NULL,
  `glossaryentryid` bigint(10) DEFAULT NULL,
  `questiontext` longtext COLLATE utf8_unicode_ci,
  `score` double DEFAULT NULL,
  `timelastattempt` bigint(10) DEFAULT NULL,
  `studentanswer` longtext COLLATE utf8_unicode_ci,
  `mycol` bigint(10) DEFAULT '0',
  `myrow` bigint(10) DEFAULT '0',
  `horizontal` bigint(10) DEFAULT NULL,
  `answertext` longtext COLLATE utf8_unicode_ci,
  `correct` bigint(10) DEFAULT NULL,
  `attachment` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `answerid` bigint(10) DEFAULT NULL,
  `tries` bigint(10) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_queries';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_repetitions`
--

CREATE TABLE IF NOT EXISTS `mdl_game_repetitions` (
  `id` bigint(10) NOT NULL,
  `gameid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `questionid` bigint(10) NOT NULL DEFAULT '0',
  `glossaryentryid` bigint(10) NOT NULL DEFAULT '0',
  `repetitions` bigint(10) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_repetitions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_snakes`
--

CREATE TABLE IF NOT EXISTS `mdl_game_snakes` (
  `id` bigint(10) NOT NULL,
  `snakesdatabaseid` bigint(10) DEFAULT NULL,
  `position` bigint(10) DEFAULT NULL,
  `queryid` bigint(10) DEFAULT NULL,
  `dice` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_snakes';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_snakes_database`
--

CREATE TABLE IF NOT EXISTS `mdl_game_snakes_database` (
  `id` bigint(10) NOT NULL,
  `usedcols` smallint(3) DEFAULT '0',
  `usedrows` smallint(3) DEFAULT '0',
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cols` smallint(3) DEFAULT NULL,
  `rows` smallint(3) DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `fileboard` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `direction` tinyint(2) DEFAULT NULL,
  `headerx` bigint(10) DEFAULT NULL,
  `headery` bigint(10) DEFAULT NULL,
  `footerx` bigint(10) DEFAULT NULL,
  `footery` bigint(10) DEFAULT NULL,
  `width` bigint(10) DEFAULT NULL,
  `height` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_snakes_database';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_sudoku`
--

CREATE TABLE IF NOT EXISTS `mdl_game_sudoku` (
  `id` bigint(10) NOT NULL,
  `level` smallint(4) DEFAULT '0',
  `data` varchar(81) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `opened` smallint(4) DEFAULT NULL,
  `guess` varchar(81) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_sudoku';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_game_sudoku_database`
--

CREATE TABLE IF NOT EXISTS `mdl_game_sudoku_database` (
  `id` bigint(10) NOT NULL,
  `level` smallint(3) DEFAULT NULL,
  `opened` tinyint(2) DEFAULT NULL,
  `data` varchar(81) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='game_sudoku_database';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_glossary`
--

CREATE TABLE IF NOT EXISTS `mdl_glossary` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `allowduplicatedentries` tinyint(2) NOT NULL DEFAULT '0',
  `displayformat` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dictionary',
  `mainglossary` tinyint(2) NOT NULL DEFAULT '0',
  `showspecial` tinyint(2) NOT NULL DEFAULT '1',
  `showalphabet` tinyint(2) NOT NULL DEFAULT '1',
  `showall` tinyint(2) NOT NULL DEFAULT '1',
  `allowcomments` tinyint(2) NOT NULL DEFAULT '0',
  `allowprintview` tinyint(2) NOT NULL DEFAULT '1',
  `usedynalink` tinyint(2) NOT NULL DEFAULT '1',
  `defaultapproval` tinyint(2) NOT NULL DEFAULT '1',
  `approvaldisplayformat` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'default',
  `globalglossary` tinyint(2) NOT NULL DEFAULT '0',
  `entbypage` smallint(3) NOT NULL DEFAULT '10',
  `editalways` tinyint(2) NOT NULL DEFAULT '0',
  `rsstype` tinyint(2) NOT NULL DEFAULT '0',
  `rssarticles` tinyint(2) NOT NULL DEFAULT '0',
  `assessed` bigint(10) NOT NULL DEFAULT '0',
  `assesstimestart` bigint(10) NOT NULL DEFAULT '0',
  `assesstimefinish` bigint(10) NOT NULL DEFAULT '0',
  `scale` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `completionentries` int(9) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='all glossaries';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_glossary_alias`
--

CREATE TABLE IF NOT EXISTS `mdl_glossary_alias` (
  `id` bigint(10) NOT NULL,
  `entryid` bigint(10) NOT NULL DEFAULT '0',
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='entries alias';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_glossary_categories`
--

CREATE TABLE IF NOT EXISTS `mdl_glossary_categories` (
  `id` bigint(10) NOT NULL,
  `glossaryid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `usedynalink` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='all categories for glossary entries';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_glossary_entries`
--

CREATE TABLE IF NOT EXISTS `mdl_glossary_entries` (
  `id` bigint(10) NOT NULL,
  `glossaryid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `concept` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `definition` longtext COLLATE utf8_unicode_ci NOT NULL,
  `definitionformat` tinyint(2) NOT NULL DEFAULT '0',
  `definitiontrust` tinyint(2) NOT NULL DEFAULT '0',
  `attachment` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `teacherentry` tinyint(2) NOT NULL DEFAULT '0',
  `sourceglossaryid` bigint(10) NOT NULL DEFAULT '0',
  `usedynalink` tinyint(2) NOT NULL DEFAULT '1',
  `casesensitive` tinyint(2) NOT NULL DEFAULT '0',
  `fullmatch` tinyint(2) NOT NULL DEFAULT '1',
  `approved` tinyint(2) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=449 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='all glossary entries';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_glossary_entries_categories`
--

CREATE TABLE IF NOT EXISTS `mdl_glossary_entries_categories` (
  `id` bigint(10) NOT NULL,
  `categoryid` bigint(10) NOT NULL DEFAULT '0',
  `entryid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='categories of each glossary entry';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_glossary_formats`
--

CREATE TABLE IF NOT EXISTS `mdl_glossary_formats` (
  `id` bigint(10) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `popupformatname` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `visible` tinyint(2) NOT NULL DEFAULT '1',
  `showgroup` tinyint(2) NOT NULL DEFAULT '1',
  `defaultmode` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `defaulthook` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sortkey` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sortorder` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Setting of the display formats';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_categories`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_categories` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `parent` bigint(10) DEFAULT NULL,
  `depth` bigint(10) NOT NULL DEFAULT '0',
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `aggregation` bigint(10) NOT NULL DEFAULT '0',
  `keephigh` bigint(10) NOT NULL DEFAULT '0',
  `droplow` bigint(10) NOT NULL DEFAULT '0',
  `aggregateonlygraded` tinyint(1) NOT NULL DEFAULT '0',
  `aggregateoutcomes` tinyint(1) NOT NULL DEFAULT '0',
  `aggregatesubcats` tinyint(1) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=669 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table keeps information about categories, used for grou';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_categories_history`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_categories_history` (
  `id` bigint(10) NOT NULL,
  `action` bigint(10) NOT NULL DEFAULT '0',
  `oldid` bigint(10) NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `loggeduser` bigint(10) DEFAULT NULL,
  `courseid` bigint(10) NOT NULL,
  `parent` bigint(10) DEFAULT NULL,
  `depth` bigint(10) NOT NULL DEFAULT '0',
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `aggregation` bigint(10) NOT NULL DEFAULT '0',
  `keephigh` bigint(10) NOT NULL DEFAULT '0',
  `droplow` bigint(10) NOT NULL DEFAULT '0',
  `aggregateonlygraded` tinyint(1) NOT NULL DEFAULT '0',
  `aggregateoutcomes` tinyint(1) NOT NULL DEFAULT '0',
  `aggregatesubcats` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2636 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='History of grade_categories';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_grades`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_grades` (
  `id` bigint(10) NOT NULL,
  `itemid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `rawgrade` decimal(10,5) DEFAULT NULL,
  `rawgrademax` decimal(10,5) NOT NULL DEFAULT '100.00000',
  `rawgrademin` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `rawscaleid` bigint(10) DEFAULT NULL,
  `usermodified` bigint(10) DEFAULT NULL,
  `finalgrade` decimal(10,5) DEFAULT NULL,
  `hidden` bigint(10) NOT NULL DEFAULT '0',
  `locked` bigint(10) NOT NULL DEFAULT '0',
  `locktime` bigint(10) NOT NULL DEFAULT '0',
  `exported` bigint(10) NOT NULL DEFAULT '0',
  `overridden` bigint(10) NOT NULL DEFAULT '0',
  `excluded` bigint(10) NOT NULL DEFAULT '0',
  `feedback` longtext COLLATE utf8_unicode_ci,
  `feedbackformat` bigint(10) NOT NULL DEFAULT '0',
  `information` longtext COLLATE utf8_unicode_ci,
  `informationformat` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=162826 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='grade_grades  This table keeps individual grades for each us';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_grades_history`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_grades_history` (
  `id` bigint(10) NOT NULL,
  `action` bigint(10) NOT NULL DEFAULT '0',
  `oldid` bigint(10) NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `loggeduser` bigint(10) DEFAULT NULL,
  `itemid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `rawgrade` decimal(10,5) DEFAULT NULL,
  `rawgrademax` decimal(10,5) NOT NULL DEFAULT '100.00000',
  `rawgrademin` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `rawscaleid` bigint(10) DEFAULT NULL,
  `usermodified` bigint(10) DEFAULT NULL,
  `finalgrade` decimal(10,5) DEFAULT NULL,
  `hidden` bigint(10) NOT NULL DEFAULT '0',
  `locked` bigint(10) NOT NULL DEFAULT '0',
  `locktime` bigint(10) NOT NULL DEFAULT '0',
  `exported` bigint(10) NOT NULL DEFAULT '0',
  `overridden` bigint(10) NOT NULL DEFAULT '0',
  `excluded` bigint(10) NOT NULL DEFAULT '0',
  `feedback` longtext COLLATE utf8_unicode_ci,
  `feedbackformat` bigint(10) NOT NULL DEFAULT '0',
  `information` longtext COLLATE utf8_unicode_ci,
  `informationformat` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=385644 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='History table';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_import_newitem`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_import_newitem` (
  `id` bigint(10) NOT NULL,
  `itemname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `importcode` bigint(10) NOT NULL,
  `importer` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='temporary table for storing new grade_item names from grade ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_import_values`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_import_values` (
  `id` bigint(10) NOT NULL,
  `itemid` bigint(10) DEFAULT NULL,
  `newgradeitem` bigint(10) DEFAULT NULL,
  `userid` bigint(10) NOT NULL,
  `finalgrade` decimal(10,5) DEFAULT NULL,
  `feedback` longtext COLLATE utf8_unicode_ci,
  `importcode` bigint(10) NOT NULL,
  `importer` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Temporary table for importing grades';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_items`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_items` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) DEFAULT NULL,
  `categoryid` bigint(10) DEFAULT NULL,
  `itemname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `itemtype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemmodule` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `iteminstance` bigint(10) DEFAULT NULL,
  `itemnumber` bigint(10) DEFAULT NULL,
  `iteminfo` longtext COLLATE utf8_unicode_ci,
  `idnumber` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `calculation` longtext COLLATE utf8_unicode_ci,
  `gradetype` smallint(4) NOT NULL DEFAULT '1',
  `grademax` decimal(10,5) NOT NULL DEFAULT '100.00000',
  `grademin` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `scaleid` bigint(10) DEFAULT NULL,
  `outcomeid` bigint(10) DEFAULT NULL,
  `gradepass` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `multfactor` decimal(10,5) NOT NULL DEFAULT '1.00000',
  `plusfactor` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `aggregationcoef` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `display` bigint(10) NOT NULL DEFAULT '0',
  `decimals` tinyint(1) DEFAULT NULL,
  `hidden` bigint(10) NOT NULL DEFAULT '0',
  `locked` bigint(10) NOT NULL DEFAULT '0',
  `locktime` bigint(10) NOT NULL DEFAULT '0',
  `needsupdate` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1656 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table keeps information about gradeable items (ie colum';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_items_history`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_items_history` (
  `id` bigint(10) NOT NULL,
  `action` bigint(10) NOT NULL DEFAULT '0',
  `oldid` bigint(10) NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `loggeduser` bigint(10) DEFAULT NULL,
  `courseid` bigint(10) DEFAULT NULL,
  `categoryid` bigint(10) DEFAULT NULL,
  `itemname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `itemtype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemmodule` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `iteminstance` bigint(10) DEFAULT NULL,
  `itemnumber` bigint(10) DEFAULT NULL,
  `iteminfo` longtext COLLATE utf8_unicode_ci,
  `idnumber` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `calculation` longtext COLLATE utf8_unicode_ci,
  `gradetype` smallint(4) NOT NULL DEFAULT '1',
  `grademax` decimal(10,5) NOT NULL DEFAULT '100.00000',
  `grademin` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `scaleid` bigint(10) DEFAULT NULL,
  `outcomeid` bigint(10) DEFAULT NULL,
  `gradepass` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `multfactor` decimal(10,5) NOT NULL DEFAULT '1.00000',
  `plusfactor` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `aggregationcoef` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `hidden` bigint(10) NOT NULL DEFAULT '0',
  `locked` bigint(10) NOT NULL DEFAULT '0',
  `locktime` bigint(10) NOT NULL DEFAULT '0',
  `needsupdate` bigint(10) NOT NULL DEFAULT '0',
  `display` bigint(10) NOT NULL DEFAULT '0',
  `decimals` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7224 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='History of grade_items';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_letters`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_letters` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `lowerboundary` decimal(10,5) NOT NULL,
  `letter` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Repository for grade letters, for courses and other moodle e';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_outcomes`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_outcomes` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) DEFAULT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fullname` longtext COLLATE utf8_unicode_ci NOT NULL,
  `scaleid` bigint(10) DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `usermodified` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table describes the outcomes used in the system. An out';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_outcomes_courses`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_outcomes_courses` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `outcomeid` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='stores what outcomes are used in what courses.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_outcomes_history`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_outcomes_history` (
  `id` bigint(10) NOT NULL,
  `action` bigint(10) NOT NULL DEFAULT '0',
  `oldid` bigint(10) NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `loggeduser` bigint(10) DEFAULT NULL,
  `courseid` bigint(10) DEFAULT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fullname` longtext COLLATE utf8_unicode_ci NOT NULL,
  `scaleid` bigint(10) DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='History table';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grade_settings`
--

CREATE TABLE IF NOT EXISTS `mdl_grade_settings` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='gradebook settings';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_gradingform_guide_comments`
--

CREATE TABLE IF NOT EXISTS `mdl_gradingform_guide_comments` (
  `id` bigint(10) NOT NULL,
  `definitionid` bigint(10) NOT NULL,
  `sortorder` bigint(10) NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='frequently used comments used in marking guide';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_gradingform_guide_criteria`
--

CREATE TABLE IF NOT EXISTS `mdl_gradingform_guide_criteria` (
  `id` bigint(10) NOT NULL,
  `definitionid` bigint(10) NOT NULL,
  `sortorder` bigint(10) NOT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) DEFAULT NULL,
  `descriptionmarkers` longtext COLLATE utf8_unicode_ci,
  `descriptionmarkersformat` tinyint(2) DEFAULT NULL,
  `maxscore` decimal(10,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the rows of the criteria grid.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_gradingform_guide_fillings`
--

CREATE TABLE IF NOT EXISTS `mdl_gradingform_guide_fillings` (
  `id` bigint(10) NOT NULL,
  `instanceid` bigint(10) NOT NULL,
  `criterionid` bigint(10) NOT NULL,
  `remark` longtext COLLATE utf8_unicode_ci,
  `remarkformat` tinyint(2) DEFAULT NULL,
  `score` decimal(10,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the data of how the guide is filled by a particular r';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_gradingform_rubric_criteria`
--

CREATE TABLE IF NOT EXISTS `mdl_gradingform_rubric_criteria` (
  `id` bigint(10) NOT NULL,
  `definitionid` bigint(10) NOT NULL,
  `sortorder` bigint(10) NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the rows of the rubric grid.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_gradingform_rubric_fillings`
--

CREATE TABLE IF NOT EXISTS `mdl_gradingform_rubric_fillings` (
  `id` bigint(10) NOT NULL,
  `instanceid` bigint(10) NOT NULL,
  `criterionid` bigint(10) NOT NULL,
  `levelid` bigint(10) DEFAULT NULL,
  `remark` longtext COLLATE utf8_unicode_ci,
  `remarkformat` tinyint(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the data of how the rubric is filled by a particular ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_gradingform_rubric_levels`
--

CREATE TABLE IF NOT EXISTS `mdl_gradingform_rubric_levels` (
  `id` bigint(10) NOT NULL,
  `criterionid` bigint(10) NOT NULL,
  `score` decimal(10,5) NOT NULL,
  `definition` longtext COLLATE utf8_unicode_ci,
  `definitionformat` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the columns of the rubric grid.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grading_areas`
--

CREATE TABLE IF NOT EXISTS `mdl_grading_areas` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `areaname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `activemethod` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Identifies gradable areas where advanced grading can happen.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grading_definitions`
--

CREATE TABLE IF NOT EXISTS `mdl_grading_definitions` (
  `id` bigint(10) NOT NULL,
  `areaid` bigint(10) NOT NULL,
  `method` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) DEFAULT NULL,
  `status` bigint(10) NOT NULL DEFAULT '0',
  `copiedfromid` bigint(10) DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL,
  `usercreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `usermodified` bigint(10) NOT NULL,
  `timecopied` bigint(10) DEFAULT '0',
  `options` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Contains the basic information about an advanced grading for';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_grading_instances`
--

CREATE TABLE IF NOT EXISTS `mdl_grading_instances` (
  `id` bigint(10) NOT NULL,
  `definitionid` bigint(10) NOT NULL,
  `raterid` bigint(10) NOT NULL,
  `itemid` bigint(10) DEFAULT NULL,
  `rawgrade` decimal(10,5) DEFAULT NULL,
  `status` bigint(10) NOT NULL DEFAULT '0',
  `feedback` longtext COLLATE utf8_unicode_ci,
  `feedbackformat` tinyint(2) DEFAULT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Grading form instance is an assessment record for one gradab';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_groupings`
--

CREATE TABLE IF NOT EXISTS `mdl_groupings` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `idnumber` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0',
  `configdata` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A grouping is a collection of groups. WAS: groups_groupings';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_groupings_groups`
--

CREATE TABLE IF NOT EXISTS `mdl_groupings_groups` (
  `id` bigint(10) NOT NULL,
  `groupingid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `timeadded` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Link a grouping to a group (note, groups can be in multiple ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_groups`
--

CREATE TABLE IF NOT EXISTS `mdl_groups` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL,
  `idnumber` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0',
  `enrolmentkey` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `picture` bigint(10) NOT NULL DEFAULT '0',
  `hidepicture` tinyint(1) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each record represents a group.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_groups_members`
--

CREATE TABLE IF NOT EXISTS `mdl_groups_members` (
  `id` bigint(10) NOT NULL,
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timeadded` bigint(10) NOT NULL DEFAULT '0',
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1913 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Link a user to a group.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hbackup_temp_items`
--

CREATE TABLE IF NOT EXISTS `mdl_hbackup_temp_items` (
  `id` bigint(10) NOT NULL,
  `oid` bigint(10) NOT NULL,
  `frameworkid` bigint(10) NOT NULL,
  `idnumber` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fullname` longtext COLLATE utf8_unicode_ci NOT NULL,
  `backup_unique_code` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=328 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `json_content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `embed_type` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `disable` bigint(10) NOT NULL DEFAULT '0',
  `main_library_id` bigint(10) NOT NULL,
  `content_type` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filtered` longtext COLLATE utf8_unicode_ci,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `authors` longtext COLLATE utf8_unicode_ci,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year_from` smallint(4) DEFAULT NULL,
  `year_to` smallint(4) DEFAULT NULL,
  `license` varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
  `license_version` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `changes` longtext COLLATE utf8_unicode_ci,
  `license_extras` longtext COLLATE utf8_unicode_ci,
  `author_comments` longtext COLLATE utf8_unicode_ci,
  `default_language` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Activity data';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_auth`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_auth` (
  `id` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `created_at` bigint(11) NOT NULL,
  `secret` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_contents_libraries`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_contents_libraries` (
  `id` bigint(10) NOT NULL,
  `hvp_id` bigint(10) NOT NULL,
  `library_id` bigint(10) NOT NULL,
  `dependency_type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `drop_css` tinyint(1) NOT NULL,
  `weight` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4486 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Store which library is used in which content.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_content_user_data`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_content_user_data` (
  `id` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `hvp_id` bigint(10) NOT NULL,
  `sub_content_id` bigint(10) NOT NULL,
  `data_id` varchar(127) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `preloaded` tinyint(1) NOT NULL,
  `delete_on_content_change` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores user data about the content';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_counters`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_counters` (
  `id` bigint(10) NOT NULL,
  `type` varchar(63) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `library_name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `library_version` varchar(31) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `num` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=259 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A set of global counters to keep track of H5P usage';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_events`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_events` (
  `id` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `created_at` bigint(10) NOT NULL,
  `type` varchar(63) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sub_type` varchar(63) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content_id` bigint(10) NOT NULL,
  `content_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `library_name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `library_version` varchar(31) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=981 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Keep track of logged H5P events';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_libraries`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_libraries` (
  `id` bigint(10) NOT NULL,
  `machine_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `major_version` smallint(4) NOT NULL,
  `minor_version` smallint(4) NOT NULL,
  `patch_version` smallint(4) NOT NULL,
  `runnable` tinyint(1) NOT NULL,
  `fullscreen` tinyint(1) NOT NULL DEFAULT '0',
  `embed_types` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `preloaded_js` longtext COLLATE utf8_unicode_ci,
  `preloaded_css` longtext COLLATE utf8_unicode_ci,
  `drop_library_css` longtext COLLATE utf8_unicode_ci,
  `semantics` longtext COLLATE utf8_unicode_ci NOT NULL,
  `restricted` tinyint(1) NOT NULL DEFAULT '0',
  `tutorial_url` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_icon` tinyint(1) NOT NULL DEFAULT '0',
  `add_to` longtext COLLATE utf8_unicode_ci,
  `metadata_settings` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores information about libraries.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_libraries_cachedassets`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_libraries_cachedassets` (
  `id` bigint(10) NOT NULL,
  `library_id` bigint(10) NOT NULL,
  `hash` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=274 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Use to know which caches to clear when a library is updated';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_libraries_hub_cache`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_libraries_hub_cache` (
  `id` bigint(10) NOT NULL,
  `machine_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `major_version` smallint(4) NOT NULL,
  `minor_version` smallint(4) NOT NULL,
  `patch_version` smallint(4) NOT NULL,
  `h5p_major_version` smallint(4) DEFAULT NULL,
  `h5p_minor_version` smallint(4) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `summary` longtext COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `icon` varchar(511) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` bigint(11) NOT NULL,
  `updated_at` bigint(11) NOT NULL,
  `is_recommended` tinyint(1) NOT NULL,
  `popularity` bigint(10) NOT NULL,
  `screenshots` longtext COLLATE utf8_unicode_ci,
  `license` longtext COLLATE utf8_unicode_ci,
  `example` varchar(511) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tutorial` varchar(511) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keywords` longtext COLLATE utf8_unicode_ci,
  `categories` longtext COLLATE utf8_unicode_ci,
  `owner` varchar(511) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='Caches content types from the H5P hub.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_libraries_languages`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_libraries_languages` (
  `id` bigint(10) NOT NULL,
  `library_id` bigint(10) NOT NULL,
  `language_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `language_json` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3953 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Translations for libraries';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_libraries_libraries`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_libraries_libraries` (
  `id` bigint(10) NOT NULL,
  `library_id` bigint(10) NOT NULL,
  `required_library_id` bigint(10) NOT NULL,
  `dependency_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=569 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Library dependencies';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_tmpfiles`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_tmpfiles` (
  `id` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Keep track of files uploaded before content is saved';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_hvp_xapi_results`
--

CREATE TABLE IF NOT EXISTS `mdl_hvp_xapi_results` (
  `id` bigint(10) NOT NULL,
  `content_id` bigint(10) NOT NULL,
  `user_id` bigint(10) NOT NULL,
  `parent_id` bigint(10) DEFAULT NULL,
  `interaction_type` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `correct_responses_pattern` longtext COLLATE utf8_unicode_ci NOT NULL,
  `response` longtext COLLATE utf8_unicode_ci NOT NULL,
  `additionals` longtext COLLATE utf8_unicode_ci NOT NULL,
  `raw_score` mediumint(6) DEFAULT NULL,
  `max_score` mediumint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stored xAPI events';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_imscp`
--

CREATE TABLE IF NOT EXISTS `mdl_imscp` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `revision` bigint(10) NOT NULL DEFAULT '0',
  `keepold` bigint(10) NOT NULL DEFAULT '-1',
  `structure` longtext COLLATE utf8_unicode_ci,
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='each record is one imscp resource';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_label`
--

CREATE TABLE IF NOT EXISTS `mdl_label` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=390 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines labels';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `practice` smallint(3) NOT NULL DEFAULT '0',
  `modattempts` smallint(3) NOT NULL DEFAULT '0',
  `usepassword` smallint(3) NOT NULL DEFAULT '0',
  `password` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `dependency` bigint(10) NOT NULL DEFAULT '0',
  `conditions` longtext COLLATE utf8_unicode_ci NOT NULL,
  `grade` bigint(10) NOT NULL DEFAULT '0',
  `custom` smallint(3) NOT NULL DEFAULT '0',
  `ongoing` smallint(3) NOT NULL DEFAULT '0',
  `usemaxgrade` smallint(3) NOT NULL DEFAULT '0',
  `maxanswers` smallint(3) NOT NULL DEFAULT '4',
  `maxattempts` smallint(3) NOT NULL DEFAULT '5',
  `review` smallint(3) NOT NULL DEFAULT '0',
  `nextpagedefault` smallint(3) NOT NULL DEFAULT '0',
  `feedback` smallint(3) NOT NULL DEFAULT '1',
  `minquestions` smallint(3) NOT NULL DEFAULT '0',
  `maxpages` smallint(3) NOT NULL DEFAULT '0',
  `timed` smallint(3) NOT NULL DEFAULT '0',
  `maxtime` bigint(10) NOT NULL DEFAULT '0',
  `retake` smallint(3) NOT NULL DEFAULT '1',
  `activitylink` bigint(10) NOT NULL DEFAULT '0',
  `mediafile` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mediaheight` bigint(10) NOT NULL DEFAULT '100',
  `mediawidth` bigint(10) NOT NULL DEFAULT '650',
  `mediaclose` smallint(3) NOT NULL DEFAULT '0',
  `slideshow` smallint(3) NOT NULL DEFAULT '0',
  `width` bigint(10) NOT NULL DEFAULT '640',
  `height` bigint(10) NOT NULL DEFAULT '480',
  `bgcolor` varchar(7) COLLATE utf8_unicode_ci NOT NULL DEFAULT '#FFFFFF',
  `displayleft` smallint(3) NOT NULL DEFAULT '0',
  `displayleftif` smallint(3) NOT NULL DEFAULT '0',
  `progressbar` smallint(3) NOT NULL DEFAULT '0',
  `highscores` smallint(3) NOT NULL DEFAULT '0',
  `maxhighscores` bigint(10) NOT NULL DEFAULT '0',
  `available` bigint(10) NOT NULL DEFAULT '0',
  `deadline` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines lesson';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson_answers`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson_answers` (
  `id` bigint(10) NOT NULL,
  `lessonid` bigint(10) NOT NULL DEFAULT '0',
  `pageid` bigint(10) NOT NULL DEFAULT '0',
  `jumpto` bigint(11) NOT NULL DEFAULT '0',
  `grade` smallint(4) NOT NULL DEFAULT '0',
  `score` bigint(10) NOT NULL DEFAULT '0',
  `flags` smallint(3) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `answer` longtext COLLATE utf8_unicode_ci,
  `answerformat` tinyint(2) NOT NULL DEFAULT '0',
  `response` longtext COLLATE utf8_unicode_ci,
  `responseformat` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines lesson_answers';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson_attempts`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson_attempts` (
  `id` bigint(10) NOT NULL,
  `lessonid` bigint(10) NOT NULL DEFAULT '0',
  `pageid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `answerid` bigint(10) NOT NULL DEFAULT '0',
  `retry` smallint(3) NOT NULL DEFAULT '0',
  `correct` bigint(10) NOT NULL DEFAULT '0',
  `useranswer` longtext COLLATE utf8_unicode_ci,
  `timeseen` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines lesson_attempts';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson_branch`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson_branch` (
  `id` bigint(10) NOT NULL,
  `lessonid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `pageid` bigint(10) NOT NULL DEFAULT '0',
  `retry` bigint(10) NOT NULL DEFAULT '0',
  `flag` smallint(3) NOT NULL DEFAULT '0',
  `timeseen` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='branches for each lesson/user';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson_grades`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson_grades` (
  `id` bigint(10) NOT NULL,
  `lessonid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `grade` double NOT NULL DEFAULT '0',
  `late` smallint(3) NOT NULL DEFAULT '0',
  `completed` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines lesson_grades';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson_high_scores`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson_high_scores` (
  `id` bigint(10) NOT NULL,
  `lessonid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `gradeid` bigint(10) NOT NULL DEFAULT '0',
  `nickname` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='high scores for each lesson';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson_pages`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson_pages` (
  `id` bigint(10) NOT NULL,
  `lessonid` bigint(10) NOT NULL DEFAULT '0',
  `prevpageid` bigint(10) NOT NULL DEFAULT '0',
  `nextpageid` bigint(10) NOT NULL DEFAULT '0',
  `qtype` smallint(3) NOT NULL DEFAULT '0',
  `qoption` smallint(3) NOT NULL DEFAULT '0',
  `layout` smallint(3) NOT NULL DEFAULT '1',
  `display` smallint(3) NOT NULL DEFAULT '1',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contents` longtext COLLATE utf8_unicode_ci NOT NULL,
  `contentsformat` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines lesson_pages';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lesson_timer`
--

CREATE TABLE IF NOT EXISTS `mdl_lesson_timer` (
  `id` bigint(10) NOT NULL,
  `lessonid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `starttime` bigint(10) NOT NULL DEFAULT '0',
  `lessontime` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='lesson timer for each lesson';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_license`
--

CREATE TABLE IF NOT EXISTS `mdl_license` (
  `id` bigint(10) NOT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullname` longtext COLLATE utf8_unicode_ci,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `version` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='store licenses used by moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_local_f2_notif`
--

CREATE TABLE IF NOT EXISTS `mdl_local_f2_notif` (
  `id` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Default comment for local_f2_notif, please edit me';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lock_db`
--

CREATE TABLE IF NOT EXISTS `mdl_lock_db` (
  `id` bigint(10) NOT NULL,
  `resourcekey` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `expires` bigint(10) DEFAULT NULL,
  `owner` varchar(36) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_log`
--

CREATE TABLE IF NOT EXISTS `mdl_log` (
  `id` bigint(10) NOT NULL,
  `time` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `course` bigint(10) NOT NULL DEFAULT '0',
  `module` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cmid` bigint(10) NOT NULL DEFAULT '0',
  `action` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `info` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=1946894 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Every action is logged as far as possible';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_logstore_standard_log`
--

CREATE TABLE IF NOT EXISTS `mdl_logstore_standard_log` (
  `id` bigint(10) NOT NULL,
  `eventname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `target` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `objecttable` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `objectid` bigint(10) DEFAULT NULL,
  `crud` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `edulevel` tinyint(1) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `contextlevel` bigint(10) NOT NULL,
  `contextinstanceid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `courseid` bigint(10) DEFAULT NULL,
  `relateduserid` bigint(10) DEFAULT NULL,
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `other` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(10) NOT NULL,
  `origin` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `realuserid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2865041 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Standard log table';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_log_corsi_ind`
--

CREATE TABLE IF NOT EXISTS `mdl_log_corsi_ind` (
  `id` bigint(10) NOT NULL,
  `data` datetime NOT NULL,
  `msg` longtext COLLATE utf8_bin
) ENGINE=InnoDB AUTO_INCREMENT=5196 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='tabella di log corsi individuali senza determina';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_log_corsi_ind_archiviazione`
--

CREATE TABLE IF NOT EXISTS `mdl_log_corsi_ind_archiviazione` (
  `id` bigint(10) NOT NULL,
  `id_corsiind` bigint(10) NOT NULL,
  `data` datetime NOT NULL,
  `msg` char(250) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9559 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='tabella di log per archiviazione dei corsi individuali senza determina';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_log_corsi_ind_prot`
--

CREATE TABLE IF NOT EXISTS `mdl_log_corsi_ind_prot` (
  `id` bigint(10) NOT NULL,
  `data` datetime NOT NULL,
  `msg` char(250) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1326 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='tabella di log per protocollo su corsi individuali senza determina';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_log_display`
--

CREATE TABLE IF NOT EXISTS `mdl_log_display` (
  `id` bigint(10) NOT NULL,
  `module` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mtable` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `field` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='For a particular module/action, specifies a moodle table/fie';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_log_queries`
--

CREATE TABLE IF NOT EXISTS `mdl_log_queries` (
  `id` bigint(10) NOT NULL,
  `qtype` mediumint(5) NOT NULL,
  `sqltext` longtext COLLATE utf8_unicode_ci NOT NULL,
  `sqlparams` longtext COLLATE utf8_unicode_ci,
  `error` mediumint(5) NOT NULL DEFAULT '0',
  `info` longtext COLLATE utf8_unicode_ci,
  `backtrace` longtext COLLATE utf8_unicode_ci,
  `exectime` decimal(10,5) NOT NULL,
  `timelogged` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1026 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Logged database queries.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lti`
--

CREATE TABLE IF NOT EXISTS `mdl_lti` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `typeid` bigint(10) DEFAULT NULL,
  `toolurl` longtext COLLATE utf8_unicode_ci NOT NULL,
  `securetoolurl` longtext COLLATE utf8_unicode_ci,
  `instructorchoicesendname` tinyint(1) DEFAULT NULL,
  `instructorchoicesendemailaddr` tinyint(1) DEFAULT NULL,
  `instructorchoiceallowroster` tinyint(1) DEFAULT NULL,
  `instructorchoiceallowsetting` tinyint(1) DEFAULT NULL,
  `instructorcustomparameters` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `instructorchoiceacceptgrades` tinyint(1) DEFAULT NULL,
  `grade` decimal(10,5) NOT NULL DEFAULT '100.00000',
  `launchcontainer` tinyint(2) NOT NULL DEFAULT '1',
  `resourcekey` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `debuglaunch` tinyint(1) NOT NULL DEFAULT '0',
  `showtitlelaunch` tinyint(1) NOT NULL DEFAULT '0',
  `showdescriptionlaunch` tinyint(1) NOT NULL DEFAULT '0',
  `servicesalt` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `icon` longtext COLLATE utf8_unicode_ci,
  `secureicon` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='This table contains Basic LTI activities instances';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lti_submission`
--

CREATE TABLE IF NOT EXISTS `mdl_lti_submission` (
  `id` bigint(10) NOT NULL,
  `ltiid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `datesubmitted` bigint(10) NOT NULL,
  `dateupdated` bigint(10) NOT NULL,
  `gradepercent` decimal(10,5) NOT NULL,
  `originalgrade` decimal(10,5) NOT NULL,
  `launchid` bigint(10) NOT NULL,
  `state` tinyint(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Keeps track of individual submissions for LTI activities.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lti_types`
--

CREATE TABLE IF NOT EXISTS `mdl_lti_types` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'basiclti Activity',
  `baseurl` longtext COLLATE utf8_unicode_ci NOT NULL,
  `tooldomain` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `state` tinyint(2) NOT NULL DEFAULT '2',
  `course` bigint(10) NOT NULL,
  `coursevisible` tinyint(1) NOT NULL DEFAULT '0',
  `createdby` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Basic LTI pre-configured activities';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_lti_types_config`
--

CREATE TABLE IF NOT EXISTS `mdl_lti_types_config` (
  `id` bigint(10) NOT NULL,
  `typeid` bigint(10) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Basic LTI types configuration';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_message`
--

CREATE TABLE IF NOT EXISTS `mdl_message` (
  `id` bigint(10) NOT NULL,
  `useridfrom` bigint(10) NOT NULL DEFAULT '0',
  `useridto` bigint(10) NOT NULL DEFAULT '0',
  `subject` longtext COLLATE utf8_unicode_ci,
  `fullmessage` longtext COLLATE utf8_unicode_ci,
  `fullmessageformat` smallint(4) DEFAULT '0',
  `fullmessagehtml` longtext COLLATE utf8_unicode_ci,
  `smallmessage` longtext COLLATE utf8_unicode_ci,
  `notification` tinyint(1) DEFAULT '0',
  `contexturl` longtext COLLATE utf8_unicode_ci,
  `contexturlname` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=39617 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores all unread messages';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_message_airnotifier_devices`
--

CREATE TABLE IF NOT EXISTS `mdl_message_airnotifier_devices` (
  `id` bigint(10) NOT NULL,
  `userdeviceid` bigint(10) NOT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Store information about the devices registered in Airnotifie';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_message_contacts`
--

CREATE TABLE IF NOT EXISTS `mdl_message_contacts` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `contactid` bigint(10) NOT NULL DEFAULT '0',
  `blocked` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Maintains lists of relationships between users';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_message_processors`
--

CREATE TABLE IF NOT EXISTS `mdl_message_processors` (
  `id` bigint(10) NOT NULL,
  `name` varchar(166) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='List of message output plugins';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_message_providers`
--

CREATE TABLE IF NOT EXISTS `mdl_message_providers` (
  `id` bigint(10) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `component` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `capability` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table stores the message providers (modules and core sy';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_message_read`
--

CREATE TABLE IF NOT EXISTS `mdl_message_read` (
  `id` bigint(10) NOT NULL,
  `useridfrom` bigint(10) NOT NULL DEFAULT '0',
  `useridto` bigint(10) NOT NULL DEFAULT '0',
  `subject` longtext COLLATE utf8_unicode_ci,
  `fullmessage` longtext COLLATE utf8_unicode_ci,
  `fullmessageformat` smallint(4) DEFAULT '0',
  `fullmessagehtml` longtext COLLATE utf8_unicode_ci,
  `smallmessage` longtext COLLATE utf8_unicode_ci,
  `notification` tinyint(1) DEFAULT '0',
  `contexturl` longtext COLLATE utf8_unicode_ci,
  `contexturlname` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timeread` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=33404 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores all messages that have been read';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_message_working`
--

CREATE TABLE IF NOT EXISTS `mdl_message_working` (
  `id` bigint(10) NOT NULL,
  `unreadmessageid` bigint(10) NOT NULL,
  `processorid` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=23534 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Lists all the messages and processors that need to be proces';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnetservice_enrol_courses`
--

CREATE TABLE IF NOT EXISTS `mdl_mnetservice_enrol_courses` (
  `id` bigint(10) NOT NULL,
  `hostid` bigint(10) NOT NULL,
  `remoteid` bigint(10) NOT NULL,
  `categoryid` bigint(10) NOT NULL,
  `categoryname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `fullname` varchar(254) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `idnumber` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `summary` longtext COLLATE utf8_unicode_ci NOT NULL,
  `summaryformat` smallint(3) DEFAULT '0',
  `startdate` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL,
  `rolename` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Caches the information fetched via XML-RPC about courses on ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnetservice_enrol_enrolments`
--

CREATE TABLE IF NOT EXISTS `mdl_mnetservice_enrol_enrolments` (
  `id` bigint(10) NOT NULL,
  `hostid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `remotecourseid` bigint(10) NOT NULL,
  `rolename` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `enroltime` bigint(10) NOT NULL DEFAULT '0',
  `enroltype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Caches the information about enrolments of our local users i';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_application`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_application` (
  `id` bigint(10) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `display_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `xmlrpc_server_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sso_land_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sso_jump_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Information about applications on remote hosts';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_host`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_host` (
  `id` bigint(10) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `wwwroot` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(80) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `public_key` longtext COLLATE utf8_unicode_ci NOT NULL,
  `public_key_expires` bigint(10) NOT NULL DEFAULT '0',
  `transport` tinyint(2) NOT NULL DEFAULT '0',
  `portno` mediumint(5) NOT NULL DEFAULT '0',
  `last_connect_time` bigint(10) NOT NULL DEFAULT '0',
  `last_log_id` bigint(10) NOT NULL DEFAULT '0',
  `force_theme` tinyint(1) NOT NULL DEFAULT '0',
  `theme` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `applicationid` bigint(10) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Information about the local and remote hosts for RPC';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_host2service`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_host2service` (
  `id` bigint(10) NOT NULL,
  `hostid` bigint(10) NOT NULL DEFAULT '0',
  `serviceid` bigint(10) NOT NULL DEFAULT '0',
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `subscribe` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Information about the services for a given host';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_log`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_log` (
  `id` bigint(10) NOT NULL,
  `hostid` bigint(10) NOT NULL DEFAULT '0',
  `remoteid` bigint(10) NOT NULL DEFAULT '0',
  `time` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `course` bigint(10) NOT NULL DEFAULT '0',
  `coursename` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `module` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cmid` bigint(10) NOT NULL DEFAULT '0',
  `action` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `info` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Store session data from users migrating to other sites';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_remote_rpc`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_remote_rpc` (
  `id` bigint(10) NOT NULL,
  `functionname` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `xmlrpcpath` varchar(80) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `plugintype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pluginname` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table describes functions that might be called remotely';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_remote_service2rpc`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_remote_service2rpc` (
  `id` bigint(10) NOT NULL,
  `serviceid` bigint(10) NOT NULL DEFAULT '0',
  `rpcid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Group functions or methods under a service';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_rpc`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_rpc` (
  `id` bigint(10) NOT NULL,
  `functionname` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `xmlrpcpath` varchar(80) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `plugintype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pluginname` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `help` longtext COLLATE utf8_unicode_ci NOT NULL,
  `profile` longtext COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `classname` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `static` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Functions or methods that we may publish or subscribe to';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_service`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_service` (
  `id` bigint(10) NOT NULL,
  `name` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `apiversion` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `offer` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A service is a group of functions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_service2rpc`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_service2rpc` (
  `id` bigint(10) NOT NULL,
  `serviceid` bigint(10) NOT NULL DEFAULT '0',
  `rpcid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Group functions or methods under a service';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_session`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_session` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `token` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mnethostid` bigint(10) NOT NULL DEFAULT '0',
  `useragent` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `confirm_timeout` bigint(10) NOT NULL DEFAULT '0',
  `session_id` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `expires` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Store session data from users migrating to other sites';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_mnet_sso_access_control`
--

CREATE TABLE IF NOT EXISTS `mdl_mnet_sso_access_control` (
  `id` bigint(10) NOT NULL,
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mnet_host_id` bigint(10) NOT NULL DEFAULT '0',
  `accessctrl` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'allow'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Users by host permitted (or not) to login from a remote prov';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_modules`
--

CREATE TABLE IF NOT EXISTS `mdl_modules` (
  `id` bigint(10) NOT NULL,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cron` bigint(10) NOT NULL DEFAULT '0',
  `lastcron` bigint(10) NOT NULL DEFAULT '0',
  `search` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `visible` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='modules available in the site';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_my_pages`
--

CREATE TABLE IF NOT EXISTS `mdl_my_pages` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) DEFAULT '0',
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `private` tinyint(1) NOT NULL DEFAULT '1',
  `sortorder` mediumint(6) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Extra user pages for the My Moodle system';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org`
--

CREATE TABLE IF NOT EXISTS `mdl_org` (
  `id` bigint(10) NOT NULL,
  `fullname` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `shortname` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `idnumber` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `frameworkid` bigint(10) NOT NULL,
  `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `depthid` bigint(10) NOT NULL,
  `parentid` bigint(10) NOT NULL,
  `sortorder` bigint(10) NOT NULL,
  `visible` tinyint(1) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `usermodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2689 DEFAULT CHARSET=utf8 COMMENT='org table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org_assignment`
--

CREATE TABLE IF NOT EXISTS `mdl_org_assignment` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `organisationid` bigint(10) NOT NULL,
  `viewableorganisationid` bigint(10) DEFAULT NULL,
  `timemodified` bigint(10) NOT NULL,
  `usermodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5859 DEFAULT CHARSET=utf8 COMMENT='org_assignment table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org_depth`
--

CREATE TABLE IF NOT EXISTS `mdl_org_depth` (
  `id` bigint(10) NOT NULL,
  `fullname` longtext COLLATE utf8_unicode_ci NOT NULL,
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `depthlevel` bigint(10) NOT NULL,
  `frameworkid` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `usermodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='org_depth table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org_depth_info_category`
--

CREATE TABLE IF NOT EXISTS `mdl_org_depth_info_category` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sortorder` bigint(10) NOT NULL,
  `depthid` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='org_depth_info_category table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org_depth_info_data`
--

CREATE TABLE IF NOT EXISTS `mdl_org_depth_info_data` (
  `id` bigint(10) NOT NULL,
  `fieldid` bigint(10) NOT NULL,
  `organisationid` bigint(10) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='org_depth_info_data table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org_depth_info_field`
--

CREATE TABLE IF NOT EXISTS `mdl_org_depth_info_field` (
  `id` bigint(10) NOT NULL,
  `fullname` longtext COLLATE utf8_unicode_ci NOT NULL,
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `depthid` bigint(10) NOT NULL,
  `datatype` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `sortorder` bigint(10) NOT NULL,
  `categoryid` bigint(10) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `locked` tinyint(1) NOT NULL,
  `required` tinyint(1) NOT NULL,
  `forceunique` tinyint(1) NOT NULL,
  `defaultdata` longtext COLLATE utf8_unicode_ci,
  `param1` longtext COLLATE utf8_unicode_ci,
  `param2` longtext COLLATE utf8_unicode_ci,
  `param3` longtext COLLATE utf8_unicode_ci,
  `param4` longtext COLLATE utf8_unicode_ci,
  `param5` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='org_depth_info_field table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org_framework`
--

CREATE TABLE IF NOT EXISTS `mdl_org_framework` (
  `id` bigint(10) NOT NULL,
  `fullname` longtext COLLATE utf8_unicode_ci NOT NULL,
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `idnumber` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `sortorder` bigint(10) NOT NULL,
  `visible` tinyint(1) NOT NULL,
  `hidecustomfields` tinyint(1) NOT NULL,
  `showitemfullname` tinyint(1) NOT NULL,
  `showdepthfullname` tinyint(1) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `usermodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='org_framework table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_org_relations`
--

CREATE TABLE IF NOT EXISTS `mdl_org_relations` (
  `id` bigint(10) NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `id1` bigint(10) NOT NULL,
  `id2` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='org_relations table retrofitted from MySQL';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_page`
--

CREATE TABLE IF NOT EXISTS `mdl_page` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8_unicode_ci,
  `contentformat` smallint(4) NOT NULL DEFAULT '0',
  `legacyfiles` smallint(4) NOT NULL DEFAULT '0',
  `legacyfileslast` bigint(10) DEFAULT NULL,
  `display` smallint(4) NOT NULL DEFAULT '0',
  `displayoptions` longtext COLLATE utf8_unicode_ci,
  `revision` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each record is one page and its config data';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_ponos_course`
--

CREATE TABLE IF NOT EXISTS `mdl_ponos_course` (
  `id` bigint(10) NOT NULL,
  `id_inserimento` bigint(10) DEFAULT NULL,
  `anno_gestione` char(4) DEFAULT NULL,
  `id_attivita` varchar(50) DEFAULT NULL,
  `data_trasmissione` char(8) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8 COMMENT='Tabella di report per i corsi inseriti da ponos';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_ponos_insert`
--

CREATE TABLE IF NOT EXISTS `mdl_ponos_insert` (
  `id` bigint(10) NOT NULL,
  `file` varchar(50) DEFAULT NULL,
  `data` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 COMMENT='Tabella di report per i files inseriti da ponos';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_ponos_log`
--

CREATE TABLE IF NOT EXISTS `mdl_ponos_log` (
  `id` bigint(10) NOT NULL,
  `id_inserimento` bigint(10) DEFAULT NULL,
  `file` varchar(50) DEFAULT NULL,
  `step` varchar(100) DEFAULT NULL,
  `msg` varchar(500) DEFAULT NULL,
  `data` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11656 DEFAULT CHARSET=utf8 COMMENT='Tabella di log per i record caricati su mdl_f2_storico_corsi da ponos';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_ponos_report`
--

CREATE TABLE IF NOT EXISTS `mdl_ponos_report` (
  `id` bigint(10) NOT NULL,
  `id_inserimento` bigint(10) DEFAULT NULL,
  `file` varchar(50) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `record` int(9) DEFAULT NULL,
  `inseriti` int(9) DEFAULT NULL,
  `errori` int(9) DEFAULT NULL,
  `data` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8 COMMENT='Tabella di report per i record caricati su mdl_f2_storico_corsi da ponos';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_portfolio_instance`
--

CREATE TABLE IF NOT EXISTS `mdl_portfolio_instance` (
  `id` bigint(10) NOT NULL,
  `plugin` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `visible` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='base table (not including config data) for instances of port';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_portfolio_instance_config`
--

CREATE TABLE IF NOT EXISTS `mdl_portfolio_instance_config` (
  `id` bigint(10) NOT NULL,
  `instance` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='config for portfolio plugin instances';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_portfolio_instance_user`
--

CREATE TABLE IF NOT EXISTS `mdl_portfolio_instance_user` (
  `id` bigint(10) NOT NULL,
  `instance` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='user data for portfolio instances.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_portfolio_log`
--

CREATE TABLE IF NOT EXISTS `mdl_portfolio_log` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `time` bigint(10) NOT NULL,
  `portfolio` bigint(10) NOT NULL,
  `caller_class` varchar(150) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `caller_file` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `caller_component` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `caller_sha1` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tempdataid` bigint(10) NOT NULL DEFAULT '0',
  `returnurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `continueurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='log of portfolio transfers (used to later check for duplicat';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_portfolio_mahara_queue`
--

CREATE TABLE IF NOT EXISTS `mdl_portfolio_mahara_queue` (
  `id` bigint(10) NOT NULL,
  `transferid` bigint(10) NOT NULL,
  `token` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='maps mahara tokens to transfer ids';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_portfolio_tempdata`
--

CREATE TABLE IF NOT EXISTS `mdl_portfolio_tempdata` (
  `id` bigint(10) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `expirytime` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `instance` bigint(10) DEFAULT '0',
  `queued` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='stores temporary data for portfolio exports. the id of this ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_post`
--

CREATE TABLE IF NOT EXISTS `mdl_post` (
  `id` bigint(10) NOT NULL,
  `module` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `moduleid` bigint(10) NOT NULL DEFAULT '0',
  `coursemoduleid` bigint(10) NOT NULL DEFAULT '0',
  `subject` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `summary` longtext COLLATE utf8_unicode_ci,
  `content` longtext COLLATE utf8_unicode_ci,
  `uniquehash` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rating` bigint(10) NOT NULL DEFAULT '0',
  `format` bigint(10) NOT NULL DEFAULT '0',
  `summaryformat` tinyint(2) NOT NULL DEFAULT '0',
  `attachment` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publishstate` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'draft',
  `lastmodified` bigint(10) NOT NULL DEFAULT '0',
  `created` bigint(10) NOT NULL DEFAULT '0',
  `usermodified` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Generic post table to hold data blog entries etc in differen';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_profiling`
--

CREATE TABLE IF NOT EXISTS `mdl_profiling` (
  `id` bigint(10) NOT NULL,
  `runid` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `data` longtext COLLATE utf8_unicode_ci NOT NULL,
  `totalexecutiontime` bigint(10) NOT NULL,
  `totalcputime` bigint(10) NOT NULL,
  `totalcalls` bigint(10) NOT NULL,
  `totalmemory` bigint(10) NOT NULL,
  `runreference` tinyint(2) NOT NULL DEFAULT '0',
  `runcomment` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the results of all the profiling runs';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_qtype_essay_options`
--

CREATE TABLE IF NOT EXISTS `mdl_qtype_essay_options` (
  `id` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL,
  `responseformat` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'editor',
  `responserequired` tinyint(2) NOT NULL DEFAULT '1',
  `responsefieldlines` smallint(4) NOT NULL DEFAULT '15',
  `attachments` smallint(4) NOT NULL DEFAULT '0',
  `attachmentsrequired` smallint(4) NOT NULL DEFAULT '0',
  `graderinfo` longtext COLLATE utf8_unicode_ci,
  `graderinfoformat` smallint(4) NOT NULL DEFAULT '0',
  `responsetemplate` longtext COLLATE utf8_unicode_ci,
  `responsetemplateformat` smallint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Extra options for essay questions.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_qtype_match_options`
--

CREATE TABLE IF NOT EXISTS `mdl_qtype_match_options` (
  `id` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL DEFAULT '0',
  `shuffleanswers` smallint(4) NOT NULL DEFAULT '1',
  `correctfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `correctfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `partiallycorrectfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `partiallycorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `incorrectfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `incorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `shownumcorrect` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines fixed matching questions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_qtype_match_subquestions`
--

CREATE TABLE IF NOT EXISTS `mdl_qtype_match_subquestions` (
  `id` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL DEFAULT '0',
  `questiontext` longtext COLLATE utf8_unicode_ci NOT NULL,
  `questiontextformat` tinyint(2) NOT NULL DEFAULT '0',
  `answertext` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines the subquestions that make up a matching question';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_qtype_multichoice_options`
--

CREATE TABLE IF NOT EXISTS `mdl_qtype_multichoice_options` (
  `id` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL DEFAULT '0',
  `layout` smallint(4) NOT NULL DEFAULT '0',
  `single` smallint(4) NOT NULL DEFAULT '0',
  `shuffleanswers` smallint(4) NOT NULL DEFAULT '1',
  `correctfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `correctfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `partiallycorrectfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `partiallycorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `incorrectfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `incorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `answernumbering` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'abc',
  `shownumcorrect` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=929 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for multiple choice questions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_qtype_randomsamatch_options`
--

CREATE TABLE IF NOT EXISTS `mdl_qtype_randomsamatch_options` (
  `id` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL DEFAULT '0',
  `choose` bigint(10) NOT NULL DEFAULT '4',
  `subcats` tinyint(2) NOT NULL DEFAULT '1',
  `correctfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `correctfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `partiallycorrectfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `partiallycorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `incorrectfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `incorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `shownumcorrect` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about a random short-answer matching question';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_qtype_shortanswer_options`
--

CREATE TABLE IF NOT EXISTS `mdl_qtype_shortanswer_options` (
  `id` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL DEFAULT '0',
  `usecase` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for short answer questions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question`
--

CREATE TABLE IF NOT EXISTS `mdl_question` (
  `id` bigint(10) NOT NULL,
  `category` bigint(10) NOT NULL DEFAULT '0',
  `parent` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `questiontext` longtext COLLATE utf8_unicode_ci NOT NULL,
  `questiontextformat` tinyint(2) NOT NULL DEFAULT '0',
  `generalfeedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `generalfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `defaultmark` decimal(12,7) NOT NULL DEFAULT '1.0000000',
  `penalty` decimal(12,7) NOT NULL DEFAULT '0.3333333',
  `qtype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `length` bigint(10) NOT NULL DEFAULT '1',
  `stamp` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `createdby` bigint(10) DEFAULT NULL,
  `modifiedby` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1665 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The questions themselves';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_answers`
--

CREATE TABLE IF NOT EXISTS `mdl_question_answers` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `answer` longtext COLLATE utf8_unicode_ci NOT NULL,
  `answerformat` tinyint(2) NOT NULL DEFAULT '0',
  `fraction` decimal(12,7) NOT NULL DEFAULT '0.0000000',
  `feedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `feedbackformat` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3139 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Answers, with a fractional grade (0-1) and feedback';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_attempts`
--

CREATE TABLE IF NOT EXISTS `mdl_question_attempts` (
  `id` bigint(10) NOT NULL,
  `questionusageid` bigint(10) NOT NULL,
  `slot` bigint(10) NOT NULL,
  `behaviour` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `questionid` bigint(10) NOT NULL,
  `variant` bigint(10) NOT NULL DEFAULT '1',
  `maxmark` decimal(12,7) NOT NULL,
  `minfraction` decimal(12,7) NOT NULL,
  `maxfraction` decimal(12,7) NOT NULL DEFAULT '1.0000000',
  `flagged` tinyint(1) NOT NULL DEFAULT '0',
  `questionsummary` longtext COLLATE utf8_unicode_ci,
  `rightanswer` longtext COLLATE utf8_unicode_ci,
  `responsesummary` longtext COLLATE utf8_unicode_ci,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=340524 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each row here corresponds to an attempt at one question, as ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_attempt_steps`
--

CREATE TABLE IF NOT EXISTS `mdl_question_attempt_steps` (
  `id` bigint(10) NOT NULL,
  `questionattemptid` bigint(10) NOT NULL,
  `sequencenumber` bigint(10) NOT NULL,
  `state` varchar(13) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fraction` decimal(12,7) DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL,
  `userid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1011272 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores one step in in a question attempt. As well as the dat';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_attempt_step_data`
--

CREATE TABLE IF NOT EXISTS `mdl_question_attempt_step_data` (
  `id` bigint(10) NOT NULL,
  `attemptstepid` bigint(10) NOT NULL,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=985195 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each question_attempt_step has an associative array of the d';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_calculated`
--

CREATE TABLE IF NOT EXISTS `mdl_question_calculated` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `answer` bigint(10) NOT NULL DEFAULT '0',
  `tolerance` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0',
  `tolerancetype` bigint(10) NOT NULL DEFAULT '1',
  `correctanswerlength` bigint(10) NOT NULL DEFAULT '2',
  `correctanswerformat` bigint(10) NOT NULL DEFAULT '2'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for questions of type calculated';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_calculated_options`
--

CREATE TABLE IF NOT EXISTS `mdl_question_calculated_options` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `synchronize` tinyint(2) NOT NULL DEFAULT '0',
  `single` smallint(4) NOT NULL DEFAULT '0',
  `shuffleanswers` smallint(4) NOT NULL DEFAULT '0',
  `correctfeedback` longtext COLLATE utf8_unicode_ci,
  `correctfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `partiallycorrectfeedback` longtext COLLATE utf8_unicode_ci,
  `partiallycorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `incorrectfeedback` longtext COLLATE utf8_unicode_ci,
  `incorrectfeedbackformat` tinyint(2) NOT NULL DEFAULT '0',
  `answernumbering` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'abc',
  `shownumcorrect` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for questions of type calculated';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_categories`
--

CREATE TABLE IF NOT EXISTS `mdl_question_categories` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contextid` bigint(10) NOT NULL DEFAULT '0',
  `info` longtext COLLATE utf8_unicode_ci NOT NULL,
  `infoformat` tinyint(2) NOT NULL DEFAULT '0',
  `stamp` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `parent` bigint(10) NOT NULL DEFAULT '0',
  `sortorder` bigint(10) NOT NULL DEFAULT '999'
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Categories are for grouping questions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_datasets`
--

CREATE TABLE IF NOT EXISTS `mdl_question_datasets` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `datasetdefinition` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Many-many relation between questions and dataset definitions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_dataset_definitions`
--

CREATE TABLE IF NOT EXISTS `mdl_question_dataset_definitions` (
  `id` bigint(10) NOT NULL,
  `category` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` bigint(10) NOT NULL DEFAULT '0',
  `options` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemcount` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Organises and stores properties for dataset items';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_dataset_items`
--

CREATE TABLE IF NOT EXISTS `mdl_question_dataset_items` (
  `id` bigint(10) NOT NULL,
  `definition` bigint(10) NOT NULL DEFAULT '0',
  `itemnumber` bigint(10) NOT NULL DEFAULT '0',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Individual dataset items';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_hints`
--

CREATE TABLE IF NOT EXISTS `mdl_question_hints` (
  `id` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL,
  `hint` longtext COLLATE utf8_unicode_ci NOT NULL,
  `hintformat` smallint(4) NOT NULL DEFAULT '0',
  `shownumcorrect` tinyint(1) DEFAULT NULL,
  `clearwrong` tinyint(1) DEFAULT NULL,
  `options` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the the part of the question definition that gives di';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_multianswer`
--

CREATE TABLE IF NOT EXISTS `mdl_question_multianswer` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `sequence` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for multianswer questions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_numerical`
--

CREATE TABLE IF NOT EXISTS `mdl_question_numerical` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `answer` bigint(10) NOT NULL DEFAULT '0',
  `tolerance` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for numerical questions.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_numerical_options`
--

CREATE TABLE IF NOT EXISTS `mdl_question_numerical_options` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `showunits` smallint(4) NOT NULL DEFAULT '0',
  `unitsleft` smallint(4) NOT NULL DEFAULT '0',
  `unitgradingtype` smallint(4) NOT NULL DEFAULT '0',
  `unitpenalty` decimal(12,7) NOT NULL DEFAULT '0.1000000'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for questions of type numerical This table is also u';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_numerical_units`
--

CREATE TABLE IF NOT EXISTS `mdl_question_numerical_units` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `multiplier` decimal(40,20) NOT NULL DEFAULT '1.00000000000000000000',
  `unit` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Optional unit options for numerical questions. This table is';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_response_analysis`
--

CREATE TABLE IF NOT EXISTS `mdl_question_response_analysis` (
  `id` bigint(10) NOT NULL,
  `hashcode` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `whichtries` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timemodified` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL,
  `variant` bigint(10) DEFAULT NULL,
  `subqid` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `aid` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `response` longtext COLLATE utf8_unicode_ci,
  `credit` decimal(15,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_response_count`
--

CREATE TABLE IF NOT EXISTS `mdl_question_response_count` (
  `id` bigint(10) NOT NULL,
  `analysisid` bigint(10) NOT NULL,
  `try` bigint(10) NOT NULL,
  `rcount` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_statistics`
--

CREATE TABLE IF NOT EXISTS `mdl_question_statistics` (
  `id` bigint(10) NOT NULL,
  `hashcode` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timemodified` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL,
  `slot` bigint(10) DEFAULT NULL,
  `subquestion` smallint(4) NOT NULL,
  `variant` bigint(10) DEFAULT NULL,
  `s` bigint(10) NOT NULL DEFAULT '0',
  `effectiveweight` decimal(15,5) DEFAULT NULL,
  `negcovar` tinyint(2) NOT NULL DEFAULT '0',
  `discriminationindex` decimal(15,5) DEFAULT NULL,
  `discriminativeefficiency` decimal(15,5) DEFAULT NULL,
  `sd` decimal(15,10) DEFAULT NULL,
  `facility` decimal(15,10) DEFAULT NULL,
  `subquestions` longtext COLLATE utf8_unicode_ci,
  `maxmark` decimal(12,7) DEFAULT NULL,
  `positions` longtext COLLATE utf8_unicode_ci,
  `randomguessscore` decimal(12,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_truefalse`
--

CREATE TABLE IF NOT EXISTS `mdl_question_truefalse` (
  `id` bigint(10) NOT NULL,
  `question` bigint(10) NOT NULL DEFAULT '0',
  `trueanswer` bigint(10) NOT NULL DEFAULT '0',
  `falseanswer` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=170 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Options for True-False questions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_question_usages`
--

CREATE TABLE IF NOT EXISTS `mdl_question_usages` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `component` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `preferredbehaviour` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=31162 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table''s main purpose it to assign a unique id to each a';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `timeopen` bigint(10) NOT NULL DEFAULT '0',
  `timeclose` bigint(10) NOT NULL DEFAULT '0',
  `preferredbehaviour` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `attempts` mediumint(6) NOT NULL DEFAULT '0',
  `attemptonlast` smallint(4) NOT NULL DEFAULT '0',
  `grademethod` smallint(4) NOT NULL DEFAULT '1',
  `decimalpoints` smallint(4) NOT NULL DEFAULT '2',
  `questiondecimalpoints` smallint(4) NOT NULL DEFAULT '-1',
  `reviewattempt` mediumint(6) NOT NULL DEFAULT '0',
  `reviewcorrectness` mediumint(6) NOT NULL DEFAULT '0',
  `reviewmarks` mediumint(6) NOT NULL DEFAULT '0',
  `reviewspecificfeedback` mediumint(6) NOT NULL DEFAULT '0',
  `reviewgeneralfeedback` mediumint(6) NOT NULL DEFAULT '0',
  `reviewrightanswer` mediumint(6) NOT NULL DEFAULT '0',
  `reviewoverallfeedback` mediumint(6) NOT NULL DEFAULT '0',
  `questionsperpage` bigint(10) NOT NULL DEFAULT '0',
  `shufflequestions` smallint(4) NOT NULL DEFAULT '0',
  `shuffleanswers` smallint(4) NOT NULL DEFAULT '0',
  `sumgrades` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `grade` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `timelimit` bigint(10) NOT NULL DEFAULT '0',
  `overduehandling` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'autoabandon',
  `graceperiod` bigint(10) NOT NULL DEFAULT '0',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `subnet` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `browsersecurity` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `delay1` bigint(10) NOT NULL DEFAULT '0',
  `delay2` bigint(10) NOT NULL DEFAULT '0',
  `showuserpicture` smallint(4) NOT NULL DEFAULT '0',
  `showblocks` smallint(4) NOT NULL DEFAULT '0',
  `navmethod` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'free'
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Main information about each quiz';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_attempts`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_attempts` (
  `id` bigint(10) NOT NULL,
  `uniqueid` bigint(10) NOT NULL DEFAULT '0',
  `quiz` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `attempt` mediumint(6) NOT NULL DEFAULT '0',
  `sumgrades` decimal(10,5) DEFAULT NULL,
  `timestart` bigint(10) NOT NULL DEFAULT '0',
  `timefinish` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `timecheckstate` bigint(10) DEFAULT '0',
  `layout` longtext COLLATE utf8_unicode_ci NOT NULL,
  `preview` smallint(3) NOT NULL DEFAULT '0',
  `state` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'inprogress',
  `currentpage` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=30613 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores various attempts on a quiz';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_feedback`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_feedback` (
  `id` bigint(10) NOT NULL,
  `quizid` bigint(10) NOT NULL DEFAULT '0',
  `feedbacktext` longtext COLLATE utf8_unicode_ci NOT NULL,
  `feedbacktextformat` tinyint(2) NOT NULL DEFAULT '0',
  `mingrade` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `maxgrade` decimal(10,5) NOT NULL DEFAULT '0.00000'
) ENGINE=InnoDB AUTO_INCREMENT=597 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Feedback given to students based on which grade band their o';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_grades`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_grades` (
  `id` bigint(10) NOT NULL,
  `quiz` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `grade` decimal(10,5) NOT NULL DEFAULT '0.00000',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=22405 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The overall grade for each user on the quiz, based on their ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_overrides`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_overrides` (
  `id` bigint(10) NOT NULL,
  `quiz` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) DEFAULT NULL,
  `userid` bigint(10) DEFAULT NULL,
  `timeopen` bigint(10) DEFAULT NULL,
  `timeclose` bigint(10) DEFAULT NULL,
  `timelimit` bigint(10) DEFAULT NULL,
  `attempts` mediumint(6) DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The overrides to quiz settings on a per-user and per-group b';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_overview_regrades`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_overview_regrades` (
  `id` bigint(10) NOT NULL,
  `questionusageid` bigint(10) NOT NULL,
  `slot` bigint(10) NOT NULL,
  `newfraction` decimal(12,7) DEFAULT NULL,
  `oldfraction` decimal(12,7) DEFAULT NULL,
  `regraded` smallint(4) NOT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table records which question attempts need regrading an';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_reports`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_reports` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `displayorder` bigint(10) NOT NULL,
  `capability` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Lists all the installed quiz reports and their display order';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_slots`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_slots` (
  `id` bigint(10) NOT NULL,
  `slot` bigint(10) NOT NULL,
  `quizid` bigint(10) NOT NULL DEFAULT '0',
  `page` bigint(10) NOT NULL,
  `questionid` bigint(10) NOT NULL DEFAULT '0',
  `maxmark` decimal(12,7) NOT NULL DEFAULT '0.0000000'
) ENGINE=InnoDB AUTO_INCREMENT=726 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the maximum possible grade (weight) for each question';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_quiz_statistics`
--

CREATE TABLE IF NOT EXISTS `mdl_quiz_statistics` (
  `id` bigint(10) NOT NULL,
  `hashcode` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `whichattempts` smallint(4) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `firstattemptscount` bigint(10) NOT NULL,
  `highestattemptscount` bigint(10) NOT NULL,
  `lastattemptscount` bigint(10) NOT NULL,
  `allattemptscount` bigint(10) NOT NULL,
  `firstattemptsavg` decimal(15,5) DEFAULT NULL,
  `highestattemptsavg` decimal(15,5) DEFAULT NULL,
  `lastattemptsavg` decimal(15,5) DEFAULT NULL,
  `allattemptsavg` decimal(15,5) DEFAULT NULL,
  `median` decimal(15,5) DEFAULT NULL,
  `standarddeviation` decimal(15,5) DEFAULT NULL,
  `skewness` decimal(15,10) DEFAULT NULL,
  `kurtosis` decimal(15,5) DEFAULT NULL,
  `cic` decimal(15,10) DEFAULT NULL,
  `errorratio` decimal(15,10) DEFAULT NULL,
  `standarderror` decimal(15,10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_rating`
--

CREATE TABLE IF NOT EXISTS `mdl_rating` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ratingarea` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemid` bigint(10) NOT NULL,
  `scaleid` bigint(10) NOT NULL,
  `rating` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='moodle ratings';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_registration_hubs`
--

CREATE TABLE IF NOT EXISTS `mdl_registration_hubs` (
  `id` bigint(10) NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hubname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `huburl` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `secret` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='hub where the site is registered on with their associated to';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_repository`
--

CREATE TABLE IF NOT EXISTS `mdl_repository` (
  `id` bigint(10) NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `visible` tinyint(1) DEFAULT '1',
  `sortorder` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table contains one entry for every configured external ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_repository_instances`
--

CREATE TABLE IF NOT EXISTS `mdl_repository_instances` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `typeid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `contextid` bigint(10) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timecreated` bigint(10) DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `readonly` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table contains one entry for every configured external ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_repository_instance_config`
--

CREATE TABLE IF NOT EXISTS `mdl_repository_instance_config` (
  `id` bigint(10) NOT NULL,
  `instanceid` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The config for intances';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_resource`
--

CREATE TABLE IF NOT EXISTS `mdl_resource` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `tobemigrated` smallint(4) NOT NULL DEFAULT '0',
  `legacyfiles` smallint(4) NOT NULL DEFAULT '0',
  `legacyfileslast` bigint(10) DEFAULT NULL,
  `display` smallint(4) NOT NULL DEFAULT '0',
  `displayoptions` longtext COLLATE utf8_unicode_ci,
  `filterfiles` smallint(4) NOT NULL DEFAULT '0',
  `revision` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=890 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each record is one resource and its config data';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_resource_old`
--

CREATE TABLE IF NOT EXISTS `mdl_resource_old` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `reference` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `alltext` longtext COLLATE utf8_unicode_ci NOT NULL,
  `popup` longtext COLLATE utf8_unicode_ci NOT NULL,
  `options` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `oldid` bigint(10) NOT NULL,
  `cmid` bigint(10) DEFAULT NULL,
  `newmodule` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL,
  `migrated` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='backup of all old resource instances from 1.9';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role`
--

CREATE TABLE IF NOT EXISTS `mdl_role` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `archetype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='moodle roles';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_allow_assign`
--

CREATE TABLE IF NOT EXISTS `mdl_role_allow_assign` (
  `id` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `allowassign` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='this defines what role can assign what role';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_allow_override`
--

CREATE TABLE IF NOT EXISTS `mdl_role_allow_override` (
  `id` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `allowoverride` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='this defines what role can override what role';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_allow_switch`
--

CREATE TABLE IF NOT EXISTS `mdl_role_allow_switch` (
  `id` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL,
  `allowswitch` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table stores which which other roles a user is allowed ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_assignments`
--

CREATE TABLE IF NOT EXISTS `mdl_role_assignments` (
  `id` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `contextid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `modifierid` bigint(10) NOT NULL DEFAULT '0',
  `component` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemid` bigint(10) NOT NULL DEFAULT '0',
  `sortorder` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=73546 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='assigning roles in different context';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_capabilities`
--

CREATE TABLE IF NOT EXISTS `mdl_role_capabilities` (
  `id` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL DEFAULT '0',
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `capability` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `permission` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `modifierid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=4911 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='permission has to be signed, overriding a capability for a p';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_context_levels`
--

CREATE TABLE IF NOT EXISTS `mdl_role_context_levels` (
  `id` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL,
  `contextlevel` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=353 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Lists which roles can be assigned at which context levels. T';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_names`
--

CREATE TABLE IF NOT EXISTS `mdl_role_names` (
  `id` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `contextid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='role names in native strings';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_role_sortorder`
--

CREATE TABLE IF NOT EXISTS `mdl_role_sortorder` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `roleid` bigint(10) NOT NULL,
  `contextid` bigint(10) NOT NULL,
  `sortoder` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='sort order of course managers in a course';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scale`
--

CREATE TABLE IF NOT EXISTS `mdl_scale` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `scale` longtext COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Defines grading scales';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scale_history`
--

CREATE TABLE IF NOT EXISTS `mdl_scale_history` (
  `id` bigint(10) NOT NULL,
  `action` bigint(10) NOT NULL DEFAULT '0',
  `oldid` bigint(10) NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timemodified` bigint(10) DEFAULT NULL,
  `loggeduser` bigint(10) DEFAULT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `scale` longtext COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='History table';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `scormtype` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'local',
  `reference` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `version` varchar(9) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `maxgrade` double NOT NULL DEFAULT '0',
  `grademethod` tinyint(2) NOT NULL DEFAULT '0',
  `whatgrade` bigint(10) NOT NULL DEFAULT '0',
  `maxattempt` bigint(10) NOT NULL DEFAULT '1',
  `forcecompleted` tinyint(1) NOT NULL DEFAULT '1',
  `forcenewattempt` tinyint(1) NOT NULL DEFAULT '0',
  `lastattemptlock` tinyint(1) NOT NULL DEFAULT '0',
  `displayattemptstatus` tinyint(1) NOT NULL DEFAULT '1',
  `displaycoursestructure` tinyint(1) NOT NULL DEFAULT '1',
  `updatefreq` tinyint(1) NOT NULL DEFAULT '0',
  `sha1hash` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `md5hash` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `revision` bigint(10) NOT NULL DEFAULT '0',
  `launch` bigint(10) NOT NULL DEFAULT '0',
  `skipview` tinyint(1) NOT NULL DEFAULT '1',
  `hidebrowse` tinyint(1) NOT NULL DEFAULT '0',
  `hidetoc` tinyint(1) NOT NULL DEFAULT '0',
  `nav` tinyint(1) NOT NULL DEFAULT '1',
  `navpositionleft` bigint(10) DEFAULT '-100',
  `navpositiontop` bigint(10) DEFAULT '-100',
  `auto` tinyint(1) NOT NULL DEFAULT '0',
  `popup` tinyint(1) NOT NULL DEFAULT '0',
  `options` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `width` bigint(10) NOT NULL DEFAULT '100',
  `height` bigint(10) NOT NULL DEFAULT '600',
  `timeopen` bigint(10) NOT NULL DEFAULT '0',
  `timeclose` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `completionstatusrequired` tinyint(1) DEFAULT NULL,
  `completionscorerequired` tinyint(2) DEFAULT NULL,
  `displayactivityname` smallint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='each table is one SCORM module and its configuration';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_aicc_session`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_aicc_session` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `scormid` bigint(10) NOT NULL DEFAULT '0',
  `hacpsession` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `scoid` bigint(10) DEFAULT '0',
  `scormmode` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `scormstatus` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attempt` bigint(10) DEFAULT NULL,
  `lessonstatus` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sessiontime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Used by AICC HACP to store session information';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_scoes`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_scoes` (
  `id` bigint(10) NOT NULL,
  `scorm` bigint(10) NOT NULL DEFAULT '0',
  `manifest` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `organization` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `parent` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `identifier` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `launch` longtext COLLATE utf8_unicode_ci NOT NULL,
  `scormtype` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sortorder` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=701 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='each SCO part of the SCORM module';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_scoes_data`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_scoes_data` (
  `id` bigint(10) NOT NULL,
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=754 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Contains variable data get from packages';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_scoes_track`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_scoes_track` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `scormid` bigint(10) NOT NULL DEFAULT '0',
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `attempt` bigint(10) NOT NULL DEFAULT '1',
  `element` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=395709 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='to track SCOes';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_seq_mapinfo`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_seq_mapinfo` (
  `id` bigint(10) NOT NULL,
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `objectiveid` bigint(10) NOT NULL DEFAULT '0',
  `targetobjectiveid` bigint(10) NOT NULL DEFAULT '0',
  `readsatisfiedstatus` tinyint(1) NOT NULL DEFAULT '1',
  `readnormalizedmeasure` tinyint(1) NOT NULL DEFAULT '1',
  `writesatisfiedstatus` tinyint(1) NOT NULL DEFAULT '0',
  `writenormalizedmeasure` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='SCORM2004 objective mapinfo description';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_seq_objective`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_seq_objective` (
  `id` bigint(10) NOT NULL,
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `primaryobj` tinyint(1) NOT NULL DEFAULT '0',
  `objectiveid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `satisfiedbymeasure` tinyint(1) NOT NULL DEFAULT '1',
  `minnormalizedmeasure` float(11,4) NOT NULL DEFAULT '0.0000'
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='SCORM2004 objective description';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_seq_rolluprule`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_seq_rolluprule` (
  `id` bigint(10) NOT NULL,
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `childactivityset` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `minimumcount` bigint(10) NOT NULL DEFAULT '0',
  `minimumpercent` float(11,4) NOT NULL DEFAULT '0.0000',
  `conditioncombination` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'all',
  `action` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='SCORM2004 sequencing rule';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_seq_rolluprulecond`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_seq_rolluprulecond` (
  `id` bigint(10) NOT NULL,
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `rollupruleid` bigint(10) NOT NULL DEFAULT '0',
  `operator` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'noOp',
  `cond` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='SCORM2004 sequencing rule';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_seq_rulecond`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_seq_rulecond` (
  `id` bigint(10) NOT NULL,
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `ruleconditionsid` bigint(10) NOT NULL DEFAULT '0',
  `refrencedobjective` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `measurethreshold` float(11,4) NOT NULL DEFAULT '0.0000',
  `operator` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'noOp',
  `cond` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'always'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='SCORM2004 rule condition';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_scorm_seq_ruleconds`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_seq_ruleconds` (
  `id` bigint(10) NOT NULL,
  `scoid` bigint(10) NOT NULL DEFAULT '0',
  `conditioncombination` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'all',
  `ruletype` tinyint(2) NOT NULL DEFAULT '0',
  `action` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='SCORM2004 rule conditions';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_sessions`
--

CREATE TABLE IF NOT EXISTS `mdl_sessions` (
  `id` bigint(10) NOT NULL,
  `state` bigint(10) NOT NULL DEFAULT '0',
  `sid` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `userid` bigint(10) NOT NULL,
  `sessdata` longtext COLLATE utf8_unicode_ci,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `firstip` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastip` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2385534 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Database based session storage - now recommended';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_stats_daily`
--

CREATE TABLE IF NOT EXISTS `mdl_stats_daily` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0',
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `stattype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'activity',
  `stat1` bigint(10) NOT NULL DEFAULT '0',
  `stat2` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1452338 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='to accumulate daily stats';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_stats_monthly`
--

CREATE TABLE IF NOT EXISTS `mdl_stats_monthly` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0',
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `stattype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'activity',
  `stat1` bigint(10) NOT NULL DEFAULT '0',
  `stat2` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=61399 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To accumulate monthly stats';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_stats_user_daily`
--

CREATE TABLE IF NOT EXISTS `mdl_stats_user_daily` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0',
  `statsreads` bigint(10) NOT NULL DEFAULT '0',
  `statswrites` bigint(10) NOT NULL DEFAULT '0',
  `stattype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=354858 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To accumulate daily stats per course/user';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_stats_user_monthly`
--

CREATE TABLE IF NOT EXISTS `mdl_stats_user_monthly` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0',
  `statsreads` bigint(10) NOT NULL DEFAULT '0',
  `statswrites` bigint(10) NOT NULL DEFAULT '0',
  `stattype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=183519 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To accumulate monthly stats per course/user';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_stats_user_weekly`
--

CREATE TABLE IF NOT EXISTS `mdl_stats_user_weekly` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0',
  `statsreads` bigint(10) NOT NULL DEFAULT '0',
  `statswrites` bigint(10) NOT NULL DEFAULT '0',
  `stattype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=251865 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To accumulate weekly stats per course/user';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_stats_weekly`
--

CREATE TABLE IF NOT EXISTS `mdl_stats_weekly` (
  `id` bigint(10) NOT NULL,
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '0',
  `roleid` bigint(10) NOT NULL DEFAULT '0',
  `stattype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'activity',
  `stat1` bigint(10) NOT NULL DEFAULT '0',
  `stat2` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=226902 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To accumulate weekly stats';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_survey`
--

CREATE TABLE IF NOT EXISTS `mdl_survey` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `template` bigint(10) NOT NULL DEFAULT '0',
  `days` mediumint(6) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci NOT NULL,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `questions` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Each record is one SURVEY module with its configuration';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_survey_analysis`
--

CREATE TABLE IF NOT EXISTS `mdl_survey_analysis` (
  `id` bigint(10) NOT NULL,
  `survey` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `notes` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='text about each survey submission';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_survey_answers`
--

CREATE TABLE IF NOT EXISTS `mdl_survey_answers` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `survey` bigint(10) NOT NULL DEFAULT '0',
  `question` bigint(10) NOT NULL DEFAULT '0',
  `time` bigint(10) NOT NULL DEFAULT '0',
  `answer1` longtext COLLATE utf8_unicode_ci NOT NULL,
  `answer2` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='the answers to each questions filled by the users';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_survey_questions`
--

CREATE TABLE IF NOT EXISTS `mdl_survey_questions` (
  `id` bigint(10) NOT NULL,
  `text` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `shorttext` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `multi` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` smallint(3) NOT NULL DEFAULT '0',
  `options` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='the questions conforming one survey';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_tag`
--

CREATE TABLE IF NOT EXISTS `mdl_tag` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rawname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tagtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0',
  `flag` smallint(4) DEFAULT '0',
  `timemodified` bigint(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tag table - this generic table will replace the old "tags" t';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_tag_correlation`
--

CREATE TABLE IF NOT EXISTS `mdl_tag_correlation` (
  `id` bigint(10) NOT NULL,
  `tagid` bigint(10) NOT NULL,
  `correlatedtags` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The rationale for the ''tag_correlation'' table is performance';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_tag_instance`
--

CREATE TABLE IF NOT EXISTS `mdl_tag_instance` (
  `id` bigint(10) NOT NULL,
  `tagid` bigint(10) NOT NULL,
  `component` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `itemid` bigint(10) NOT NULL,
  `contextid` bigint(10) DEFAULT NULL,
  `tiuserid` bigint(10) NOT NULL DEFAULT '0',
  `ordering` bigint(10) DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tag_instance table holds the information of associations bet';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_task_adhoc`
--

CREATE TABLE IF NOT EXISTS `mdl_task_adhoc` (
  `id` bigint(10) NOT NULL,
  `component` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `classname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `nextruntime` bigint(10) NOT NULL,
  `faildelay` bigint(10) DEFAULT NULL,
  `customdata` longtext COLLATE utf8_unicode_ci,
  `blocking` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_task_scheduled`
--

CREATE TABLE IF NOT EXISTS `mdl_task_scheduled` (
  `id` bigint(10) NOT NULL,
  `component` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `classname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastruntime` bigint(10) DEFAULT NULL,
  `nextruntime` bigint(10) DEFAULT NULL,
  `blocking` tinyint(2) NOT NULL DEFAULT '0',
  `minute` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hour` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `day` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `month` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `dayofweek` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `faildelay` bigint(10) DEFAULT NULL,
  `customised` tinyint(2) NOT NULL DEFAULT '0',
  `disabled` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_timezone`
--

CREATE TABLE IF NOT EXISTS `mdl_timezone` (
  `id` bigint(10) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `year` bigint(11) NOT NULL DEFAULT '0',
  `tzrule` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gmtoff` bigint(11) NOT NULL DEFAULT '0',
  `dstoff` bigint(11) NOT NULL DEFAULT '0',
  `dst_month` tinyint(2) NOT NULL DEFAULT '0',
  `dst_startday` smallint(3) NOT NULL DEFAULT '0',
  `dst_weekday` smallint(3) NOT NULL DEFAULT '0',
  `dst_skipweeks` smallint(3) NOT NULL DEFAULT '0',
  `dst_time` varchar(6) COLLATE utf8_unicode_ci NOT NULL DEFAULT '00:00',
  `std_month` tinyint(2) NOT NULL DEFAULT '0',
  `std_startday` smallint(3) NOT NULL DEFAULT '0',
  `std_weekday` smallint(3) NOT NULL DEFAULT '0',
  `std_skipweeks` smallint(3) NOT NULL DEFAULT '0',
  `std_time` varchar(6) COLLATE utf8_unicode_ci NOT NULL DEFAULT '00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Rules for calculating local wall clock time for users';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_tool_customlang`
--

CREATE TABLE IF NOT EXISTS `mdl_tool_customlang` (
  `id` bigint(10) NOT NULL,
  `lang` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `componentid` bigint(10) NOT NULL,
  `stringid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `original` longtext COLLATE utf8_unicode_ci NOT NULL,
  `master` longtext COLLATE utf8_unicode_ci,
  `local` longtext COLLATE utf8_unicode_ci,
  `timemodified` bigint(10) NOT NULL,
  `timecustomized` bigint(10) DEFAULT NULL,
  `outdated` smallint(3) DEFAULT '0',
  `modified` smallint(3) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=25610 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Contains the working checkout of all strings and their custo';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_tool_customlang_components`
--

CREATE TABLE IF NOT EXISTS `mdl_tool_customlang_components` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=468 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Contains the list of all installed plugins that provide thei';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_tool_mergeusers`
--

CREATE TABLE IF NOT EXISTS `mdl_tool_mergeusers` (
  `id` bigint(10) NOT NULL,
  `touserid` bigint(10) NOT NULL,
  `fromuserid` bigint(10) NOT NULL,
  `success` smallint(4) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `log` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='List of merged users: data from fromuserid user is merged in';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_upgrade_log`
--

CREATE TABLE IF NOT EXISTS `mdl_upgrade_log` (
  `id` bigint(10) NOT NULL,
  `type` bigint(10) NOT NULL,
  `plugin` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `targetversion` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `info` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `details` longtext COLLATE utf8_unicode_ci,
  `backtrace` longtext COLLATE utf8_unicode_ci,
  `userid` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2285 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Upgrade logging';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_url`
--

CREATE TABLE IF NOT EXISTS `mdl_url` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `externalurl` longtext COLLATE utf8_unicode_ci NOT NULL,
  `display` smallint(4) NOT NULL DEFAULT '0',
  `displayoptions` longtext COLLATE utf8_unicode_ci,
  `parameters` longtext COLLATE utf8_unicode_ci,
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=998 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='each record is one url resource';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_url_new`
--

CREATE TABLE IF NOT EXISTS `mdl_url_new` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `externalurl` longtext COLLATE utf8_unicode_ci NOT NULL,
  `display` smallint(4) NOT NULL DEFAULT '0',
  `displayoptions` longtext COLLATE utf8_unicode_ci,
  `parameters` longtext COLLATE utf8_unicode_ci,
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='each record is one url resource';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user`
--

CREATE TABLE IF NOT EXISTS `mdl_user` (
  `id` bigint(10) NOT NULL,
  `auth` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'manual',
  `confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `policyagreed` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `suspended` tinyint(1) NOT NULL DEFAULT '0',
  `mnethostid` bigint(10) NOT NULL DEFAULT '0',
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `idnumber` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `firstname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `emailstop` tinyint(1) NOT NULL DEFAULT '0',
  `icq` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `skype` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `yahoo` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `aim` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `msn` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `phone1` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `phone2` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `institution` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `department` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(120) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `country` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lang` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `theme` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timezone` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '99',
  `firstaccess` bigint(10) NOT NULL DEFAULT '0',
  `lastaccess` bigint(10) NOT NULL DEFAULT '0',
  `lastlogin` bigint(10) NOT NULL DEFAULT '0',
  `currentlogin` bigint(10) NOT NULL DEFAULT '0',
  `lastip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `secret` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `picture` bigint(10) NOT NULL DEFAULT '0',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '1',
  `mailformat` tinyint(1) NOT NULL DEFAULT '1',
  `maildigest` tinyint(1) NOT NULL DEFAULT '0',
  `maildisplay` tinyint(2) NOT NULL DEFAULT '2',
  `autosubscribe` tinyint(1) NOT NULL DEFAULT '1',
  `trackforums` tinyint(1) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `trustbitmask` bigint(10) NOT NULL DEFAULT '0',
  `imagealt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastnamephonetic` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstnamephonetic` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `middlename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alternatename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `calendartype` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'gregorian'
) ENGINE=InnoDB AUTO_INCREMENT=13699 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='One record for each person';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_devices`
--

CREATE TABLE IF NOT EXISTS `mdl_user_devices` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `appid` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `model` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `platform` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `version` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pushid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_enrolments`
--

CREATE TABLE IF NOT EXISTS `mdl_user_enrolments` (
  `id` bigint(10) NOT NULL,
  `status` bigint(10) NOT NULL DEFAULT '0',
  `enrolid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `timestart` bigint(10) NOT NULL DEFAULT '0',
  `timeend` bigint(10) NOT NULL DEFAULT '2147483647',
  `modifierid` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=63035 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Users participating in courses (aka enrolled users) - everyb';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_info_category`
--

CREATE TABLE IF NOT EXISTS `mdl_user_info_category` (
  `id` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sortorder` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Customisable fields categories';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_info_data`
--

CREATE TABLE IF NOT EXISTS `mdl_user_info_data` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `fieldid` bigint(10) NOT NULL DEFAULT '0',
  `data` longtext COLLATE utf8_unicode_ci,
  `dataformat` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=21368 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Data for the customisable user fields';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_info_field`
--

CREATE TABLE IF NOT EXISTS `mdl_user_info_field` (
  `id` bigint(10) NOT NULL,
  `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'shortname',
  `name` longtext COLLATE utf8_unicode_ci NOT NULL,
  `datatype` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` tinyint(2) NOT NULL DEFAULT '0',
  `categoryid` bigint(10) NOT NULL DEFAULT '0',
  `sortorder` bigint(10) NOT NULL DEFAULT '0',
  `required` tinyint(2) NOT NULL DEFAULT '0',
  `locked` tinyint(2) NOT NULL DEFAULT '0',
  `visible` smallint(4) NOT NULL DEFAULT '0',
  `forceunique` tinyint(2) NOT NULL DEFAULT '0',
  `signup` tinyint(2) NOT NULL DEFAULT '0',
  `defaultdata` longtext COLLATE utf8_unicode_ci,
  `defaultdataformat` tinyint(2) NOT NULL DEFAULT '0',
  `param1` longtext COLLATE utf8_unicode_ci,
  `param2` longtext COLLATE utf8_unicode_ci,
  `param3` longtext COLLATE utf8_unicode_ci,
  `param4` longtext COLLATE utf8_unicode_ci,
  `param5` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='Customisable user profile fields';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_lastaccess`
--

CREATE TABLE IF NOT EXISTS `mdl_user_lastaccess` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `courseid` bigint(10) NOT NULL DEFAULT '0',
  `timeaccess` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=36956 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='To keep track of course page access times, used in online pa';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_password_resets`
--

CREATE TABLE IF NOT EXISTS `mdl_user_password_resets` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `timerequested` bigint(10) NOT NULL,
  `timererequested` bigint(10) NOT NULL DEFAULT '0',
  `token` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_preferences`
--

CREATE TABLE IF NOT EXISTS `mdl_user_preferences` (
  `id` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(1333) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=11987 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Allows modules to store arbitrary user preferences';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_user_private_key`
--

CREATE TABLE IF NOT EXISTS `mdl_user_private_key` (
  `id` bigint(10) NOT NULL,
  `script` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `userid` bigint(10) NOT NULL,
  `instance` bigint(10) DEFAULT NULL,
  `iprestriction` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `validuntil` bigint(10) DEFAULT NULL,
  `timecreated` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='access keys used in cookieless scripts - rss, etc.';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_webdav_locks`
--

CREATE TABLE IF NOT EXISTS `mdl_webdav_locks` (
  `id` bigint(10) NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `expiry` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `recursive` tinyint(1) NOT NULL DEFAULT '0',
  `exclusivelock` tinyint(1) NOT NULL DEFAULT '0',
  `created` bigint(10) NOT NULL DEFAULT '0',
  `modified` bigint(10) NOT NULL DEFAULT '0',
  `owner` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Resource locks for WebDAV users';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_wiki`
--

CREATE TABLE IF NOT EXISTS `mdl_wiki` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Wiki',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(4) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `firstpagetitle` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'First Page',
  `wikimode` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'collaborative',
  `defaultformat` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'creole',
  `forceformat` tinyint(1) NOT NULL DEFAULT '1',
  `editbegin` bigint(10) NOT NULL DEFAULT '0',
  `editend` bigint(10) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores Wiki activity configuration';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_wiki_links`
--

CREATE TABLE IF NOT EXISTS `mdl_wiki_links` (
  `id` bigint(10) NOT NULL,
  `subwikiid` bigint(10) NOT NULL DEFAULT '0',
  `frompageid` bigint(10) NOT NULL DEFAULT '0',
  `topageid` bigint(10) NOT NULL DEFAULT '0',
  `tomissingpage` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Page wiki links';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_wiki_locks`
--

CREATE TABLE IF NOT EXISTS `mdl_wiki_locks` (
  `id` bigint(10) NOT NULL,
  `pageid` bigint(10) NOT NULL DEFAULT '0',
  `sectionname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `lockedat` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=804 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Manages page locks';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_wiki_pages`
--

CREATE TABLE IF NOT EXISTS `mdl_wiki_pages` (
  `id` bigint(10) NOT NULL,
  `subwikiid` bigint(10) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'title',
  `cachedcontent` longtext COLLATE utf8_unicode_ci NOT NULL,
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `timerendered` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `pageviews` bigint(10) NOT NULL DEFAULT '0',
  `readonly` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores wiki pages';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_wiki_subwikis`
--

CREATE TABLE IF NOT EXISTS `mdl_wiki_subwikis` (
  `id` bigint(10) NOT NULL,
  `wikiid` bigint(10) NOT NULL DEFAULT '0',
  `groupid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores subwiki instances';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_wiki_synonyms`
--

CREATE TABLE IF NOT EXISTS `mdl_wiki_synonyms` (
  `id` bigint(10) NOT NULL,
  `subwikiid` bigint(10) NOT NULL DEFAULT '0',
  `pageid` bigint(10) NOT NULL DEFAULT '0',
  `pagesynonym` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pagesynonym'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores wiki pages synonyms';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_wiki_versions`
--

CREATE TABLE IF NOT EXISTS `mdl_wiki_versions` (
  `id` bigint(10) NOT NULL,
  `pageid` bigint(10) NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `contentformat` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'creole',
  `version` mediumint(5) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=473 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores wiki page history';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `intro` longtext COLLATE utf8_unicode_ci,
  `introformat` smallint(3) NOT NULL DEFAULT '0',
  `instructauthors` longtext COLLATE utf8_unicode_ci,
  `instructauthorsformat` smallint(3) NOT NULL DEFAULT '0',
  `instructreviewers` longtext COLLATE utf8_unicode_ci,
  `instructreviewersformat` smallint(3) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL,
  `phase` smallint(3) DEFAULT '0',
  `useexamples` tinyint(2) DEFAULT '0',
  `usepeerassessment` tinyint(2) DEFAULT '0',
  `useselfassessment` tinyint(2) DEFAULT '0',
  `grade` decimal(10,5) DEFAULT '80.00000',
  `gradinggrade` decimal(10,5) DEFAULT '20.00000',
  `strategy` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `evaluation` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gradedecimals` smallint(3) DEFAULT '0',
  `nattachments` smallint(3) DEFAULT '0',
  `latesubmissions` tinyint(2) DEFAULT '0',
  `maxbytes` bigint(10) DEFAULT '100000',
  `examplesmode` smallint(3) DEFAULT '0',
  `submissionstart` bigint(10) DEFAULT '0',
  `submissionend` bigint(10) DEFAULT '0',
  `assessmentstart` bigint(10) DEFAULT '0',
  `assessmentend` bigint(10) DEFAULT '0',
  `phaseswitchassessment` tinyint(2) NOT NULL DEFAULT '0',
  `conclusion` longtext COLLATE utf8_unicode_ci,
  `conclusionformat` smallint(3) NOT NULL DEFAULT '1',
  `overallfeedbackmode` smallint(3) DEFAULT '1',
  `overallfeedbackfiles` smallint(3) DEFAULT '0',
  `overallfeedbackmaxbytes` bigint(10) DEFAULT '100000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This table keeps information about the module instances and ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopallocation_scheduled`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopallocation_scheduled` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `enabled` tinyint(2) NOT NULL DEFAULT '0',
  `submissionend` bigint(10) NOT NULL,
  `timeallocated` bigint(10) DEFAULT NULL,
  `settings` longtext COLLATE utf8_unicode_ci,
  `resultstatus` bigint(10) DEFAULT NULL,
  `resultmessage` varchar(1333) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resultlog` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores the allocation settings for the scheduled allocator';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopeval_best_settings`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopeval_best_settings` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `comparison` smallint(3) DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Settings for the grading evaluation subplugin Comparison wit';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopform_accumulative`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopform_accumulative` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `sort` bigint(10) DEFAULT '0',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` smallint(3) DEFAULT '0',
  `grade` bigint(10) NOT NULL,
  `weight` mediumint(5) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The assessment dimensions definitions of Accumulative gradin';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopform_comments`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopform_comments` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `sort` bigint(10) DEFAULT '0',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` smallint(3) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The assessment dimensions definitions of Comments strategy f';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopform_numerrors`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopform_numerrors` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `sort` bigint(10) DEFAULT '0',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` smallint(3) DEFAULT '0',
  `descriptiontrust` bigint(10) DEFAULT NULL,
  `grade0` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `grade1` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `weight` mediumint(5) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The assessment dimensions definitions of Number of errors gr';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopform_numerrors_map`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopform_numerrors_map` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `nonegative` bigint(10) NOT NULL,
  `grade` decimal(10,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='This maps the number of errors to a percentual grade for sub';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopform_rubric`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopform_rubric` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `sort` bigint(10) DEFAULT '0',
  `description` longtext COLLATE utf8_unicode_ci,
  `descriptionformat` smallint(3) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The assessment dimensions definitions of Rubric grading stra';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopform_rubric_config`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopform_rubric_config` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `layout` varchar(30) COLLATE utf8_unicode_ci DEFAULT 'list'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Configuration table for the Rubric grading strategy';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshopform_rubric_levels`
--

CREATE TABLE IF NOT EXISTS `mdl_workshopform_rubric_levels` (
  `id` bigint(10) NOT NULL,
  `dimensionid` bigint(10) NOT NULL,
  `grade` decimal(10,5) NOT NULL,
  `definition` longtext COLLATE utf8_unicode_ci,
  `definitionformat` smallint(3) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='The definition of rubric rating scales';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_aggregations`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_aggregations` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `gradinggrade` decimal(10,5) DEFAULT NULL,
  `timegraded` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Aggregated grades for assessment are stored here. The aggreg';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_assessments`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_assessments` (
  `id` bigint(10) NOT NULL,
  `submissionid` bigint(10) NOT NULL,
  `reviewerid` bigint(10) NOT NULL,
  `weight` bigint(10) NOT NULL DEFAULT '1',
  `timecreated` bigint(10) DEFAULT '0',
  `timemodified` bigint(10) DEFAULT '0',
  `grade` decimal(10,5) DEFAULT NULL,
  `gradinggrade` decimal(10,5) DEFAULT NULL,
  `gradinggradeover` decimal(10,5) DEFAULT NULL,
  `gradinggradeoverby` bigint(10) DEFAULT NULL,
  `feedbackauthor` longtext COLLATE utf8_unicode_ci,
  `feedbackauthorformat` smallint(3) DEFAULT '0',
  `feedbackauthorattachment` smallint(3) DEFAULT '0',
  `feedbackreviewer` longtext COLLATE utf8_unicode_ci,
  `feedbackreviewerformat` smallint(3) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about the made assessment and automatically calculated ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_assessments_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_assessments_old` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL DEFAULT '0',
  `submissionid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `timegraded` bigint(10) NOT NULL DEFAULT '0',
  `timeagreed` bigint(10) NOT NULL DEFAULT '0',
  `grade` double NOT NULL DEFAULT '0',
  `gradinggrade` smallint(3) NOT NULL DEFAULT '0',
  `teachergraded` smallint(3) NOT NULL DEFAULT '0',
  `mailed` smallint(3) NOT NULL DEFAULT '0',
  `resubmission` smallint(3) NOT NULL DEFAULT '0',
  `donotuse` smallint(3) NOT NULL DEFAULT '0',
  `generalcomment` longtext COLLATE utf8_unicode_ci,
  `teachercomment` longtext COLLATE utf8_unicode_ci,
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop_assessments table to be dropped later in Moo';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_comments_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_comments_old` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL DEFAULT '0',
  `assessmentid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `mailed` tinyint(2) NOT NULL DEFAULT '0',
  `comments` longtext COLLATE utf8_unicode_ci NOT NULL,
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop_comments table to be dropped later in Moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_elements_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_elements_old` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL DEFAULT '0',
  `elementno` smallint(3) NOT NULL DEFAULT '0',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `scale` smallint(3) NOT NULL DEFAULT '0',
  `maxscore` smallint(3) NOT NULL DEFAULT '1',
  `weight` smallint(3) NOT NULL DEFAULT '11',
  `stddev` double NOT NULL DEFAULT '0',
  `totalassessments` bigint(10) NOT NULL DEFAULT '0',
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop_elements table to be dropped later in Moodle';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_grades`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_grades` (
  `id` bigint(10) NOT NULL,
  `assessmentid` bigint(10) NOT NULL,
  `strategy` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `dimensionid` bigint(10) NOT NULL,
  `grade` decimal(10,5) NOT NULL,
  `peercomment` longtext COLLATE utf8_unicode_ci,
  `peercommentformat` smallint(3) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='How the reviewers filled-up the grading forms, given grades ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_grades_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_grades_old` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL DEFAULT '0',
  `assessmentid` bigint(10) NOT NULL DEFAULT '0',
  `elementno` bigint(10) NOT NULL DEFAULT '0',
  `feedback` longtext COLLATE utf8_unicode_ci NOT NULL,
  `grade` smallint(3) NOT NULL DEFAULT '0',
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop_grades table to be dropped later in Moodle 2';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_old` (
  `id` bigint(10) NOT NULL,
  `course` bigint(10) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `wtype` smallint(3) NOT NULL DEFAULT '0',
  `nelements` smallint(3) NOT NULL DEFAULT '1',
  `nattachments` smallint(3) NOT NULL DEFAULT '0',
  `phase` tinyint(2) NOT NULL DEFAULT '0',
  `format` tinyint(2) NOT NULL DEFAULT '0',
  `gradingstrategy` tinyint(2) NOT NULL DEFAULT '1',
  `resubmit` tinyint(2) NOT NULL DEFAULT '0',
  `agreeassessments` tinyint(2) NOT NULL DEFAULT '0',
  `hidegrades` tinyint(2) NOT NULL DEFAULT '0',
  `anonymous` tinyint(2) NOT NULL DEFAULT '0',
  `includeself` tinyint(2) NOT NULL DEFAULT '0',
  `maxbytes` bigint(10) NOT NULL DEFAULT '100000',
  `submissionstart` bigint(10) NOT NULL DEFAULT '0',
  `assessmentstart` bigint(10) NOT NULL DEFAULT '0',
  `submissionend` bigint(10) NOT NULL DEFAULT '0',
  `assessmentend` bigint(10) NOT NULL DEFAULT '0',
  `releasegrades` bigint(10) NOT NULL DEFAULT '0',
  `grade` smallint(3) NOT NULL DEFAULT '0',
  `gradinggrade` smallint(3) NOT NULL DEFAULT '0',
  `ntassessments` smallint(3) NOT NULL DEFAULT '0',
  `assessmentcomps` smallint(3) NOT NULL DEFAULT '2',
  `nsassessments` smallint(3) NOT NULL DEFAULT '0',
  `overallocation` smallint(3) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) NOT NULL DEFAULT '0',
  `teacherweight` smallint(3) NOT NULL DEFAULT '1',
  `showleaguetable` smallint(3) NOT NULL DEFAULT '0',
  `usepassword` smallint(3) NOT NULL DEFAULT '0',
  `password` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop table to be dropped later in Moodle 2.x';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_rubrics_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_rubrics_old` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL DEFAULT '0',
  `elementno` bigint(10) NOT NULL DEFAULT '0',
  `rubricno` smallint(3) NOT NULL DEFAULT '0',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop_rubrics table to be dropped later in Moodle ';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_stockcomments_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_stockcomments_old` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL DEFAULT '0',
  `elementno` bigint(10) NOT NULL DEFAULT '0',
  `comments` longtext COLLATE utf8_unicode_ci NOT NULL,
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop_stockcomments table to be dropped later in M';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_submissions`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_submissions` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL,
  `example` tinyint(2) DEFAULT '0',
  `authorid` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timemodified` bigint(10) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content` longtext COLLATE utf8_unicode_ci,
  `contentformat` smallint(3) NOT NULL DEFAULT '0',
  `contenttrust` smallint(3) NOT NULL DEFAULT '0',
  `attachment` tinyint(2) DEFAULT '0',
  `grade` decimal(10,5) DEFAULT NULL,
  `gradeover` decimal(10,5) DEFAULT NULL,
  `gradeoverby` bigint(10) DEFAULT NULL,
  `feedbackauthor` longtext COLLATE utf8_unicode_ci,
  `feedbackauthorformat` smallint(3) DEFAULT '0',
  `timegraded` bigint(10) DEFAULT NULL,
  `published` tinyint(2) DEFAULT '0',
  `late` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about the submission and the aggregation of the grade f';

-- --------------------------------------------------------

--
-- Struttura della tabella `mdl_workshop_submissions_old`
--

CREATE TABLE IF NOT EXISTS `mdl_workshop_submissions_old` (
  `id` bigint(10) NOT NULL,
  `workshopid` bigint(10) NOT NULL DEFAULT '0',
  `userid` bigint(10) NOT NULL DEFAULT '0',
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timecreated` bigint(10) NOT NULL DEFAULT '0',
  `mailed` tinyint(2) NOT NULL DEFAULT '0',
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `gradinggrade` smallint(3) NOT NULL DEFAULT '0',
  `finalgrade` smallint(3) NOT NULL DEFAULT '0',
  `late` smallint(3) NOT NULL DEFAULT '0',
  `nassessments` bigint(10) NOT NULL DEFAULT '0',
  `newplugin` varchar(28) COLLATE utf8_unicode_ci DEFAULT NULL,
  `newid` bigint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Legacy workshop_submissions table to be dropped later in Moo';

-- --------------------------------------------------------

--
-- Struttura della tabella `monitor_config`
--

CREATE TABLE IF NOT EXISTS `monitor_config` (
  `config_id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `monitor_log`
--

CREATE TABLE IF NOT EXISTS `monitor_log` (
  `log_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `type` enum('status','email','sms') NOT NULL,
  `message` varchar(255) NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` varchar(255) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `monitor_servers`
--

CREATE TABLE IF NOT EXISTS `monitor_servers` (
  `server_id` int(11) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `port` int(5) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` enum('service','website') NOT NULL DEFAULT 'service',
  `status` enum('on','off') NOT NULL DEFAULT 'on',
  `error` varchar(255) NOT NULL,
  `rtime` float(9,7) NOT NULL,
  `last_online` datetime NOT NULL,
  `last_check` datetime NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes',
  `email` enum('yes','no') NOT NULL DEFAULT 'yes',
  `sms` enum('yes','no') NOT NULL DEFAULT 'no'
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `monitor_users`
--

CREATE TABLE IF NOT EXISTS `monitor_users` (
  `user_id` int(11) NOT NULL,
  `server_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_cf_da_ignorare`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_cf_da_ignorare` (
  `ID` int(11) NOT NULL,
  `CODICE_FISCALE` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `NOME_FUNCTION` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `NOTA` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11685 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_dipendenti_da_elaborare`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_dipendenti_da_elaborare` (
  `CODICE_FISCALE` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `NOME` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `COGNOME` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EX_MATRICOLA` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SESSO` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ENTE` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EMAIL` varchar(240) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DA_VERIFICARE` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CODICE_POSIZ_ECONOM` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CODICE_STRUTTURA` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FLAG_INDIVIDUALI` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Moodle_id_sesso` int(11) DEFAULT NULL,
  `Moodle_sesso` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Moodle_id_categoria` int(11) DEFAULT NULL,
  `Moodle_categoria` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Moodle_flag_mantieni_precedente_categoria` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Moodle_id_ap` int(11) DEFAULT NULL,
  `Moodle_ap` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Moodle_cohort_id_macrocategoria` int(11) DEFAULT NULL,
  `Moodle_cohort_id_individuali` int(11) DEFAULT NULL,
  `Moodle_org_id` int(11) DEFAULT NULL,
  `Moodle_cohort_id_G_C_E` int(11) DEFAULT NULL,
  `FLAG_GESTIONE` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NOTA` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_forzature_forma`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_forzature_forma` (
  `CODICE_FISCALE` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `COGNOME` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `NOME` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `MATRICOLA` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ORG_SHORTNAME` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `DATA_FINE` datetime NOT NULL,
  `NOTA` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_grep_feed_back`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_grep_feed_back` (
  `id` int(11) NOT NULL,
  `operazione` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `stato` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nota_1` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nota_2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nota_3` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nota_4` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='tabella di appoggio per pagina feed-back gestione report';

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_grfo_feed_back`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_grfo_feed_back` (
  `id` int(11) NOT NULL,
  `id_corso` int(11) NOT NULL,
  `cod_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `operazione` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `stato` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `flag_parametro_id_corso` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `nota` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_grfo_log`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_grfo_log` (
  `id` int(11) NOT NULL,
  `data` datetime NOT NULL,
  `id_corso` int(11) NOT NULL,
  `cod_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pagina` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `livello_msg` int(11) NOT NULL,
  `cod_msg` int(11) NOT NULL,
  `descr_msg` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `utente` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nota` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_mapping_categorie`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_mapping_categorie` (
  `id` bigint(10) unsigned NOT NULL,
  `codice_posiz_econom` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `descrizione` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mdl_user_info_data_data_categoria` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_user_info_data_data_ap` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_cohort_name_macrocategoria` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_cohort_id_macrocategoria` bigint(11) NOT NULL,
  `flag_mantieni_precedente` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `flag_piano_di_studi` varchar(1) COLLATE utf8_unicode_ci DEFAULT 'S',
  `nota` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_mapping_org`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_mapping_org` (
  `CODICE_STRUTTURA` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `ENTE` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `DENOMINAZIONE` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_org_id` int(11) NOT NULL,
  `mdl_org_shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_org_fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `direzione_mdl_org_id` int(11) NOT NULL,
  `direzione_mdl_org_shortname` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `direzione_mdl_org_fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_cohort_name_G_C_E` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_cohort_id_G_C_E` int(11) NOT NULL,
  `STATO` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `NOTA` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_mapping_sesso`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_mapping_sesso` (
  `SESSO` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `mdl_user_info_data_fieldid_sesso` int(11) NOT NULL,
  `mdl_user_info_data_data_sesso` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `NOTA` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_budget_individuali`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_budget_individuali` (
  `anno` int(11) NOT NULL,
  `tipo_budget` int(11) NOT NULL,
  `descr_budget` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `orgfk_direzione` bigint(20) NOT NULL,
  `cod_direzione` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `descr_direzione` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `giunta_consiglio` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `budget` double NOT NULL,
  `impegnato_con_determina` double DEFAULT NULL,
  `impegnato_senza_determina` double DEFAULT NULL,
  `totale_impegnato` double DEFAULT NULL,
  `disponibile` double DEFAULT NULL,
  `totale_numero_corsi` int(11) DEFAULT NULL,
  `numero_corsi_con_determina` int(11) DEFAULT NULL,
  `numero_corsi_senza_determina` int(11) DEFAULT NULL,
  `numero_corsi_con_costo` int(11) DEFAULT NULL,
  `numero_corsi_con_cassa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_cf_parametri`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_cf_parametri` (
  `codice_fiscale` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `matricola` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cognome` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ruolo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `like_cod_direzione` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nota` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_completamento_corsi_on_line`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_completamento_corsi_on_line` (
  `id_corso` bigint(10) unsigned NOT NULL,
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `id_edizione` bigint(10) unsigned NOT NULL,
  `edizione` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio_edizione` date NOT NULL,
  `data_elaborazione` date NOT NULL,
  `id_user` bigint(10) unsigned NOT NULL,
  `cognome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `matricola` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `codice_fiscale` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `cod_struttura` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `descr_struttura` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `l_data_inizio_fruizione` bigint(10) unsigned DEFAULT NULL,
  `data_inizio_fruizione` date DEFAULT NULL,
  `l_data_fine_fruizione` bigint(10) unsigned DEFAULT NULL,
  `data_fine_fruizione` date DEFAULT NULL,
  `punteggio` double DEFAULT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_edizioni_corsi_on_line`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_edizioni_corsi_on_line` (
  `id_corso` bigint(10) unsigned NOT NULL,
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `id_edizione` bigint(10) unsigned NOT NULL,
  `edizione` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio` date NOT NULL,
  `flag_monitorata_S_N` char(1) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_moduli_corsi_on_line`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_moduli_corsi_on_line` (
  `id_corso` bigint(10) unsigned NOT NULL,
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `progressivo` tinyint(3) unsigned NOT NULL,
  `id_modulo` bigint(10) unsigned NOT NULL,
  `tipo_modulo` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `istanza_modulo` bigint(10) unsigned NOT NULL,
  `nome_modulo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `visibile` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `monitorabile` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `posizione_in_report` tinyint(4) DEFAULT NULL,
  `flag_punteggio_finale` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_monitoraggio_corsi_on_line`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_monitoraggio_corsi_on_line` (
  `id_corso` bigint(10) unsigned NOT NULL,
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `id_edizione` bigint(10) unsigned NOT NULL,
  `edizione` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio` date NOT NULL,
  `data_elaborazione` date NOT NULL,
  `id_user` bigint(10) unsigned NOT NULL,
  `cognome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `codice_fiscale` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `cod_struttura` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `descr_struttura` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_01_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_01_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_01_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_01_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_02_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_02_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_02_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_02_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_03_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_03_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_03_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_03_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_04_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_04_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_04_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_04_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_05_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_05_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_05_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_05_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_06_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_06_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_06_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_06_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_07_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_07_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_07_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_07_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_08_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_08_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_08_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_08_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_09_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_09_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_09_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_09_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_10_nome_risorsa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ris_10_tipo_risorsa` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_10_stato_fruizione` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ris_10_punteggio` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_piani_di_studio`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_piani_di_studio` (
  `matricola` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `cognome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `org_id_struttura` bigint(10) NOT NULL,
  `org_shortname_struttura` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `org_fullname_struttura` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `org_id_direzione` bigint(10) NOT NULL,
  `org_shortname_direzione` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `org_fullname_direzione` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `codice_categoria` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `sf_tot_ric` decimal(5,2) NOT NULL,
  `sf1_ric` decimal(5,2) NOT NULL,
  `sf2_ric` decimal(5,2) NOT NULL,
  `sfj_ric` decimal(5,2) NOT NULL,
  `prima_data_inizio_elab` datetime NOT NULL,
  `prima_data_fine_elab` datetime NOT NULL,
  `prima_perc_compl_piano` decimal(6,2) NOT NULL,
  `non_completato_prima_data_int` int(11) NOT NULL,
  `completato_prima_data_int` int(11) NOT NULL,
  `completato_prima_data_char` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `punti_prima_data` int(11) NOT NULL,
  `primo_sf_tot_ott` decimal(5,2) NOT NULL,
  `primo_sf1_ott` decimal(5,2) NOT NULL,
  `primo_sf2_ott` decimal(5,2) NOT NULL,
  `primo_sfj_ott` decimal(5,2) NOT NULL,
  `primo_sf_tot_util` decimal(5,2) NOT NULL,
  `primo_sf1_util` decimal(5,2) NOT NULL,
  `primo_sf2_util` decimal(5,2) NOT NULL,
  `primo_sfj_util` decimal(5,2) NOT NULL,
  `seconda_data_inizio_elab` datetime NOT NULL,
  `seconda_data_fine_elab` datetime NOT NULL,
  `seconda_perc_compl_piano` decimal(6,2) NOT NULL,
  `non_completato_seconda_data_int` int(11) NOT NULL,
  `completato_seconda_data_int` int(11) NOT NULL,
  `completato_seconda_data_char` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `punti_seconda_data` int(11) NOT NULL,
  `secondo_sf_tot_ott` decimal(5,2) NOT NULL,
  `secondo_sf1_ott` decimal(5,2) NOT NULL,
  `secondo_sf2_ott` decimal(5,2) NOT NULL,
  `secondo_sfj_ott` decimal(5,2) NOT NULL,
  `secondo_sf_tot_util` decimal(5,2) NOT NULL,
  `secondo_sf1_util` decimal(5,2) NOT NULL,
  `secondo_sf2_util` decimal(5,2) NOT NULL,
  `secondo_sfj_util` decimal(5,2) NOT NULL,
  `data_elaborazione` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_questionari_corso`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_questionari_corso` (
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `scuola` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tipo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio_edizione_date` date NOT NULL,
  `somma_domanda_01` double DEFAULT NULL,
  `contatore_domanda_01` double DEFAULT NULL,
  `media_domanda_01` double DEFAULT NULL,
  `somma_domanda_02` double DEFAULT NULL,
  `contatore_domanda_02` double DEFAULT NULL,
  `media_domanda_02` double DEFAULT NULL,
  `somma_domanda_03` double DEFAULT NULL,
  `contatore_domanda_03` double DEFAULT NULL,
  `media_domanda_03` double DEFAULT NULL,
  `somma_domanda_04` double DEFAULT NULL,
  `contatore_domanda_04` double DEFAULT NULL,
  `media_domanda_04` double DEFAULT NULL,
  `somma_domanda_05a` double DEFAULT NULL,
  `contatore_domanda_05a` double DEFAULT NULL,
  `media_domanda_05a` double DEFAULT NULL,
  `somma_domanda_05b` double DEFAULT NULL,
  `contatore_domanda_05b` double DEFAULT NULL,
  `media_domanda_05b` double DEFAULT NULL,
  `somma_domanda_05c` double DEFAULT NULL,
  `contatore_domanda_05c` double DEFAULT NULL,
  `media_domanda_05c` double DEFAULT NULL,
  `somma_domanda_06` double DEFAULT NULL,
  `contatore_domanda_06` double DEFAULT NULL,
  `media_domanda_06` double DEFAULT NULL,
  `somma_domanda_07` double DEFAULT NULL,
  `contatore_domanda_07` double DEFAULT NULL,
  `media_domanda_07` double DEFAULT NULL,
  `somma_domanda_09a` double DEFAULT NULL,
  `contatore_domanda_09a` double DEFAULT NULL,
  `media_domanda_09a` double DEFAULT NULL,
  `somma_domanda_09b` double DEFAULT NULL,
  `contatore_domanda_09b` double DEFAULT NULL,
  `media_domanda_09b` double DEFAULT NULL,
  `somma_domanda_09c` double DEFAULT NULL,
  `contatore_domanda_09c` double DEFAULT NULL,
  `media_domanda_09c` double DEFAULT NULL,
  `somma_domanda_09d` double DEFAULT NULL,
  `contatore_domanda_09d` double DEFAULT NULL,
  `media_domanda_09d` double DEFAULT NULL,
  `somma_domanda_09e` double DEFAULT NULL,
  `contatore_domanda_09e` double DEFAULT NULL,
  `media_domanda_09e` double DEFAULT NULL,
  `somma_domanda_10` double DEFAULT NULL,
  `contatore_domanda_10` double DEFAULT NULL,
  `media_domanda_10` double DEFAULT NULL,
  `somma_domanda_11` double DEFAULT NULL,
  `contatore_domanda_11` double DEFAULT NULL,
  `media_domanda_11` double DEFAULT NULL,
  `somma_domanda_12` double DEFAULT NULL,
  `contatore_domanda_12` double DEFAULT NULL,
  `media_domanda_12` double DEFAULT NULL,
  `somma_domanda_13` double DEFAULT NULL,
  `contatore_domanda_13` double DEFAULT NULL,
  `media_domanda_13` double DEFAULT NULL,
  `somma_globale` double DEFAULT NULL,
  `contatore_globale` double DEFAULT NULL,
  `media_globale` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_questionari_dati`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_questionari_dati` (
  `id` bigint(10) unsigned NOT NULL,
  `id_corso` bigint(10) unsigned NOT NULL,
  `tipo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `id_edizione` bigint(10) unsigned NOT NULL,
  `data_inizio_edizione_long` bigint(10) unsigned NOT NULL,
  `data_inizio_edizione_date` date NOT NULL,
  `tipo_questionario` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value_string` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_int` tinyint(4) DEFAULT NULL,
  `domanda` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `id_docente` bigint(10) unsigned DEFAULT NULL,
  `cognome_docente` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nome_docente` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flag_dir_scuola` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `id_dir_scuola` bigint(10) unsigned NOT NULL,
  `dir_scuola` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=70578599 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_questionari_docenti`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_questionari_docenti` (
  `progressivo` bigint(20) NOT NULL,
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `scuola` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tipo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio_edizione_date` date NOT NULL,
  `id_docente` bigint(10) unsigned NOT NULL,
  `cognome_docente` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `nome_docente` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `somma_domanda_08a` double DEFAULT NULL,
  `contatore_domanda_08a` double DEFAULT NULL,
  `media_domanda_08a` double DEFAULT NULL,
  `somma_domanda_08b` double DEFAULT NULL,
  `contatore_domanda_08b` double DEFAULT NULL,
  `media_domanda_08b` double DEFAULT NULL,
  `somma_domanda_08c` double DEFAULT NULL,
  `contatore_domanda_08c` double DEFAULT NULL,
  `media_domanda_08c` double DEFAULT NULL,
  `somma_domanda_08d` double DEFAULT NULL,
  `contatore_domanda_08d` double DEFAULT NULL,
  `media_domanda_08d` double DEFAULT NULL,
  `somma_domanda_08e` double DEFAULT NULL,
  `contatore_domanda_08e` double DEFAULT NULL,
  `media_domanda_08e` double DEFAULT NULL,
  `somma_domanda_08f` double DEFAULT NULL,
  `contatore_domanda_08f` double DEFAULT NULL,
  `media_domanda_08f` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_questionari_docenti_edizioni`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_questionari_docenti_edizioni` (
  `progressivo` bigint(20) NOT NULL,
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `scuola` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tipo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio_edizione_date` date NOT NULL,
  `id_docente` bigint(10) unsigned NOT NULL,
  `cognome_docente` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `nome_docente` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `somma_domanda_08a` double DEFAULT NULL,
  `contatore_domanda_08a` double DEFAULT NULL,
  `media_domanda_08a` double DEFAULT NULL,
  `somma_domanda_08b` double DEFAULT NULL,
  `contatore_domanda_08b` double DEFAULT NULL,
  `media_domanda_08b` double DEFAULT NULL,
  `somma_domanda_08c` double DEFAULT NULL,
  `contatore_domanda_08c` double DEFAULT NULL,
  `media_domanda_08c` double DEFAULT NULL,
  `somma_domanda_08d` double DEFAULT NULL,
  `contatore_domanda_08d` double DEFAULT NULL,
  `media_domanda_08d` double DEFAULT NULL,
  `somma_domanda_08e` double DEFAULT NULL,
  `contatore_domanda_08e` double DEFAULT NULL,
  `media_domanda_08e` double DEFAULT NULL,
  `somma_domanda_08f` double DEFAULT NULL,
  `contatore_domanda_08f` double DEFAULT NULL,
  `media_domanda_08f` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_pent_questionari_edizioni`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_pent_questionari_edizioni` (
  `cod_corso` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `titolo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `scuola` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tipo_corso` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data_inizio_edizione_date` date NOT NULL,
  `somma_domanda_01` double DEFAULT NULL,
  `contatore_domanda_01` double DEFAULT NULL,
  `media_domanda_01` double DEFAULT NULL,
  `somma_domanda_02` double DEFAULT NULL,
  `contatore_domanda_02` double DEFAULT NULL,
  `media_domanda_02` double DEFAULT NULL,
  `somma_domanda_03` double DEFAULT NULL,
  `contatore_domanda_03` double DEFAULT NULL,
  `media_domanda_03` double DEFAULT NULL,
  `somma_domanda_04` double DEFAULT NULL,
  `contatore_domanda_04` double DEFAULT NULL,
  `media_domanda_04` double DEFAULT NULL,
  `somma_domanda_05a` double DEFAULT NULL,
  `contatore_domanda_05a` double DEFAULT NULL,
  `media_domanda_05a` double DEFAULT NULL,
  `somma_domanda_05b` double DEFAULT NULL,
  `contatore_domanda_05b` double DEFAULT NULL,
  `media_domanda_05b` double DEFAULT NULL,
  `somma_domanda_05c` double DEFAULT NULL,
  `contatore_domanda_05c` double DEFAULT NULL,
  `media_domanda_05c` double DEFAULT NULL,
  `somma_domanda_06` double DEFAULT NULL,
  `contatore_domanda_06` double DEFAULT NULL,
  `media_domanda_06` double DEFAULT NULL,
  `somma_domanda_07` double DEFAULT NULL,
  `contatore_domanda_07` double DEFAULT NULL,
  `media_domanda_07` double DEFAULT NULL,
  `somma_domanda_09a` double DEFAULT NULL,
  `contatore_domanda_09a` double DEFAULT NULL,
  `media_domanda_09a` double DEFAULT NULL,
  `somma_domanda_09b` double DEFAULT NULL,
  `contatore_domanda_09b` double DEFAULT NULL,
  `media_domanda_09b` double DEFAULT NULL,
  `somma_domanda_09c` double DEFAULT NULL,
  `contatore_domanda_09c` double DEFAULT NULL,
  `media_domanda_09c` double DEFAULT NULL,
  `somma_domanda_09d` double DEFAULT NULL,
  `contatore_domanda_09d` double DEFAULT NULL,
  `media_domanda_09d` double DEFAULT NULL,
  `somma_domanda_09e` double DEFAULT NULL,
  `contatore_domanda_09e` double DEFAULT NULL,
  `media_domanda_09e` double DEFAULT NULL,
  `somma_domanda_10` double DEFAULT NULL,
  `contatore_domanda_10` double DEFAULT NULL,
  `media_domanda_10` double DEFAULT NULL,
  `somma_domanda_11` double DEFAULT NULL,
  `contatore_domanda_11` double DEFAULT NULL,
  `media_domanda_11` double DEFAULT NULL,
  `somma_domanda_12` double DEFAULT NULL,
  `contatore_domanda_12` double DEFAULT NULL,
  `media_domanda_12` double DEFAULT NULL,
  `somma_domanda_13` double DEFAULT NULL,
  `contatore_domanda_13` double DEFAULT NULL,
  `media_domanda_13` double DEFAULT NULL,
  `somma_globale` double DEFAULT NULL,
  `contatore_globale` double DEFAULT NULL,
  `media_globale` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_php_ctrl`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_php_ctrl` (
  `NOME_FUNCTION` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `FLAG_ATTIVAZIONE` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `LIVELLO_LOG` int(11) NOT NULL,
  `NOTA` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_php_log`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_php_log` (
  `ID` int(11) NOT NULL,
  `ID_ELAB` int(11) NOT NULL,
  `DATA` datetime NOT NULL,
  `NOME_FUNCTION` varchar(64) NOT NULL,
  `LIVELLO_MSG` int(11) NOT NULL,
  `COD_MSG` int(11) NOT NULL,
  `DESCR_MSG` varchar(255) NOT NULL,
  `NOTA` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=146848 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struttura della tabella `tbl_eml_php_log_query`
--

CREATE TABLE IF NOT EXISTS `tbl_eml_php_log_query` (
  `id` bigint(10) NOT NULL,
  `id_elab` bigint(10) DEFAULT NULL,
  `funzione` varchar(100) DEFAULT NULL,
  `query` varchar(250) DEFAULT NULL,
  `data` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tabella di log per query procedure batch Forma20';

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_EML_STORICO_X_HR_RUPM`
--

CREATE TABLE IF NOT EXISTS `TBL_EML_STORICO_X_HR_RUPM` (
  `iD_RECORD` bigint(8) unsigned DEFAULT NULL,
  `CODICE_FISCALE` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EX_MATRICOLA` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `COGNOME` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NOME` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `COD_DIREZIONE` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DATA_INIZIO` date DEFAULT NULL,
  `COD_CORSO` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TITOLO` varchar(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DURATA` decimal(5,2) DEFAULT NULL,
  `AREA_FORMATIVA` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CATEGORIA` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tabella usata in HR per la gestione delle professionalita';

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_ASS_MANSIONE_DIP`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_ASS_MANSIONE_DIP` (
  `ID_MANSIONE_DIP` int(11) NOT NULL,
  `ID_DIPENDENTE` int(11) NOT NULL,
  `ID_TIPO_MANSIONE` int(11) NOT NULL,
  `DT_INIZIO_ASSEGNAZIONE` datetime NOT NULL,
  `DT_FINE_ASSEGNAZIONE` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_ASS_POSIZ_ECONOM_DIP`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_ASS_POSIZ_ECONOM_DIP` (
  `ID_POSIZ_ECONOMICA` int(11) NOT NULL,
  `ID_DIPENDENTE` int(11) NOT NULL,
  `DT_INIZIO_ASSEGNAZIONE` datetime NOT NULL,
  `DT_FINE_ASSEGNAZIONE` datetime DEFAULT NULL,
  `ID_STOR_POSIZ_ECONOM_DIP` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_ASS_POSIZ_ORG_DIP`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_ASS_POSIZ_ORG_DIP` (
  `ID_POSIZ_ORGANIZZATIVA` int(11) NOT NULL,
  `ID_DIPENDENTE` int(11) NOT NULL,
  `DT_INIZIO_ASSEGNAZIONE` datetime NOT NULL,
  `DT_FINE_ASSEGNAZIONE` datetime DEFAULT NULL,
  `ID_STOR_POSIZ_ORG` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_ASS_RAPPORTO_DIP`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_ASS_RAPPORTO_DIP` (
  `ID_RAPPORTO_DIPENDENTE` int(11) NOT NULL,
  `ID_DIPENDENTE` int(11) NOT NULL,
  `ID_TIPO_RAPPORTO` int(11) NOT NULL,
  `DT_INIZIO_ASSEGNAZIONE` datetime NOT NULL,
  `DT_FINE_ASSEGNAZIONE` datetime DEFAULT NULL,
  `FLAG_ASPETTATIVA` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_ASS_RUOLO_DIP`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_ASS_RUOLO_DIP` (
  `ID_RUOLO_DIP` int(11) NOT NULL,
  `ID_DIPENDENTE` int(11) NOT NULL,
  `ID_RUOLO` int(11) NOT NULL,
  `DT_INIZIO_ASSEGNAZIONE` datetime NOT NULL,
  `DT_FINE_ASSEGNAZIONE` datetime DEFAULT NULL,
  `ID_TIPO_RUOLO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_ASS_STRUTTURA_DIP`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_ASS_STRUTTURA_DIP` (
  `ID_STOR_STRUTTURA_DIP` int(11) NOT NULL,
  `ID_STRUTTURA` int(11) NOT NULL,
  `ID_DIPENDENTE` int(11) NOT NULL,
  `DT_INIZIO_ASSEGNAZIONE` datetime NOT NULL,
  `DT_FINE_ASSEGNAZIONE` datetime DEFAULT NULL,
  `FLAG_ASS_PRIMARIA` varchar(2) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_DATA_SCARICO`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_DATA_SCARICO` (
  `ID_SCARICO` int(11) NOT NULL,
  `DATA` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_DIPENDENTE`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_DIPENDENTE` (
  `ID_DIPENDENTE` int(11) NOT NULL,
  `CODICE_FISCALE` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `NOME` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `COGNOME` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `EX_MATRICOLA` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `SESSO` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MATRICOLA_HR` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EX_ENTE` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ENTE` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DATA_ASSUNZIONE` datetime DEFAULT NULL,
  `DATA_CESSAZIONE` datetime DEFAULT NULL,
  `TIPO_PERSONA` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EMAIL` varchar(240) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DA_VERIFICARE` char(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_POSIZIONE_ECONOMICA`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_POSIZIONE_ECONOMICA` (
  `ID_POSIZ_ECONOMICA` int(11) NOT NULL,
  `CODICE_POSIZ_ECONOM` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `DESCRIZIONE` varchar(250) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_POSIZ_ORGANIZZATIVA`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_POSIZ_ORGANIZZATIVA` (
  `ID_POSIZ_ORGANIZZATIVA` int(11) NOT NULL,
  `ID_TIPO_POSIZ_ORG` int(11) NOT NULL,
  `ID_POSIZI_ORGANIZZATIVA_HR` int(11) DEFAULT NULL,
  `CODICE_POSIZ_ORGANIZ` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `DESCRIZIONE` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_RUOLO`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_RUOLO` (
  `ID_RUOLO` int(11) NOT NULL,
  `CODICE` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `DESCRIZIONE` varchar(240) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SIGNIFICATO` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RUOLO_VALIDO` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RUOLO_PER_FORM_IND` char(1) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_STRUTTURA`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_STRUTTURA` (
  `ID_STRUTTURA` int(11) NOT NULL,
  `ID_STRUTTURA_HR` int(11) DEFAULT NULL,
  `ID_TIPO_STRUTTURA` int(11) NOT NULL,
  `DENOMINAZIONE` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `CODICE_STRUTTURA` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `ID_STRUTTURA_PADRE` int(11) DEFAULT NULL,
  `DT_INIZIO_VALID` datetime NOT NULL,
  `DT_FINE_VALID` datetime DEFAULT NULL,
  `CITTA` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PROVINCIA` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CAP` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VIA` varchar(240) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ENTE` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_TIPO_MANSIONE`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_TIPO_MANSIONE` (
  `ID_TIPO_MANSIONE` int(11) NOT NULL,
  `DESCRIZIONE` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_TIPO_POSIZ_ORG`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_TIPO_POSIZ_ORG` (
  `ID_TIPO_POSIZ_ORG` int(11) NOT NULL,
  `CODICE` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `DESCRIZIONE` varchar(250) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_TIPO_RAPPORTO`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_TIPO_RAPPORTO` (
  `ID_TIPO_RAPPORTO` int(11) NOT NULL,
  `CODICE_RAPPORTO` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `FLG_TEMPODET` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DESCRIZIONE` varchar(250) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_TIPO_RUOLO`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_TIPO_RUOLO` (
  `ID_TIPO_RUOLO` int(11) NOT NULL,
  `NOME` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ID_TIPO_RUOLO_HR` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `TBL_HR_TIPO_STRUTTURA`
--

CREATE TABLE IF NOT EXISTS `TBL_HR_TIPO_STRUTTURA` (
  `ID_TIPO_STRUTTURA` int(11) NOT NULL,
  `DESCRIZIONE` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ID_TIPO_STRUTTURA_HR` varchar(30) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura per la vista `mdl_f2_posiz_econom_qualifica`
--
DROP TABLE IF EXISTS `mdl_f2_posiz_econom_qualifica`;

CREATE ALGORITHM=UNDEFINED DEFINER=`forma2`@`%` SQL SECURITY DEFINER VIEW `mdl_f2_posiz_econom_qualifica` AS (select `tbl_eml_mapping_categorie`.`id` AS `id`,`tbl_eml_mapping_categorie`.`mdl_user_info_data_data_categoria` AS `codqual`,`tbl_eml_mapping_categorie`.`mdl_user_info_data_data_ap` AS `ap`,`tbl_eml_mapping_categorie`.`mdl_cohort_name_macrocategoria` AS `macrocategory`,`tbl_eml_mapping_categorie`.`descrizione` AS `descrizione`,`tbl_eml_mapping_categorie`.`mdl_cohort_id_macrocategoria` AS `cohortid` from `tbl_eml_mapping_categorie` where (`tbl_eml_mapping_categorie`.`mdl_cohort_name_macrocategoria` in ('Dir','A','B','C','D','UE')));

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mdl_assign`
--
ALTER TABLE `mdl_assign`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assi_cou_ix` (`course`), ADD KEY `mdl_assi_tea_ix` (`teamsubmissiongroupingid`);

--
-- Indexes for table `mdl_assignfeedback_comments`
--
ALTER TABLE `mdl_assignfeedback_comments`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assicomm_ass_ix` (`assignment`), ADD KEY `mdl_assicomm_gra_ix` (`grade`);

--
-- Indexes for table `mdl_assignfeedback_editpdf_annot`
--
ALTER TABLE `mdl_assignfeedback_editpdf_annot`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assieditanno_grapag_ix` (`gradeid`,`pageno`), ADD KEY `mdl_assieditanno_gra_ix` (`gradeid`);

--
-- Indexes for table `mdl_assignfeedback_editpdf_cmnt`
--
ALTER TABLE `mdl_assignfeedback_editpdf_cmnt`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assieditcmnt_grapag_ix` (`gradeid`,`pageno`), ADD KEY `mdl_assieditcmnt_gra_ix` (`gradeid`);

--
-- Indexes for table `mdl_assignfeedback_editpdf_quick`
--
ALTER TABLE `mdl_assignfeedback_editpdf_quick`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assieditquic_use_ix` (`userid`);

--
-- Indexes for table `mdl_assignfeedback_file`
--
ALTER TABLE `mdl_assignfeedback_file`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assifile_ass2_ix` (`assignment`), ADD KEY `mdl_assifile_gra_ix` (`grade`);

--
-- Indexes for table `mdl_assignment`
--
ALTER TABLE `mdl_assignment`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assi_cou_ix` (`course`);

--
-- Indexes for table `mdl_assignment_submissions`
--
ALTER TABLE `mdl_assignment_submissions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assisubm_use_ix` (`userid`), ADD KEY `mdl_assisubm_mai_ix` (`mailed`), ADD KEY `mdl_assisubm_tim_ix` (`timemarked`), ADD KEY `mdl_assisubm_ass_ix` (`assignment`);

--
-- Indexes for table `mdl_assignment_upgrade`
--
ALTER TABLE `mdl_assignment_upgrade`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assiupgr_old_ix` (`oldcmid`), ADD KEY `mdl_assiupgr_old2_ix` (`oldinstance`);

--
-- Indexes for table `mdl_assignsubmission_file`
--
ALTER TABLE `mdl_assignsubmission_file`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assifile_ass_ix` (`assignment`), ADD KEY `mdl_assifile_sub_ix` (`submission`);

--
-- Indexes for table `mdl_assignsubmission_onlinetext`
--
ALTER TABLE `mdl_assignsubmission_onlinetext`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assionli_ass_ix` (`assignment`), ADD KEY `mdl_assionli_sub_ix` (`submission`);

--
-- Indexes for table `mdl_assign_grades`
--
ALTER TABLE `mdl_assign_grades`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_assigrad_assuseatt_uix` (`assignment`,`userid`,`attemptnumber`), ADD KEY `mdl_assigrad_use_ix` (`userid`), ADD KEY `mdl_assigrad_att_ix` (`attemptnumber`), ADD KEY `mdl_assigrad_ass_ix` (`assignment`);

--
-- Indexes for table `mdl_assign_plugin_config`
--
ALTER TABLE `mdl_assign_plugin_config`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assiplugconf_plu_ix` (`plugin`), ADD KEY `mdl_assiplugconf_sub_ix` (`subtype`), ADD KEY `mdl_assiplugconf_nam_ix` (`name`), ADD KEY `mdl_assiplugconf_ass_ix` (`assignment`);

--
-- Indexes for table `mdl_assign_submission`
--
ALTER TABLE `mdl_assign_submission`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_assisubm_assusegroatt_uix` (`assignment`,`userid`,`groupid`,`attemptnumber`), ADD KEY `mdl_assisubm_use_ix` (`userid`), ADD KEY `mdl_assisubm_att_ix` (`attemptnumber`), ADD KEY `mdl_assisubm_ass_ix` (`assignment`);

--
-- Indexes for table `mdl_assign_user_flags`
--
ALTER TABLE `mdl_assign_user_flags`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assiuserflag_mai_ix` (`mailed`), ADD KEY `mdl_assiuserflag_use_ix` (`userid`), ADD KEY `mdl_assiuserflag_ass_ix` (`assignment`);

--
-- Indexes for table `mdl_assign_user_mapping`
--
ALTER TABLE `mdl_assign_user_mapping`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_assiusermapp_ass_ix` (`assignment`), ADD KEY `mdl_assiusermapp_use_ix` (`userid`);

--
-- Indexes for table `mdl_backup_controllers`
--
ALTER TABLE `mdl_backup_controllers`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_backcont_bac_uix` (`backupid`), ADD KEY `mdl_backcont_typite_ix` (`type`,`itemid`), ADD KEY `mdl_backcont_use_ix` (`userid`);

--
-- Indexes for table `mdl_backup_courses`
--
ALTER TABLE `mdl_backup_courses`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_backcour_cou_uix` (`courseid`);

--
-- Indexes for table `mdl_backup_logs`
--
ALTER TABLE `mdl_backup_logs`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_backlogs_bacid_uix` (`backupid`,`id`), ADD KEY `mdl_backlogs_bac_ix` (`backupid`);

--
-- Indexes for table `mdl_badge`
--
ALTER TABLE `mdl_badge`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_badg_typ_ix` (`type`), ADD KEY `mdl_badg_cou_ix` (`courseid`), ADD KEY `mdl_badg_use_ix` (`usermodified`), ADD KEY `mdl_badg_use2_ix` (`usercreated`);

--
-- Indexes for table `mdl_badge_backpack`
--
ALTER TABLE `mdl_badge_backpack`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_badgback_use_ix` (`userid`);

--
-- Indexes for table `mdl_badge_criteria`
--
ALTER TABLE `mdl_badge_criteria`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_badgcrit_badcri_uix` (`badgeid`,`criteriatype`), ADD KEY `mdl_badgcrit_cri_ix` (`criteriatype`), ADD KEY `mdl_badgcrit_bad_ix` (`badgeid`);

--
-- Indexes for table `mdl_badge_criteria_met`
--
ALTER TABLE `mdl_badge_criteria_met`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_badgcritmet_cri_ix` (`critid`), ADD KEY `mdl_badgcritmet_use_ix` (`userid`), ADD KEY `mdl_badgcritmet_iss_ix` (`issuedid`);

--
-- Indexes for table `mdl_badge_criteria_param`
--
ALTER TABLE `mdl_badge_criteria_param`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_badgcritpara_cri_ix` (`critid`);

--
-- Indexes for table `mdl_badge_external`
--
ALTER TABLE `mdl_badge_external`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_badgexte_bac_ix` (`backpackid`);

--
-- Indexes for table `mdl_badge_issued`
--
ALTER TABLE `mdl_badge_issued`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_badgissu_baduse_uix` (`badgeid`,`userid`), ADD KEY `mdl_badgissu_bad_ix` (`badgeid`), ADD KEY `mdl_badgissu_use_ix` (`userid`);

--
-- Indexes for table `mdl_badge_manual_award`
--
ALTER TABLE `mdl_badge_manual_award`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_badgmanuawar_bad_ix` (`badgeid`), ADD KEY `mdl_badgmanuawar_rec_ix` (`recipientid`), ADD KEY `mdl_badgmanuawar_iss_ix` (`issuerid`), ADD KEY `mdl_badgmanuawar_iss2_ix` (`issuerrole`);

--
-- Indexes for table `mdl_block`
--
ALTER TABLE `mdl_block`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_bloc_nam_uix` (`name`);

--
-- Indexes for table `mdl_block_community`
--
ALTER TABLE `mdl_block_community`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_block_f2_gestione_risorse`
--
ALTER TABLE `mdl_block_f2_gestione_risorse`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_block_formindbudget`
--
ALTER TABLE `mdl_block_formindbudget`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_block_formindbudget_log`
--
ALTER TABLE `mdl_block_formindbudget_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_block_formindbudget_storico`
--
ALTER TABLE `mdl_block_formindbudget_storico`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_block_instances`
--
ALTER TABLE `mdl_block_instances`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_blocinst_parshopagsub_ix` (`parentcontextid`,`showinsubcontexts`,`pagetypepattern`,`subpagepattern`), ADD KEY `mdl_blocinst_par_ix` (`parentcontextid`);

--
-- Indexes for table `mdl_block_positions`
--
ALTER TABLE `mdl_block_positions`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_blocposi_bloconpagsub_uix` (`blockinstanceid`,`contextid`,`pagetype`,`subpage`), ADD KEY `mdl_blocposi_blo_ix` (`blockinstanceid`), ADD KEY `mdl_blocposi_con_ix` (`contextid`);

--
-- Indexes for table `mdl_block_recent_activity`
--
ALTER TABLE `mdl_block_recent_activity`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_blocreceacti_coutim_ix` (`courseid`,`timecreated`);

--
-- Indexes for table `mdl_block_rss_client`
--
ALTER TABLE `mdl_block_rss_client`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_blog_association`
--
ALTER TABLE `mdl_blog_association`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_blogasso_con_ix` (`contextid`), ADD KEY `mdl_blogasso_blo_ix` (`blogid`);

--
-- Indexes for table `mdl_blog_external`
--
ALTER TABLE `mdl_blog_external`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_blogexte_use_ix` (`userid`);

--
-- Indexes for table `mdl_book`
--
ALTER TABLE `mdl_book`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_book_chapters`
--
ALTER TABLE `mdl_book_chapters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_cache_filters`
--
ALTER TABLE `mdl_cache_filters`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_cachfilt_filmd5_ix` (`filter`,`md5key`);

--
-- Indexes for table `mdl_cache_flags`
--
ALTER TABLE `mdl_cache_flags`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_cachflag_fla_ix` (`flagtype`), ADD KEY `mdl_cachflag_nam_ix` (`name`);

--
-- Indexes for table `mdl_capabilities`
--
ALTER TABLE `mdl_capabilities`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_capa_nam_uix` (`name`);

--
-- Indexes for table `mdl_certificate`
--
ALTER TABLE `mdl_certificate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_certificate_issues`
--
ALTER TABLE `mdl_certificate_issues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_chat`
--
ALTER TABLE `mdl_chat`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_chat_cou_ix` (`course`);

--
-- Indexes for table `mdl_chat_messages`
--
ALTER TABLE `mdl_chat_messages`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_chatmess_use_ix` (`userid`), ADD KEY `mdl_chatmess_gro_ix` (`groupid`), ADD KEY `mdl_chatmess_timcha_ix` (`timestamp`,`chatid`), ADD KEY `mdl_chatmess_cha_ix` (`chatid`);

--
-- Indexes for table `mdl_chat_messages_current`
--
ALTER TABLE `mdl_chat_messages_current`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_chatmesscurr_use_ix` (`userid`), ADD KEY `mdl_chatmesscurr_gro_ix` (`groupid`), ADD KEY `mdl_chatmesscurr_timcha_ix` (`timestamp`,`chatid`), ADD KEY `mdl_chatmesscurr_cha_ix` (`chatid`);

--
-- Indexes for table `mdl_chat_users`
--
ALTER TABLE `mdl_chat_users`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_chatuser_use_ix` (`userid`), ADD KEY `mdl_chatuser_las_ix` (`lastping`), ADD KEY `mdl_chatuser_gro_ix` (`groupid`), ADD KEY `mdl_chatuser_cha_ix` (`chatid`);

--
-- Indexes for table `mdl_choice`
--
ALTER TABLE `mdl_choice`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_choi_cou_ix` (`course`);

--
-- Indexes for table `mdl_choice_answers`
--
ALTER TABLE `mdl_choice_answers`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_choiansw_use_ix` (`userid`), ADD KEY `mdl_choiansw_cho_ix` (`choiceid`), ADD KEY `mdl_choiansw_opt_ix` (`optionid`);

--
-- Indexes for table `mdl_choice_options`
--
ALTER TABLE `mdl_choice_options`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_choiopti_cho_ix` (`choiceid`);

--
-- Indexes for table `mdl_cohort`
--
ALTER TABLE `mdl_cohort`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_coho_con_ix` (`contextid`);

--
-- Indexes for table `mdl_cohort_members`
--
ALTER TABLE `mdl_cohort_members`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_cohomemb_cohuse_uix` (`cohortid`,`userid`), ADD KEY `mdl_cohomemb_coh_ix` (`cohortid`), ADD KEY `mdl_cohomemb_use_ix` (`userid`);

--
-- Indexes for table `mdl_comments`
--
ALTER TABLE `mdl_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_config`
--
ALTER TABLE `mdl_config`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_conf_nam_uix` (`name`);

--
-- Indexes for table `mdl_config_log`
--
ALTER TABLE `mdl_config_log`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_conflog_tim_ix` (`timemodified`), ADD KEY `mdl_conflog_use_ix` (`userid`);

--
-- Indexes for table `mdl_config_plugins`
--
ALTER TABLE `mdl_config_plugins`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_confplug_plunam_uix` (`plugin`,`name`);

--
-- Indexes for table `mdl_context`
--
ALTER TABLE `mdl_context`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_cont_conins_uix` (`contextlevel`,`instanceid`), ADD KEY `mdl_cont_ins_ix` (`instanceid`), ADD KEY `mdl_cont_pat_ix` (`path`);

--
-- Indexes for table `mdl_context_temp`
--
ALTER TABLE `mdl_context_temp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_controlli_log`
--
ALTER TABLE `mdl_controlli_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_course`
--
ALTER TABLE `mdl_course`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_cour_cat_ix` (`category`), ADD KEY `mdl_cour_idn_ix` (`idnumber`), ADD KEY `mdl_cour_sho_ix` (`shortname`), ADD KEY `mdl_cour_sor_ix` (`sortorder`);

--
-- Indexes for table `mdl_course_categories`
--
ALTER TABLE `mdl_course_categories`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_courcate_par_ix` (`parent`);

--
-- Indexes for table `mdl_course_completions`
--
ALTER TABLE `mdl_course_completions`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_courcomp_usecou_uix` (`userid`,`course`), ADD KEY `mdl_courcomp_use_ix` (`userid`), ADD KEY `mdl_courcomp_cou_ix` (`course`), ADD KEY `mdl_courcomp_tim_ix` (`timecompleted`);

--
-- Indexes for table `mdl_course_completion_aggr_methd`
--
ALTER TABLE `mdl_course_completion_aggr_methd`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_courcompaggrmeth_coucr_uix` (`course`,`criteriatype`), ADD KEY `mdl_courcompaggrmeth_cou_ix` (`course`), ADD KEY `mdl_courcompaggrmeth_cri_ix` (`criteriatype`);

--
-- Indexes for table `mdl_course_completion_criteria`
--
ALTER TABLE `mdl_course_completion_criteria`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_courcompcrit_cou_ix` (`course`);

--
-- Indexes for table `mdl_course_completion_crit_compl`
--
ALTER TABLE `mdl_course_completion_crit_compl`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_courcompcritcomp_useco_uix` (`userid`,`course`,`criteriaid`), ADD KEY `mdl_courcompcritcomp_use_ix` (`userid`), ADD KEY `mdl_courcompcritcomp_cou_ix` (`course`), ADD KEY `mdl_courcompcritcomp_cri_ix` (`criteriaid`), ADD KEY `mdl_courcompcritcomp_tim_ix` (`timecompleted`);

--
-- Indexes for table `mdl_course_format_options`
--
ALTER TABLE `mdl_course_format_options`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_courformopti_couforsec_uix` (`courseid`,`format`,`sectionid`,`name`), ADD KEY `mdl_courformopti_cou_ix` (`courseid`);

--
-- Indexes for table `mdl_course_modules`
--
ALTER TABLE `mdl_course_modules`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_courmodu_vis_ix` (`visible`), ADD KEY `mdl_courmodu_cou_ix` (`course`), ADD KEY `mdl_courmodu_mod_ix` (`module`), ADD KEY `mdl_courmodu_ins_ix` (`instance`), ADD KEY `mdl_courmodu_idncou_ix` (`idnumber`,`course`), ADD KEY `mdl_courmodu_gro_ix` (`groupingid`);

--
-- Indexes for table `mdl_course_modules_completion`
--
ALTER TABLE `mdl_course_modules_completion`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_courmoducomp_usecou_uix` (`userid`,`coursemoduleid`), ADD KEY `mdl_courmoducomp_cou_ix` (`coursemoduleid`);

--
-- Indexes for table `mdl_course_published`
--
ALTER TABLE `mdl_course_published`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_course_request`
--
ALTER TABLE `mdl_course_request`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_courrequ_sho_ix` (`shortname`);

--
-- Indexes for table `mdl_course_sections`
--
ALTER TABLE `mdl_course_sections`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_coursect_cousec_uix` (`course`,`section`);

--
-- Indexes for table `mdl_csibenchmark`
--
ALTER TABLE `mdl_csibenchmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarkdettagliprove`
--
ALTER TABLE `mdl_csibenchmarkdettagliprove`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarkprove`
--
ALTER TABLE `mdl_csibenchmarkprove`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarkraw_v0`
--
ALTER TABLE `mdl_csibenchmarkraw_v0`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarkraw_v1`
--
ALTER TABLE `mdl_csibenchmarkraw_v1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarksessioni`
--
ALTER TABLE `mdl_csibenchmarksessioni`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarktest`
--
ALTER TABLE `mdl_csibenchmarktest`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarktestraw_v0`
--
ALTER TABLE `mdl_csibenchmarktestraw_v0`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarktestraw_v1`
--
ALTER TABLE `mdl_csibenchmarktestraw_v1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarktest_v0`
--
ALTER TABLE `mdl_csibenchmarktest_v0`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmarktest_v1`
--
ALTER TABLE `mdl_csibenchmarktest_v1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmark_v0`
--
ALTER TABLE `mdl_csibenchmark_v0`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_csibenchmark_v1`
--
ALTER TABLE `mdl_csibenchmark_v1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_data`
--
ALTER TABLE `mdl_data`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_data_cou_ix` (`course`);

--
-- Indexes for table `mdl_data_content`
--
ALTER TABLE `mdl_data_content`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_datacont_rec_ix` (`recordid`), ADD KEY `mdl_datacont_fie_ix` (`fieldid`);

--
-- Indexes for table `mdl_data_fields`
--
ALTER TABLE `mdl_data_fields`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_datafiel_typdat_ix` (`type`,`dataid`), ADD KEY `mdl_datafiel_dat_ix` (`dataid`);

--
-- Indexes for table `mdl_data_records`
--
ALTER TABLE `mdl_data_records`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_datareco_dat_ix` (`dataid`);

--
-- Indexes for table `mdl_eml_php_log_query`
--
ALTER TABLE `mdl_eml_php_log_query`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_enrol`
--
ALTER TABLE `mdl_enrol`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_enro_enr_ix` (`enrol`), ADD KEY `mdl_enro_cou_ix` (`courseid`);

--
-- Indexes for table `mdl_enrol_flatfile`
--
ALTER TABLE `mdl_enrol_flatfile`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_enroflat_cou_ix` (`courseid`), ADD KEY `mdl_enroflat_use_ix` (`userid`), ADD KEY `mdl_enroflat_rol_ix` (`roleid`);

--
-- Indexes for table `mdl_enrol_paypal`
--
ALTER TABLE `mdl_enrol_paypal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_event`
--
ALTER TABLE `mdl_event`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_even_cou_ix` (`courseid`), ADD KEY `mdl_even_use_ix` (`userid`), ADD KEY `mdl_even_tim_ix` (`timestart`), ADD KEY `mdl_even_tim2_ix` (`timeduration`), ADD KEY `mdl_even_grocouvisuse_ix` (`groupid`,`courseid`,`visible`,`userid`);

--
-- Indexes for table `mdl_events_handlers`
--
ALTER TABLE `mdl_events_handlers`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_evenhand_evecom_uix` (`eventname`,`component`);

--
-- Indexes for table `mdl_events_queue`
--
ALTER TABLE `mdl_events_queue`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_evenqueu_use_ix` (`userid`);

--
-- Indexes for table `mdl_events_queue_handlers`
--
ALTER TABLE `mdl_events_queue_handlers`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_evenqueuhand_que_ix` (`queuedeventid`), ADD KEY `mdl_evenqueuhand_han_ix` (`handlerid`);

--
-- Indexes for table `mdl_event_subscriptions`
--
ALTER TABLE `mdl_event_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_external_functions`
--
ALTER TABLE `mdl_external_functions`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_extefunc_nam_uix` (`name`);

--
-- Indexes for table `mdl_external_services`
--
ALTER TABLE `mdl_external_services`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_exteserv_nam_uix` (`name`);

--
-- Indexes for table `mdl_external_services_functions`
--
ALTER TABLE `mdl_external_services_functions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_exteservfunc_ext_ix` (`externalserviceid`);

--
-- Indexes for table `mdl_external_services_users`
--
ALTER TABLE `mdl_external_services_users`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_exteservuser_ext_ix` (`externalserviceid`), ADD KEY `mdl_exteservuser_use_ix` (`userid`);

--
-- Indexes for table `mdl_external_tokens`
--
ALTER TABLE `mdl_external_tokens`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_extetoke_use_ix` (`userid`), ADD KEY `mdl_extetoke_ext_ix` (`externalserviceid`), ADD KEY `mdl_extetoke_con_ix` (`contextid`), ADD KEY `mdl_extetoke_cre_ix` (`creatorid`);

--
-- Indexes for table `mdl_f2_af`
--
ALTER TABLE `mdl_f2_af`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_anagrafica_corsi`
--
ALTER TABLE `mdl_f2_anagrafica_corsi`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2anagcors_cou_uix` (`courseid`);

--
-- Indexes for table `mdl_f2_b`
--
ALTER TABLE `mdl_f2_b`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsiind`
--
ALTER TABLE `mdl_f2_corsiind`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsiind_anno_finanziario`
--
ALTER TABLE `mdl_f2_corsiind_anno_finanziario`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsiind_log`
--
ALTER TABLE `mdl_f2_corsiind_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsiind_prot`
--
ALTER TABLE `mdl_f2_corsiind_prot`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsiind_senza_spesa`
--
ALTER TABLE `mdl_f2_corsiind_senza_spesa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsiind_senza_spesa_query_log`
--
ALTER TABLE `mdl_f2_corsiind_senza_spesa_query_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsi_coorti_map`
--
ALTER TABLE `mdl_f2_corsi_coorti_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_corsi_sedi_map`
--
ALTER TABLE `mdl_f2_corsi_sedi_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_course_org_mapping`
--
ALTER TABLE `mdl_f2_course_org_mapping`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `courseid` (`courseid`,`orgid`);

--
-- Indexes for table `mdl_f2_csi_pent_gruppi_report`
--
ALTER TABLE `mdl_f2_csi_pent_gruppi_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_csi_pent_menu_report`
--
ALTER TABLE `mdl_f2_csi_pent_menu_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_csi_pent_param`
--
ALTER TABLE `mdl_f2_csi_pent_param`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_csi_pent_param_map`
--
ALTER TABLE `mdl_f2_csi_pent_param_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_csi_pent_report`
--
ALTER TABLE `mdl_f2_csi_pent_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_csi_pent_role_map`
--
ALTER TABLE `mdl_f2_csi_pent_role_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_determine`
--
ALTER TABLE `mdl_f2_determine`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_edizioni_postiris_map`
--
ALTER TABLE `mdl_f2_edizioni_postiris_map`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2edizpostmap_sesdir_uix` (`sessionid`,`direzioneid`);

--
-- Indexes for table `mdl_f2_edz_pianificate_corsi_prg`
--
ALTER TABLE `mdl_f2_edz_pianificate_corsi_prg`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_fi_partialbudget`
--
ALTER TABLE `mdl_f2_fi_partialbudget`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2fipart_annorgtip_uix` (`anno`,`orgfk`,`tipo`);

--
-- Indexes for table `mdl_f2_forma2riforma_log`
--
ALTER TABLE `mdl_f2_forma2riforma_log`
  ADD PRIMARY KEY (`id`), ADD KEY `shortname` (`shortname`);

--
-- Indexes for table `mdl_f2_forma2riforma_mapping`
--
ALTER TABLE `mdl_f2_forma2riforma_mapping`
  ADD PRIMARY KEY (`id`), ADD KEY `shortname` (`shortname`);

--
-- Indexes for table `mdl_f2_forma2riforma_partecipazioni`
--
ALTER TABLE `mdl_f2_forma2riforma_partecipazioni`
  ADD PRIMARY KEY (`id`), ADD KEY `id_mapping` (`id_mapping`,`matricola`);

--
-- Indexes for table `mdl_f2_formatore`
--
ALTER TABLE `mdl_f2_formatore`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2form_usr_ix` (`usrid`);

--
-- Indexes for table `mdl_f2_formsubaf_map`
--
ALTER TABLE `mdl_f2_formsubaf_map`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2formmap_forsub_uix` (`formid`,`subafid`), ADD KEY `mdl_f2formmap_sub_ix` (`subafid`);

--
-- Indexes for table `mdl_f2_fornitori`
--
ALTER TABLE `mdl_f2_fornitori`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2forn_cod_uix` (`codice_fiscale`);

--
-- Indexes for table `mdl_f2_forzature`
--
ALTER TABLE `mdl_f2_forzature`
  ADD PRIMARY KEY (`id`), ADD KEY `index_pk` (`codice_fiscale`);

--
-- Indexes for table `mdl_f2_gest_codpart`
--
ALTER TABLE `mdl_f2_gest_codpart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_notif_corso`
--
ALTER TABLE `mdl_f2_notif_corso`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2noticors_id_id_id__uix` (`id_corso`,`id_edizione`,`id_tipo_notif`);

--
-- Indexes for table `mdl_f2_notif_templates`
--
ALTER TABLE `mdl_f2_notif_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_notif_template_log`
--
ALTER TABLE `mdl_f2_notif_template_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_notif_template_mailqueue`
--
ALTER TABLE `mdl_f2_notif_template_mailqueue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_notif_tipo`
--
ALTER TABLE `mdl_f2_notif_tipo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_org_budget`
--
ALTER TABLE `mdl_f2_org_budget`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2orgbudg_annorgtip_uix` (`anno`,`orgfk`,`tipo`);

--
-- Indexes for table `mdl_f2_parametri`
--
ALTER TABLE `mdl_f2_parametri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_partecipazioni`
--
ALTER TABLE `mdl_f2_partecipazioni`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_partialbdgt`
--
ALTER TABLE `mdl_f2_partialbdgt`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_pd`
--
ALTER TABLE `mdl_f2_pd`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_piani_di_studio`
--
ALTER TABLE `mdl_f2_piani_di_studio`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2piandistud_sf_ix` (`sf`);

--
-- Indexes for table `mdl_f2_prenotati`
--
ALTER TABLE `mdl_f2_prenotati`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2pren_anncouuse_uix` (`anno`,`courseid`,`userid`), ADD KEY `mdl_f2pren_couuse_ix` (`courseid`,`userid`), ADD KEY `mdl_f2pren_useann_ix` (`userid`,`anno`), ADD KEY `mdl_f2pren_annval_ix` (`anno`,`validato_sett`), ADD KEY `mdl_f2pren_annval2_ix` (`anno`,`validato_dir`), ADD KEY `mdl_f2pren_anncouuse_ix` (`anno`,`courseid`,`userid`), ADD KEY `mdl_f2pren_sed_ix` (`sede`), ADD KEY `mdl_f2pren_isd_ix` (`isdeleted`);

--
-- Indexes for table `mdl_f2_report_pentaho`
--
ALTER TABLE `mdl_f2_report_pentaho`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_f2_report_full_path` (`full_path`(255));

--
-- Indexes for table `mdl_f2_report_pentaho_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_formind`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_f2_report_full_path_fi` (`full_path`(255)), ADD UNIQUE KEY `idx_f2_report_nome_fi` (`nome`(255));

--
-- Indexes for table `mdl_f2_report_pentaho_param`
--
ALTER TABLE `mdl_f2_report_pentaho_param`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_param_formind`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_map`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentparamap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentparamap_id_2_ix` (`id_report_param`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_map_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_formind`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentparamap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentparamap_id_2_ix` (`id_report_param`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_map_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_partecipazione`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentparamap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentparamap_id_2_ix` (`id_report_param`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_map_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_questionari`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentparamap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentparamap_id_2_ix` (`id_report_param`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_map_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_stat`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentparamap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentparamap_id_2_ix` (`id_report_param`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_map_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_visual_on_line`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentparamap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentparamap_id_2_ix` (`id_report_param`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_param_partecipazione`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_param_questionari`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_param_stat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_report_pentaho_param_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_param_visual_on_line`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_report_pentaho_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_partecipazione`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_f2_report_full_path_st` (`full_path`(255)), ADD UNIQUE KEY `idx_f2_report_nome_st` (`nome`(255));

--
-- Indexes for table `mdl_f2_report_pentaho_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_questionari`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_f2_report_full_path_st` (`full_path`(255)), ADD UNIQUE KEY `idx_f2_report_nome_st` (`nome`(255));

--
-- Indexes for table `mdl_f2_report_pentaho_role_map`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentrolemap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentrolemap_id_2_ix` (`id_role`);

--
-- Indexes for table `mdl_f2_report_pentaho_role_map_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_formind`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentrolemap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentrolemap_id_2_ix` (`id_role`);

--
-- Indexes for table `mdl_f2_report_pentaho_role_map_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_partecipazione`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentrolemap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentrolemap_id_2_ix` (`id_role`);

--
-- Indexes for table `mdl_f2_report_pentaho_role_map_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_questionari`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentrolemap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentrolemap_id_2_ix` (`id_role`);

--
-- Indexes for table `mdl_f2_report_pentaho_role_map_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_stat`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentrolemap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentrolemap_id_2_ix` (`id_role`);

--
-- Indexes for table `mdl_f2_report_pentaho_role_map_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_visual_on_line`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2repopentrolemap_id__ix` (`id_report`), ADD KEY `mdl_f2repopentrolemap_id_2_ix` (`id_role`);

--
-- Indexes for table `mdl_f2_report_pentaho_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_stat`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_f2_report_full_path_st` (`full_path`(255)), ADD UNIQUE KEY `idx_f2_report_nome_st` (`nome`(255));

--
-- Indexes for table `mdl_f2_report_pentaho_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_visual_on_line`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_f2_report_full_path_st` (`full_path`(255)), ADD UNIQUE KEY `idx_f2_report_nome_st` (`nome`(255));

--
-- Indexes for table `mdl_f2_saf`
--
ALTER TABLE `mdl_f2_saf`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2saf_afsub_uix` (`af`,`sub`), ADD KEY `mdl_f2saf_af_ix` (`af`), ADD KEY `mdl_f2saf_sub_ix` (`sub`);

--
-- Indexes for table `mdl_f2_scheda_progetto`
--
ALTER TABLE `mdl_f2_scheda_progetto`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2scheprog_cou_uix` (`courseid`);

--
-- Indexes for table `mdl_f2_sedi`
--
ALTER TABLE `mdl_f2_sedi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_sessioni`
--
ALTER TABLE `mdl_f2_sessioni`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2sess_numann_uix` (`numero`,`anno`);

--
-- Indexes for table `mdl_f2_sf`
--
ALTER TABLE `mdl_f2_sf`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_sf_af_map`
--
ALTER TABLE `mdl_f2_sf_af_map`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_f2sfafmap_sf_ix` (`sf`), ADD KEY `mdl_f2sfafmap_af_ix` (`af`);

--
-- Indexes for table `mdl_f2_stati_funz`
--
ALTER TABLE `mdl_f2_stati_funz`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_stati_validazione`
--
ALTER TABLE `mdl_f2_stati_validazione`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_f2statvali_annorg_uix` (`anno`,`orgid`), ADD KEY `mdl_f2statvali_ann_ix` (`anno`), ADD KEY `mdl_f2statvali_org_ix` (`orgid`);

--
-- Indexes for table `mdl_f2_storico_corsi`
--
ALTER TABLE `mdl_f2_storico_corsi`
  ADD PRIMARY KEY (`id`), ADD KEY `matricola` (`matricola`), ADD KEY `f2_storico_corsi_cd_startdt_ix` (`codcorso`,`data_inizio`);

--
-- Indexes for table `mdl_f2_subaf`
--
ALTER TABLE `mdl_f2_subaf`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_te`
--
ALTER TABLE `mdl_f2_te`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_tipo`
--
ALTER TABLE `mdl_f2_tipo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_tipo_pianificazione`
--
ALTER TABLE `mdl_f2_tipo_pianificazione`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_totali_crediti`
--
ALTER TABLE `mdl_f2_totali_crediti`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_to_x`
--
ALTER TABLE `mdl_f2_to_x`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_va`
--
ALTER TABLE `mdl_f2_va`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_f2_webi_piani_di_studio`
--
ALTER TABLE `mdl_f2_webi_piani_di_studio`
  ADD PRIMARY KEY (`matricola`);

--
-- Indexes for table `mdl_facetoface`
--
ALTER TABLE `mdl_facetoface`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_face_cou_ix` (`course`), ADD KEY `mdl_face_f2s_ix` (`f2session`);

--
-- Indexes for table `mdl_facetoface_notice`
--
ALTER TABLE `mdl_facetoface_notice`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_facetoface_notice_data`
--
ALTER TABLE `mdl_facetoface_notice_data`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_facenotidata_fie_ix` (`fieldid`);

--
-- Indexes for table `mdl_facetoface_sessions`
--
ALTER TABLE `mdl_facetoface_sessions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_facesess_fac_ix` (`facetoface`);

--
-- Indexes for table `mdl_facetoface_sessions_dates`
--
ALTER TABLE `mdl_facetoface_sessions_dates`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_facesessdate_ses_ix` (`sessionid`);

--
-- Indexes for table `mdl_facetoface_sessions_docenti`
--
ALTER TABLE `mdl_facetoface_sessions_docenti`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_facesessdoce_ses_ix` (`sessionid`), ADD KEY `mdl_facesessdoce_use_ix` (`userid`);

--
-- Indexes for table `mdl_facetoface_session_data`
--
ALTER TABLE `mdl_facetoface_session_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_facetoface_session_field`
--
ALTER TABLE `mdl_facetoface_session_field`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_facesessfiel_sho_uix` (`shortname`);

--
-- Indexes for table `mdl_facetoface_session_roles`
--
ALTER TABLE `mdl_facetoface_session_roles`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_facesessrole_ses_ix` (`sessionid`);

--
-- Indexes for table `mdl_facetoface_signups`
--
ALTER TABLE `mdl_facetoface_signups`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_facesign_ses_ix` (`sessionid`);

--
-- Indexes for table `mdl_facetoface_signups_status`
--
ALTER TABLE `mdl_facetoface_signups_status`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_facesignstat_sig_ix` (`signupid`);

--
-- Indexes for table `mdl_feedback`
--
ALTER TABLE `mdl_feedback`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feed_cou_ix` (`course`);

--
-- Indexes for table `mdl_feedback_completed`
--
ALTER TABLE `mdl_feedback_completed`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedcomp_use_ix` (`userid`), ADD KEY `mdl_feedcomp_fee_ix` (`feedback`);

--
-- Indexes for table `mdl_feedback_completedtmp`
--
ALTER TABLE `mdl_feedback_completedtmp`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedcomp_use2_ix` (`userid`), ADD KEY `mdl_feedcomp_fee2_ix` (`feedback`);

--
-- Indexes for table `mdl_feedback_completed_session`
--
ALTER TABLE `mdl_feedback_completed_session`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedcompsess_ses_ix` (`session`), ADD KEY `mdl_feedcompsess_fee_ix` (`feedback`), ADD KEY `mdl_feedcompsess_com_ix` (`completed`);

--
-- Indexes for table `mdl_feedback_item`
--
ALTER TABLE `mdl_feedback_item`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feeditem_fee_ix` (`feedback`), ADD KEY `mdl_feeditem_tem_ix` (`template`);

--
-- Indexes for table `mdl_feedback_sitecourse_map`
--
ALTER TABLE `mdl_feedback_sitecourse_map`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedsitemap_cou_ix` (`courseid`), ADD KEY `mdl_feedsitemap_fee_ix` (`feedbackid`);

--
-- Indexes for table `mdl_feedback_template`
--
ALTER TABLE `mdl_feedback_template`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedtemp_cou_ix` (`course`);

--
-- Indexes for table `mdl_feedback_tracking`
--
ALTER TABLE `mdl_feedback_tracking`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedtrac_use_ix` (`userid`), ADD KEY `mdl_feedtrac_fee_ix` (`feedback`), ADD KEY `mdl_feedtrac_com_ix` (`completed`);

--
-- Indexes for table `mdl_feedback_value`
--
ALTER TABLE `mdl_feedback_value`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedvalu_cou_ix` (`course_id`), ADD KEY `mdl_feedvalu_ite_ix` (`item`);

--
-- Indexes for table `mdl_feedback_valuetmp`
--
ALTER TABLE `mdl_feedback_valuetmp`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_feedvalu_cou2_ix` (`course_id`), ADD KEY `mdl_feedvalu_ite2_ix` (`item`);

--
-- Indexes for table `mdl_files`
--
ALTER TABLE `mdl_files`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_file_pat_uix` (`pathnamehash`), ADD KEY `mdl_file_comfilconite_ix` (`component`,`filearea`,`contextid`,`itemid`), ADD KEY `mdl_file_con_ix` (`contenthash`), ADD KEY `mdl_file_con2_ix` (`contextid`), ADD KEY `mdl_file_use_ix` (`userid`), ADD KEY `mdl_file_ref_ix` (`referencefileid`);

--
-- Indexes for table `mdl_files_reference`
--
ALTER TABLE `mdl_files_reference`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_filerefe_refrep_uix` (`referencehash`,`repositoryid`), ADD KEY `mdl_filerefe_rep_ix` (`repositoryid`);

--
-- Indexes for table `mdl_filter_active`
--
ALTER TABLE `mdl_filter_active`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_filtacti_confil_uix` (`contextid`,`filter`), ADD KEY `mdl_filtacti_con_ix` (`contextid`);

--
-- Indexes for table `mdl_filter_config`
--
ALTER TABLE `mdl_filter_config`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_filtconf_confilnam_uix` (`contextid`,`filter`,`name`), ADD KEY `mdl_filtconf_con_ix` (`contextid`);

--
-- Indexes for table `mdl_folder`
--
ALTER TABLE `mdl_folder`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_fold_cou_ix` (`course`);

--
-- Indexes for table `mdl_format_grid_icon`
--
ALTER TABLE `mdl_format_grid_icon`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_formgridicon_sec_uix` (`sectionid`);

--
-- Indexes for table `mdl_format_grid_summary`
--
ALTER TABLE `mdl_format_grid_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_forum`
--
ALTER TABLE `mdl_forum`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_foru_cou_ix` (`course`);

--
-- Indexes for table `mdl_forum_digests`
--
ALTER TABLE `mdl_forum_digests`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_forudige_forusemai_uix` (`forum`,`userid`,`maildigest`), ADD KEY `mdl_forudige_use_ix` (`userid`), ADD KEY `mdl_forudige_for_ix` (`forum`);

--
-- Indexes for table `mdl_forum_discussions`
--
ALTER TABLE `mdl_forum_discussions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_forudisc_use_ix` (`userid`), ADD KEY `mdl_forudisc_for_ix` (`forum`);

--
-- Indexes for table `mdl_forum_posts`
--
ALTER TABLE `mdl_forum_posts`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_forupost_use_ix` (`userid`), ADD KEY `mdl_forupost_cre_ix` (`created`), ADD KEY `mdl_forupost_mai_ix` (`mailed`), ADD KEY `mdl_forupost_dis_ix` (`discussion`), ADD KEY `mdl_forupost_par_ix` (`parent`);

--
-- Indexes for table `mdl_forum_queue`
--
ALTER TABLE `mdl_forum_queue`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_foruqueu_use_ix` (`userid`), ADD KEY `mdl_foruqueu_dis_ix` (`discussionid`), ADD KEY `mdl_foruqueu_pos_ix` (`postid`);

--
-- Indexes for table `mdl_forum_read`
--
ALTER TABLE `mdl_forum_read`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_foruread_usefor_ix` (`userid`,`forumid`), ADD KEY `mdl_foruread_usedis_ix` (`userid`,`discussionid`), ADD KEY `mdl_foruread_posuse_ix` (`postid`,`userid`);

--
-- Indexes for table `mdl_forum_subscriptions`
--
ALTER TABLE `mdl_forum_subscriptions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_forusubs_use_ix` (`userid`), ADD KEY `mdl_forusubs_for_ix` (`forum`);

--
-- Indexes for table `mdl_forum_track_prefs`
--
ALTER TABLE `mdl_forum_track_prefs`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_forutracpref_usefor_ix` (`userid`,`forumid`);

--
-- Indexes for table `mdl_game`
--
ALTER TABLE `mdl_game`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_attempts`
--
ALTER TABLE `mdl_game_attempts`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gameatte_gamusetim_ix` (`gameid`,`userid`,`timefinish`);

--
-- Indexes for table `mdl_game_bookquiz`
--
ALTER TABLE `mdl_game_bookquiz`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_bookquiz_chapters`
--
ALTER TABLE `mdl_game_bookquiz_chapters`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gamebookchap_attcha_ix` (`attemptid`,`chapterid`);

--
-- Indexes for table `mdl_game_bookquiz_questions`
--
ALTER TABLE `mdl_game_bookquiz_questions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gamebookques_gamcha_ix` (`gameid`,`chapterid`);

--
-- Indexes for table `mdl_game_cross`
--
ALTER TABLE `mdl_game_cross`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_cryptex`
--
ALTER TABLE `mdl_game_cryptex`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_export_html`
--
ALTER TABLE `mdl_game_export_html`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_export_javame`
--
ALTER TABLE `mdl_game_export_javame`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_grades`
--
ALTER TABLE `mdl_game_grades`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gamegrad_use_ix` (`userid`), ADD KEY `mdl_gamegrad_gam_ix` (`gameid`);

--
-- Indexes for table `mdl_game_hangman`
--
ALTER TABLE `mdl_game_hangman`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_hiddenpicture`
--
ALTER TABLE `mdl_game_hiddenpicture`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_millionaire`
--
ALTER TABLE `mdl_game_millionaire`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_queries`
--
ALTER TABLE `mdl_game_queries`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gamequer_att_ix` (`attemptid`);

--
-- Indexes for table `mdl_game_repetitions`
--
ALTER TABLE `mdl_game_repetitions`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gamerepe_gamusequeglo_uix` (`gameid`,`userid`,`questionid`,`glossaryentryid`);

--
-- Indexes for table `mdl_game_snakes`
--
ALTER TABLE `mdl_game_snakes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_snakes_database`
--
ALTER TABLE `mdl_game_snakes_database`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_sudoku`
--
ALTER TABLE `mdl_game_sudoku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_game_sudoku_database`
--
ALTER TABLE `mdl_game_sudoku_database`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gamesudodata_dat_uix` (`data`);

--
-- Indexes for table `mdl_glossary`
--
ALTER TABLE `mdl_glossary`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_glos_cou_ix` (`course`);

--
-- Indexes for table `mdl_glossary_alias`
--
ALTER TABLE `mdl_glossary_alias`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_glosalia_ent_ix` (`entryid`);

--
-- Indexes for table `mdl_glossary_categories`
--
ALTER TABLE `mdl_glossary_categories`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gloscate_glo_ix` (`glossaryid`);

--
-- Indexes for table `mdl_glossary_entries`
--
ALTER TABLE `mdl_glossary_entries`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_glosentr_use_ix` (`userid`), ADD KEY `mdl_glosentr_con_ix` (`concept`), ADD KEY `mdl_glosentr_glo_ix` (`glossaryid`);

--
-- Indexes for table `mdl_glossary_entries_categories`
--
ALTER TABLE `mdl_glossary_entries_categories`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_glosentrcate_cat_ix` (`categoryid`), ADD KEY `mdl_glosentrcate_ent_ix` (`entryid`);

--
-- Indexes for table `mdl_glossary_formats`
--
ALTER TABLE `mdl_glossary_formats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_grade_categories`
--
ALTER TABLE `mdl_grade_categories`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradcate_cou_ix` (`courseid`), ADD KEY `mdl_gradcate_par_ix` (`parent`);

--
-- Indexes for table `mdl_grade_categories_history`
--
ALTER TABLE `mdl_grade_categories_history`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradcatehist_act_ix` (`action`), ADD KEY `mdl_gradcatehist_old_ix` (`oldid`), ADD KEY `mdl_gradcatehist_cou_ix` (`courseid`), ADD KEY `mdl_gradcatehist_par_ix` (`parent`), ADD KEY `mdl_gradcatehist_log_ix` (`loggeduser`);

--
-- Indexes for table `mdl_grade_grades`
--
ALTER TABLE `mdl_grade_grades`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradgrad_useite_uix` (`userid`,`itemid`), ADD KEY `mdl_gradgrad_locloc_ix` (`locked`,`locktime`), ADD KEY `mdl_gradgrad_ite_ix` (`itemid`), ADD KEY `mdl_gradgrad_use_ix` (`userid`), ADD KEY `mdl_gradgrad_raw_ix` (`rawscaleid`), ADD KEY `mdl_gradgrad_use2_ix` (`usermodified`);

--
-- Indexes for table `mdl_grade_grades_history`
--
ALTER TABLE `mdl_grade_grades_history`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradgradhist_act_ix` (`action`), ADD KEY `mdl_gradgradhist_old_ix` (`oldid`), ADD KEY `mdl_gradgradhist_ite_ix` (`itemid`), ADD KEY `mdl_gradgradhist_use_ix` (`userid`), ADD KEY `mdl_gradgradhist_raw_ix` (`rawscaleid`), ADD KEY `mdl_gradgradhist_use2_ix` (`usermodified`), ADD KEY `mdl_gradgradhist_log_ix` (`loggeduser`), ADD KEY `mdl_gradgradhist_tim_ix` (`timemodified`);

--
-- Indexes for table `mdl_grade_import_newitem`
--
ALTER TABLE `mdl_grade_import_newitem`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradimponewi_imp_ix` (`importer`);

--
-- Indexes for table `mdl_grade_import_values`
--
ALTER TABLE `mdl_grade_import_values`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradimpovalu_ite_ix` (`itemid`), ADD KEY `mdl_gradimpovalu_new_ix` (`newgradeitem`), ADD KEY `mdl_gradimpovalu_imp_ix` (`importer`);

--
-- Indexes for table `mdl_grade_items`
--
ALTER TABLE `mdl_grade_items`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_graditem_locloc_ix` (`locked`,`locktime`), ADD KEY `mdl_graditem_itenee_ix` (`itemtype`,`needsupdate`), ADD KEY `mdl_graditem_gra_ix` (`gradetype`), ADD KEY `mdl_graditem_idncou_ix` (`idnumber`,`courseid`), ADD KEY `mdl_graditem_cou_ix` (`courseid`), ADD KEY `mdl_graditem_cat_ix` (`categoryid`), ADD KEY `mdl_graditem_sca_ix` (`scaleid`), ADD KEY `mdl_graditem_out_ix` (`outcomeid`);

--
-- Indexes for table `mdl_grade_items_history`
--
ALTER TABLE `mdl_grade_items_history`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_graditemhist_act_ix` (`action`), ADD KEY `mdl_graditemhist_old_ix` (`oldid`), ADD KEY `mdl_graditemhist_cou_ix` (`courseid`), ADD KEY `mdl_graditemhist_cat_ix` (`categoryid`), ADD KEY `mdl_graditemhist_sca_ix` (`scaleid`), ADD KEY `mdl_graditemhist_out_ix` (`outcomeid`), ADD KEY `mdl_graditemhist_log_ix` (`loggeduser`);

--
-- Indexes for table `mdl_grade_letters`
--
ALTER TABLE `mdl_grade_letters`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradlett_conlowlet_uix` (`contextid`,`lowerboundary`,`letter`);

--
-- Indexes for table `mdl_grade_outcomes`
--
ALTER TABLE `mdl_grade_outcomes`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradoutc_cousho_uix` (`courseid`,`shortname`), ADD KEY `mdl_gradoutc_cou_ix` (`courseid`), ADD KEY `mdl_gradoutc_sca_ix` (`scaleid`), ADD KEY `mdl_gradoutc_use_ix` (`usermodified`);

--
-- Indexes for table `mdl_grade_outcomes_courses`
--
ALTER TABLE `mdl_grade_outcomes_courses`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradoutccour_couout_uix` (`courseid`,`outcomeid`), ADD KEY `mdl_gradoutccour_cou_ix` (`courseid`), ADD KEY `mdl_gradoutccour_out_ix` (`outcomeid`);

--
-- Indexes for table `mdl_grade_outcomes_history`
--
ALTER TABLE `mdl_grade_outcomes_history`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradoutchist_act_ix` (`action`), ADD KEY `mdl_gradoutchist_old_ix` (`oldid`), ADD KEY `mdl_gradoutchist_cou_ix` (`courseid`), ADD KEY `mdl_gradoutchist_sca_ix` (`scaleid`), ADD KEY `mdl_gradoutchist_log_ix` (`loggeduser`);

--
-- Indexes for table `mdl_grade_settings`
--
ALTER TABLE `mdl_grade_settings`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradsett_counam_uix` (`courseid`,`name`), ADD KEY `mdl_gradsett_cou_ix` (`courseid`);

--
-- Indexes for table `mdl_gradingform_guide_comments`
--
ALTER TABLE `mdl_gradingform_guide_comments`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradguidcomm_def_ix` (`definitionid`);

--
-- Indexes for table `mdl_gradingform_guide_criteria`
--
ALTER TABLE `mdl_gradingform_guide_criteria`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradguidcrit_def_ix` (`definitionid`);

--
-- Indexes for table `mdl_gradingform_guide_fillings`
--
ALTER TABLE `mdl_gradingform_guide_fillings`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradguidfill_inscri_uix` (`instanceid`,`criterionid`), ADD KEY `mdl_gradguidfill_ins_ix` (`instanceid`), ADD KEY `mdl_gradguidfill_cri_ix` (`criterionid`);

--
-- Indexes for table `mdl_gradingform_rubric_criteria`
--
ALTER TABLE `mdl_gradingform_rubric_criteria`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradrubrcrit_def_ix` (`definitionid`);

--
-- Indexes for table `mdl_gradingform_rubric_fillings`
--
ALTER TABLE `mdl_gradingform_rubric_fillings`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradrubrfill_inscri_uix` (`instanceid`,`criterionid`), ADD KEY `mdl_gradrubrfill_lev_ix` (`levelid`), ADD KEY `mdl_gradrubrfill_ins_ix` (`instanceid`), ADD KEY `mdl_gradrubrfill_cri_ix` (`criterionid`);

--
-- Indexes for table `mdl_gradingform_rubric_levels`
--
ALTER TABLE `mdl_gradingform_rubric_levels`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradrubrleve_cri_ix` (`criterionid`);

--
-- Indexes for table `mdl_grading_areas`
--
ALTER TABLE `mdl_grading_areas`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_gradarea_concomare_uix` (`contextid`,`component`,`areaname`), ADD KEY `mdl_gradarea_con_ix` (`contextid`);

--
-- Indexes for table `mdl_grading_definitions`
--
ALTER TABLE `mdl_grading_definitions`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_graddefi_aremet_uix` (`areaid`,`method`), ADD KEY `mdl_graddefi_are_ix` (`areaid`), ADD KEY `mdl_graddefi_use_ix` (`usermodified`), ADD KEY `mdl_graddefi_use2_ix` (`usercreated`);

--
-- Indexes for table `mdl_grading_instances`
--
ALTER TABLE `mdl_grading_instances`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_gradinst_def_ix` (`definitionid`), ADD KEY `mdl_gradinst_rat_ix` (`raterid`);

--
-- Indexes for table `mdl_groupings`
--
ALTER TABLE `mdl_groupings`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_grou_cou2_ix` (`courseid`), ADD KEY `mdl_grou_idn2_ix` (`idnumber`);

--
-- Indexes for table `mdl_groupings_groups`
--
ALTER TABLE `mdl_groupings_groups`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_grougrou_gro_ix` (`groupingid`), ADD KEY `mdl_grougrou_gro2_ix` (`groupid`);

--
-- Indexes for table `mdl_groups`
--
ALTER TABLE `mdl_groups`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_grou_cou_ix` (`courseid`), ADD KEY `mdl_grou_idn_ix` (`idnumber`);

--
-- Indexes for table `mdl_groups_members`
--
ALTER TABLE `mdl_groups_members`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_groumemb_gro_ix` (`groupid`), ADD KEY `mdl_groumemb_use_ix` (`userid`);

--
-- Indexes for table `mdl_hbackup_temp_items`
--
ALTER TABLE `mdl_hbackup_temp_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp`
--
ALTER TABLE `mdl_hvp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp_auth`
--
ALTER TABLE `mdl_hvp_auth`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_hvpauth_use_uix` (`user_id`);

--
-- Indexes for table `mdl_hvp_contents_libraries`
--
ALTER TABLE `mdl_hvp_contents_libraries`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_hvpcontlibr_dro_ix` (`drop_css`);

--
-- Indexes for table `mdl_hvp_content_user_data`
--
ALTER TABLE `mdl_hvp_content_user_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp_counters`
--
ALTER TABLE `mdl_hvp_counters`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_hvpcoun_typliblib_ix` (`type`,`library_name`,`library_version`);

--
-- Indexes for table `mdl_hvp_events`
--
ALTER TABLE `mdl_hvp_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp_libraries`
--
ALTER TABLE `mdl_hvp_libraries`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_hvplibr_macmajminpatrun_ix` (`machine_name`,`major_version`,`minor_version`,`patch_version`,`runnable`);

--
-- Indexes for table `mdl_hvp_libraries_cachedassets`
--
ALTER TABLE `mdl_hvp_libraries_cachedassets`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_hvplibrcach_libhas_uix` (`library_id`,`hash`);

--
-- Indexes for table `mdl_hvp_libraries_hub_cache`
--
ALTER TABLE `mdl_hvp_libraries_hub_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp_libraries_languages`
--
ALTER TABLE `mdl_hvp_libraries_languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp_libraries_libraries`
--
ALTER TABLE `mdl_hvp_libraries_libraries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp_tmpfiles`
--
ALTER TABLE `mdl_hvp_tmpfiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_hvp_xapi_results`
--
ALTER TABLE `mdl_hvp_xapi_results`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_hvpxapiresu_idconuse_uix` (`id`,`content_id`,`user_id`);

--
-- Indexes for table `mdl_imscp`
--
ALTER TABLE `mdl_imscp`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_imsc_cou_ix` (`course`);

--
-- Indexes for table `mdl_label`
--
ALTER TABLE `mdl_label`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_labe_cou_ix` (`course`);

--
-- Indexes for table `mdl_lesson`
--
ALTER TABLE `mdl_lesson`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_less_cou_ix` (`course`);

--
-- Indexes for table `mdl_lesson_answers`
--
ALTER TABLE `mdl_lesson_answers`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lessansw_les_ix` (`lessonid`), ADD KEY `mdl_lessansw_pag_ix` (`pageid`);

--
-- Indexes for table `mdl_lesson_attempts`
--
ALTER TABLE `mdl_lesson_attempts`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lessatte_use_ix` (`userid`), ADD KEY `mdl_lessatte_les_ix` (`lessonid`), ADD KEY `mdl_lessatte_pag_ix` (`pageid`), ADD KEY `mdl_lessatte_ans_ix` (`answerid`);

--
-- Indexes for table `mdl_lesson_branch`
--
ALTER TABLE `mdl_lesson_branch`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lessbran_use_ix` (`userid`), ADD KEY `mdl_lessbran_les_ix` (`lessonid`), ADD KEY `mdl_lessbran_pag_ix` (`pageid`);

--
-- Indexes for table `mdl_lesson_grades`
--
ALTER TABLE `mdl_lesson_grades`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lessgrad_use_ix` (`userid`), ADD KEY `mdl_lessgrad_les_ix` (`lessonid`);

--
-- Indexes for table `mdl_lesson_high_scores`
--
ALTER TABLE `mdl_lesson_high_scores`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lesshighscor_use_ix` (`userid`), ADD KEY `mdl_lesshighscor_les_ix` (`lessonid`);

--
-- Indexes for table `mdl_lesson_pages`
--
ALTER TABLE `mdl_lesson_pages`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lesspage_les_ix` (`lessonid`);

--
-- Indexes for table `mdl_lesson_timer`
--
ALTER TABLE `mdl_lesson_timer`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lesstime_use_ix` (`userid`), ADD KEY `mdl_lesstime_les_ix` (`lessonid`);

--
-- Indexes for table `mdl_license`
--
ALTER TABLE `mdl_license`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_local_f2_notif`
--
ALTER TABLE `mdl_local_f2_notif`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_lock_db`
--
ALTER TABLE `mdl_lock_db`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_lockdb_res_uix` (`resourcekey`), ADD KEY `mdl_lockdb_exp_ix` (`expires`), ADD KEY `mdl_lockdb_own_ix` (`owner`);

--
-- Indexes for table `mdl_log`
--
ALTER TABLE `mdl_log`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_log_coumodact_ix` (`course`,`module`,`action`), ADD KEY `mdl_log_tim_ix` (`time`), ADD KEY `mdl_log_act_ix` (`action`), ADD KEY `mdl_log_usecou_ix` (`userid`,`course`), ADD KEY `mdl_log_cmi_ix` (`cmid`);

--
-- Indexes for table `mdl_logstore_standard_log`
--
ALTER TABLE `mdl_logstore_standard_log`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_logsstanlog_tim_ix` (`timecreated`), ADD KEY `mdl_logsstanlog_couanotim_ix` (`courseid`,`anonymous`,`timecreated`), ADD KEY `mdl_logsstanlog_useconconcr_ix` (`userid`,`contextlevel`,`contextinstanceid`,`crud`,`edulevel`,`timecreated`);

--
-- Indexes for table `mdl_log_corsi_ind`
--
ALTER TABLE `mdl_log_corsi_ind`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_log_corsi_ind_archiviazione`
--
ALTER TABLE `mdl_log_corsi_ind_archiviazione`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_log_corsi_ind_prot`
--
ALTER TABLE `mdl_log_corsi_ind_prot`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_log_display`
--
ALTER TABLE `mdl_log_display`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_logdisp_modact_uix` (`module`,`action`);

--
-- Indexes for table `mdl_log_queries`
--
ALTER TABLE `mdl_log_queries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_lti`
--
ALTER TABLE `mdl_lti`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_lti_cou_ix` (`course`);

--
-- Indexes for table `mdl_lti_submission`
--
ALTER TABLE `mdl_lti_submission`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_ltisubm_lti_ix` (`ltiid`);

--
-- Indexes for table `mdl_lti_types`
--
ALTER TABLE `mdl_lti_types`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_ltitype_cou_ix` (`course`), ADD KEY `mdl_ltitype_too_ix` (`tooldomain`);

--
-- Indexes for table `mdl_lti_types_config`
--
ALTER TABLE `mdl_lti_types_config`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_ltitypeconf_typ_ix` (`typeid`);

--
-- Indexes for table `mdl_message`
--
ALTER TABLE `mdl_message`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_mess_use_ix` (`useridfrom`), ADD KEY `mdl_mess_use2_ix` (`useridto`);

--
-- Indexes for table `mdl_message_airnotifier_devices`
--
ALTER TABLE `mdl_message_airnotifier_devices`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_messairndevi_use_uix` (`userdeviceid`);

--
-- Indexes for table `mdl_message_contacts`
--
ALTER TABLE `mdl_message_contacts`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_messcont_usecon_uix` (`userid`,`contactid`);

--
-- Indexes for table `mdl_message_processors`
--
ALTER TABLE `mdl_message_processors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_message_providers`
--
ALTER TABLE `mdl_message_providers`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_messprov_comnam_uix` (`component`,`name`);

--
-- Indexes for table `mdl_message_read`
--
ALTER TABLE `mdl_message_read`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_messread_use_ix` (`useridfrom`), ADD KEY `mdl_messread_use2_ix` (`useridto`);

--
-- Indexes for table `mdl_message_working`
--
ALTER TABLE `mdl_message_working`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_messwork_unr_ix` (`unreadmessageid`);

--
-- Indexes for table `mdl_mnetservice_enrol_courses`
--
ALTER TABLE `mdl_mnetservice_enrol_courses`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_mnetenrocour_hosrem_uix` (`hostid`,`remoteid`);

--
-- Indexes for table `mdl_mnetservice_enrol_enrolments`
--
ALTER TABLE `mdl_mnetservice_enrol_enrolments`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_mnetenroenro_use_ix` (`userid`), ADD KEY `mdl_mnetenroenro_hos_ix` (`hostid`);

--
-- Indexes for table `mdl_mnet_application`
--
ALTER TABLE `mdl_mnet_application`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_mnet_host`
--
ALTER TABLE `mdl_mnet_host`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_mnethost_app_ix` (`applicationid`);

--
-- Indexes for table `mdl_mnet_host2service`
--
ALTER TABLE `mdl_mnet_host2service`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_mnethost_hosser_uix` (`hostid`,`serviceid`);

--
-- Indexes for table `mdl_mnet_log`
--
ALTER TABLE `mdl_mnet_log`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_mnetlog_hosusecou_ix` (`hostid`,`userid`,`course`);

--
-- Indexes for table `mdl_mnet_remote_rpc`
--
ALTER TABLE `mdl_mnet_remote_rpc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_mnet_remote_service2rpc`
--
ALTER TABLE `mdl_mnet_remote_service2rpc`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_mnetremoserv_rpcser_uix` (`rpcid`,`serviceid`);

--
-- Indexes for table `mdl_mnet_rpc`
--
ALTER TABLE `mdl_mnet_rpc`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_mnetrpc_enaxml_ix` (`enabled`,`xmlrpcpath`);

--
-- Indexes for table `mdl_mnet_service`
--
ALTER TABLE `mdl_mnet_service`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_mnet_service2rpc`
--
ALTER TABLE `mdl_mnet_service2rpc`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_mnetserv_rpcser_uix` (`rpcid`,`serviceid`);

--
-- Indexes for table `mdl_mnet_session`
--
ALTER TABLE `mdl_mnet_session`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_mnetsess_tok_uix` (`token`);

--
-- Indexes for table `mdl_mnet_sso_access_control`
--
ALTER TABLE `mdl_mnet_sso_access_control`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_mnetssoaccecont_mneuse_uix` (`mnet_host_id`,`username`);

--
-- Indexes for table `mdl_modules`
--
ALTER TABLE `mdl_modules`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_modu_nam_ix` (`name`);

--
-- Indexes for table `mdl_my_pages`
--
ALTER TABLE `mdl_my_pages`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_mypage_usepri_ix` (`userid`,`private`);

--
-- Indexes for table `mdl_org`
--
ALTER TABLE `mdl_org`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_org_fra_ix` (`frameworkid`), ADD KEY `mdl_org_dep_ix` (`depthid`);

--
-- Indexes for table `mdl_org_assignment`
--
ALTER TABLE `mdl_org_assignment`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_orgassi_use_uix` (`userid`), ADD KEY `mdl_orgassi_org_ix` (`organisationid`), ADD KEY `mdl_orgassi_vie_ix` (`viewableorganisationid`);

--
-- Indexes for table `mdl_org_depth`
--
ALTER TABLE `mdl_org_depth`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_orgdept_fra_ix` (`frameworkid`);

--
-- Indexes for table `mdl_org_depth_info_category`
--
ALTER TABLE `mdl_org_depth_info_category`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_orgdeptinfocate_dep_ix` (`depthid`);

--
-- Indexes for table `mdl_org_depth_info_data`
--
ALTER TABLE `mdl_org_depth_info_data`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_orgdeptinfodata_org_ix` (`organisationid`), ADD KEY `mdl_orgdeptinfodata_fie_ix` (`fieldid`);

--
-- Indexes for table `mdl_org_depth_info_field`
--
ALTER TABLE `mdl_org_depth_info_field`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_orgdeptinfofiel_dep_ix` (`depthid`), ADD KEY `mdl_orgdeptinfofiel_cat_ix` (`categoryid`);

--
-- Indexes for table `mdl_org_framework`
--
ALTER TABLE `mdl_org_framework`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_orgfram_sor_uix` (`sortorder`);

--
-- Indexes for table `mdl_org_relations`
--
ALTER TABLE `mdl_org_relations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_page`
--
ALTER TABLE `mdl_page`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_page_cou_ix` (`course`);

--
-- Indexes for table `mdl_ponos_course`
--
ALTER TABLE `mdl_ponos_course`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_ponos_insert`
--
ALTER TABLE `mdl_ponos_insert`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_ponos_log`
--
ALTER TABLE `mdl_ponos_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_ponos_report`
--
ALTER TABLE `mdl_ponos_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_portfolio_instance`
--
ALTER TABLE `mdl_portfolio_instance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_portfolio_instance_config`
--
ALTER TABLE `mdl_portfolio_instance_config`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_portinstconf_nam_ix` (`name`), ADD KEY `mdl_portinstconf_ins_ix` (`instance`);

--
-- Indexes for table `mdl_portfolio_instance_user`
--
ALTER TABLE `mdl_portfolio_instance_user`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_portinstuser_ins_ix` (`instance`), ADD KEY `mdl_portinstuser_use_ix` (`userid`);

--
-- Indexes for table `mdl_portfolio_log`
--
ALTER TABLE `mdl_portfolio_log`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_portlog_use_ix` (`userid`), ADD KEY `mdl_portlog_por_ix` (`portfolio`);

--
-- Indexes for table `mdl_portfolio_mahara_queue`
--
ALTER TABLE `mdl_portfolio_mahara_queue`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_portmahaqueu_tok_ix` (`token`), ADD KEY `mdl_portmahaqueu_tra_ix` (`transferid`);

--
-- Indexes for table `mdl_portfolio_tempdata`
--
ALTER TABLE `mdl_portfolio_tempdata`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_porttemp_use_ix` (`userid`), ADD KEY `mdl_porttemp_ins_ix` (`instance`);

--
-- Indexes for table `mdl_post`
--
ALTER TABLE `mdl_post`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_post_iduse_uix` (`id`,`userid`), ADD KEY `mdl_post_las_ix` (`lastmodified`), ADD KEY `mdl_post_mod_ix` (`module`), ADD KEY `mdl_post_sub_ix` (`subject`), ADD KEY `mdl_post_use_ix` (`usermodified`);

--
-- Indexes for table `mdl_profiling`
--
ALTER TABLE `mdl_profiling`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_prof_run_uix` (`runid`), ADD KEY `mdl_prof_urlrun_ix` (`url`,`runreference`), ADD KEY `mdl_prof_timrun_ix` (`timecreated`,`runreference`);

--
-- Indexes for table `mdl_qtype_essay_options`
--
ALTER TABLE `mdl_qtype_essay_options`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_qtypessaopti_que_uix` (`questionid`);

--
-- Indexes for table `mdl_qtype_match_options`
--
ALTER TABLE `mdl_qtype_match_options`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_qtypmatcopti_que_uix` (`questionid`);

--
-- Indexes for table `mdl_qtype_match_subquestions`
--
ALTER TABLE `mdl_qtype_match_subquestions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_qtypmatcsubq_que_ix` (`questionid`);

--
-- Indexes for table `mdl_qtype_multichoice_options`
--
ALTER TABLE `mdl_qtype_multichoice_options`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_qtypmultopti_que_uix` (`questionid`);

--
-- Indexes for table `mdl_qtype_randomsamatch_options`
--
ALTER TABLE `mdl_qtype_randomsamatch_options`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_qtyprandopti_que_uix` (`questionid`);

--
-- Indexes for table `mdl_qtype_shortanswer_options`
--
ALTER TABLE `mdl_qtype_shortanswer_options`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quesshor_que_uix` (`questionid`);

--
-- Indexes for table `mdl_question`
--
ALTER TABLE `mdl_question`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_ques_cat_ix` (`category`), ADD KEY `mdl_ques_par_ix` (`parent`), ADD KEY `mdl_ques_cre_ix` (`createdby`), ADD KEY `mdl_ques_mod_ix` (`modifiedby`);

--
-- Indexes for table `mdl_question_answers`
--
ALTER TABLE `mdl_question_answers`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesansw_que_ix` (`question`);

--
-- Indexes for table `mdl_question_attempts`
--
ALTER TABLE `mdl_question_attempts`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quesatte_queslo_uix` (`questionusageid`,`slot`), ADD KEY `mdl_quesatte_que_ix` (`questionid`), ADD KEY `mdl_quesatte_que2_ix` (`questionusageid`), ADD KEY `mdl_quesatte_beh_ix` (`behaviour`);

--
-- Indexes for table `mdl_question_attempt_steps`
--
ALTER TABLE `mdl_question_attempt_steps`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quesattestep_queseq_uix` (`questionattemptid`,`sequencenumber`), ADD KEY `mdl_quesattestep_que_ix` (`questionattemptid`), ADD KEY `mdl_quesattestep_use_ix` (`userid`);

--
-- Indexes for table `mdl_question_attempt_step_data`
--
ALTER TABLE `mdl_question_attempt_step_data`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quesattestepdata_attna_uix` (`attemptstepid`,`name`), ADD KEY `mdl_quesattestepdata_att_ix` (`attemptstepid`);

--
-- Indexes for table `mdl_question_calculated`
--
ALTER TABLE `mdl_question_calculated`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quescalc_ans_ix` (`answer`), ADD KEY `mdl_quescalc_que_ix` (`question`);

--
-- Indexes for table `mdl_question_calculated_options`
--
ALTER TABLE `mdl_question_calculated_options`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quescalcopti_que_ix` (`question`);

--
-- Indexes for table `mdl_question_categories`
--
ALTER TABLE `mdl_question_categories`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quescate_con_ix` (`contextid`), ADD KEY `mdl_quescate_par_ix` (`parent`);

--
-- Indexes for table `mdl_question_datasets`
--
ALTER TABLE `mdl_question_datasets`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesdata_quedat_ix` (`question`,`datasetdefinition`), ADD KEY `mdl_quesdata_que_ix` (`question`), ADD KEY `mdl_quesdata_dat_ix` (`datasetdefinition`);

--
-- Indexes for table `mdl_question_dataset_definitions`
--
ALTER TABLE `mdl_question_dataset_definitions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesdatadefi_cat_ix` (`category`);

--
-- Indexes for table `mdl_question_dataset_items`
--
ALTER TABLE `mdl_question_dataset_items`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesdataitem_def_ix` (`definition`);

--
-- Indexes for table `mdl_question_hints`
--
ALTER TABLE `mdl_question_hints`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_queshint_que_ix` (`questionid`);

--
-- Indexes for table `mdl_question_multianswer`
--
ALTER TABLE `mdl_question_multianswer`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesmult_que_ix` (`question`);

--
-- Indexes for table `mdl_question_numerical`
--
ALTER TABLE `mdl_question_numerical`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesnume_ans_ix` (`answer`), ADD KEY `mdl_quesnume_que_ix` (`question`);

--
-- Indexes for table `mdl_question_numerical_options`
--
ALTER TABLE `mdl_question_numerical_options`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesnumeopti_que_ix` (`question`);

--
-- Indexes for table `mdl_question_numerical_units`
--
ALTER TABLE `mdl_question_numerical_units`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quesnumeunit_queuni_uix` (`question`,`unit`), ADD KEY `mdl_quesnumeunit_que_ix` (`question`);

--
-- Indexes for table `mdl_question_response_analysis`
--
ALTER TABLE `mdl_question_response_analysis`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_question_response_count`
--
ALTER TABLE `mdl_question_response_count`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesrespcoun_ana_ix` (`analysisid`);

--
-- Indexes for table `mdl_question_statistics`
--
ALTER TABLE `mdl_question_statistics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_question_truefalse`
--
ALTER TABLE `mdl_question_truefalse`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_questrue_que_ix` (`question`);

--
-- Indexes for table `mdl_question_usages`
--
ALTER TABLE `mdl_question_usages`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quesusag_con_ix` (`contextid`);

--
-- Indexes for table `mdl_quiz`
--
ALTER TABLE `mdl_quiz`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quiz_cou_ix` (`course`);

--
-- Indexes for table `mdl_quiz_attempts`
--
ALTER TABLE `mdl_quiz_attempts`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quizatte_uni_uix` (`uniqueid`), ADD UNIQUE KEY `mdl_quizatte_quiuseatt_uix` (`quiz`,`userid`,`attempt`), ADD KEY `mdl_quizatte_qui_ix` (`quiz`), ADD KEY `mdl_quizatte_use_ix` (`userid`), ADD KEY `mdl_quizatte_statim_ix` (`state`,`timecheckstate`);

--
-- Indexes for table `mdl_quiz_feedback`
--
ALTER TABLE `mdl_quiz_feedback`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quizfeed_qui_ix` (`quizid`);

--
-- Indexes for table `mdl_quiz_grades`
--
ALTER TABLE `mdl_quiz_grades`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quizgrad_use_ix` (`userid`), ADD KEY `mdl_quizgrad_qui_ix` (`quiz`);

--
-- Indexes for table `mdl_quiz_overrides`
--
ALTER TABLE `mdl_quiz_overrides`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_quizover_qui_ix` (`quiz`), ADD KEY `mdl_quizover_gro_ix` (`groupid`), ADD KEY `mdl_quizover_use_ix` (`userid`);

--
-- Indexes for table `mdl_quiz_overview_regrades`
--
ALTER TABLE `mdl_quiz_overview_regrades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_quiz_reports`
--
ALTER TABLE `mdl_quiz_reports`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quizrepo_nam_uix` (`name`);

--
-- Indexes for table `mdl_quiz_slots`
--
ALTER TABLE `mdl_quiz_slots`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_quizslot_quislo_uix` (`quizid`,`slot`), ADD KEY `mdl_quizquesinst_qui_ix` (`quizid`), ADD KEY `mdl_quizquesinst_que_ix` (`questionid`);

--
-- Indexes for table `mdl_quiz_statistics`
--
ALTER TABLE `mdl_quiz_statistics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_rating`
--
ALTER TABLE `mdl_rating`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_rati_comratconite_ix` (`component`,`ratingarea`,`contextid`,`itemid`), ADD KEY `mdl_rati_con_ix` (`contextid`), ADD KEY `mdl_rati_use_ix` (`userid`);

--
-- Indexes for table `mdl_registration_hubs`
--
ALTER TABLE `mdl_registration_hubs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_repository`
--
ALTER TABLE `mdl_repository`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_repository_instances`
--
ALTER TABLE `mdl_repository_instances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_repository_instance_config`
--
ALTER TABLE `mdl_repository_instance_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_resource`
--
ALTER TABLE `mdl_resource`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_reso_cou_ix` (`course`);

--
-- Indexes for table `mdl_resource_old`
--
ALTER TABLE `mdl_resource_old`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_resoold_old_uix` (`oldid`), ADD KEY `mdl_resoold_cmi_ix` (`cmid`);

--
-- Indexes for table `mdl_role`
--
ALTER TABLE `mdl_role`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_role_sor_uix` (`sortorder`), ADD UNIQUE KEY `mdl_role_sho_uix` (`shortname`);

--
-- Indexes for table `mdl_role_allow_assign`
--
ALTER TABLE `mdl_role_allow_assign`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_rolealloassi_rolall_uix` (`roleid`,`allowassign`), ADD KEY `mdl_rolealloassi_rol_ix` (`roleid`), ADD KEY `mdl_rolealloassi_all_ix` (`allowassign`);

--
-- Indexes for table `mdl_role_allow_override`
--
ALTER TABLE `mdl_role_allow_override`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_rolealloover_rolall_uix` (`roleid`,`allowoverride`), ADD KEY `mdl_rolealloover_rol_ix` (`roleid`), ADD KEY `mdl_rolealloover_all_ix` (`allowoverride`);

--
-- Indexes for table `mdl_role_allow_switch`
--
ALTER TABLE `mdl_role_allow_switch`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_rolealloswit_rolall_uix` (`roleid`,`allowswitch`), ADD KEY `mdl_rolealloswit_rol_ix` (`roleid`), ADD KEY `mdl_rolealloswit_all_ix` (`allowswitch`);

--
-- Indexes for table `mdl_role_assignments`
--
ALTER TABLE `mdl_role_assignments`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_roleassi_sor_ix` (`sortorder`), ADD KEY `mdl_roleassi_rol_ix` (`roleid`), ADD KEY `mdl_roleassi_con_ix` (`contextid`), ADD KEY `mdl_roleassi_use_ix` (`userid`), ADD KEY `mdl_roleassi_rolcon_ix` (`roleid`,`contextid`), ADD KEY `mdl_roleassi_useconrol_ix` (`userid`,`contextid`,`roleid`), ADD KEY `mdl_roleassi_comiteuse_ix` (`component`,`itemid`,`userid`);

--
-- Indexes for table `mdl_role_capabilities`
--
ALTER TABLE `mdl_role_capabilities`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_rolecapa_rolconcap_uix` (`roleid`,`contextid`,`capability`), ADD KEY `mdl_rolecapa_rol_ix` (`roleid`), ADD KEY `mdl_rolecapa_con_ix` (`contextid`), ADD KEY `mdl_rolecapa_mod_ix` (`modifierid`), ADD KEY `mdl_rolecapa_cap_ix` (`capability`);

--
-- Indexes for table `mdl_role_context_levels`
--
ALTER TABLE `mdl_role_context_levels`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_rolecontleve_conrol_uix` (`contextlevel`,`roleid`), ADD KEY `mdl_rolecontleve_rol_ix` (`roleid`);

--
-- Indexes for table `mdl_role_names`
--
ALTER TABLE `mdl_role_names`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_rolename_rolcon_uix` (`roleid`,`contextid`), ADD KEY `mdl_rolename_rol_ix` (`roleid`), ADD KEY `mdl_rolename_con_ix` (`contextid`);

--
-- Indexes for table `mdl_role_sortorder`
--
ALTER TABLE `mdl_role_sortorder`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_rolesort_userolcon_uix` (`userid`,`roleid`,`contextid`), ADD KEY `mdl_rolesort_use_ix` (`userid`), ADD KEY `mdl_rolesort_rol_ix` (`roleid`), ADD KEY `mdl_rolesort_con_ix` (`contextid`);

--
-- Indexes for table `mdl_scale`
--
ALTER TABLE `mdl_scale`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_scal_cou_ix` (`courseid`);

--
-- Indexes for table `mdl_scale_history`
--
ALTER TABLE `mdl_scale_history`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_scalhist_act_ix` (`action`), ADD KEY `mdl_scalhist_old_ix` (`oldid`), ADD KEY `mdl_scalhist_cou_ix` (`courseid`), ADD KEY `mdl_scalhist_log_ix` (`loggeduser`);

--
-- Indexes for table `mdl_scorm`
--
ALTER TABLE `mdl_scorm`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_scor_cou_ix` (`course`);

--
-- Indexes for table `mdl_scorm_aicc_session`
--
ALTER TABLE `mdl_scorm_aicc_session`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_scoraiccsess_sco_ix` (`scormid`), ADD KEY `mdl_scoraiccsess_use_ix` (`userid`);

--
-- Indexes for table `mdl_scorm_scoes`
--
ALTER TABLE `mdl_scorm_scoes`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_scorscoe_sco_ix` (`scorm`);

--
-- Indexes for table `mdl_scorm_scoes_data`
--
ALTER TABLE `mdl_scorm_scoes_data`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_scorscoedata_sco_ix` (`scoid`);

--
-- Indexes for table `mdl_scorm_scoes_track`
--
ALTER TABLE `mdl_scorm_scoes_track`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_scorscoetrac_usescosco_uix` (`userid`,`scormid`,`scoid`,`attempt`,`element`), ADD KEY `mdl_scorscoetrac_use_ix` (`userid`), ADD KEY `mdl_scorscoetrac_ele_ix` (`element`), ADD KEY `mdl_scorscoetrac_sco_ix` (`scormid`), ADD KEY `mdl_scorscoetrac_sco2_ix` (`scoid`);

--
-- Indexes for table `mdl_scorm_seq_mapinfo`
--
ALTER TABLE `mdl_scorm_seq_mapinfo`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_scorseqmapi_scoidobj_uix` (`scoid`,`id`,`objectiveid`), ADD KEY `mdl_scorseqmapi_sco_ix` (`scoid`), ADD KEY `mdl_scorseqmapi_obj_ix` (`objectiveid`);

--
-- Indexes for table `mdl_scorm_seq_objective`
--
ALTER TABLE `mdl_scorm_seq_objective`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_scorseqobje_scoid_uix` (`scoid`,`id`), ADD KEY `mdl_scorseqobje_sco_ix` (`scoid`);

--
-- Indexes for table `mdl_scorm_seq_rolluprule`
--
ALTER TABLE `mdl_scorm_seq_rolluprule`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_scorseqroll_scoid_uix` (`scoid`,`id`), ADD KEY `mdl_scorseqroll_sco_ix` (`scoid`);

--
-- Indexes for table `mdl_scorm_seq_rolluprulecond`
--
ALTER TABLE `mdl_scorm_seq_rolluprulecond`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_scorseqroll_scorolid_uix` (`scoid`,`rollupruleid`,`id`), ADD KEY `mdl_scorseqroll_sco2_ix` (`scoid`), ADD KEY `mdl_scorseqroll_rol_ix` (`rollupruleid`);

--
-- Indexes for table `mdl_scorm_seq_rulecond`
--
ALTER TABLE `mdl_scorm_seq_rulecond`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_scorseqrule_idscorul_uix` (`id`,`scoid`,`ruleconditionsid`), ADD KEY `mdl_scorseqrule_sco2_ix` (`scoid`), ADD KEY `mdl_scorseqrule_rul_ix` (`ruleconditionsid`);

--
-- Indexes for table `mdl_scorm_seq_ruleconds`
--
ALTER TABLE `mdl_scorm_seq_ruleconds`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_scorseqrule_scoid_uix` (`scoid`,`id`), ADD KEY `mdl_scorseqrule_sco_ix` (`scoid`);

--
-- Indexes for table `mdl_sessions`
--
ALTER TABLE `mdl_sessions`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_sess_sid_uix` (`sid`), ADD KEY `mdl_sess_sta_ix` (`state`), ADD KEY `mdl_sess_tim_ix` (`timecreated`), ADD KEY `mdl_sess_tim2_ix` (`timemodified`), ADD KEY `mdl_sess_use_ix` (`userid`);

--
-- Indexes for table `mdl_stats_daily`
--
ALTER TABLE `mdl_stats_daily`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_statdail_cou_ix` (`courseid`), ADD KEY `mdl_statdail_tim_ix` (`timeend`), ADD KEY `mdl_statdail_rol_ix` (`roleid`);

--
-- Indexes for table `mdl_stats_monthly`
--
ALTER TABLE `mdl_stats_monthly`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_statmont_cou_ix` (`courseid`), ADD KEY `mdl_statmont_tim_ix` (`timeend`), ADD KEY `mdl_statmont_rol_ix` (`roleid`);

--
-- Indexes for table `mdl_stats_user_daily`
--
ALTER TABLE `mdl_stats_user_daily`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_statuserdail_cou_ix` (`courseid`), ADD KEY `mdl_statuserdail_use_ix` (`userid`), ADD KEY `mdl_statuserdail_rol_ix` (`roleid`), ADD KEY `mdl_statuserdail_tim_ix` (`timeend`);

--
-- Indexes for table `mdl_stats_user_monthly`
--
ALTER TABLE `mdl_stats_user_monthly`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_statusermont_cou_ix` (`courseid`), ADD KEY `mdl_statusermont_use_ix` (`userid`), ADD KEY `mdl_statusermont_rol_ix` (`roleid`), ADD KEY `mdl_statusermont_tim_ix` (`timeend`);

--
-- Indexes for table `mdl_stats_user_weekly`
--
ALTER TABLE `mdl_stats_user_weekly`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_statuserweek_cou_ix` (`courseid`), ADD KEY `mdl_statuserweek_use_ix` (`userid`), ADD KEY `mdl_statuserweek_rol_ix` (`roleid`), ADD KEY `mdl_statuserweek_tim_ix` (`timeend`);

--
-- Indexes for table `mdl_stats_weekly`
--
ALTER TABLE `mdl_stats_weekly`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_statweek_cou_ix` (`courseid`), ADD KEY `mdl_statweek_tim_ix` (`timeend`), ADD KEY `mdl_statweek_rol_ix` (`roleid`);

--
-- Indexes for table `mdl_survey`
--
ALTER TABLE `mdl_survey`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_surv_cou_ix` (`course`);

--
-- Indexes for table `mdl_survey_analysis`
--
ALTER TABLE `mdl_survey_analysis`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_survanal_use_ix` (`userid`), ADD KEY `mdl_survanal_sur_ix` (`survey`);

--
-- Indexes for table `mdl_survey_answers`
--
ALTER TABLE `mdl_survey_answers`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_survansw_use_ix` (`userid`), ADD KEY `mdl_survansw_sur_ix` (`survey`), ADD KEY `mdl_survansw_que_ix` (`question`);

--
-- Indexes for table `mdl_survey_questions`
--
ALTER TABLE `mdl_survey_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_tag`
--
ALTER TABLE `mdl_tag`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_tag_nam_uix` (`name`), ADD UNIQUE KEY `mdl_tag_idnam_uix` (`id`,`name`), ADD KEY `mdl_tag_use_ix` (`userid`);

--
-- Indexes for table `mdl_tag_correlation`
--
ALTER TABLE `mdl_tag_correlation`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_tagcorr_tag_ix` (`tagid`);

--
-- Indexes for table `mdl_tag_instance`
--
ALTER TABLE `mdl_tag_instance`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_taginst_iteitetagtiu_uix` (`itemtype`,`itemid`,`tagid`,`tiuserid`), ADD KEY `mdl_taginst_tag_ix` (`tagid`), ADD KEY `mdl_taginst_con_ix` (`contextid`);

--
-- Indexes for table `mdl_task_adhoc`
--
ALTER TABLE `mdl_task_adhoc`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_taskadho_nex_ix` (`nextruntime`);

--
-- Indexes for table `mdl_task_scheduled`
--
ALTER TABLE `mdl_task_scheduled`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_tasksche_cla_uix` (`classname`);

--
-- Indexes for table `mdl_timezone`
--
ALTER TABLE `mdl_timezone`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_tool_customlang`
--
ALTER TABLE `mdl_tool_customlang`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_toolcust_lancomstr_uix` (`lang`,`componentid`,`stringid`), ADD KEY `mdl_toolcust_com_ix` (`componentid`);

--
-- Indexes for table `mdl_tool_customlang_components`
--
ALTER TABLE `mdl_tool_customlang_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_tool_mergeusers`
--
ALTER TABLE `mdl_tool_mergeusers`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_toolmerg_tou_ix` (`touserid`), ADD KEY `mdl_toolmerg_fro_ix` (`fromuserid`), ADD KEY `mdl_toolmerg_suc_ix` (`success`), ADD KEY `mdl_toolmerg_toufrosuc_ix` (`touserid`,`fromuserid`,`success`);

--
-- Indexes for table `mdl_upgrade_log`
--
ALTER TABLE `mdl_upgrade_log`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_upgrlog_tim_ix` (`timemodified`), ADD KEY `mdl_upgrlog_typtim_ix` (`type`,`timemodified`), ADD KEY `mdl_upgrlog_use_ix` (`userid`);

--
-- Indexes for table `mdl_url`
--
ALTER TABLE `mdl_url`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_url_cou_ix` (`course`);

--
-- Indexes for table `mdl_url_new`
--
ALTER TABLE `mdl_url_new`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_url_cou_ix` (`course`);

--
-- Indexes for table `mdl_user`
--
ALTER TABLE `mdl_user`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_user_mneuse_uix` (`mnethostid`,`username`), ADD KEY `mdl_user_del_ix` (`deleted`), ADD KEY `mdl_user_con_ix` (`confirmed`), ADD KEY `mdl_user_fir_ix` (`firstname`), ADD KEY `mdl_user_las_ix` (`lastname`), ADD KEY `mdl_user_cit_ix` (`city`), ADD KEY `mdl_user_cou_ix` (`country`), ADD KEY `mdl_user_las2_ix` (`lastaccess`), ADD KEY `mdl_user_ema_ix` (`email`), ADD KEY `mdl_user_aut_ix` (`auth`), ADD KEY `mdl_user_idn_ix` (`idnumber`), ADD KEY `mdl_user_las3_ix` (`lastnamephonetic`), ADD KEY `mdl_user_fir2_ix` (`firstnamephonetic`), ADD KEY `mdl_user_mid_ix` (`middlename`), ADD KEY `mdl_user_alt_ix` (`alternatename`);

--
-- Indexes for table `mdl_user_devices`
--
ALTER TABLE `mdl_user_devices`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_userdevi_pususe_uix` (`pushid`,`userid`), ADD KEY `mdl_userdevi_use_ix` (`userid`);

--
-- Indexes for table `mdl_user_enrolments`
--
ALTER TABLE `mdl_user_enrolments`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_userenro_enruse_uix` (`enrolid`,`userid`), ADD KEY `mdl_userenro_enr_ix` (`enrolid`), ADD KEY `mdl_userenro_use_ix` (`userid`), ADD KEY `mdl_userenro_mod_ix` (`modifierid`);

--
-- Indexes for table `mdl_user_info_category`
--
ALTER TABLE `mdl_user_info_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_user_info_data`
--
ALTER TABLE `mdl_user_info_data`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_userinfodata_usefie_uix` (`userid`,`fieldid`);

--
-- Indexes for table `mdl_user_info_field`
--
ALTER TABLE `mdl_user_info_field`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_user_lastaccess`
--
ALTER TABLE `mdl_user_lastaccess`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_userlast_usecou_uix` (`userid`,`courseid`), ADD KEY `mdl_userlast_use_ix` (`userid`), ADD KEY `mdl_userlast_cou_ix` (`courseid`);

--
-- Indexes for table `mdl_user_password_resets`
--
ALTER TABLE `mdl_user_password_resets`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_userpassrese_use_ix` (`userid`);

--
-- Indexes for table `mdl_user_preferences`
--
ALTER TABLE `mdl_user_preferences`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_userpref_usenam_uix` (`userid`,`name`);

--
-- Indexes for table `mdl_user_private_key`
--
ALTER TABLE `mdl_user_private_key`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_userprivkey_scrval_ix` (`script`,`value`), ADD KEY `mdl_userprivkey_use_ix` (`userid`);

--
-- Indexes for table `mdl_webdav_locks`
--
ALTER TABLE `mdl_webdav_locks`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_webdlock_tok_uix` (`token`), ADD KEY `mdl_webdlock_pat_ix` (`path`), ADD KEY `mdl_webdlock_exp_ix` (`expiry`);

--
-- Indexes for table `mdl_wiki`
--
ALTER TABLE `mdl_wiki`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_wiki_cou_ix` (`course`);

--
-- Indexes for table `mdl_wiki_links`
--
ALTER TABLE `mdl_wiki_links`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_wikilink_fro_ix` (`frompageid`), ADD KEY `mdl_wikilink_sub_ix` (`subwikiid`);

--
-- Indexes for table `mdl_wiki_locks`
--
ALTER TABLE `mdl_wiki_locks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mdl_wiki_pages`
--
ALTER TABLE `mdl_wiki_pages`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_wikipage_subtituse_uix` (`subwikiid`,`title`,`userid`), ADD KEY `mdl_wikipage_sub_ix` (`subwikiid`);

--
-- Indexes for table `mdl_wiki_subwikis`
--
ALTER TABLE `mdl_wiki_subwikis`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_wikisubw_wikgrouse_uix` (`wikiid`,`groupid`,`userid`), ADD KEY `mdl_wikisubw_wik_ix` (`wikiid`);

--
-- Indexes for table `mdl_wiki_synonyms`
--
ALTER TABLE `mdl_wiki_synonyms`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_wikisyno_pagpag_uix` (`pageid`,`pagesynonym`);

--
-- Indexes for table `mdl_wiki_versions`
--
ALTER TABLE `mdl_wiki_versions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_wikivers_pag_ix` (`pageid`);

--
-- Indexes for table `mdl_workshop`
--
ALTER TABLE `mdl_workshop`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_work_cou_ix` (`course`);

--
-- Indexes for table `mdl_workshopallocation_scheduled`
--
ALTER TABLE `mdl_workshopallocation_scheduled`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_worksche_wor_uix` (`workshopid`);

--
-- Indexes for table `mdl_workshopeval_best_settings`
--
ALTER TABLE `mdl_workshopeval_best_settings`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_workbestsett_wor_uix` (`workshopid`);

--
-- Indexes for table `mdl_workshopform_accumulative`
--
ALTER TABLE `mdl_workshopform_accumulative`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workaccu_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshopform_comments`
--
ALTER TABLE `mdl_workshopform_comments`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workcomm_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshopform_numerrors`
--
ALTER TABLE `mdl_workshopform_numerrors`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_worknume_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshopform_numerrors_map`
--
ALTER TABLE `mdl_workshopform_numerrors_map`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_worknumemap_wornon_uix` (`workshopid`,`nonegative`), ADD KEY `mdl_worknumemap_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshopform_rubric`
--
ALTER TABLE `mdl_workshopform_rubric`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workrubr_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshopform_rubric_config`
--
ALTER TABLE `mdl_workshopform_rubric_config`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_workrubrconf_wor_uix` (`workshopid`);

--
-- Indexes for table `mdl_workshopform_rubric_levels`
--
ALTER TABLE `mdl_workshopform_rubric_levels`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workrubrleve_dim_ix` (`dimensionid`);

--
-- Indexes for table `mdl_workshop_aggregations`
--
ALTER TABLE `mdl_workshop_aggregations`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_workaggr_woruse_uix` (`workshopid`,`userid`), ADD KEY `mdl_workaggr_wor_ix` (`workshopid`), ADD KEY `mdl_workaggr_use_ix` (`userid`);

--
-- Indexes for table `mdl_workshop_assessments`
--
ALTER TABLE `mdl_workshop_assessments`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workasse_sub_ix` (`submissionid`), ADD KEY `mdl_workasse_gra_ix` (`gradinggradeoverby`), ADD KEY `mdl_workasse_rev_ix` (`reviewerid`);

--
-- Indexes for table `mdl_workshop_assessments_old`
--
ALTER TABLE `mdl_workshop_assessments_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workasseold_use_ix` (`userid`), ADD KEY `mdl_workasseold_mai_ix` (`mailed`), ADD KEY `mdl_workasseold_wor_ix` (`workshopid`), ADD KEY `mdl_workasseold_sub_ix` (`submissionid`);

--
-- Indexes for table `mdl_workshop_comments_old`
--
ALTER TABLE `mdl_workshop_comments_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workcommold_use_ix` (`userid`), ADD KEY `mdl_workcommold_mai_ix` (`mailed`), ADD KEY `mdl_workcommold_wor_ix` (`workshopid`), ADD KEY `mdl_workcommold_ass_ix` (`assessmentid`);

--
-- Indexes for table `mdl_workshop_elements_old`
--
ALTER TABLE `mdl_workshop_elements_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workelemold_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshop_grades`
--
ALTER TABLE `mdl_workshop_grades`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `mdl_workgrad_assstrdim_uix` (`assessmentid`,`strategy`,`dimensionid`), ADD KEY `mdl_workgrad_ass_ix` (`assessmentid`);

--
-- Indexes for table `mdl_workshop_grades_old`
--
ALTER TABLE `mdl_workshop_grades_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workgradold_wor_ix` (`workshopid`), ADD KEY `mdl_workgradold_ass_ix` (`assessmentid`);

--
-- Indexes for table `mdl_workshop_old`
--
ALTER TABLE `mdl_workshop_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workold_cou_ix` (`course`);

--
-- Indexes for table `mdl_workshop_rubrics_old`
--
ALTER TABLE `mdl_workshop_rubrics_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workrubrold_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshop_stockcomments_old`
--
ALTER TABLE `mdl_workshop_stockcomments_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_workstocold_wor_ix` (`workshopid`);

--
-- Indexes for table `mdl_workshop_submissions`
--
ALTER TABLE `mdl_workshop_submissions`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_worksubm_wor_ix` (`workshopid`), ADD KEY `mdl_worksubm_gra_ix` (`gradeoverby`), ADD KEY `mdl_worksubm_aut_ix` (`authorid`);

--
-- Indexes for table `mdl_workshop_submissions_old`
--
ALTER TABLE `mdl_workshop_submissions_old`
  ADD PRIMARY KEY (`id`), ADD KEY `mdl_worksubmold_use_ix` (`userid`), ADD KEY `mdl_worksubmold_mai_ix` (`mailed`), ADD KEY `mdl_worksubmold_wor_ix` (`workshopid`);

--
-- Indexes for table `monitor_config`
--
ALTER TABLE `monitor_config`
  ADD PRIMARY KEY (`config_id`), ADD KEY `key` (`key`(50));

--
-- Indexes for table `monitor_log`
--
ALTER TABLE `monitor_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `monitor_servers`
--
ALTER TABLE `monitor_servers`
  ADD PRIMARY KEY (`server_id`);

--
-- Indexes for table `monitor_users`
--
ALTER TABLE `monitor_users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `tbl_eml_cf_da_ignorare`
--
ALTER TABLE `tbl_eml_cf_da_ignorare`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_eml_dipendenti_da_elaborare`
--
ALTER TABLE `tbl_eml_dipendenti_da_elaborare`
  ADD PRIMARY KEY (`CODICE_FISCALE`);

--
-- Indexes for table `tbl_eml_forzature_forma`
--
ALTER TABLE `tbl_eml_forzature_forma`
  ADD PRIMARY KEY (`CODICE_FISCALE`);

--
-- Indexes for table `tbl_eml_grep_feed_back`
--
ALTER TABLE `tbl_eml_grep_feed_back`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_eml_grfo_feed_back`
--
ALTER TABLE `tbl_eml_grfo_feed_back`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_eml_grfo_log`
--
ALTER TABLE `tbl_eml_grfo_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_eml_mapping_categorie`
--
ALTER TABLE `tbl_eml_mapping_categorie`
  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `uk_codice_posiz_econom` (`codice_posiz_econom`);

--
-- Indexes for table `tbl_eml_mapping_org`
--
ALTER TABLE `tbl_eml_mapping_org`
  ADD PRIMARY KEY (`CODICE_STRUTTURA`,`ENTE`);

--
-- Indexes for table `tbl_eml_mapping_sesso`
--
ALTER TABLE `tbl_eml_mapping_sesso`
  ADD PRIMARY KEY (`SESSO`);

--
-- Indexes for table `tbl_eml_pent_budget_individuali`
--
ALTER TABLE `tbl_eml_pent_budget_individuali`
  ADD PRIMARY KEY (`anno`,`tipo_budget`,`cod_direzione`);

--
-- Indexes for table `tbl_eml_pent_cf_parametri`
--
ALTER TABLE `tbl_eml_pent_cf_parametri`
  ADD PRIMARY KEY (`codice_fiscale`);

--
-- Indexes for table `tbl_eml_pent_completamento_corsi_on_line`
--
ALTER TABLE `tbl_eml_pent_completamento_corsi_on_line`
  ADD PRIMARY KEY (`id_corso`,`id_edizione`,`id_user`);

--
-- Indexes for table `tbl_eml_pent_edizioni_corsi_on_line`
--
ALTER TABLE `tbl_eml_pent_edizioni_corsi_on_line`
  ADD PRIMARY KEY (`id_corso`,`id_edizione`);

--
-- Indexes for table `tbl_eml_pent_moduli_corsi_on_line`
--
ALTER TABLE `tbl_eml_pent_moduli_corsi_on_line`
  ADD PRIMARY KEY (`id_corso`,`id_modulo`);

--
-- Indexes for table `tbl_eml_pent_monitoraggio_corsi_on_line`
--
ALTER TABLE `tbl_eml_pent_monitoraggio_corsi_on_line`
  ADD PRIMARY KEY (`id_corso`,`id_edizione`,`id_user`);

--
-- Indexes for table `tbl_eml_pent_piani_di_studio`
--
ALTER TABLE `tbl_eml_pent_piani_di_studio`
  ADD PRIMARY KEY (`matricola`);

--
-- Indexes for table `tbl_eml_pent_questionari_dati`
--
ALTER TABLE `tbl_eml_pent_questionari_dati`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_eml_php_ctrl`
--
ALTER TABLE `tbl_eml_php_ctrl`
  ADD PRIMARY KEY (`NOME_FUNCTION`);

--
-- Indexes for table `tbl_eml_php_log`
--
ALTER TABLE `tbl_eml_php_log`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_eml_php_log_query`
--
ALTER TABLE `tbl_eml_php_log_query`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `TBL_HR_ASS_MANSIONE_DIP`
--
ALTER TABLE `TBL_HR_ASS_MANSIONE_DIP`
  ADD PRIMARY KEY (`ID_MANSIONE_DIP`);

--
-- Indexes for table `TBL_HR_ASS_POSIZ_ECONOM_DIP`
--
ALTER TABLE `TBL_HR_ASS_POSIZ_ECONOM_DIP`
  ADD PRIMARY KEY (`ID_STOR_POSIZ_ECONOM_DIP`);

--
-- Indexes for table `TBL_HR_ASS_POSIZ_ORG_DIP`
--
ALTER TABLE `TBL_HR_ASS_POSIZ_ORG_DIP`
  ADD PRIMARY KEY (`ID_STOR_POSIZ_ORG`);

--
-- Indexes for table `TBL_HR_ASS_RAPPORTO_DIP`
--
ALTER TABLE `TBL_HR_ASS_RAPPORTO_DIP`
  ADD PRIMARY KEY (`ID_RAPPORTO_DIPENDENTE`);

--
-- Indexes for table `TBL_HR_ASS_RUOLO_DIP`
--
ALTER TABLE `TBL_HR_ASS_RUOLO_DIP`
  ADD PRIMARY KEY (`ID_RUOLO_DIP`);

--
-- Indexes for table `TBL_HR_ASS_STRUTTURA_DIP`
--
ALTER TABLE `TBL_HR_ASS_STRUTTURA_DIP`
  ADD PRIMARY KEY (`ID_STOR_STRUTTURA_DIP`);

--
-- Indexes for table `TBL_HR_DATA_SCARICO`
--
ALTER TABLE `TBL_HR_DATA_SCARICO`
  ADD PRIMARY KEY (`ID_SCARICO`);

--
-- Indexes for table `TBL_HR_DIPENDENTE`
--
ALTER TABLE `TBL_HR_DIPENDENTE`
  ADD PRIMARY KEY (`ID_DIPENDENTE`);

--
-- Indexes for table `TBL_HR_POSIZIONE_ECONOMICA`
--
ALTER TABLE `TBL_HR_POSIZIONE_ECONOMICA`
  ADD PRIMARY KEY (`ID_POSIZ_ECONOMICA`);

--
-- Indexes for table `TBL_HR_POSIZ_ORGANIZZATIVA`
--
ALTER TABLE `TBL_HR_POSIZ_ORGANIZZATIVA`
  ADD PRIMARY KEY (`ID_POSIZ_ORGANIZZATIVA`);

--
-- Indexes for table `TBL_HR_RUOLO`
--
ALTER TABLE `TBL_HR_RUOLO`
  ADD PRIMARY KEY (`ID_RUOLO`);

--
-- Indexes for table `TBL_HR_STRUTTURA`
--
ALTER TABLE `TBL_HR_STRUTTURA`
  ADD PRIMARY KEY (`ID_STRUTTURA`);

--
-- Indexes for table `TBL_HR_TIPO_MANSIONE`
--
ALTER TABLE `TBL_HR_TIPO_MANSIONE`
  ADD PRIMARY KEY (`ID_TIPO_MANSIONE`);

--
-- Indexes for table `TBL_HR_TIPO_POSIZ_ORG`
--
ALTER TABLE `TBL_HR_TIPO_POSIZ_ORG`
  ADD PRIMARY KEY (`ID_TIPO_POSIZ_ORG`);

--
-- Indexes for table `TBL_HR_TIPO_RAPPORTO`
--
ALTER TABLE `TBL_HR_TIPO_RAPPORTO`
  ADD PRIMARY KEY (`ID_TIPO_RAPPORTO`);

--
-- Indexes for table `TBL_HR_TIPO_RUOLO`
--
ALTER TABLE `TBL_HR_TIPO_RUOLO`
  ADD PRIMARY KEY (`ID_TIPO_RUOLO`);

--
-- Indexes for table `TBL_HR_TIPO_STRUTTURA`
--
ALTER TABLE `TBL_HR_TIPO_STRUTTURA`
  ADD PRIMARY KEY (`ID_TIPO_STRUTTURA`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mdl_assign`
--
ALTER TABLE `mdl_assign`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_assignfeedback_comments`
--
ALTER TABLE `mdl_assignfeedback_comments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assignfeedback_editpdf_annot`
--
ALTER TABLE `mdl_assignfeedback_editpdf_annot`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assignfeedback_editpdf_cmnt`
--
ALTER TABLE `mdl_assignfeedback_editpdf_cmnt`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assignfeedback_editpdf_quick`
--
ALTER TABLE `mdl_assignfeedback_editpdf_quick`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assignfeedback_file`
--
ALTER TABLE `mdl_assignfeedback_file`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assignment`
--
ALTER TABLE `mdl_assignment`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_assignment_submissions`
--
ALTER TABLE `mdl_assignment_submissions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assignment_upgrade`
--
ALTER TABLE `mdl_assignment_upgrade`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_assignsubmission_file`
--
ALTER TABLE `mdl_assignsubmission_file`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assignsubmission_onlinetext`
--
ALTER TABLE `mdl_assignsubmission_onlinetext`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=44;
--
-- AUTO_INCREMENT for table `mdl_assign_grades`
--
ALTER TABLE `mdl_assign_grades`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assign_plugin_config`
--
ALTER TABLE `mdl_assign_plugin_config`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `mdl_assign_submission`
--
ALTER TABLE `mdl_assign_submission`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=44;
--
-- AUTO_INCREMENT for table `mdl_assign_user_flags`
--
ALTER TABLE `mdl_assign_user_flags`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_assign_user_mapping`
--
ALTER TABLE `mdl_assign_user_mapping`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_backup_controllers`
--
ALTER TABLE `mdl_backup_controllers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=708;
--
-- AUTO_INCREMENT for table `mdl_backup_courses`
--
ALTER TABLE `mdl_backup_courses`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_backup_logs`
--
ALTER TABLE `mdl_backup_logs`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge`
--
ALTER TABLE `mdl_badge`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge_backpack`
--
ALTER TABLE `mdl_badge_backpack`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge_criteria`
--
ALTER TABLE `mdl_badge_criteria`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge_criteria_met`
--
ALTER TABLE `mdl_badge_criteria_met`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge_criteria_param`
--
ALTER TABLE `mdl_badge_criteria_param`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge_external`
--
ALTER TABLE `mdl_badge_external`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge_issued`
--
ALTER TABLE `mdl_badge_issued`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_badge_manual_award`
--
ALTER TABLE `mdl_badge_manual_award`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_block`
--
ALTER TABLE `mdl_block`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=46;
--
-- AUTO_INCREMENT for table `mdl_block_community`
--
ALTER TABLE `mdl_block_community`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_block_f2_gestione_risorse`
--
ALTER TABLE `mdl_block_f2_gestione_risorse`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_block_formindbudget`
--
ALTER TABLE `mdl_block_formindbudget`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_block_formindbudget_log`
--
ALTER TABLE `mdl_block_formindbudget_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_block_formindbudget_storico`
--
ALTER TABLE `mdl_block_formindbudget_storico`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_block_instances`
--
ALTER TABLE `mdl_block_instances`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3387;
--
-- AUTO_INCREMENT for table `mdl_block_positions`
--
ALTER TABLE `mdl_block_positions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=113;
--
-- AUTO_INCREMENT for table `mdl_block_recent_activity`
--
ALTER TABLE `mdl_block_recent_activity`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2545;
--
-- AUTO_INCREMENT for table `mdl_block_rss_client`
--
ALTER TABLE `mdl_block_rss_client`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `mdl_blog_association`
--
ALTER TABLE `mdl_blog_association`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_blog_external`
--
ALTER TABLE `mdl_blog_external`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_book`
--
ALTER TABLE `mdl_book`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_book_chapters`
--
ALTER TABLE `mdl_book_chapters`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_cache_filters`
--
ALTER TABLE `mdl_cache_filters`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_cache_flags`
--
ALTER TABLE `mdl_cache_flags`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=58607;
--
-- AUTO_INCREMENT for table `mdl_capabilities`
--
ALTER TABLE `mdl_capabilities`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=627;
--
-- AUTO_INCREMENT for table `mdl_certificate`
--
ALTER TABLE `mdl_certificate`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_certificate_issues`
--
ALTER TABLE `mdl_certificate_issues`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1076;
--
-- AUTO_INCREMENT for table `mdl_chat`
--
ALTER TABLE `mdl_chat`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_chat_messages`
--
ALTER TABLE `mdl_chat_messages`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_chat_messages_current`
--
ALTER TABLE `mdl_chat_messages_current`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_chat_users`
--
ALTER TABLE `mdl_chat_users`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_choice`
--
ALTER TABLE `mdl_choice`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_choice_answers`
--
ALTER TABLE `mdl_choice_answers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_choice_options`
--
ALTER TABLE `mdl_choice_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_cohort`
--
ALTER TABLE `mdl_cohort`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `mdl_cohort_members`
--
ALTER TABLE `mdl_cohort_members`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20249;
--
-- AUTO_INCREMENT for table `mdl_comments`
--
ALTER TABLE `mdl_comments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=258;
--
-- AUTO_INCREMENT for table `mdl_config`
--
ALTER TABLE `mdl_config`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2137;
--
-- AUTO_INCREMENT for table `mdl_config_log`
--
ALTER TABLE `mdl_config_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1718;
--
-- AUTO_INCREMENT for table `mdl_config_plugins`
--
ALTER TABLE `mdl_config_plugins`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1503;
--
-- AUTO_INCREMENT for table `mdl_context`
--
ALTER TABLE `mdl_context`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=15198;
--
-- AUTO_INCREMENT for table `mdl_controlli_log`
--
ALTER TABLE `mdl_controlli_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5302;
--
-- AUTO_INCREMENT for table `mdl_course`
--
ALTER TABLE `mdl_course`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=194882;
--
-- AUTO_INCREMENT for table `mdl_course_categories`
--
ALTER TABLE `mdl_course_categories`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `mdl_course_completions`
--
ALTER TABLE `mdl_course_completions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=103417;
--
-- AUTO_INCREMENT for table `mdl_course_completion_aggr_methd`
--
ALTER TABLE `mdl_course_completion_aggr_methd`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=405;
--
-- AUTO_INCREMENT for table `mdl_course_completion_criteria`
--
ALTER TABLE `mdl_course_completion_criteria`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=325;
--
-- AUTO_INCREMENT for table `mdl_course_completion_crit_compl`
--
ALTER TABLE `mdl_course_completion_crit_compl`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=87782;
--
-- AUTO_INCREMENT for table `mdl_course_format_options`
--
ALTER TABLE `mdl_course_format_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4221;
--
-- AUTO_INCREMENT for table `mdl_course_modules`
--
ALTER TABLE `mdl_course_modules`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5047;
--
-- AUTO_INCREMENT for table `mdl_course_modules_completion`
--
ALTER TABLE `mdl_course_modules_completion`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=316514;
--
-- AUTO_INCREMENT for table `mdl_course_published`
--
ALTER TABLE `mdl_course_published`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_course_request`
--
ALTER TABLE `mdl_course_request`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_course_sections`
--
ALTER TABLE `mdl_course_sections`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4273;
--
-- AUTO_INCREMENT for table `mdl_csibenchmark`
--
ALTER TABLE `mdl_csibenchmark`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1145;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarkdettagliprove`
--
ALTER TABLE `mdl_csibenchmarkdettagliprove`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=107913;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarkprove`
--
ALTER TABLE `mdl_csibenchmarkprove`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7709;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarkraw_v0`
--
ALTER TABLE `mdl_csibenchmarkraw_v0`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3285;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarkraw_v1`
--
ALTER TABLE `mdl_csibenchmarkraw_v1`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3281;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarksessioni`
--
ALTER TABLE `mdl_csibenchmarksessioni`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarktest`
--
ALTER TABLE `mdl_csibenchmarktest`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16017;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarktestraw_v0`
--
ALTER TABLE `mdl_csibenchmarktestraw_v0`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=45977;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarktestraw_v1`
--
ALTER TABLE `mdl_csibenchmarktestraw_v1`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=45921;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarktest_v0`
--
ALTER TABLE `mdl_csibenchmarktest_v0`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=45977;
--
-- AUTO_INCREMENT for table `mdl_csibenchmarktest_v1`
--
ALTER TABLE `mdl_csibenchmarktest_v1`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=45921;
--
-- AUTO_INCREMENT for table `mdl_csibenchmark_v0`
--
ALTER TABLE `mdl_csibenchmark_v0`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3285;
--
-- AUTO_INCREMENT for table `mdl_csibenchmark_v1`
--
ALTER TABLE `mdl_csibenchmark_v1`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3281;
--
-- AUTO_INCREMENT for table `mdl_data`
--
ALTER TABLE `mdl_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_data_content`
--
ALTER TABLE `mdl_data_content`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `mdl_data_fields`
--
ALTER TABLE `mdl_data_fields`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_data_records`
--
ALTER TABLE `mdl_data_records`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `mdl_eml_php_log_query`
--
ALTER TABLE `mdl_eml_php_log_query`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_enrol`
--
ALTER TABLE `mdl_enrol`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2228;
--
-- AUTO_INCREMENT for table `mdl_enrol_flatfile`
--
ALTER TABLE `mdl_enrol_flatfile`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_enrol_paypal`
--
ALTER TABLE `mdl_enrol_paypal`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_event`
--
ALTER TABLE `mdl_event`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=310764;
--
-- AUTO_INCREMENT for table `mdl_events_handlers`
--
ALTER TABLE `mdl_events_handlers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_events_queue`
--
ALTER TABLE `mdl_events_queue`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_events_queue_handlers`
--
ALTER TABLE `mdl_events_queue_handlers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_event_subscriptions`
--
ALTER TABLE `mdl_event_subscriptions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_external_functions`
--
ALTER TABLE `mdl_external_functions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=120;
--
-- AUTO_INCREMENT for table `mdl_external_services`
--
ALTER TABLE `mdl_external_services`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_external_services_functions`
--
ALTER TABLE `mdl_external_services_functions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=41;
--
-- AUTO_INCREMENT for table `mdl_external_services_users`
--
ALTER TABLE `mdl_external_services_users`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_external_tokens`
--
ALTER TABLE `mdl_external_tokens`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_anagrafica_corsi`
--
ALTER TABLE `mdl_f2_anagrafica_corsi`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=702;
--
-- AUTO_INCREMENT for table `mdl_f2_corsiind`
--
ALTER TABLE `mdl_f2_corsiind`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2728;
--
-- AUTO_INCREMENT for table `mdl_f2_corsiind_anno_finanziario`
--
ALTER TABLE `mdl_f2_corsiind_anno_finanziario`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=834;
--
-- AUTO_INCREMENT for table `mdl_f2_corsiind_log`
--
ALTER TABLE `mdl_f2_corsiind_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3915;
--
-- AUTO_INCREMENT for table `mdl_f2_corsiind_prot`
--
ALTER TABLE `mdl_f2_corsiind_prot`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=63;
--
-- AUTO_INCREMENT for table `mdl_f2_corsiind_senza_spesa`
--
ALTER TABLE `mdl_f2_corsiind_senza_spesa`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1397;
--
-- AUTO_INCREMENT for table `mdl_f2_corsiind_senza_spesa_query_log`
--
ALTER TABLE `mdl_f2_corsiind_senza_spesa_query_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_corsi_coorti_map`
--
ALTER TABLE `mdl_f2_corsi_coorti_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3493;
--
-- AUTO_INCREMENT for table `mdl_f2_corsi_sedi_map`
--
ALTER TABLE `mdl_f2_corsi_sedi_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1983;
--
-- AUTO_INCREMENT for table `mdl_f2_course_org_mapping`
--
ALTER TABLE `mdl_f2_course_org_mapping`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1022;
--
-- AUTO_INCREMENT for table `mdl_f2_csi_pent_gruppi_report`
--
ALTER TABLE `mdl_f2_csi_pent_gruppi_report`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `mdl_f2_csi_pent_menu_report`
--
ALTER TABLE `mdl_f2_csi_pent_menu_report`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `mdl_f2_csi_pent_param`
--
ALTER TABLE `mdl_f2_csi_pent_param`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_f2_csi_pent_param_map`
--
ALTER TABLE `mdl_f2_csi_pent_param_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mdl_f2_csi_pent_report`
--
ALTER TABLE `mdl_f2_csi_pent_report`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=35;
--
-- AUTO_INCREMENT for table `mdl_f2_csi_pent_role_map`
--
ALTER TABLE `mdl_f2_csi_pent_role_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=35;
--
-- AUTO_INCREMENT for table `mdl_f2_determine`
--
ALTER TABLE `mdl_f2_determine`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=549;
--
-- AUTO_INCREMENT for table `mdl_f2_edizioni_postiris_map`
--
ALTER TABLE `mdl_f2_edizioni_postiris_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3664;
--
-- AUTO_INCREMENT for table `mdl_f2_edz_pianificate_corsi_prg`
--
ALTER TABLE `mdl_f2_edz_pianificate_corsi_prg`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5720;
--
-- AUTO_INCREMENT for table `mdl_f2_fi_partialbudget`
--
ALTER TABLE `mdl_f2_fi_partialbudget`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=83;
--
-- AUTO_INCREMENT for table `mdl_f2_forma2riforma_log`
--
ALTER TABLE `mdl_f2_forma2riforma_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_forma2riforma_mapping`
--
ALTER TABLE `mdl_f2_forma2riforma_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_forma2riforma_partecipazioni`
--
ALTER TABLE `mdl_f2_forma2riforma_partecipazioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_formatore`
--
ALTER TABLE `mdl_f2_formatore`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=823;
--
-- AUTO_INCREMENT for table `mdl_f2_formsubaf_map`
--
ALTER TABLE `mdl_f2_formsubaf_map`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=605;
--
-- AUTO_INCREMENT for table `mdl_f2_fornitori`
--
ALTER TABLE `mdl_f2_fornitori`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=223;
--
-- AUTO_INCREMENT for table `mdl_f2_forzature`
--
ALTER TABLE `mdl_f2_forzature`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `mdl_f2_gest_codpart`
--
ALTER TABLE `mdl_f2_gest_codpart`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `mdl_f2_notif_corso`
--
ALTER TABLE `mdl_f2_notif_corso`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=346;
--
-- AUTO_INCREMENT for table `mdl_f2_notif_templates`
--
ALTER TABLE `mdl_f2_notif_templates`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `mdl_f2_notif_template_log`
--
ALTER TABLE `mdl_f2_notif_template_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3031;
--
-- AUTO_INCREMENT for table `mdl_f2_notif_template_mailqueue`
--
ALTER TABLE `mdl_f2_notif_template_mailqueue`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=689;
--
-- AUTO_INCREMENT for table `mdl_f2_notif_tipo`
--
ALTER TABLE `mdl_f2_notif_tipo`
  MODIFY `id` mediumint(6) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `mdl_f2_org_budget`
--
ALTER TABLE `mdl_f2_org_budget`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1343;
--
-- AUTO_INCREMENT for table `mdl_f2_partecipazioni`
--
ALTER TABLE `mdl_f2_partecipazioni`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `mdl_f2_partialbdgt`
--
ALTER TABLE `mdl_f2_partialbdgt`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=211;
--
-- AUTO_INCREMENT for table `mdl_f2_piani_di_studio`
--
ALTER TABLE `mdl_f2_piani_di_studio`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=270;
--
-- AUTO_INCREMENT for table `mdl_f2_prenotati`
--
ALTER TABLE `mdl_f2_prenotati`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=429;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho`
--
ALTER TABLE `mdl_f2_report_pentaho`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_formind`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param`
--
ALTER TABLE `mdl_f2_report_pentaho_param`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_param_formind`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_map`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_map_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_formind`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_map_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_partecipazione`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_map_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_questionari`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_map_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_stat`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_map_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_param_map_visual_on_line`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_param_partecipazione`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_param_questionari`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_param_stat`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_param_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_param_visual_on_line`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_partecipazione`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_questionari`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_role_map`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=49;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_role_map_formind`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_formind`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_role_map_partecipazione`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_partecipazione`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_role_map_questionari`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_questionari`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_role_map_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_stat`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=51;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_role_map_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_role_map_visual_on_line`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=54;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_stat`
--
ALTER TABLE `mdl_f2_report_pentaho_stat`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `mdl_f2_report_pentaho_visual_on_line`
--
ALTER TABLE `mdl_f2_report_pentaho_visual_on_line`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `mdl_f2_saf`
--
ALTER TABLE `mdl_f2_saf`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `mdl_f2_scheda_progetto`
--
ALTER TABLE `mdl_f2_scheda_progetto`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=358;
--
-- AUTO_INCREMENT for table `mdl_f2_sessioni`
--
ALTER TABLE `mdl_f2_sessioni`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mdl_f2_sf_af_map`
--
ALTER TABLE `mdl_f2_sf_af_map`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT for table `mdl_f2_stati_validazione`
--
ALTER TABLE `mdl_f2_stati_validazione`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=195;
--
-- AUTO_INCREMENT for table `mdl_f2_storico_corsi`
--
ALTER TABLE `mdl_f2_storico_corsi`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=379406;
--
-- AUTO_INCREMENT for table `mdl_facetoface`
--
ALTER TABLE `mdl_facetoface`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=644;
--
-- AUTO_INCREMENT for table `mdl_facetoface_notice`
--
ALTER TABLE `mdl_facetoface_notice`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_facetoface_notice_data`
--
ALTER TABLE `mdl_facetoface_notice_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_facetoface_sessions`
--
ALTER TABLE `mdl_facetoface_sessions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1221;
--
-- AUTO_INCREMENT for table `mdl_facetoface_sessions_dates`
--
ALTER TABLE `mdl_facetoface_sessions_dates`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2609;
--
-- AUTO_INCREMENT for table `mdl_facetoface_sessions_docenti`
--
ALTER TABLE `mdl_facetoface_sessions_docenti`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3101;
--
-- AUTO_INCREMENT for table `mdl_facetoface_session_data`
--
ALTER TABLE `mdl_facetoface_session_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8567;
--
-- AUTO_INCREMENT for table `mdl_facetoface_session_field`
--
ALTER TABLE `mdl_facetoface_session_field`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `mdl_facetoface_session_roles`
--
ALTER TABLE `mdl_facetoface_session_roles`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_facetoface_signups`
--
ALTER TABLE `mdl_facetoface_signups`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=62939;
--
-- AUTO_INCREMENT for table `mdl_facetoface_signups_status`
--
ALTER TABLE `mdl_facetoface_signups_status`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=132941;
--
-- AUTO_INCREMENT for table `mdl_feedback`
--
ALTER TABLE `mdl_feedback`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1423;
--
-- AUTO_INCREMENT for table `mdl_feedback_completed`
--
ALTER TABLE `mdl_feedback_completed`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20512;
--
-- AUTO_INCREMENT for table `mdl_feedback_completedtmp`
--
ALTER TABLE `mdl_feedback_completedtmp`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14981;
--
-- AUTO_INCREMENT for table `mdl_feedback_completed_session`
--
ALTER TABLE `mdl_feedback_completed_session`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20512;
--
-- AUTO_INCREMENT for table `mdl_feedback_item`
--
ALTER TABLE `mdl_feedback_item`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=56540;
--
-- AUTO_INCREMENT for table `mdl_feedback_sitecourse_map`
--
ALTER TABLE `mdl_feedback_sitecourse_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_feedback_template`
--
ALTER TABLE `mdl_feedback_template`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `mdl_feedback_tracking`
--
ALTER TABLE `mdl_feedback_tracking`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20512;
--
-- AUTO_INCREMENT for table `mdl_feedback_value`
--
ALTER TABLE `mdl_feedback_value`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=499254;
--
-- AUTO_INCREMENT for table `mdl_feedback_valuetmp`
--
ALTER TABLE `mdl_feedback_valuetmp`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=350324;
--
-- AUTO_INCREMENT for table `mdl_files`
--
ALTER TABLE `mdl_files`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=230371;
--
-- AUTO_INCREMENT for table `mdl_files_reference`
--
ALTER TABLE `mdl_files_reference`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_filter_active`
--
ALTER TABLE `mdl_filter_active`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT for table `mdl_filter_config`
--
ALTER TABLE `mdl_filter_config`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_folder`
--
ALTER TABLE `mdl_folder`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=38;
--
-- AUTO_INCREMENT for table `mdl_format_grid_icon`
--
ALTER TABLE `mdl_format_grid_icon`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1363;
--
-- AUTO_INCREMENT for table `mdl_format_grid_summary`
--
ALTER TABLE `mdl_format_grid_summary`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=251;
--
-- AUTO_INCREMENT for table `mdl_forum`
--
ALTER TABLE `mdl_forum`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=273;
--
-- AUTO_INCREMENT for table `mdl_forum_digests`
--
ALTER TABLE `mdl_forum_digests`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_forum_discussions`
--
ALTER TABLE `mdl_forum_discussions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=182;
--
-- AUTO_INCREMENT for table `mdl_forum_posts`
--
ALTER TABLE `mdl_forum_posts`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=297;
--
-- AUTO_INCREMENT for table `mdl_forum_queue`
--
ALTER TABLE `mdl_forum_queue`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_forum_read`
--
ALTER TABLE `mdl_forum_read`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_forum_subscriptions`
--
ALTER TABLE `mdl_forum_subscriptions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1752;
--
-- AUTO_INCREMENT for table `mdl_forum_track_prefs`
--
ALTER TABLE `mdl_forum_track_prefs`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game`
--
ALTER TABLE `mdl_game`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_attempts`
--
ALTER TABLE `mdl_game_attempts`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_bookquiz_chapters`
--
ALTER TABLE `mdl_game_bookquiz_chapters`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_bookquiz_questions`
--
ALTER TABLE `mdl_game_bookquiz_questions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_export_html`
--
ALTER TABLE `mdl_game_export_html`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_export_javame`
--
ALTER TABLE `mdl_game_export_javame`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_grades`
--
ALTER TABLE `mdl_game_grades`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_queries`
--
ALTER TABLE `mdl_game_queries`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_repetitions`
--
ALTER TABLE `mdl_game_repetitions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_game_snakes_database`
--
ALTER TABLE `mdl_game_snakes_database`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_game_sudoku_database`
--
ALTER TABLE `mdl_game_sudoku_database`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_glossary`
--
ALTER TABLE `mdl_glossary`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `mdl_glossary_alias`
--
ALTER TABLE `mdl_glossary_alias`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=39;
--
-- AUTO_INCREMENT for table `mdl_glossary_categories`
--
ALTER TABLE `mdl_glossary_categories`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `mdl_glossary_entries`
--
ALTER TABLE `mdl_glossary_entries`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=449;
--
-- AUTO_INCREMENT for table `mdl_glossary_entries_categories`
--
ALTER TABLE `mdl_glossary_entries_categories`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=117;
--
-- AUTO_INCREMENT for table `mdl_glossary_formats`
--
ALTER TABLE `mdl_glossary_formats`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `mdl_grade_categories`
--
ALTER TABLE `mdl_grade_categories`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=669;
--
-- AUTO_INCREMENT for table `mdl_grade_categories_history`
--
ALTER TABLE `mdl_grade_categories_history`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2636;
--
-- AUTO_INCREMENT for table `mdl_grade_grades`
--
ALTER TABLE `mdl_grade_grades`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=162826;
--
-- AUTO_INCREMENT for table `mdl_grade_grades_history`
--
ALTER TABLE `mdl_grade_grades_history`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=385644;
--
-- AUTO_INCREMENT for table `mdl_grade_import_newitem`
--
ALTER TABLE `mdl_grade_import_newitem`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grade_import_values`
--
ALTER TABLE `mdl_grade_import_values`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grade_items`
--
ALTER TABLE `mdl_grade_items`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1656;
--
-- AUTO_INCREMENT for table `mdl_grade_items_history`
--
ALTER TABLE `mdl_grade_items_history`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7224;
--
-- AUTO_INCREMENT for table `mdl_grade_letters`
--
ALTER TABLE `mdl_grade_letters`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grade_outcomes`
--
ALTER TABLE `mdl_grade_outcomes`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grade_outcomes_courses`
--
ALTER TABLE `mdl_grade_outcomes_courses`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grade_outcomes_history`
--
ALTER TABLE `mdl_grade_outcomes_history`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grade_settings`
--
ALTER TABLE `mdl_grade_settings`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=52;
--
-- AUTO_INCREMENT for table `mdl_gradingform_guide_comments`
--
ALTER TABLE `mdl_gradingform_guide_comments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_gradingform_guide_criteria`
--
ALTER TABLE `mdl_gradingform_guide_criteria`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_gradingform_guide_fillings`
--
ALTER TABLE `mdl_gradingform_guide_fillings`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_gradingform_rubric_criteria`
--
ALTER TABLE `mdl_gradingform_rubric_criteria`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_gradingform_rubric_fillings`
--
ALTER TABLE `mdl_gradingform_rubric_fillings`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_gradingform_rubric_levels`
--
ALTER TABLE `mdl_gradingform_rubric_levels`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grading_areas`
--
ALTER TABLE `mdl_grading_areas`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_grading_definitions`
--
ALTER TABLE `mdl_grading_definitions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_grading_instances`
--
ALTER TABLE `mdl_grading_instances`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_groupings`
--
ALTER TABLE `mdl_groupings`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `mdl_groupings_groups`
--
ALTER TABLE `mdl_groupings_groups`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_groups`
--
ALTER TABLE `mdl_groups`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=36;
--
-- AUTO_INCREMENT for table `mdl_groups_members`
--
ALTER TABLE `mdl_groups_members`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1913;
--
-- AUTO_INCREMENT for table `mdl_hbackup_temp_items`
--
ALTER TABLE `mdl_hbackup_temp_items`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=328;
--
-- AUTO_INCREMENT for table `mdl_hvp`
--
ALTER TABLE `mdl_hvp`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=126;
--
-- AUTO_INCREMENT for table `mdl_hvp_auth`
--
ALTER TABLE `mdl_hvp_auth`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_hvp_contents_libraries`
--
ALTER TABLE `mdl_hvp_contents_libraries`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4486;
--
-- AUTO_INCREMENT for table `mdl_hvp_content_user_data`
--
ALTER TABLE `mdl_hvp_content_user_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_hvp_counters`
--
ALTER TABLE `mdl_hvp_counters`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=259;
--
-- AUTO_INCREMENT for table `mdl_hvp_events`
--
ALTER TABLE `mdl_hvp_events`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=981;
--
-- AUTO_INCREMENT for table `mdl_hvp_libraries`
--
ALTER TABLE `mdl_hvp_libraries`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=145;
--
-- AUTO_INCREMENT for table `mdl_hvp_libraries_cachedassets`
--
ALTER TABLE `mdl_hvp_libraries_cachedassets`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=274;
--
-- AUTO_INCREMENT for table `mdl_hvp_libraries_hub_cache`
--
ALTER TABLE `mdl_hvp_libraries_hub_cache`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=43;
--
-- AUTO_INCREMENT for table `mdl_hvp_libraries_languages`
--
ALTER TABLE `mdl_hvp_libraries_languages`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3953;
--
-- AUTO_INCREMENT for table `mdl_hvp_libraries_libraries`
--
ALTER TABLE `mdl_hvp_libraries_libraries`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=569;
--
-- AUTO_INCREMENT for table `mdl_hvp_xapi_results`
--
ALTER TABLE `mdl_hvp_xapi_results`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_imscp`
--
ALTER TABLE `mdl_imscp`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_label`
--
ALTER TABLE `mdl_label`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=390;
--
-- AUTO_INCREMENT for table `mdl_lesson`
--
ALTER TABLE `mdl_lesson`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_lesson_answers`
--
ALTER TABLE `mdl_lesson_answers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lesson_attempts`
--
ALTER TABLE `mdl_lesson_attempts`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lesson_branch`
--
ALTER TABLE `mdl_lesson_branch`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lesson_grades`
--
ALTER TABLE `mdl_lesson_grades`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lesson_high_scores`
--
ALTER TABLE `mdl_lesson_high_scores`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lesson_pages`
--
ALTER TABLE `mdl_lesson_pages`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lesson_timer`
--
ALTER TABLE `mdl_lesson_timer`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_license`
--
ALTER TABLE `mdl_license`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `mdl_local_f2_notif`
--
ALTER TABLE `mdl_local_f2_notif`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lock_db`
--
ALTER TABLE `mdl_lock_db`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_log`
--
ALTER TABLE `mdl_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1946894;
--
-- AUTO_INCREMENT for table `mdl_logstore_standard_log`
--
ALTER TABLE `mdl_logstore_standard_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2865041;
--
-- AUTO_INCREMENT for table `mdl_log_corsi_ind`
--
ALTER TABLE `mdl_log_corsi_ind`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5196;
--
-- AUTO_INCREMENT for table `mdl_log_corsi_ind_archiviazione`
--
ALTER TABLE `mdl_log_corsi_ind_archiviazione`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9559;
--
-- AUTO_INCREMENT for table `mdl_log_corsi_ind_prot`
--
ALTER TABLE `mdl_log_corsi_ind_prot`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1326;
--
-- AUTO_INCREMENT for table `mdl_log_display`
--
ALTER TABLE `mdl_log_display`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=206;
--
-- AUTO_INCREMENT for table `mdl_log_queries`
--
ALTER TABLE `mdl_log_queries`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1026;
--
-- AUTO_INCREMENT for table `mdl_lti`
--
ALTER TABLE `mdl_lti`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lti_submission`
--
ALTER TABLE `mdl_lti_submission`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lti_types`
--
ALTER TABLE `mdl_lti_types`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_lti_types_config`
--
ALTER TABLE `mdl_lti_types_config`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_message`
--
ALTER TABLE `mdl_message`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=39617;
--
-- AUTO_INCREMENT for table `mdl_message_airnotifier_devices`
--
ALTER TABLE `mdl_message_airnotifier_devices`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_message_contacts`
--
ALTER TABLE `mdl_message_contacts`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=203;
--
-- AUTO_INCREMENT for table `mdl_message_processors`
--
ALTER TABLE `mdl_message_processors`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_message_providers`
--
ALTER TABLE `mdl_message_providers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=26;
--
-- AUTO_INCREMENT for table `mdl_message_read`
--
ALTER TABLE `mdl_message_read`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=33404;
--
-- AUTO_INCREMENT for table `mdl_message_working`
--
ALTER TABLE `mdl_message_working`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=23534;
--
-- AUTO_INCREMENT for table `mdl_mnetservice_enrol_courses`
--
ALTER TABLE `mdl_mnetservice_enrol_courses`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_mnetservice_enrol_enrolments`
--
ALTER TABLE `mdl_mnetservice_enrol_enrolments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_mnet_application`
--
ALTER TABLE `mdl_mnet_application`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mdl_mnet_host`
--
ALTER TABLE `mdl_mnet_host`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `mdl_mnet_host2service`
--
ALTER TABLE `mdl_mnet_host2service`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_mnet_log`
--
ALTER TABLE `mdl_mnet_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_mnet_remote_rpc`
--
ALTER TABLE `mdl_mnet_remote_rpc`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `mdl_mnet_remote_service2rpc`
--
ALTER TABLE `mdl_mnet_remote_service2rpc`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `mdl_mnet_rpc`
--
ALTER TABLE `mdl_mnet_rpc`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `mdl_mnet_service`
--
ALTER TABLE `mdl_mnet_service`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `mdl_mnet_service2rpc`
--
ALTER TABLE `mdl_mnet_service2rpc`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `mdl_mnet_session`
--
ALTER TABLE `mdl_mnet_session`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_mnet_sso_access_control`
--
ALTER TABLE `mdl_mnet_sso_access_control`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_modules`
--
ALTER TABLE `mdl_modules`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT for table `mdl_my_pages`
--
ALTER TABLE `mdl_my_pages`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=87;
--
-- AUTO_INCREMENT for table `mdl_org`
--
ALTER TABLE `mdl_org`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2689;
--
-- AUTO_INCREMENT for table `mdl_org_assignment`
--
ALTER TABLE `mdl_org_assignment`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5859;
--
-- AUTO_INCREMENT for table `mdl_org_depth`
--
ALTER TABLE `mdl_org_depth`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_org_depth_info_category`
--
ALTER TABLE `mdl_org_depth_info_category`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_org_depth_info_data`
--
ALTER TABLE `mdl_org_depth_info_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_org_depth_info_field`
--
ALTER TABLE `mdl_org_depth_info_field`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_org_framework`
--
ALTER TABLE `mdl_org_framework`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_org_relations`
--
ALTER TABLE `mdl_org_relations`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_page`
--
ALTER TABLE `mdl_page`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `mdl_ponos_course`
--
ALTER TABLE `mdl_ponos_course`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=54;
--
-- AUTO_INCREMENT for table `mdl_ponos_insert`
--
ALTER TABLE `mdl_ponos_insert`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=56;
--
-- AUTO_INCREMENT for table `mdl_ponos_log`
--
ALTER TABLE `mdl_ponos_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11656;
--
-- AUTO_INCREMENT for table `mdl_ponos_report`
--
ALTER TABLE `mdl_ponos_report`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `mdl_portfolio_instance`
--
ALTER TABLE `mdl_portfolio_instance`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_portfolio_instance_config`
--
ALTER TABLE `mdl_portfolio_instance_config`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mdl_portfolio_instance_user`
--
ALTER TABLE `mdl_portfolio_instance_user`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_portfolio_log`
--
ALTER TABLE `mdl_portfolio_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_portfolio_mahara_queue`
--
ALTER TABLE `mdl_portfolio_mahara_queue`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_portfolio_tempdata`
--
ALTER TABLE `mdl_portfolio_tempdata`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_post`
--
ALTER TABLE `mdl_post`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_profiling`
--
ALTER TABLE `mdl_profiling`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_qtype_essay_options`
--
ALTER TABLE `mdl_qtype_essay_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_qtype_match_options`
--
ALTER TABLE `mdl_qtype_match_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_qtype_match_subquestions`
--
ALTER TABLE `mdl_qtype_match_subquestions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `mdl_qtype_multichoice_options`
--
ALTER TABLE `mdl_qtype_multichoice_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=929;
--
-- AUTO_INCREMENT for table `mdl_qtype_randomsamatch_options`
--
ALTER TABLE `mdl_qtype_randomsamatch_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_qtype_shortanswer_options`
--
ALTER TABLE `mdl_qtype_shortanswer_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=46;
--
-- AUTO_INCREMENT for table `mdl_question`
--
ALTER TABLE `mdl_question`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1665;
--
-- AUTO_INCREMENT for table `mdl_question_answers`
--
ALTER TABLE `mdl_question_answers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3139;
--
-- AUTO_INCREMENT for table `mdl_question_attempts`
--
ALTER TABLE `mdl_question_attempts`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=340524;
--
-- AUTO_INCREMENT for table `mdl_question_attempt_steps`
--
ALTER TABLE `mdl_question_attempt_steps`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1011272;
--
-- AUTO_INCREMENT for table `mdl_question_attempt_step_data`
--
ALTER TABLE `mdl_question_attempt_step_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=985195;
--
-- AUTO_INCREMENT for table `mdl_question_calculated`
--
ALTER TABLE `mdl_question_calculated`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_question_calculated_options`
--
ALTER TABLE `mdl_question_calculated_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_question_categories`
--
ALTER TABLE `mdl_question_categories`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=173;
--
-- AUTO_INCREMENT for table `mdl_question_datasets`
--
ALTER TABLE `mdl_question_datasets`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mdl_question_dataset_definitions`
--
ALTER TABLE `mdl_question_dataset_definitions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mdl_question_dataset_items`
--
ALTER TABLE `mdl_question_dataset_items`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `mdl_question_hints`
--
ALTER TABLE `mdl_question_hints`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_question_multianswer`
--
ALTER TABLE `mdl_question_multianswer`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_question_numerical`
--
ALTER TABLE `mdl_question_numerical`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_question_numerical_options`
--
ALTER TABLE `mdl_question_numerical_options`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_question_numerical_units`
--
ALTER TABLE `mdl_question_numerical_units`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_question_response_analysis`
--
ALTER TABLE `mdl_question_response_analysis`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_question_response_count`
--
ALTER TABLE `mdl_question_response_count`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_question_statistics`
--
ALTER TABLE `mdl_question_statistics`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_question_truefalse`
--
ALTER TABLE `mdl_question_truefalse`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=170;
--
-- AUTO_INCREMENT for table `mdl_question_usages`
--
ALTER TABLE `mdl_question_usages`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=31162;
--
-- AUTO_INCREMENT for table `mdl_quiz`
--
ALTER TABLE `mdl_quiz`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=54;
--
-- AUTO_INCREMENT for table `mdl_quiz_attempts`
--
ALTER TABLE `mdl_quiz_attempts`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=30613;
--
-- AUTO_INCREMENT for table `mdl_quiz_feedback`
--
ALTER TABLE `mdl_quiz_feedback`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=597;
--
-- AUTO_INCREMENT for table `mdl_quiz_grades`
--
ALTER TABLE `mdl_quiz_grades`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22405;
--
-- AUTO_INCREMENT for table `mdl_quiz_overrides`
--
ALTER TABLE `mdl_quiz_overrides`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_quiz_overview_regrades`
--
ALTER TABLE `mdl_quiz_overview_regrades`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_quiz_reports`
--
ALTER TABLE `mdl_quiz_reports`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_quiz_slots`
--
ALTER TABLE `mdl_quiz_slots`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=726;
--
-- AUTO_INCREMENT for table `mdl_quiz_statistics`
--
ALTER TABLE `mdl_quiz_statistics`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_rating`
--
ALTER TABLE `mdl_rating`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_registration_hubs`
--
ALTER TABLE `mdl_registration_hubs`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_repository`
--
ALTER TABLE `mdl_repository`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `mdl_repository_instances`
--
ALTER TABLE `mdl_repository_instances`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `mdl_repository_instance_config`
--
ALTER TABLE `mdl_repository_instance_config`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_resource`
--
ALTER TABLE `mdl_resource`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=890;
--
-- AUTO_INCREMENT for table `mdl_resource_old`
--
ALTER TABLE `mdl_resource_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_role`
--
ALTER TABLE `mdl_role`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=19;
--
-- AUTO_INCREMENT for table `mdl_role_allow_assign`
--
ALTER TABLE `mdl_role_allow_assign`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=38;
--
-- AUTO_INCREMENT for table `mdl_role_allow_override`
--
ALTER TABLE `mdl_role_allow_override`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=34;
--
-- AUTO_INCREMENT for table `mdl_role_allow_switch`
--
ALTER TABLE `mdl_role_allow_switch`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT for table `mdl_role_assignments`
--
ALTER TABLE `mdl_role_assignments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=73546;
--
-- AUTO_INCREMENT for table `mdl_role_capabilities`
--
ALTER TABLE `mdl_role_capabilities`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4911;
--
-- AUTO_INCREMENT for table `mdl_role_context_levels`
--
ALTER TABLE `mdl_role_context_levels`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=353;
--
-- AUTO_INCREMENT for table `mdl_role_names`
--
ALTER TABLE `mdl_role_names`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `mdl_role_sortorder`
--
ALTER TABLE `mdl_role_sortorder`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_scale`
--
ALTER TABLE `mdl_scale`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_scale_history`
--
ALTER TABLE `mdl_scale_history`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_scorm`
--
ALTER TABLE `mdl_scorm`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=163;
--
-- AUTO_INCREMENT for table `mdl_scorm_aicc_session`
--
ALTER TABLE `mdl_scorm_aicc_session`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_scorm_scoes`
--
ALTER TABLE `mdl_scorm_scoes`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=701;
--
-- AUTO_INCREMENT for table `mdl_scorm_scoes_data`
--
ALTER TABLE `mdl_scorm_scoes_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=754;
--
-- AUTO_INCREMENT for table `mdl_scorm_scoes_track`
--
ALTER TABLE `mdl_scorm_scoes_track`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=395709;
--
-- AUTO_INCREMENT for table `mdl_scorm_seq_mapinfo`
--
ALTER TABLE `mdl_scorm_seq_mapinfo`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_scorm_seq_objective`
--
ALTER TABLE `mdl_scorm_seq_objective`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `mdl_scorm_seq_rolluprule`
--
ALTER TABLE `mdl_scorm_seq_rolluprule`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_scorm_seq_rolluprulecond`
--
ALTER TABLE `mdl_scorm_seq_rolluprulecond`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_scorm_seq_rulecond`
--
ALTER TABLE `mdl_scorm_seq_rulecond`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_scorm_seq_ruleconds`
--
ALTER TABLE `mdl_scorm_seq_ruleconds`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_sessions`
--
ALTER TABLE `mdl_sessions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2385534;
--
-- AUTO_INCREMENT for table `mdl_stats_daily`
--
ALTER TABLE `mdl_stats_daily`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1452338;
--
-- AUTO_INCREMENT for table `mdl_stats_monthly`
--
ALTER TABLE `mdl_stats_monthly`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=61399;
--
-- AUTO_INCREMENT for table `mdl_stats_user_daily`
--
ALTER TABLE `mdl_stats_user_daily`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=354858;
--
-- AUTO_INCREMENT for table `mdl_stats_user_monthly`
--
ALTER TABLE `mdl_stats_user_monthly`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=183519;
--
-- AUTO_INCREMENT for table `mdl_stats_user_weekly`
--
ALTER TABLE `mdl_stats_user_weekly`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=251865;
--
-- AUTO_INCREMENT for table `mdl_stats_weekly`
--
ALTER TABLE `mdl_stats_weekly`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=226902;
--
-- AUTO_INCREMENT for table `mdl_survey`
--
ALTER TABLE `mdl_survey`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `mdl_survey_analysis`
--
ALTER TABLE `mdl_survey_analysis`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_survey_answers`
--
ALTER TABLE `mdl_survey_answers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_survey_questions`
--
ALTER TABLE `mdl_survey_questions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=74;
--
-- AUTO_INCREMENT for table `mdl_tag`
--
ALTER TABLE `mdl_tag`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=53;
--
-- AUTO_INCREMENT for table `mdl_tag_correlation`
--
ALTER TABLE `mdl_tag_correlation`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_tag_instance`
--
ALTER TABLE `mdl_tag_instance`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=67;
--
-- AUTO_INCREMENT for table `mdl_task_adhoc`
--
ALTER TABLE `mdl_task_adhoc`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_task_scheduled`
--
ALTER TABLE `mdl_task_scheduled`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT for table `mdl_timezone`
--
ALTER TABLE `mdl_timezone`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_tool_customlang`
--
ALTER TABLE `mdl_tool_customlang`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=25610;
--
-- AUTO_INCREMENT for table `mdl_tool_customlang_components`
--
ALTER TABLE `mdl_tool_customlang_components`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=468;
--
-- AUTO_INCREMENT for table `mdl_tool_mergeusers`
--
ALTER TABLE `mdl_tool_mergeusers`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mdl_upgrade_log`
--
ALTER TABLE `mdl_upgrade_log`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2285;
--
-- AUTO_INCREMENT for table `mdl_url`
--
ALTER TABLE `mdl_url`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=998;
--
-- AUTO_INCREMENT for table `mdl_url_new`
--
ALTER TABLE `mdl_url_new`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=111;
--
-- AUTO_INCREMENT for table `mdl_user`
--
ALTER TABLE `mdl_user`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13699;
--
-- AUTO_INCREMENT for table `mdl_user_devices`
--
ALTER TABLE `mdl_user_devices`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_user_enrolments`
--
ALTER TABLE `mdl_user_enrolments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=63035;
--
-- AUTO_INCREMENT for table `mdl_user_info_category`
--
ALTER TABLE `mdl_user_info_category`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mdl_user_info_data`
--
ALTER TABLE `mdl_user_info_data`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=21368;
--
-- AUTO_INCREMENT for table `mdl_user_info_field`
--
ALTER TABLE `mdl_user_info_field`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mdl_user_lastaccess`
--
ALTER TABLE `mdl_user_lastaccess`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=36956;
--
-- AUTO_INCREMENT for table `mdl_user_password_resets`
--
ALTER TABLE `mdl_user_password_resets`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_user_preferences`
--
ALTER TABLE `mdl_user_preferences`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11987;
--
-- AUTO_INCREMENT for table `mdl_user_private_key`
--
ALTER TABLE `mdl_user_private_key`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_webdav_locks`
--
ALTER TABLE `mdl_webdav_locks`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_wiki`
--
ALTER TABLE `mdl_wiki`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `mdl_wiki_links`
--
ALTER TABLE `mdl_wiki_links`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_wiki_locks`
--
ALTER TABLE `mdl_wiki_locks`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=804;
--
-- AUTO_INCREMENT for table `mdl_wiki_pages`
--
ALTER TABLE `mdl_wiki_pages`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=15;
--
-- AUTO_INCREMENT for table `mdl_wiki_subwikis`
--
ALTER TABLE `mdl_wiki_subwikis`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `mdl_wiki_synonyms`
--
ALTER TABLE `mdl_wiki_synonyms`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_wiki_versions`
--
ALTER TABLE `mdl_wiki_versions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=473;
--
-- AUTO_INCREMENT for table `mdl_workshop`
--
ALTER TABLE `mdl_workshop`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopallocation_scheduled`
--
ALTER TABLE `mdl_workshopallocation_scheduled`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopeval_best_settings`
--
ALTER TABLE `mdl_workshopeval_best_settings`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopform_accumulative`
--
ALTER TABLE `mdl_workshopform_accumulative`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopform_comments`
--
ALTER TABLE `mdl_workshopform_comments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopform_numerrors`
--
ALTER TABLE `mdl_workshopform_numerrors`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopform_numerrors_map`
--
ALTER TABLE `mdl_workshopform_numerrors_map`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopform_rubric`
--
ALTER TABLE `mdl_workshopform_rubric`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopform_rubric_config`
--
ALTER TABLE `mdl_workshopform_rubric_config`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshopform_rubric_levels`
--
ALTER TABLE `mdl_workshopform_rubric_levels`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_aggregations`
--
ALTER TABLE `mdl_workshop_aggregations`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_assessments`
--
ALTER TABLE `mdl_workshop_assessments`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_assessments_old`
--
ALTER TABLE `mdl_workshop_assessments_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_comments_old`
--
ALTER TABLE `mdl_workshop_comments_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_elements_old`
--
ALTER TABLE `mdl_workshop_elements_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_grades`
--
ALTER TABLE `mdl_workshop_grades`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_grades_old`
--
ALTER TABLE `mdl_workshop_grades_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_old`
--
ALTER TABLE `mdl_workshop_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_rubrics_old`
--
ALTER TABLE `mdl_workshop_rubrics_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_stockcomments_old`
--
ALTER TABLE `mdl_workshop_stockcomments_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_submissions`
--
ALTER TABLE `mdl_workshop_submissions`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mdl_workshop_submissions_old`
--
ALTER TABLE `mdl_workshop_submissions_old`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `monitor_config`
--
ALTER TABLE `monitor_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT for table `monitor_log`
--
ALTER TABLE `monitor_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `monitor_servers`
--
ALTER TABLE `monitor_servers`
  MODIFY `server_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `monitor_users`
--
ALTER TABLE `monitor_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `tbl_eml_cf_da_ignorare`
--
ALTER TABLE `tbl_eml_cf_da_ignorare`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11685;
--
-- AUTO_INCREMENT for table `tbl_eml_grep_feed_back`
--
ALTER TABLE `tbl_eml_grep_feed_back`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tbl_eml_grfo_feed_back`
--
ALTER TABLE `tbl_eml_grfo_feed_back`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=65;
--
-- AUTO_INCREMENT for table `tbl_eml_grfo_log`
--
ALTER TABLE `tbl_eml_grfo_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tbl_eml_mapping_categorie`
--
ALTER TABLE `tbl_eml_mapping_categorie`
  MODIFY `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=36;
--
-- AUTO_INCREMENT for table `tbl_eml_pent_questionari_dati`
--
ALTER TABLE `tbl_eml_pent_questionari_dati`
  MODIFY `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=70578599;
--
-- AUTO_INCREMENT for table `tbl_eml_php_log`
--
ALTER TABLE `tbl_eml_php_log`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=146848;
--
-- AUTO_INCREMENT for table `tbl_eml_php_log_query`
--
ALTER TABLE `tbl_eml_php_log_query`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
