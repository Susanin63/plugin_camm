<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2007 Susanin                                          |
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
 
$guest_account = true;
 
 chdir('../../');
 include("./include/auth.php");
 
 include_once($config["base_path"] . "/plugins/camm/lib/camm_functions.php");
 //ini_set('memory_limit', '256M');

 $title = __('CAMM');
 

camm_top_graph_header();
	
bottom_footer();

 
 ?> 
 

