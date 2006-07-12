<?php
# checking XOOPS installed files
# $Id: xoops_version.php,v 1.2 2006/07/12 18:33:55 nobu Exp $

$modversion =
      array('name' => _MI_UPDATE_NAME,
	    'version' => 0.3,
	    'description' => _MI_UPDATE_DESC,
	    'author' => "Nobuhiro YASUTOMI <nobuhiro.yasutomi@nifty.ne.jp>",
	    'credits' => "(C)2006 ScriptUpdate LLC.",
	    'help' => 'help.html',
	    'license' => "GPL",
	    'official' => 0,
	    'image' => "updateclient.png",
	    'dirname' => basename(dirname(__FILE__)));

$modversion['sqlfile']['mysql'] = "sql/mysql.sql";

$modversion['tables'][] = "update_package";
$modversion['tables'][] = "update_file";
$modversion['tables'][] = "update_diff";

$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";

// Config
$modversion['hasconfig'] = 1;

$modversion['config'][]=array(
    'name' => 'update_server',
    'title' => '_MI_UPDATE_SERVER',
    'description' => '_MI_UPDATE_SERVER_DESC',
    'formtype' => 'text',
    'valuetype' => 'string',
    'default' => _MI_UPDATE_SERVER_DEF);

$modversion['config'][]=array(
    'name' => 'cache_time',
    'title' => '_MI_UPDATE_CACHETIME',
    'description' => '_MI_UPDATE_CACHETIME_DESC',
    'formtype' => 'text',
    'valuetype' => 'int',
    'default' => 3600);
?>