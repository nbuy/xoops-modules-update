<?php
# ScriptUpdate - define admin menus
# $Id: menu.php,v 1.4 2006/12/05 03:15:51 nobu Exp $

$adminmenu[]=array('title' => _MI_UPDATE_ADCHECK,
		   'link' => "admin/index.php");
$adminmenu[]=array('title' => _MI_UPDATE_REGISTER,
		   'link' => "admin/pkgadmin.php");
$adminmenu[]=array('title' => _MI_UPDATE_ADPKG,
		   'link' => "admin/index.php?op=pkgs");
$adminmenu[]=array('title' => _MI_UPDATE_AUTH,
		   'link' => "admin/auth.php");
$adminmenu[]=array('title' => _MI_UPDATE_ABOUT,
		   'link' => "admin/help.php");

?>