<?php
# ScriptUpdate - common use functions
# $Id: functions.php,v 1.1 2006/07/19 12:47:48 nobu Exp $

function file_get_url($url, $allow_xml=false) {
    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
    $snoopy = new Snoopy;
    $snoopy->lastredirectaddr = 1;
    $cache = XOOPS_CACHE_PATH.'/update'.md5($url);
    if (file_exists($cache) && (time()-filemtime($cache))<3600) {
	return file_get_contents($cache);
    }
    if ($snoopy->fetch($url)) {
	$content = $snoopy->results;
	if ($snoopy->status == 404) return false;
	if (!$allow_xml && preg_match('/^\s*</', $content)) return false;
	$fp = fopen($cache, "w");
	fwrite($fp, $content);
	fclose($fp);
	return $content;
    }
    return false;
}

define('FTBL', $xoopsDB->prefix('update_file'));
function session_auth_server() {
    global $xoopsDB, $xoopsModuleConfig;

    $server = $xoopsModuleConfig['update_server'];
    if (!preg_match('/^\w+:/', $server)) return false;

    $res = $xoopsDB->query("SELECT hash,fileid FROM ".FTBL." WHERE pkgref=0 AND path='' AND hash<>''");
    if (!$res || $xoopsDB->getRowsNum($res)==0) return false;

    list($pass,$fid) = $xoopsDB->fetchRow($res);
    $domain = preg_replace('/\/*/i','',preg_replace('/^https?:\/\//i','',XOOPS_URL));
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
	    $res = $xoopsDB->queryF("UPDATE ".FTBL." SET hash=".$xoopsDB->quoteString($next)." WHERE fileid=$fid");
	}
    }
    return $status;
}
?>