<?php
# XoopsUpdate - Client Module
# $Id: xoops_version.php,v 1.3 2006/07/19 12:47:48 nobu Exp $

$modversion =
      array('name' => _MI_UPDATE_NAME,
	    'version' => 0.6,
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

// Blocks
$modversion['blocks'][1]=
    array('file' => "update_notice.php",
	  'name' => _MI_UPDATE_NOTICE,
	  'description' => _MI_UPDATE_NOTICE_DESC,
	  'show_func' => 'b_update_notice',
	  //'template' => 'eguide_block_top.html'
	);

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

$modversion['config'][]=array(
    'name' => 'update_method',
    'title' => '_MI_UPDATE_METHOD',
    'description' => '_MI_UPDATE_METHOD_DESC',
    'formtype' => 'select',
    'valuetype' => 'text',
    'default' => 'skip',
    'options' => array(_MI_UPDATE_METHOD_SKIP=>'skip',
		       _MI_UPDATE_METHOD_REPLACE=>'replace',
		       _MI_UPDATE_METHOD_PATCH=>'patch'));
?>