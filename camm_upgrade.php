<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2007-2010 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/* do NOT run this script through a web browser */
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}
$no_http_headers = true;

ini_set('max_execution_time', "0");
ini_set('memory_limit', '256M');


/* record the start time */
list($micro,$seconds) = explode(" ", microtime());
$start_time = $seconds + $micro;

$dir = dirname(__FILE__);
chdir($dir);

if (strpos($dir, 'plugins') !== false) {
	chdir('../../');
}
include("./include/global.php");
include_once($config['base_path'] . "/plugins/camm/lib/camm_functions.php");


$syslogdb_default = read_config_option("camm_syslog_db_name");

//db_execute("RENAME TABLE `" . $syslogdb_default . "`.`plugin_camm_syslog` TO `" . $syslogdb_default . "`.`plugin_camm_syslog_pre_upgrade`", true);


	$days=30;
	$sql = "CREATE TABLE IF NOT EXISTS `" . $syslogdb_default . "`.`plugin_camm_syslog_parted` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `host` varchar(128) DEFAULT NULL,
			  `sourceip` varchar(45) NOT NULL,
			  `facility` varchar(10) DEFAULT NULL,
			  `priority` varchar(10) DEFAULT NULL,
			  `sys_date` datetime DEFAULT NULL,
			  `message` text,
			  `status` tinyint(4) NOT NULL DEFAULT '0',
			  `alert` smallint(3) unsigned NOT NULL DEFAULT '0',
			  `add` varchar(50) DEFAULT '',
			  PRIMARY KEY (`id`,`sys_date`),
			  KEY `facility` (`facility`),
			  KEY `priority` (`priority`),
			  KEY `sourceip` (`sourceip`),
			  KEY `status` (`status`),
			  KEY `alert` (`alert`),
			  KEY `status_date` (`status`,`sys_date`),
			  KEY `sys_date` (`sys_date`),
			  KEY `hostname` (`host`)) ENGINE=MyISAM COMMENT='SNMPTT plugin SYSLOG Data'
			PARTITION BY RANGE (TO_DAYS(sys_date))\n";

	$now = time();

	$parts = "";
	for($i = $days; $i > 0; $i--) {
		$timestamp = $now - ($i * 86400);
		$date     = date('Y-m-d', $timestamp);
		$format   = date("Ymd", $timestamp);
		$parts .= ($parts != "" ? ",\n":"(") . " PARTITION d" . $format . " VALUES LESS THAN (TO_DAYS('" . $date . "'))";
	}
	$parts .= ",\nPARTITION dMaxValue VALUES LESS THAN MAXVALUE);";

	db_execute($sql . $parts, true);

	$table = 'plugin_camm_syslog';
			$seq1 = 0;	
			$seq2 = 0;
			while ( true ) {
				$fetch_size = '10000';
				$seq1   = db_fetch_cell("SELECT max(id) FROM (SELECT id FROM `" . $syslogdb_default . "`.`$table` where id >$seq1 ORDER BY id LIMIT $fetch_size) AS preupgrade", '', false);

				if ($seq1 > 0 && $seq1 != '') {
					db_execute("INSERT INTO `" . $syslogdb_default . "`.`plugin_camm_syslog_parted` 
						SELECT *
						FROM `" . $syslogdb_default . "`.`$table`
						WHERE id<$seq1 and id>$seq2 ", true);
						$seq2 = $seq1;
					//db_execute("DELETE FROM `" . $syslogdb_default . "`.`$table` WHERE id<=$seq1", true);
				}else{
					//db_execute("DROP TABLE `" . $syslogdb_default . "`.`$table`", true, $syslog_cnn);
					break;
				}
			}

	
cacti_log("CAMM NOTE: Background CAMM Database Upgrade Process Completed", false, "SYSTEM");
