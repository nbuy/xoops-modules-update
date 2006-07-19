<?php
# XoopsUpdate - notification block
# $Id: update_notice.php,v 1.1 2006/07/19 12:47:49 nobu Exp $
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
	!$xoopsUser->isAdmin($module->getVar('mid'))) return null;
    $config_handler =& xoops_gethandler('config');
    $config =& $config_handler->getConfigsByCat(0, $module->getVar('mid'));
    $svr = $config['update_server'];
    if (!preg_match('/^\w+:/', $svr)) return null;
    $url = $svr."/modules/server/list.php?pkg=all";
    $list = file_get_url($url);
    if (empty($list)) return null;
    $buf = "";
    foreach (split("\n", $list) as $ln) {
	if (empty($ln)) continue;
	list($pname, $ver, $date, $vcheck, $name) = explode(',', $ln);
	if (isset($pkgs[$pname])) {
	    if ($ver != $pkgs[$pname]['pversion']) {
		$buf .= "<div>$name</div>\n";
	    }
	}
    }
    if ($buf) {
	$buf = "<b style='color:red'>"._BL_UPDATE_EXIST."</b><hr/>$buf
<p style='text-align: right'><a href='".XOOPS_URL."/modules/$dirname/admin/'>".
	    _BL_UPDATE_DOING."</a></p>";
    } else {
	$buf = _BL_UPDATE_NONE;
    }
    return array("content"=>$buf, 'title'=>'XoopsUpdate');
}
?>