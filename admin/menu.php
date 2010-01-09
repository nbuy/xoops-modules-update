<?php
# ScriptUpdate - define admin menus
# $Id: menu.php,v 1.6 2010/01/09 07:44:28 nobu Exp $

$adminmenu[]=array('title' => _MI_UPDATE_ABOUT,
		   'link' => "admin/help.php");
$adminmenu[]=array('title' => _MI_UPDATE_ADCHECK,
		   'link' => "admin/index.php");
$adminmenu[]=array('title' => _MI_UPDATE_REGISTER,
		   'link' => "admin/pkgadmin.php");
$adminmenu[]=array('title' => _MI_UPDATE_ADPKG,
		   'link' => "admin/index.php?op=pkgs");
$adminmenu[]=array('title' => _MI_UPDATE_AUTH,
		   'link' => "admin/auth.php");

$adminmenu4altsys[]=
    array('title' => _MD_A_MYMENU_MYTPLSADMIN,
	  'link' => 'admin/index.php?mode=admin&lib=altsys&page=mytplsadmin');
$adminmenu4altsys[]=
    array('title' => _MD_A_MYMENU_MYBLOCKSADMIN,
	  'link' => 'admin/index.php?mode=admin&lib=altsys&page=myblocksadmin');
$adminmenu4altsys[]=
    array('title' => _MD_A_MYMENU_MYPREFERENCES,
	  'link' => 'admin/index.php?mode=admin&lib=altsys&page=mypreferences');
?>