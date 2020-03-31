<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2019 The Cacti Group                                 |
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
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

global $config, $menu, $user_menu;

$page_title = api_plugin_hook_function('page_title', draw_navigation_text('title'));
$using_guest_account = false;

if (!isset_request_var('headercontent')) { ?>
<!DOCTYPE html>
<html>
<head>
	<?php camm_html_common_header($page_title);?>
</head>
<body>

	<div id="cacti_north" >
		<div id='cactiPageHead' class='cactiPageHead' role='banner'>
			<div id='tabs'><?php html_show_tabs_left();?></div>
		<div class='cactiGraphHeaderBackground' style='display:none'><div id='gtabs'><?php print html_graph_tabs_right();?></div></div>
		<div class='cactiConsolePageHeadBackdrop'></div>
		</div>
		<div id='breadCrumbBar' class='breadCrumbBar'>
			<div id='navBar' class='navBar'><?php echo draw_navigation_text();?></div>
			<div class='scrollBar'></div>
		<?php if (read_config_option('auth_method') != 0) { ?><div class='infoBar'><?php echo draw_login_status($using_guest_account);?></div><?php } ?>
		</div>
	</div>	
	<div class='cactiShadow'></div>
	<?php } else { ?>
	<div id='navBar' class='navBar'><?php echo draw_navigation_text();?></div>
	<title><?php print $page_title;?></title>
	<?php } ?>
	<div id='cactiContent' class='cactiContent'>
		<?php if (isset($user_menu) && is_array($user_menu)) {?>
		<div style='display:none;' id='navigation' class='cactiConsoleNavigationArea'>
			<table style='width:100%;'>
				<?php draw_menu($user_menu);?>
				<tr>
					<td style='text-align:center;'>
						<div class='cactiLogo' onclick='loadPage("<?php print $config['url_path'];?>about.php")'></div>
					</td>
				</tr>
			</table>
		</div>
		<?php } ?>
		<div id='navigation_right' class='cactiGraphContentArea'>
			<div style='position:relative;display:none;' id='main'>

 	<div id="cacti_south" >
 		Reserved for future use
 	</div>
 	
	<div id="loading-mask" style="width:100%;height:100%;background:#fff;position:absolute;z-index:100;left:0;top:0;">
		<div class="loading-item" style="width:100%;height:50%;background:#fff;position:relative;z-index:101;left:0;top:43.2%;">
			<div style="left:40%;width:20%;border: 1px solid #99bbe8;position:relative;">
				<br />
				<center>
					<img src="<?php echo $config['url_path']; ?>plugins/camm/images/large-loading.gif" style="margin-right:12px;" align="absmiddle"/><span id="loading-text" class="loading-item">Loading Cacti camm plugin...</span>
				</center>
				<br />
			</div>
		</div>
	</div>
 		    			