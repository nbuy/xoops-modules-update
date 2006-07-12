<?php
# ScriptUpdate - define admin menus
# $Id: menu.php,v 1.2 2006/07/12 18:33:56 nobu Exp $

$adminmenu[]=array('title' => _MI_UPDATE_ADCHECK,
		   'link' => "admin/index.php");
$adminmenu[]=array('title' => _MI_UPDATE_ADPKG,
		   'link' => "admin/index.php?op=pkgs");
$adminmenu[]=array('title' => _MI_UPDATE_ABOUT,
		   'link' => "admin/help.php");

?>