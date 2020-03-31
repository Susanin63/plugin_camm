<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2008 Susanin                                          |
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
 */
 
 /*******************************************************************************
 
     Author ......... Susanin (gthe in forum.cacti.net)
     Program ........ camm viewer for cacti
     Version ........ 0.0.08b
 
 *******************************************************************************/
 function plugin_init_camm() {
 
    global $plugin_hooks;
    $plugin_hooks['config_arrays']['camm'] = 'camm_config_arrays'; 
    $plugin_hooks['config_settings']['camm'] = 'camm_config_settings'; // Settings tab
    $plugin_hooks['top_header_tabs']['camm'] = 'plugin_camm_show_tab'; // Top tab
    $plugin_hooks['top_graph_header_tabs']['camm'] = 'plugin_camm_show_tab'; // Top tab for graphs
    $plugin_hooks['draw_navigation_text']['camm'] = 'camm_draw_navigation_text';
 	$plugin_hooks['poller_top']['camm'] = 'camm_poller_bottom';
 
 }
 
 function plugin_camm_install () {
 
	api_plugin_register_hook('camm', 'top_header_tabs',       'camm_show_tab',             'setup.php');
	api_plugin_register_hook('camm', 'top_graph_header_tabs', 'camm_show_tab',             'setup.php');
	api_plugin_register_hook('camm', 'config_arrays',         'camm_config_arrays',        'setup.php');
	api_plugin_register_hook('camm', 'config_settings',       'camm_config_settings',      'setup.php');
	api_plugin_register_hook('camm', 'draw_navigation_text',  'camm_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('camm', 'poller_bottom',         'camm_poller_bottom',        'setup.php');
	api_plugin_register_hook('camm', 'page_head',             'camm_page_head',            'setup.php');


	# Register our realms
 	api_plugin_register_realm('camm', 'camm_view.php,camm_db.php', 'Plugin -> camm: View', 1);
 	api_plugin_register_realm('camm', 'camm_db_admin.php', 'Plugin -> camm: Manage', 1);
		
	camm_setup_table ();


 }
 
 function camm_show_tab () {
	global $config;

	if (api_user_realm_auth('camm_view.php')) {
		if (substr_count($_SERVER['REQUEST_URI'], 'camm_view')) {
			print '<a href="' . $config['url_path'] . 'plugins/camm/camm_view.php"><img src="' . $config['url_path'] . 'plugins/camm/images/tab_camm_red.gif" alt="' . __('CAMM') . '"></a>';
		}else{
			print '<a href="' . $config['url_path'] . 'plugins/camm/camm_view.php"><img src="' . $config['url_path'] . 'plugins/camm/images/tab_camm.gif" alt="' . __('CAMM') . '"></a>';
		}
	}

 }

 function camm_page_title ($in) {
 	global $config;
 	
 	$out = $in;
 	
 	$url = $_SERVER['REQUEST_URI'];
 		
 	if(preg_match('#/plugins/camm/camm_view.php#', $url))
 	{
 		$out .= " - CAMM (CActi Message Managment)";
 	}
 		
 	return ($out);	
 }
 
 function plugin_camm_uninstall () {
     // Do any extra Uninstall stuff here
	db_execute('DROP TABLE IF EXISTS `plugin_camm_keys`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_rule`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_snmptt`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_snmptt_stat`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_snmptt_unk`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_syslog`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_syslog_incoming`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_temp`');
	db_execute('DROP TABLE IF EXISTS `plugin_camm_tree2`');

 	db_execute("delete FROM settings where name like 'camm%';");
 	kill_session_var("camm_output_messages");
 }
 
 
 function plugin_camm_check_config () {
     // Here we will check to ensure everything is configured
     camm_check_upgrade ();
     return true;
 }
 
 function plugin_camm_upgrade () {
     // Here we will upgrade to the newest version
     camm_check_upgrade ();
     return false;
 }
 
 function plugin_camm_version () {
     // Here we will upgrade to the newest version
     return camm_version ();
 }
 
 function camm_config_arrays () {
 	global $user_auth_realms, $menu, $user_auth_realm_filenames;
 	global $camm_poller_purge_frequencies, $camm_purge_delay, $camm_purge_tables,  $camm_rows_test, $camm_tree_update, $camm_rows_selector,  $camm_grid_update;
 
 
     $camm_rows_test = array(
     100 => "100",
     200 => "200",
     500 => "500",
     1000 => "1000",
 	5000 => "5000",
 	10000 => "10000",
     0 => "ALL");
 	
 	$camm_rows_selector = array(
 		-1 => "Default",
 		10 => "10",
 		15 => "15",
 		20 => "20",
 		30 => "30",
 		50 => "50",
 		100 => "100",
 		500 => "500",
 		1000 => "1000",
 		-2 => "All");	
 		
 	$camm_poller_purge_frequencies = array(
 		"disabled" => "Disabled",
 		"10" => "Every 10 Minutes",
 		"15" => "Every 30 Minutes",
 		"60" => "Every 1 Hour",
 		"120" => "Every 2 Hours",
 		"240" => "Every 4 Hours",
 		"480" => "Every 8 Hours",
 		"720" => "Every 12 Hours",
 		"1440" => "Every Day");
 	$camm_purge_delay = array(
 		"1" => "1 Day",
 		"3" => "3 Days",
 		"5" => "5 Days",
 		"7" => "1 Week",
 		"14" => "2 Week",
 		"30" => "1 Month",
 		"60" => "2 Month");	
 	$camm_tree_update = array(
 		"30" => "30 Sec",
 		"60" => "1 Minute",
 		"120" => "2 Minutes",
 		"180" => "3 Minutes",
 		"300" => "5 Minutes",
 		"600" => "10 Minutes",
 		"1800" => "30 Minutes",
 		"3600" => "Every 1 Hour",
 		"7200" => "Every 2 Hours",
 		"14400" => "Every 4 Hours",
 		"28800" => "Every 8 Hours"		
 		);		
 	$camm_purge_tables = array(
 		"1" => "plugin_camm_traps",
 		"2" => "plugin_camm_unknown_traps",
 		"3" => "both");
 	$camm_grid_update = array(
 		"0" => "Never",
 		"0.2" => "12 Sec",
 		"0.5" => "30 Sec",
 		"1" => "1 Minute",
 		"5" => "5 Minutes",
 		"10" => "10 Minutes",
 		"15" => "15 Minutes",
 		"30" => "30 Minutes",
 		"60" => "Every 1 Hour"
 		);			
 }
 
 function camm_config_settings () {
 	global $tabs, $settings, $camm_poller_purge_frequencies, $camm_purge_delay, $camm_purge_tables, $camm_tree_update, $camm_rows_test, $camm_grid_update;
	global $database_default;
 
 	$tabs["camm"] = "camm";
	$camm_use_group_by_host = read_config_option("camm_use_group_by_host", true);
	
 	$settings["camm"] = array(
 		"camm_hdr_components" => array(
 			"friendly_name" => "1. CaMM components",
 			"method" => "spacer",
 			),
 		"camm_use_snmptt" => array(
 			"friendly_name" => "Use SNMPTT",
 			"description" => "Use SNMPTT component (both traps and unknown traps)",
 			"order" => "1.1.",			
 			"method" => "drop_array",
 			"default" => "false",
 			"array" => array(1=>"true",0=>"false"),
 			),
 		"camm_use_syslog" => array(
 			"friendly_name" => "Use SYSLOG",
 			"description" => "Use Syslog-ng database data",
 			"order" => "1.2.",			
 			"method" => "drop_array",
 			"default" => "false",
 			"array" => array(1=>"true",0=>"false"),
 			),
 		"camm_use_cactilog" => array(
 			"friendly_name" => "Use Cacti log",
 			"description" => "Use Cacti log from database",
 			"order" => "1.3.",			
 			"method" => "drop_array",
 			"default" => "not yet :)",
 			"array" => array(0=>"not yet :)"),
 			),				
 		"camm_hdr_general" => array(
 			"friendly_name" => "2. CaMM General Settings",
 			"method" => "spacer",
 			),			
 		"camm_test_row_count" => array(
 			"friendly_name" => "Count rows to test",
 			"description" => "Choose count rows to test with rule when create it.",
 			"order" => "2.1.",
 			"method" => "drop_array",
 			"default" => "1000",
 			"array" => $camm_rows_test,			
 			),
 		"camm_autopurge_timing" => array(
 			"friendly_name" => "Data Purge Timing",
 			"description" => "Choose when auto purge records from database.",
 			"order" => "2.2.",			
 			"method" => "drop_array",
 			"default" => "disabled",
 			"array" => $camm_poller_purge_frequencies,
 			),		
 		"camm_show_all_records" => array(
 			"friendly_name" => "Show all records",
 			"description" => "Choose - show all records or only already processed by rules.",
 			"order" => "2.3.",			
 			"method" => "drop_array",
 			"default" => "show all",
 			"array" => array(0=>"show only processed",1=>"show all"),
 			),
 		"camm_join_field" => array(
 			"friendly_name" => "Join field name",
 			"description" => "Choose join field on which record (trap or syslog) will be joined with cacti device's",
 			"order" => "2.3.",			
 			"method" => "drop_array",
 			"default" => "IP-address (if you device DON'T use DNS name)",
 			"array" => array("hostname"=>"DNS-hostname (if you device use DNS name)","sourceip"=>"IP-address (if you device DON'T use DNS name)"),
 			),
 		"camm_use_fqdn" => array(
 			"friendly_name" => "Hostname include domain",
 			"description" => "Do you use host with FQDN in cacti device's ?",
 			"order" => "2.4.",			
 			"method" => "drop_array",
 			"default" => "Don't use FQDN in cacti hosts hostname (like cacti) - default",
 			"array" => array(0=>"0 - Don't use FQDN in cacti hosts hostname (like cacti) - default",1=>"1 - Use FQDN in cacti hosts hostname(like cacti.domain.local). Parameter [Join field name] MUST BE hostname"),
 			),			
 		"camm_debug_mode" => array(
 			"friendly_name" => "Debug mode",
 			"description" => "Enable debug mode for more verbose output in cacti log file",
 			"order" => "2.5.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Disable",1=>"Enable"),	
 			),
 		"camm_general_graphs_ids" => array(
 			"friendly_name" => "Graphs ID's to show",
 			"description" => "Enter the Graph's ID to show in stats tab.",
 			"order" => "2.6.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_general_graphs_ids|",
 			"default" => "0",
 			"max_length" => "50",
 			),			
 		"camm_tab_image_size" => array(
 			"friendly_name" => "Tab style",
 			"description" => "Which size tabs to use?",
 			"order" => "2.7.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Regular",1=>"Smaller"),	
 			),
		"camm_process_markers" => array(
 			"friendly_name" => "Create tree menu for Markers",
 			"description" => "Create tree menu based on markers (alert) field ?",
 			"order" => "2.8.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"true",0=>"false"),
 			),
		"camm_rule_order" => array(
 			"friendly_name" => "Choose a sort order rules",
 			"description" => "What sort of use? (which rules to handle in the first place)",
 			"order" => "2.9.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"default (first maximum for removal)",2=>"by order field"),
 			),
		"camm_action_order" => array(
 			"friendly_name" => "Choose a order of actions in rule",
 			"description" => "In what order to perform actions in each rule?",
 			"order" => "2.10.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"func, mail, del or mark (default )",2=>"mail, func, mark, del",3=>"mark, mail, func, del",4=>"mail, mark, func, del"),
 			),
 		"camm_email_title" => array(
 			"friendly_name" => "Email Title",
 			"description" => "Enter string that will be put in every email",
 			"order" => "2.11.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_email_title|",
 			"default" => "An alert has been issued that requires your attention.",
 			"max_length" => "150",
 			),
		"camm_use_group_by_host" => array(
 			"friendly_name" => "Use grouping by host's hostname ? (Read-only)",
 			"description" => "If the host table has records with identical values of the hostname field that will be used to force grouping",
 			"order" => "2.11.",			
 			"method" => "drop_array",
 			"default" => "1",
 			),		
 		"camm_hdr_period" => array(
 			"friendly_name" => "3. CaMM Period Settings",
 			"method" => "spacer",
 			),
		"camm_period_hour" => array(
 			"friendly_name" => "Hour period",
 			"description" => "Create menu and stat for hour period ?",
 			"order" => "3.1.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"true",0=>"false"),
 			),
		"camm_period_day" => array(
 			"friendly_name" => "Day period",
 			"description" => "Create menu and stat for day period ?",
 			"order" => "3.2.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"true",0=>"false"),
 			),
		"camm_period_week" => array(
 			"friendly_name" => "Week period",
 			"description" => "Create menu and stat for week period ?",
 			"order" => "3.3.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(1=>"true",0=>"false"),
 			),				
 		"camm_hdr_timing" => array(
 			"friendly_name" => "4. CaMM SNMPTT Settings",
 			"method" => "spacer",
 			),
 		"camm_snmptt_delay_purge_day" => array(
 			"friendly_name" => "Data Purge Delay",
 			"description" => "Choose after what period data may be purged.",
 			"order" => "4.1.",			
 			"method" => "drop_array",
 			"default" => "7",
 			"array" => $camm_purge_delay,
 			),			
 		"camm_snmptt_min_row_all" => array(
 			"friendly_name" => "Min rows in tables",
 			"description" => "Specify the minimum number of rows.<br>No matter their retention period specified number of rows can not be removed.<br>Ie If you specify 1 million, then deleting the old records at least 1 million will remain,<br>even if they are older than the specified term. <br> Zerro for unlimited.",
 			"order" => "4.2.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_snmptt_min_row_all|",
 			"default" => "50000",
 			"max_length" => "7",
 			),
 		"camm_snmptt_max_row_per_device" => array(
 			"friendly_name" => "Max rows per device in day",
 			"description" => "Enter max count rows in tables per device per day. Zerro for unlimited.",
 			"order" => "4.3.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_snmptt_max_row_per_device|",
 			"default" => "1200",
 			"max_length" => "7",
 			),				
 		"camm_snmptt_tables" => array(
 			"friendly_name" => "What tables process",
 			"description" => "Choose table for processing",
 			"order" => "4.4.",			
 			"method" => "drop_array",
 			"default" => "3",
 			"array" => $camm_purge_tables,
 			),
 		"camm_snmptt_trap_tab_update" => array(
 			"friendly_name" => "Default Traps tab autoupdate interval",
 			"description" => "Choose how often Traps Tab grid will be AutoUpdated ?",
 			"order" => "4.5.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),
 		"camm_snmptt_unktrap_tab_update" => array(
 			"friendly_name" => "Default Unk. Traps tab autoupdate interval",
 			"description" => "Choose how often Unk. Traps Tab grid will be AutoUpdated ?",
 			"order" => "4.6.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),				
 		"camm_hdr_sys_purge" => array(
 			"friendly_name" => "5. CaMM SYSLOG Settings",
 			"method" => "spacer",
 			),
			
 		"camm_syslog_db_name" => array(
 			"friendly_name" => "Syslog db name",
 			"description" => "Enter syslog database name.",
 			"order" => "5.1.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_syslog_db_name|",
 			"default" => $database_default,
 			"max_length" => "50",
 			),
 		"camm_syslog_pretable_name" => array(
 			"friendly_name" => "Syslog incoming table",
 			"description" => "If You use separate table for incoming messages before processing rules - enter table name here <br> Or use [plugin_camm_syslog] for default (one table shema) <br> Table must be in [Syslog db name] database!",
 			"order" => "5.2.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_syslog_pretable_name|",
 			"default" => "plugin_camm_syslog",
 			"max_length" => "50",
 			),			
 		"camm_sys_delay_purge_day" => array(
 			"friendly_name" => "Data Purge Delay",
 			"description" => "Choose after what period data may be purged.",
 			"order" => "5.3.",			
 			"method" => "drop_array",
 			"default" => "7",
 			"array" => $camm_purge_delay,
 			),			
 		"camm_sys_min_row_all" => array(
 			"friendly_name" => "Min rows in table",
 			"description" => "Specify the minimum number of rows.<br>No matter their retention period specified number of rows can not be removed.<br>Ie If you specify 1 million, then deleting the old records at least 1 million will remain,<br>even if they are older than the specified term. <br> Zerro for unlimited.",
 			"order" => "5.4.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_sys_min_row_all|",
 			"default" => "50000",
 			"max_length" => "7",
 			),
 		"camm_sys_max_row_per_device" => array(
 			"friendly_name" => "Max rows per device in day",
 			"description" => "Enter max count rows in table per device per day. Zerro for unlimited. . Maximum = 5000000",
 			"order" => "5.5.",			
 			"method" => "numberfield",
			"max_value" => 5000000,
 			"value" => "|arg1:camm_sys_max_row_per_device|",
 			"default" => "1200",
 			"max_length" => "7",
 			),
 		"camm_sys_tab_update" => array(
 			"friendly_name" => "Default Sysalog tab autoupdate interval",
 			"description" => "Choose how often Syslog Tab grid will be AutoUpdated ?",
 			"order" => "5.6.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),			
 		"camm_hdr_startup" => array(
 			"friendly_name" => "6. CaMM Startup Settings",
 			"method" => "spacer",
 			),	
 		"camm_startup_tab" => array(
 			"friendly_name" => "Default start tab",
 			"description" => "Choose which tab will be opeb by default, at startup",
 			"order" => "6.1.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Syslog",1=>"Traps",2=>"Unknown Traps",3=>"Rules",4=>"Stats"),		
 			),				
 		"camm_tree_update" => array(
 			"friendly_name" => "Tree update interval",
 			"description" => "Choose how often update Tree.",
 			"order" => "6.2.",			
 			"method" => "drop_array",
 			"default" => "300",
 			"array" => $camm_tree_update,			
 			),
 		"camm_num_rows" => array(
 			"friendly_name" => "Rows Per Page",
 			"description" => "The number of rows to display on a single page for Syslog messages, Traps and unknow Traps.",
 			"order" => "6.3.",			
 			"method" => "drop_array",
 			"default" => "50",
 			"array" => array("5"=>5,"10"=>10,"20"=>20,"50"=>50,"100"=>100,"200"=>200)		
 			),
 		"camm_tree_menu_width" => array(
 			"friendly_name" => "Startup tree menu width",
 			"description" => "Enter tree menu width in % of browser width (10, 20 etc). Maximum = 50",
 			"order" => "5.6.",			
 			"method" => "numberfield",
 			"value" => "|arg1:camm_tree_menu_width|",
 			"default" => "20",
			"max_value" => 50,
 			"max_length" => "3",
 			) 		
 			
 	);
	
	if ($camm_use_group_by_host == 0) {
		$settings["camm"]["camm_use_group_by_host"]["array"]["0"] = "not use (fast select record)";
	} else {
		$settings["camm"]["camm_use_group_by_host"]["array"]["1"] = "use grouping (default )";
	}
	
	
	
 
 }
 
 
 function camm_draw_navigation_text ($nav) {

   $nav['camm_devices.php:'] = array('title' => 'camm', 'mapping' => 'index.php:', 'url' => 'camm_devices.php', 'level' => '1');
   $nav['camm_view.php:'] = array('title' => 'camm', 'mapping' => 'index.php:', 'url' => 'camm_view.php', 'level' => '1');
   $nav['start.php:'] = array('title' => 'CAMM (CActi Message Manager)', 'mapping' => 'index.php:', 'url' => 'start.php', 'level' => '2');
   
    return $nav;
 }
 
 function camm_poller_bottom () {
 	global $config;
 	include_once($config["base_path"] . "/lib/poller.php");
 	include_once($config["base_path"] . "/lib/data_query.php");
 
 	$command_string = read_config_option("path_php_binary");
 	$extra_args = "-q " . $config["base_path"] . "/plugins/camm/poller_camm.php";
 	exec_background($command_string, "$extra_args");
 }
 
  function camm_page_head() {
	global $config;

	if (substr_count(get_current_page(), 'camm_')) {

		/*<!-- Ext CSS and Libs -->*/
		print "<link type='text/css' href='" . $config['url_path'] . "plugins/camm/css/ext-all.css' rel='stylesheet'>\n";
		print "<link type='text/css' href='" . $config['url_path'] . "plugins/camm/css/xtheme-default.css' rel='stylesheet'>\n";
		print "<link type='text/css' href='" . $config['url_path'] . "plugins/camm/css/main.css' rel='stylesheet'>\n";
		
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/js/ext-base.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/js/ext-all.js'></script>\n";
	 
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/js/cacti.plugin.camm-min.js'></script>\n";
					
		
	}
}

function camm_page_head_debug() {
	global $config;

	if (substr_count(get_current_page(), 'camm_')) {

		/*<!-- Ext CSS and Libs -->*/
		print "<link type='text/css' href='" . $config['url_path'] . "plugins/camm/css/ext-all.css' rel='stylesheet'>\n";
		print "<link type='text/css' href='" . $config['url_path'] . "plugins/camm/css/xtheme-default.css' rel='stylesheet'>\n";
		print "<link type='text/css' href='" . $config['url_path'] . "plugins/camm/css/main.css' rel='stylesheet'>\n";
		
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/ext-base.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/ext-all.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/ext-overrides.js'></script>\n";

		//<!-- Custom CSS and Libs -->
		//<!-- print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/excanvas.r60.js'></script>\n"; -->

		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/pPageSize.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/PageSizerPlugin.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cherryonext.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.grid.RowActions.js'></script>\n";	
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.IconMenu.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.grid.RecordForm.js'></script>\n";	
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.plugins.js'></script>\n";	
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.PanelBlind.js'></script>\n";	
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/ExportGridToExcel.js'></script>\n";	
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/CheckBoxMemory.js'></script>\n";	


		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.wam.PropertyGrid.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/TreeGrid.packed.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.grid.ProgressColumn.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.plugins.TreeGridStatefull.js'></script>\n";

		//<!-- print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/jquery.flot.trunk.pie.js'></script>\n"; -->
		//<!-- print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/GetText.js'></script>\n"; -->
		//<!-- print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Flot.js'></script>\n"; -->

		//<!-- ExtJS adapter for Highcharts -->
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/adapter-extjs.js'></script>\n";
		//<!-- Highcharts includes -->
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/highcharts.js'></script>\n";
		//<!--[if IE]>print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/excanvas.compiled.js'></script>\n";<![endif]-->
		//<!-- ExtJS Plugin for Highcharts -->
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/Ext.ux.HighChart.js'></script>\n";	
		//<!-- print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/exporting.js'></script>\n";	 -->

		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.def_grid.js'></script>\n";		
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.windows.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.stats_tab.js'></script>\n";	
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.rules_tab.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.syslog_tab.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.traps_tab.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.unktraps_tab.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.settings_tab.js'></script>\n";


		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/camm/_js/cacti.plugin.camm.js'></script>\n";
	
					
		
	}
}


 function camm_version () {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/camm/INFO', true);
	return $info['info'];
 }
 
 
 function camm_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php', 'camm_view.php');
	if (!in_array(get_current_page(), $files)) {
		return;
	}

	include_once($config['base_path'] . '/plugins/camm/lib/camm_functions.php');

	$current = plugin_camm_version();
	$current = $current['version'];

	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='camm'");
	if (!sizeof($old) || $current != $old['version']) {
		/* if the plugin is installed and/or active */
		if (!sizeof($old) || $old['status'] == 1 || $old['status'] == 4) {
			/* re-register the hooks */
			plugin_camm_install();
			if (api_plugin_is_enabled('camm')) {
				# may sound ridiculous, but enables new hooks
				api_plugin_enable_hooks('camm');
			}

			/* perform a database upgrade */
			camm_setup_table();
		}

		
		
		/* rebuild the scanning functions */
		//bdcom_rebuild_scanning_funcs();

		/* update the plugin information */
		$info = plugin_camm_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='camm'");

		db_execute("UPDATE plugin_config
			SET name='" . $info['longname'] . "',
			author='"   . $info['author']   . "',
			webpage='"  . $info['homepage'] . "',
			version='"  . $info['version']  . "'
			WHERE id='$id'");
	}	
	
	
 }
 

 

 function camm_setup_table () {
     global $config, $database_default;
 	include_once($config["library_path"] . "/database.php");
	include_once($config['base_path'] . '/plugins/camm/lib/camm_functions.php');
 


 	// Set the new version
 	$new = plugin_camm_version();
 	$new = $new['version'];
 	$old = db_fetch_cell("SELECT `value` FROM `settings` where name = 'camm_version'");
 	db_execute("REPLACE INTO settings (name, value) VALUES ('camm_version', '$new')");
 	if (trim($old) == '') {
 		$old = "0.0.1";
 	}
 	$sql = "show tables from `" . $database_default . "`";
 	$result = db_fetch_assoc($sql) or die (mysql_error());
 
 	$tables = array();
 	$sql = array();
 
 	if (count($result) > 1) {
 		foreach($result as $index => $arr) {
 			foreach ($arr as $t) {
 				$tables[] = $t;
 			}
 		}
 	}
 	$result = db_fetch_assoc("SELECT `name` FROM `settings` where name like 'camm%%' order by name");
 	foreach($result as $row) {
 		$result_new[] =$row['name'];
 	}

 	//delete block
 	if (in_array("stats_camm_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [stats_camm_tree]","DELETE FROM `settings` WHERE `name` = 'stats_camm_tree';");	
 	if (in_array("camm_sys_collection_timing", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [camm_sys_collection_timing]","DELETE FROM `settings` WHERE `name` = 'camm_sys_collection_timing';");			
 	if (in_array("camm_stats_ruledel", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [camm_stats_ruledel]","DELETE FROM `settings` WHERE `name` = 'camm_stats_ruledel';");			
 	if (in_array("camm_collection_timing", $result_new)) {
 		if (in_array("camm_autopurge_timing", $result_new)) {
 			$sql[] = array("camm_execute_sql","Delete unused parameter in  [settings] [camm_collection_timing]","DELETE FROM `settings` WHERE `name` = 'camm_collection_timing';");			
 		}else{
 			$sql[] = array("camm_execute_sql","Change parameter in  [settings] unused parameter [camm_collection_timing] to [camm_autopurge_timing]","UPDATE settings SET `name` = 'camm_autopurge_timing' WHERE `name` = 'camm_collection_timing';");					
 		}
 	}

 	//change block
 	if (in_array("snmptt_delay_purge_day", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_delay_purge_day] to [camm_snmptt_delay_purge_day]","UPDATE `settings`  SET `name`='camm_snmptt_delay_purge_day' WHERE `name` = 'snmptt_delay_purge_day';");			
 	if (in_array("snmptt_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_max_row_all] to [camm_snmptt_min_row_all]","UPDATE `settings`  SET `name`='camm_snmptt_min_row_all' WHERE `name` = 'snmptt_max_row_all';");
 	if (in_array("snmptt_max_row_per_device", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_max_row_per_device] to [camm_snmptt_max_row_per_device]","UPDATE `settings`  SET `name`='camm_snmptt_max_row_per_device' WHERE `name` = 'snmptt_max_row_per_device';");			
 	if (in_array("snmptt_tables", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_tables] to [camm_snmptt_tables]","UPDATE `settings`  SET `name`='camm_snmptt_tables' WHERE `name` = 'snmptt_tables';");			
 	if (in_array("snmptt_trap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_trap_tab_update] to [camm_snmptt_trap_tab_update]","UPDATE `settings`  SET `name`='camm_snmptt_trap_tab_update' WHERE `name` = 'snmptt_trap_tab_update';");			
 	if (in_array("snmptt_unktrap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_unktrap_tab_update] to [camm_snmptt_unktrap_tab_update]","UPDATE `settings`  SET `name`='camm_snmptt_unktrap_tab_update' WHERE `name` = 'snmptt_unktrap_tab_update';");
  	if (in_array("camm_sys_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [camm_sys_max_row_all] to [camm_sys_min_row_all]","UPDATE `settings`  SET `name`='camm_sys_min_row_all' WHERE `name` = 'camm_sys_max_row_all';");
  	if (in_array("camm_snmptt_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [camm_snmptt_max_row_all] to [camm_snmptt_min_row_all]","UPDATE `settings`  SET `name`='camm_snmptt_min_row_all' WHERE `name` = 'camm_snmptt_max_row_all';");

  		
 
 	//add block				
 	if (!in_array("camm_num_rows", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_num_rows]","INSERT INTO settings VALUES ('camm_num_rows','50');");	
 	if (!in_array("camm_last_run_time", $result_new))		
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_last_run_time]","INSERT INTO settings VALUES ('camm_last_run_time',0);");
 	if (!in_array("camm_autopurge_timing", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_autopurge_timing]","INSERT INTO settings VALUES ('camm_autopurge_timing','120');");
 	if (!in_array("camm_snmptt_delay_purge_day", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_delay_purge_day]","INSERT INTO settings VALUES ('camm_snmptt_delay_purge_day','7');");
 	if (!in_array("camm_snmptt_min_row_all", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_min_row_all]","INSERT INTO settings VALUES ('camm_snmptt_min_row_all','0');");
 	if (!in_array("camm_snmptt_max_row_per_device", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_max_row_per_device]","INSERT INTO settings VALUES ('camm_snmptt_max_row_per_device','0');");
 	if (!in_array("camm_snmptt_tables", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_tables]","INSERT INTO settings VALUES ('camm_snmptt_tables','3');");	
 	if (!in_array("camm_startup_tab", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_startup_tab]","INSERT INTO settings VALUES ('camm_startup_tab','0');");	
 	if (!in_array("camm_tree_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tree_update]","INSERT INTO settings VALUES ('camm_tree_update','300');");	
 	if (!in_array("camm_stats_time", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_time]","INSERT INTO settings VALUES ('camm_stats_time','Time:0');");	
 	if (!in_array("camm_stats_ruledel_snmptt", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_ruledel_snmptt]","INSERT INTO settings VALUES ('camm_stats_ruledel_snmptt','0');");	
 	if (!in_array("camm_stats_ruledel_syslog", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_ruledel_syslog]","INSERT INTO settings VALUES ('camm_stats_ruledel_syslog','0');");	
 	if (!in_array("camm_test_row_count", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_test_row_count]","INSERT INTO settings VALUES ('camm_test_row_count','1000');");	
 	if (!in_array("camm_use_syslog", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_use_syslog]","INSERT INTO settings VALUES ('camm_use_syslog','0');");	
 	if (!in_array("camm_sys_delay_purge_day", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_delay_purge_day]","INSERT INTO settings VALUES ('camm_sys_delay_purge_day','7');");	
 	if (!in_array("camm_sys_min_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_min_row_all]","INSERT INTO settings VALUES ('camm_sys_min_row_all','50000');");	
 	if (!in_array("camm_sys_max_row_per_device", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_max_row_per_device]","INSERT INTO settings VALUES ('camm_sys_max_row_per_device','1200');");	
 	if (!in_array("camm_snmptt_unktrap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_unktrap_tab_update]","INSERT INTO settings VALUES ('camm_snmptt_unktrap_tab_update','0');");	
 	if (!in_array("camm_snmptt_trap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_trap_tab_update]","INSERT INTO settings VALUES ('camm_snmptt_trap_tab_update','0');");	
 	if (!in_array("camm_sys_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_tab_update]","INSERT INTO settings VALUES ('camm_sys_tab_update','0');");	
 	if (!in_array("camm_stats_snmptt_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_snmptt_tree]","INSERT INTO settings VALUES ('camm_stats_snmptt_tree','TreecammTime:0');");	
 	if (!in_array("camm_stats_syslog_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_syslog_tree]","INSERT INTO settings VALUES ('camm_stats_syslog_tree','TreesyslogTime:0');");	
 	if (!in_array("camm_syslog_db_name", $result_new)) {
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_syslog_db_name]","INSERT INTO settings VALUES ('camm_syslog_db_name','" . $database_default . "');");	
 		$camm_syslog_db_name = $database_default;
 	}else{
 		$camm_syslog_db_name = read_config_option("camm_syslog_db_name");
 	}
 	if (!in_array("camm_show_all_records", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_show_all_records]","INSERT INTO settings VALUES ('camm_show_all_records','1');");				
 	if (!in_array("camm_join_field", $result_new)) {
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_join_field]","INSERT INTO settings VALUES ('camm_join_field','sourceip');");				
 	}
 	if (!in_array("camm_tab_image_size", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tab_image_size]","INSERT INTO settings VALUES ('camm_tab_image_size','0');");				
 	if (!in_array("camm_debug_mode", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_debug_mode]","INSERT INTO settings VALUES ('camm_debug_mode','0');");				
 	if (!in_array("camm_syslog_pretable_name", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_syslog_pretable_name]","INSERT INTO settings VALUES ('camm_syslog_pretable_name','plugin_camm_syslog');");				
 	if (!in_array("camm_period_hour", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_period_hour]","INSERT INTO settings VALUES ('camm_period_hour','1');");				
 	if (!in_array("camm_period_day", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_period_day]","INSERT INTO settings VALUES ('camm_period_day','1');");	
 	if (!in_array("camm_period_week", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_period_week]","INSERT INTO settings VALUES ('camm_period_week','0');");	 
 	if (!in_array("camm_tree_menu_width", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tree_menu_width]","INSERT INTO settings VALUES ('camm_tree_menu_width','20');");	 
 	if (!in_array("camm_process_markers", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_process_markers]","INSERT INTO settings VALUES ('camm_process_markers','0');");	     
 	if (!in_array("camm_action_order", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_action_order]","INSERT INTO settings VALUES ('camm_action_order','1');");	      
 	if (!in_array("camm_rule_order", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_rule_order]","INSERT INTO settings VALUES ('camm_rule_order','1');");	       		
 	if (!in_array("camm_email_title", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_email_title]","INSERT INTO settings VALUES ('camm_email_title','An alert has been issued that requires your attention.');");	       		
 	if (!in_array("camm_use_fqdn", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_use_fqdn]","INSERT INTO settings VALUES ('camm_use_fqdn','0');");	       		  
 	if (!in_array("camm_use_group_by_host", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_use_group_by_host]","INSERT INTO settings VALUES ('camm_use_group_by_host','1');");	       		  
 	if (!in_array("camm_dependencies", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_dependencies]","INSERT INTO settings VALUES ('camm_dependencies','false');");	       		    
 	

	
 	if (!in_array('plugin_camm_keys', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_keys","CREATE TABLE `plugin_camm_keys` (
			  `krid` int(11) unsigned NOT NULL,
			  `rule_id` int(11) unsigned NOT NULL,
			  `ktype` tinyint(1) unsigned NOT NULL,
			  PRIMARY KEY (`rule_id`,`krid`,`ktype`),
			  KEY `krid` (`krid`),
			  KEY `rule_id` (`rule_id`,`ktype`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='camm plugin';");	
 	}
	

 	if (!in_array('plugin_camm_rule', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_rule","CREATE TABLE `plugin_camm_rule` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `order` tinyint(4) unsigned NOT NULL DEFAULT '0',
		  `rule_type` varchar(10) NOT NULL DEFAULT 'camm',
		  `rule_enable` tinyint(1) NOT NULL DEFAULT '1',
		  `is_function` tinyint(1) NOT NULL DEFAULT '0',
		  `is_email` tinyint(1) NOT NULL DEFAULT '0',
		  `is_mark` tinyint(1) NOT NULL DEFAULT '0',
		  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
		  `function_name` varchar(255) DEFAULT NULL,
		  `email` varchar(255) DEFAULT NULL,
		  `email_mode` tinyint(1) unsigned NOT NULL DEFAULT '1',
		  `email_message` text,
		  `marker__` tinyint(2) NOT NULL DEFAULT '0',
		  `notes` varchar(255) DEFAULT NULL,
		  `json_filter` text,
		  `sql_filter` text,
		  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
		  `date` datetime DEFAULT NULL,
		  `count_triggered` int(11) unsigned NOT NULL DEFAULT '0',
		  `inc_cacti_name` tinyint(1) unsigned NOT NULL DEFAULT '1',
		  `sup_mode` tinyint(1) unsigned NOT NULL DEFAULT '1',
		  `email_format` tinyint(1) unsigned NOT NULL DEFAULT '1',
		  `actual_triggered` int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `id` (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COMMENT='Traps Alert';");	
 	}

 	if (!in_array('plugin_camm_snmptt', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_snmptt","CREATE TABLE `plugin_camm_snmptt` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `eventname` varchar(50) DEFAULT NULL,
		  `eventid` varchar(50) DEFAULT NULL,
		  `trapoid` varchar(100) DEFAULT NULL,
		  `enterprise` varchar(100) DEFAULT NULL,
		  `community` varchar(20) DEFAULT NULL,
		  `hostname` varchar(250) DEFAULT NULL,
		  `agentip` varchar(16) DEFAULT NULL,
		  `category` varchar(20) DEFAULT NULL,
		  `severity` varchar(20) DEFAULT NULL,
		  `uptime` varchar(20) DEFAULT NULL,
		  `traptime` datetime DEFAULT NULL,
		  `formatline` text,
		  `add` varchar(50) DEFAULT NULL,
		  `status` tinyint(1) NOT NULL DEFAULT '0',
		  `alert` int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `hostname` (hostname(191)),
		  KEY `traptime` (`traptime`),
		  KEY `eventname` (`eventname`),
		  KEY `severity` (`severity`),
		  KEY `category` (`category`),
		  KEY `status_date` (`status`,`traptime`),
		  KEY `status` (`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='camm data';");	
 	}

 	if (!in_array('plugin_camm_snmptt_stat', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_snmptt_stat","CREATE TABLE `plugin_camm_snmptt_stat` (
		  `stat_time` datetime DEFAULT NULL,
		  `total_received` bigint(20) DEFAULT NULL,
		  `total_translated` bigint(20) DEFAULT NULL,
		  `total_ignored` bigint(20) DEFAULT NULL,
		  `total_unknown` bigint(20) DEFAULT NULL,
		  KEY `stat_time` (`stat_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='camm Statistics';");	
 	}

 	if (!in_array('plugin_camm_snmptt_unk', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_snmptt_unk","CREATE TABLE `plugin_camm_snmptt_unk` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `trapoid` varchar(100) DEFAULT NULL,
		  `enterprise` varchar(100) DEFAULT NULL,
		  `community` varchar(20) DEFAULT NULL,
		  `hostname` varchar(250) DEFAULT NULL,
		  `agentip` varchar(16) DEFAULT NULL,
		  `uptime` varchar(20) DEFAULT NULL,
		  `traptime` datetime DEFAULT NULL,
		  `formatline` text,
		  PRIMARY KEY (`id`),
		  KEY `id` (`id`),
		  KEY `traptime` (`traptime`),
		  KEY `trapoid` (`trapoid`),
		  KEY `community` (`community`),
		  KEY `hostname` (hostname(191)) USING BTREE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='camm Unkn Traps';");	
 	}

 	if (!in_array('plugin_camm_syslog', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_syslog","CREATE TABLE `plugin_camm_syslog` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `host` varchar(128) DEFAULT NULL,
		  `sourceip` varchar(45) NOT NULL,
		  `facility` varchar(10) DEFAULT NULL,
		  `priority` varchar(10) DEFAULT NULL,
		  `sys_date` datetime DEFAULT NULL,
		  `message` text,
		  `status` tinyint(4) NOT NULL DEFAULT '0',
		  `alert` smallint(3) unsigned NOT NULL DEFAULT '0',
		  `add` varchar(50) DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  KEY `facility` (`facility`),
		  KEY `priority` (`priority`),
		  KEY `sourceip` (`sourceip`),
		  KEY `status` (`status`),
		  KEY `alert` (`alert`),
		  KEY `status_date` (`status`,`sys_date`),
		  KEY `sys_date` (`sys_date`),
		  KEY `hostname` (`host`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='camm plugin SYSLOG Data';");	
 	}

 	if (!in_array('plugin_camm_syslog_incoming', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_syslog_incoming","CREATE TABLE `plugin_camm_syslog_incoming` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `host` varchar(128) DEFAULT NULL,
		  `sourceip` varchar(45) NOT NULL,
		  `facility` varchar(10) DEFAULT NULL,
		  `priority` varchar(10) DEFAULT NULL,
		  `sys_date` datetime DEFAULT NULL,
		  `message` varchar(255) DEFAULT NULL,
		  `status` tinyint(4) NOT NULL DEFAULT '0',
		  `alert` smallint(3) unsigned NOT NULL DEFAULT '0',
		  `add` varchar(50) DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  KEY `facility` (`facility`),
		  KEY `priority` (`priority`),
		  KEY `sourceip` (`sourceip`),
		  KEY `sys_date` (`sys_date`),
		  KEY `status` (`status`),
		  KEY `alert` (`alert`)
		) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COMMENT='camm plugin SYSLOG incoming Data';");	
 	}

 	if (!in_array('plugin_camm_temp', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_temp","CREATE TABLE `plugin_camm_temp` (
		  `device_type_name` varchar(100) DEFAULT NULL,
		  `device_type_id` mediumint(8) NOT NULL DEFAULT '0',
		  `device_id` int(10) unsigned NOT NULL DEFAULT '0',
		  `description` varchar(150) DEFAULT NULL,
		  `hostname` varchar(100) NOT NULL DEFAULT '',
		  `agentip` int(10) unsigned DEFAULT NULL,
		  `agentip_source` varchar(16) NOT NULL DEFAULT '0',
		  `gr_f` varchar(50) NOT NULL,
		  `gr_v` varchar(50) NOT NULL,
		  `type` varchar(10) NOT NULL DEFAULT '',
		  `period` varchar(4) NOT NULL DEFAULT '',
		  `count` int(10) unsigned NOT NULL DEFAULT '0',
		  `dev_count` int(10) unsigned NOT NULL DEFAULT '0',
		  `typ_count` int(10) unsigned NOT NULL DEFAULT '0',
		  `online` tinyint(1) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`),
		  KEY `type` (`type`),
		  KEY `period` (`period`),
		  KEY `device_type_id` (`device_type_id`),
		  KEY `device_id` (`device_id`)
		) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COMMENT='camm temp data for menu';");	
 	}

 	if (!in_array('plugin_camm_tree2', $tables)) {
 		$sql[] = array("camm_create_table","plugin_camm_tree2","CREATE TABLE `plugin_camm_tree2` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `device_type_name` varchar(100) DEFAULT NULL,
		  `device_type_id` mediumint(8) NOT NULL DEFAULT '0',
		  `device_id` int(10) unsigned NOT NULL DEFAULT '0',
		  `description` varchar(150) DEFAULT NULL,
		  `hostname` varchar(100) NOT NULL DEFAULT '',
		  `agentip` int(10) unsigned DEFAULT NULL,
		  `agentip_source` varchar(16) NOT NULL DEFAULT '0',
		  `gr_f` varchar(50) NOT NULL,
		  `gr_v` varchar(50) NOT NULL,
		  `type` varchar(10) NOT NULL DEFAULT '',
		  `period` varchar(4) NOT NULL DEFAULT '',
		  `count` int(10) unsigned NOT NULL DEFAULT '0',
		  `dev_count` int(10) unsigned NOT NULL DEFAULT '0',
		  `typ_count` int(10) unsigned NOT NULL DEFAULT '0',
		  `online` tinyint(1) NOT NULL DEFAULT '0',
		  `_is_device` tinyint(1) NOT NULL DEFAULT '0',
		  `_is_type` tinyint(1) NOT NULL DEFAULT '0',
		  `_is_marker` tinyint(1) NOT NULL DEFAULT '0',
		  `_parent` int(11) unsigned NOT NULL,
		  `_is_leaf` tinyint(1) NOT NULL DEFAULT '0',
		  `_lvl` tinyint(1) NOT NULL,
		  `_path` varchar(45) NOT NULL DEFAULT '',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `unique` (`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`_is_device`,`_is_type`,`_is_marker`,`_lvl`) USING BTREE,
		  KEY `type` (`type`),
		  KEY `period` (`period`),
		  KEY `hostname` (`hostname`),
		  KEY `gr_f` (`gr_f`),
		  KEY `gr_v` (`gr_v`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='camm menu tree';");	
 	}
	
	
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `host`;");
 	foreach($result as $row) {
 		if ($row['Column_name'] == 'hostname')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","host","hostname", "ALTER TABLE `host` ADD INDEX `hostname`(`hostname`);");
 	}
	
	
 	/*
	switch($old) {
	case '0.1':
		$sql[] = array("camm_add_column","plugin_camm_onu","onu_done_view_count","alter table `plugin_camm_onu` add column `onu_done_view_count` TINYINT(1) NOT NULL DEFAULT 0 AFTER `onu_dereg_status`;");
		$old = '0.2'; 
	case '0.2':
		$sql[] = array("camm_add_column","plugin_camm_onu","onu_done_reason","alter table `plugin_camm_onu` add column `onu_done_reason` char(3) DEFAULT '' AFTER `onu_dereg_status`;");
		$old = '0.3'; 	
		
	}	
	*/
	
 
 	if (!empty($sql)) {
 		for ($a = 0; $a < count($sql); $a++) {
 			$step_sql = $sql[$a];
 			$rezult = '';
 			switch ($step_sql[0]) {
 				case 'camm_execute_sql':
 					$rezult = camm_execute_sql ($step_sql[1], $step_sql[2]);
 					break;
 				case 'camm_create_table':
 					$rezult = camm_create_table ($step_sql[1], $step_sql[2]);
 					break;
 				case 'camm_add_column':
 					$rezult = camm_add_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;				
 				case 'camm_modify_column':
 					$rezult = camm_modify_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_delete_column':
 					$rezult = camm_delete_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_add_index':
 					$rezult = camm_add_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_delete_index':
 					$rezult = camm_delete_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 			}
 			camm_raise_message3(array("device_descr" => "Обновление до версии [" . $new . "]" , "type" => "update_db", "object"=> "update","cellpading" => false, "message" => $rezult["message"], "step_rezult" => $rezult["step_rezult"], "step_data" => $rezult["step_data"]));     
 			//$result = db_execute($sql[$a]);
 		}
 	}
 
  db_execute('REPLACE INTO settings (name, value) VALUES ("camm_version", "' .  $new . '")');	
	
 
 }
 
 	

 function camm_execute_sql($message, $syntax) {
 	$result = db_execute($syntax);
 	$return_rezult = array();
 	
 	if ($result) {
 		$return_rezult['message'] =  "SUCCESS: Execute SQL,   $message";
 		$return_rezult['step_rezult'] = 'OK';
 	}else{
 		$return_rezult['message'] =  "ERROR: Execute SQL,   $message";
 		$return_rezult['step_rezult'] = 'Error';
 	}
 	$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 	return $return_rezult;
 }
 
 function camm_create_table($table, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (!sizeof($tables)) {
 		$result = db_execute($syntax);
 		if ($result) {
 			$return_rezult['message'] =  "SUCCESS: Create Table,  Table -> $table";
 			$return_rezult['step_rezult'] = 'OK';
 		}else{
 			$return_rezult['message'] =  "ERROR: Create Table,  Table -> $table";
 			$return_rezult['step_rezult'] = 'Error';
 		}
 		$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 	}else{
 		$return_rezult['message'] =  "SUCCESS: Create Table,  Table -> $table";
 		$return_rezult['step_rezult'] = 'OK';
 		$return_rezult['step_data'] = "Already Exists";
 	}
 	return $return_rezult;
 }
 
 function camm_add_column($table, $column, $syntax) {
 	$return_rezult = array();
 	$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 	if (sizeof($columns)) {
 		$return_rezult['message'] = "SUCCESS: Add Column,    Table -> $table, Column -> $column";
 		$return_rezult['step_rezult'] = 'OK';
 		$return_rezult['step_data'] = "Already Exists";
 	}else{
 		$result = db_execute($syntax);
 
 		if ($result) {
 			$return_rezult['message'] ="SUCCESS: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'OK';
 		}else{
 			$return_rezult['message'] ="ERROR: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'Error';
 		}
 		$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 	}
 	return $return_rezult;
 }
 
 function camm_add_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if ($index_exists) {
 			$return_rezult['message'] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 			$return_rezult['step_rezult'] = 'OK';
 			$return_rezult['step_data'] = "Already Exists";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}
 	}else{
 		$return_rezult['message'] ="ERROR: Add Index,     Table -> $table, Index -> $index";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 
 function camm_modify_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}else{
 			$return_rezult['message'] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'Error';
 			$return_rezult['step_data'] = "Column Does NOT Exist";
 		}
 	}else{
 		$return_rezult['message'] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 
 function camm_delete_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}else{
 			$return_rezult['message'] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'Error';
 			$return_rezult['step_data'] = "Column Does NOT Exist";			
 		}
 	}else{
 		$return_rezult['message'] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 
 function camm_delete_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if (!$index_exists) {
 			$return_rezult['message'] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 			$return_rezult['step_rezult'] = 'OK';
 			$return_rezult['step_data'] = "Index Does NOT Exist!";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}
 	}else{
 		$return_rezult['message'] ="ERROR: Delete Index,     Table -> $table, Index -> $index";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 	
 	
 	?>
