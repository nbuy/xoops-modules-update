<?php
# ScriptUpdate class defines
# $Id: package.class.php,v 1.2 2006/07/12 18:33:55 nobu Exp $

// Package class
// methods:
  // ->getVar('name')         get a meta data
  // ->setVar('name', value)  set a meta data
  // ->load($id)              restore instance by id
  // ->store()                store instance in database (result store id)

define('UPDATE_PKG', $xoopsDB->prefix('update_package'));
define('UPDATE_FILE', $xoopsDB->prefix('update_file'));
define('UPDATE_DIFF', $xoopsDB->prefix('update_diff'));

function is_binary($file) {
    return preg_match('/\.(png|gif|jpe?g|swf|zip|gz|tar)$/i', $file);
    //$type = preg_replace('/^[^:]*:\s*/', '', `file $file`);
    //return preg_match('/(image)/', $file);
}

if(!function_exists("file_get_contents")) {
   function file_get_contents($filename) {
       $fp = fopen($filename, "rb");
       if (!$fp) return false;
       $contents = "";
       while (! feof($fp)) {
	   $contents .= fread($fp, 4096);
       }
       return $contents;
   }
}

if (!function_exists('file_put_contents')) {
    // have php 4.4 later
    function file_put_contents($file, $text) {
	$fp = fopen($file, "w");
	if (!$fp) return false;
	$ret = fwrite($fp, $text);
	fclose($fp);
	return $ret;
    }
}

$meta_fileds = array(
    'x-dist-date'=>'dtime',
    'x-info-date'=>'ctime',
    'x-package-name'=>'pname',
    'x-package-version'=>'pversion',
    'x-version-check'=>'vcheck',
    'x-name'=>'name');

// package object was keep meta information of fileset:
//    file consistency, come from, modification, etc.
//
// a dist package instance build from maifest, it's feed from server or file.
//
//   Installed package is a instance that derived from dist package instance,
// with modifications.

class Package {
    var $files=array();
    var $fdirty=array();
    var $vars=array();
    var $vdirty=array();

    function Package($id=0, $ver='') {
	if (is_array($id)) $this->vars=$id;
	elseif ($id) $this->load($id, $ver);
    }

    function load($id=0, $ver='') {
	global $xoopsDB;
	if (preg_match('/^\d+$/', $id)) {
	    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pkgid=".$id);
	    $this->vars = $xoopsDB->fetchArray($res);
	} elseif ($id===0) {
	    $id = $this->getVar('pkgid');
	    $res = true;
	} else {
	    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pname=".$xoopsDB->quoteString($id)." AND pversion=".$xoopsDB->quoteString($ver));
	    $this->vars = $xoopsDB->fetchArray($res);
	    $id = $this->getVar('pkgid');
	}
	if (!$res) return false;

	$this->vdirty = array();
	$res = $xoopsDB->query("SELECT hash, path FROM ".UPDATE_FILE." WHERE pkgref=".$id);
	$files =& $this->files;
	while (list($hash, $path) = $xoopsDB->fetchRow($res)) {
	    $files[$path] = $hash;
	}
	return $res;
    }

    function loadStr($content) {
	global $meta_fileds;
	$vars = &$this->vars;
	$vdirty = &$this->vdirty;
	$lines = preg_split('/\n/', $content);
	while ($ln = array_shift($lines)) {
	    if (preg_match('/^$/', $ln)) break;
	    list($name, $value) = preg_split('/:\s*/', rtrim($ln), 2);
	    $name = strtolower($name);
	    if (preg_match('/date$/', $name)) {
		$value = strtotime($value);
	    }
	    if (isset($meta_fileds[$name])) {
		$fname = $meta_fileds[$name];
		$vars[$fname] = $value;
		if (!in_array($fname, $vdirty)) $vdirty[] = $fname;
	    }
	}
	$files = &$this->files;
	while ($ln = array_shift($lines)) {
	    if (empty($ln)) continue;
	    list($hash, $path) = preg_split('/\s+/', rtrim($ln), 2);
	    $files[$path] = $hash;
	}
	return true;
    }

    function loadFile($file) {
	$this->loadStr(file_get_contents($file));
    }

    function getVar($name=null) {
	if (empty($name)) return $this->vars;
	else return @$this->vars[$name];
    }

    function setVar($name, $value=null) {
	if (is_array($name)) {
	    foreach ($name as $k=>$v) {
		$this->setVar($k, $v);
	    }
	} else {
	    $this->vars[$name]=$value;
	    if (!in_array($name, $this->vdirty)) $this->vdirty[] = $name;
	}
    }

    function getHash($file) {
	return @$this->files[$file];
    }

    function checkFile($path) {
	$file = XOOPS_ROOT_PATH."/".$path;
	$hash = $this->getHash($path);
	if (!file_exists($file)) {
	    if ($hash != 'delete') return 'del';
	} elseif (md5_file($file)!=$hash) return 'chg';
	return false;
    }

    function checkFiles() {
	$updates = array();
	foreach ($this->files as $file => $hash) {
	    if ($stat = $this->checkFile($file)) {
		$updates[$file] = $stat;
	    }
	}
	return $updates;
    }

    function store() {
	global $xoopsDB;
	$vars =& $this->vars;
	$keys =& $this->vdirty;
	if (!in_array('mtime', $keys)) $this->setVar('mtime', time());
	$values = array();
	if (empty($vars['pkgid'])) {
	    $res = $xoopsDB->query("SELECT pkgid FROM ".UPDATE_PKG." WHERE pname=".$xoopsDB->quoteString($vars['pname'])." AND pversion=".$xoopsDB->quoteString($vars['pversion']));
	    if (!$res || $xoopsDB->getRowsNum($res)>0) return false;
	    foreach ($keys as $key) {
		$values[] = $xoopsDB->quoteString($vars[$key]);
	    }
	    $res = $xoopsDB->queryF("INSERT INTO ".UPDATE_PKG."(".join(',', $keys).")VALUES(".join(',', $values).")");
	    if (!$res) die(UPDATE_PKG);
	    $vars['pkgid'] = $pkgid = $xoopsDB->getInsertID();
	    if (empty($pkgid)) die('Insert');
	    $sql = "INSERT INTO ".UPDATE_FILE."(pkgref, hash, path)VALUES($pkgid,%s,%s)";
	    foreach ($this->files as $path => $hash) {
		$xoopsDB->queryF(sprintf($sql, $xoopsDB->quoteString($hash), $xoopsDB->quoteString($path)));
	    }
	} else { // update
	    foreach ($keys as $key) {
		$values[] = $key.'='.$xoopsDB->quoteString($vars[$key]);
	    }
	    $res = $xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET ".join(',', $values)." WHERE pkgid=".$vars['pkgid']);
	}
	if ($res) {
	    $this->vdirty = array();
	}
	return true;
    }

    function getFile($path, $ver=null) {
	global $xoopsModuleConfig;
	if (empty($ver)) $ver = $this->getVar('pversion');
	$pname = $this->getVar('pname');
	$file = XOOPS_ROOT_PATH."/uploads/update/source/$pname/$ver/".$path;
	if (file_exists($file)) {
	    return file_get_contents($file);
	} else {
	    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
	    $url = $xoopsModuleConfig['update_server'].
		"file.php?pkg=".urlencode($pname).
		"&v=".urlencode($ver)."&file=".urlencode($path);
	    $snoopy = new Snoopy;
	    $snoopy->lastredirectaddr = 1;
	    if ($snoopy->fetch($url)) return $snoopy->results;
	    else echo "<div>Error: $url</div>";
	}
	return null;
    }
}

class InstallPackage extends Package {
    var $modifies = array();	// modification files info

    function InstallPackage($id=0) {
	if (is_array($id)) $this->vars=$id;
	elseif ($id) $this->load($id);
    }

    function load($id=0) {
	global $xoopsDB;
	if ($id) {
	    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pkgid=".$id);
	    $vars = $xoopsDB->fetchArray($res);
	} else {
	    $id = $this->getVar('pkgid');
	    $res = true;
	    $vars = $this->vars;
	}
	if ($res) {
	    // get parent information
	    $pid = $vars['parent'];
	    if ($pid && !parent::load($pid)) return false;
	    $res = $xoopsDB->query("SELECT hash, path FROM ".UPDATE_FILE." WHERE pkgref=".$id);
	    // override modify information
	    $files =& $this->files;
	    $vars['origin'] = $this->getVar('pversion');
	    $mods = array();
	    while (list($hash, $path) = $xoopsDB->fetchRow($res)) {
		if (isset($files[$path])) {
		    $mods[$path] = $files[$path];
		}
		$files[$path] = $hash;
	    }
	    //$this->files = $files;
	    $this->modifies = $mods;
	    $this->vars = $vars;
	}
	return $res;
    }

    function modifyFiles() {
	$mods = array();
	$files =& $this->files;
	foreach ($this->modifies as $path=>$hash) {
	    $mods[$path] = ($files[$path]=='delete')?'del':'chg';
	}
	ksort($mods);
	return $mods;
    }

    function setModify($path) {
	global $xoopsDB;
	$file = XOOPS_ROOT_PATH.'/'.$path;
	$id = $this->getVar('pkgid');
	if (empty($id)) die('pkgid='.$id);
	$md5 = file_exists($file)?md5_file($file):'delete';
	if ($this->getHash($path) != $md5) {
	    $hash = $xoopsDB->quoteString($md5);
	    $key = $xoopsDB->quoteString($path);
	    if ($md5 != 'delete') {
		$diff = $this->getDiff($path);
		if ($diff === null) return false; // error!
		$diff = $xoopsDB->quoteString($diff);
	    }
	    $now = time();
	    if (isset($this->modifies[$path])) {
		$res = $xoopsDB->query("SELECT fileid FROM ".UPDATE_FILE." WHERE pkgref=$id AND path=$key");
		if (!$res || $xoopsDB->getRowsNum($res)==0) die('setModify');
		list($fileid) = $xoopsDB->fetchRow($res);
		if ($this->modifies[$path] == $md5) { // back orignal
			$xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE fileid=$fileid");
			$xoopsDB->query("DELETE FROM ".UPDATE_DIFF." WHERE fileref=$fileid");
			unset($this->modifies[$path]);
		} else {
		    $xoopsDB->query("UPDATE ".UPDATE_FILE." SET hash=$hash WHERE fileid=$fileid");
		    if ($md5=='delete') {
			$xoopsDB->query("DELETE FROM ".UPDATE_DIFF." WHERE fileref=$fileid");
		    } else {
			$xoopsDB->query("UPDATE ".UPDATE_DIFF." SET diff=$diff,mtime=$now WHERE fileref=$fileid");
		    }
		}
	    } else {
		$sql = "INSERT INTO ".UPDATE_FILE."(pkgref, hash, path) VALUES(%u,%s,%s)";
		$xoopsDB->query(sprintf($sql, $id, $hash, $key));
		if ($md5!='delete') {
		    $fileid = $xoopsDB->getInsertID();
		    $xoopsDB->query("INSERT INTO ".UPDATE_DIFF." VALUES($fileid, $now, $now, $diff)");
		}
		$this->modifies[$path] = $this->files[$path];
	    }
	    $this->files[$path] = $md5;
	    return $md5;
	}
	return false;
    }

    function dbDiff($path) {
	global $xoopsDB;
	$res = $xoopsDB->query("SELECT diff FROM ".UPDATE_FILE.",".UPDATE_DIFF." WHERE path=".$xoopsDB->quoteString($path)." AND fileref=fileid");
	if (!$res || $xoopsDB->getRowsNum($res)==0) return false;
	list($diff) = $xoopsDB->fetchRow($res);
	return $diff;
    }

    function getDiff($path) {
	if ($this->files[$path]=='delete') return '';
	//if (!isset($this->modifies[$path])) return '';
	$ver = $this->getVar('origin');
	if (empty($ver)) $ver = $this->getVar('pversion');
	$prefix = $this->getVar('pname').'/'.$ver;
	$file = XOOPS_UPLOAD_PATH."/update/source/$prefix/$path";
	if (is_binary($path)) return 'file is binary';
	if (file_exists($file)) { // check local uploads
	    $lines0 = file($file);
	} else {
	    $src = $this->getFile($path);
	    if ($src==null) return null;
	    $lines0 = preg_split('/\n/', $src);
	}
	$lines1 = file(XOOPS_ROOT_PATH."/$path");

	// normalize cvs/rcs Id tag
	$tag = '/\\$(Id|Date|Author|Revision):[^\\$]*\\$/';
	$rep = '$\\1$';
	$lines0 = preg_replace($tag, $rep, $lines0);
	$lines1 = preg_replace($tag, $rep, $lines1);

	include_once 'Text/Diff.php';
	include_once 'Text/Diff/Renderer/unified.php';
	$diff = &new Text_Diff($lines0, $lines1);
	$renderer = &new Text_Diff_Renderer_unified();
	return $renderer->render($diff);
    }

    function getFile($path, $type='orig') {
	global $xoopsModuleConfig;
	$ver = $this->getVar('origin');
	if (empty($ver)) $ver = $this->getVar('pversion');
	return parent::getFile($path, $ver);
    }

    function checkUpdates($dstpkg) {
	$files = $dstpkg->checkFiles();	// changing file sets
	$updates = array();
	foreach ($files as $file=>$stat) {
	    if (!$this->getHash($file)) $updates[$file] = 'new';
	    else {
		if ($this->files[$file] == 'delete') $updates[$file] = 'skip';
		else {
		    $diff = $this->dbDiff($file);
		    $ostat = $this->checkFile($file);
		    if (!$ostat) { // nochange, do replaced
			if (empty($diff)) $updates[$file] = 'replace';
			elseif (is_binary($file)) $updates[$file] = 'skip';
			else $updates[$file] = 'patch';
		    } else die('unknown modification exists');
		}
	    }
	}
	return $updates;
    }

    function updatePackage($dstpkg) {
	$work = XOOPS_UPLOAD_PATH.'/update/'.$this->getVar('pname').
	    '/new-'.$dstpkg->getVar('pversion');
	foreach ($this->checkUpdates($dstpkg) as $path => $method) {
	    $file = "$work/$path";
	    if (!mkdir_p(dirname($file))) die("can't mkdir with $file");
	    switch ($method) {
	    case 'replace':
		file_put_contents($file, $dstpkg->getFile($path));
		break;
	    case 'patch':
		file_put_contents($file, $dstpkg->getFile($path));
		$fp = popen("patch '$file'", "w");
		fwrite($fp, $this->dbDiff($path));
		if (pclose($fp)) "<div>patch failed: $file</div>";
		unlink("$file.orig");
		break;
	    case 'skip':	// do nothing
		break;
	    default:
		echo "<div>$method: $file<div>\n";
	    }
	}
	return true;
    }
}

function mkdir_p($path) {
    if (is_dir($path)) return true;
    $p = dirname($path);
    if (!file_exists($p)) if (!mkdir_p($p)) return false;
    if (is_dir($p) && is_writable($p)) return mkdir($path);
    return false;
}

function get_current_version($pname, $vcheck) {
    global $xoopsDB;
    switch ($vcheck) {
    case 'xoops':
	return preg_replace(array('/^XOOPS /', '/ /'), array('','-'), XOOPS_VERSION);
    case 'modules':
	$res = $xoopsDB->query('SELECT path FROM '.UPDATE_PKG.','.UPDATE_FILE.' WHERE pname='.$xoopsDB->quoteString($pname)." AND pkgid=pkgref AND path like '%/xoops_version.php'", 1);
	if (!$res || $xoopsDB->getRowsNum($res)==0) return false;
	list($vpath) = $xoopsDB->fetchRow($res);
	$vfile = XOOPS_ROOT_PATH."/$vpath";
	if (!file_exists($vfile)) return false;
	$modpath = dirname($vfile);
	$lang = $modpath."/language/".$xoopsConfig['language']."/modinfo.php";
	if (file_exists($lang)) include_once $lang;
	else include_once $modpath."/language/english/modinfo.php";
	include $vfile;
	return $modversion['version'];
    }
    return false;
}

?>