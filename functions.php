<?php
# ScriptUpdate - common use functions
# $Id: functions.php,v 1.13 2008/01/06 12:02:44 nobu Exp $

define('UPDATE_PKG', $xoopsDB->prefix('update_package'));
define('UPDATE_FILE', $xoopsDB->prefix('update_file'));
define('UPDATE_DIFF', $xoopsDB->prefix('update_diff'));
define('UPDATE_CACHE', $xoopsDB->prefix('update_cache'));
define('ROLLBACK', XOOPS_UPLOAD_PATH."/update/work/backup-rollback.tar.gz");
define('FILE_CACHE_TIME', 3600*24*2); // file caching seconds (2days)

function get_update_otp() {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT hash,fileid FROM ".UPDATE_FILE." WHERE pkgref=0 AND path='' AND hash<>''");
    if (!$res || $xoopsDB->getRowsNum($res)==0) return null;
    list($hash) = $xoopsDB->fetchRow($res);
    return $hash;
}

function clear_get_cache($expire=0, $prefix="", $url="") {
    global $xoopsDB;
    $tm = time()-$expire;
    if ($url) {
	$cacheid = $xoopsDB->quoteString(md5($url));
	$xoopsDB->queryF("DELETE FROM ".UPDATE_CACHE." WHERE cacheid=$cacheid AND mtime<$tm");
    } else {
	$wc = $prefix?'AND cacheid LIKE '.$xoopsDB->quoteString($prefix.':%'):'';
	$xoopsDB->queryF("DELETE FROM ".UPDATE_CACHE." WHERE mtime<$tm $wc");
    }
}

function get_myconfig_value($name) {
    global $xoopsModule, $xoopsModuleConfig;
    $mydir = basename(dirname(__FILE__));
    if (is_object($xoopsModule) && $xoopsModule->getVar('dirname')==$mydir) {
	return $xoopsModuleConfig[$name];
    } else {
	$module_handler =& xoops_gethandler('module');
	$module = $module_handler->getByDirname($mydir);
	$config_handler =& xoops_gethandler('config');
	$config = $config_handler->getConfigsByCat(0, $module->getVar('mid'));
	return $config[$name];
    }
}

function file_get_url($url, $prefix="gen", $post=false, $cache=-1, $hash=false, $touch=false) {
    global $xoopsDB;
    if ($cache<0) $cache = get_myconfig_value('cache_time');
    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
    $snoopy = new Snoopy;
    $snoopy->lastredirectaddr = 1;
    $cacheid = $xoopsDB->quoteString($prefix.':'.md5($url));
    $res = $xoopsDB->query("SELECT mtime, content FROM ".UPDATE_CACHE." WHERE cacheid=".$cacheid);
    $now = time();
    if ($res && $xoopsDB->getRowsNum($res)) {
	list($mtime, $content) = $xoopsDB->fetchRow($res);
	if (($now-$mtime) < $cache) {
	    if ($touch) {
		$xoopsDB->query("UPDATE ".UPDATE_CACHE." SET mtime=$now WHERE cacheid=".$cacheid);
	    }
	    return $content;
	}
	$xoopsDB->queryF("DELETE FROM ".UPDATE_CACHE." WHERE cacheid=".$cacheid);
    }
    $snoopy->cookies['UPDATEDOMAIN'] = XOOPS_URL;
    $snoopy->cookies['UPDATEOTP'] = get_update_otp();
    if ($post?$snoopy->submit($url, $post):$snoopy->fetch($url)) {
	$content = $snoopy->results;
	if ($snoopy->status == 404 || empty($content)) return false;
	if (empty($hash)) {
	    if (preg_match('/^\s*</', $content)) return false;
	} elseif (md5($content)!=$hash) return false;
	$xoopsDB->queryF("INSERT INTO ".UPDATE_CACHE." (cacheid, mtime, content)VALUES($cacheid,$now,".$xoopsDB->quoteString($content).")");
	return $content;
    }
    return false;
}

function package_expire($pname='') {
    global $xoopsDB;
    if ($pname) $pname = " AND pname='$pname'";
    $xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET mtime=0,parent=0 WHERE pversion='HEAD'".$pname);
}

function session_auth_server() {
    global $xoopsDB, $xoopsModuleConfig;

    $server = $xoopsModuleConfig['update_server'];
    if (!preg_match('/^\w+:/', $server)) return false;

    $res = $xoopsDB->query("SELECT hash,fileid FROM ".UPDATE_FILE." WHERE pkgref=0 AND path='' AND hash<>''");
    if (!$res || $xoopsDB->getRowsNum($res)==0) return false;

    list($pass,$fid) = $xoopsDB->fetchRow($res);
    $domain = auth_domain_name();
    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
    $snoopy = new Snoopy;
    $param=array('domain'=>$domain, 'pass'=>$pass);
    $uri = $server."/modules/server/authsvr.php";

    $status = false;
    if ($snoopy->submit($uri, $param)) {
	$next = '';
	foreach (split("\n", $snoopy->results) as $ln) {
	    if (empty($ln)) continue;
	    list($head, $body) = preg_split('/:\s*/', rtrim($ln), 2);
	    $head = strtolower($head);
	    switch ($head) {
	    case 'x-status': $status = ($body=='OK'); break;
	    case 'x-next-password': $next = $body; break;
	    }
	}
	if ($status && $pass!=$next) {
	    $res = $xoopsDB->queryF("UPDATE ".UPDATE_FILE." SET hash=".$xoopsDB->quoteString($next)." WHERE fileid=$fid");
	}
    }
    return $status;
}

function strtotime_tz($date) {
    $time = strtotime($date);
    if (preg_match('/ ([\+\-]\d\d)(\d\d)$/', $date, $d)) {
	$time -= $d[1]*3600+$d[2]*60;
    }
    return $time;
}

function strip_csv($item) {
    return preg_replace('/""/', '"', preg_replace('/^"(.*)"$/', '\1', $item, 1));
}

function split_csv($line) {
    $pat = '/^("[^"]*(?:""[^"]*)*"|[^,]*),/';
    $ret = array();
    while (preg_match($pat, $line, $d)) {
	$ret[] = strip_csv($d[1]);
	$line = preg_replace($pat, '', $line);
    }
    $ret[] = strip_csv($line);
    return $ret;
}

function mystyle() {
    return '<link rel="stylesheet" type="text/css" media="all" href="'.XOOPS_URL.'/modules/'.basename(dirname(__FILE__)).'/style.css" />'."\n";
}

function redirect_result($ret, $dest='', $err=_AM_DBUPDATE_FAIL) {
    if (empty($dest)) $dist = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'index.php';
    if ($ret) {
	redirect_header($dest, 1, _AM_DBUPDATED);
    } else {
	redirect_header($dest, 3, $err);
    }
    exit;
}

function auth_domain_name($url=XOOPS_URL) {
    $reg = array('/^https?:\/\//i', '/\/+/');
    $rep = array('', '/');
    return preg_replace($reg, $rep, $url);
}

function get_system_url($type, $arg) {
    switch ($type) {
    case 'ModuleUpdate':
    case 'ModuleInstall':
	if ( defined( 'XOOPS_CUBE_LEGACY' ) ) { // XOOPS Cube Legacy
	    $fmt = "/modules/legacy/admin/index.php?action=%s&dirname=%s";
	} else {				// XOOPS 2.0.x
	    $type = strtolower(preg_replace("/Module/", '', $type));
	    $fmt = "/modules/system/admin.php?fct=modulesadmin&op=%s&module=%s";
	}
	return XOOPS_URL.sprintf($fmt, $type, $arg);
    default:
	return false;
    }
}
?>