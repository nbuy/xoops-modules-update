<?php
# XoopsUpdate - notification block
# $Id: update_notice.php,v 1.4 2007/07/16 05:18:30 nobu Exp $
function b_update_notice($options) {
    global $xoopsDB, $xoopsUser;
    $pkg = $xoopsDB->prefix('update_package');
    $res = $xoopsDB->query("SELECT a.pname, b.name, b.pversion
FROM $pkg a, $pkg b WHERE a.pversion='HEAD' AND a.parent=b.pkgid");
    $pkgs = array();
    while ($data = $xoopsDB->fetchArray($res)) {
	$pkgs[$data['pname']] = array('name'=>$data['name'], 'pversion'=>$data['pversion']);
    }

    $modpath =dirname(dirname(__FILE__));
    $dirname =basename($modpath);
    include_once $modpath.'/functions.php';
    $module_handler =& xoops_gethandler('module');
    $module =& $module_handler->getByDirname($dirname);
    // only for admin this module
    if (!is_object($xoopsUser) ||
	!$xoopsUser->isAdmin($module->getVar('mid'))) {
	return array('admin'=>false);
    }
    $config_handler =& xoops_gethandler('config');
    $config =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));
    $svr = $config['update_server'];
    if (!preg_match('/^\w+:/', $svr)) return null;
    $url = $svr."/modules/server/list.php?pkg=all&ext=1";
    $block = array('admin'=>true, 'dirname'=>$dirname);
    $updates = array();
    if (empty($pkgs)) {
	$msg = _BL_UPDATE_NOPKGS;
    } else {
	$list = file_get_url($url, 'list');
	if (empty($list)) return null;
	foreach (split("\n", $list) as $ln) {
	    if (empty($ln)) continue;
	    list($pname, $ver, $date, $vcheck, $name) = split_csv($ln);
	    if (isset($pkgs[$pname])) {
		if ($ver != $pkgs[$pname]['pversion']) {
		    $time = strtotime_tz($date);
		    $date = formatTimestamp($time, 'm/d h:i');
		    $updates[] = array('pname'=>$pname, 'pversion'=>$ver,
				       'time'=>$time, 'date'=>$date,
				       'vcheck'=>$vcheck, 'name'=>$name);
		}
	    }
	}
	$msg = empty($updates)?"":_BL_UPDATE_EXIST;
    }
    $block['message'] = $msg;
    $block['updates'] = $updates;
    return $block;
}
?>