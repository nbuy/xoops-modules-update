<?php
# ScriptUpdate class defines
# $Id: package.class.php,v 1.14 2007/01/04 07:03:05 nobu Exp $

// Package class
// methods:
  // ->getVar('name')         get a meta data
  // ->setVar('name', value)  set a meta data
  // ->load($id)              restore instance by id
  // ->store()                store instance in database (result store id)

include_once "functions.php";

function is_binary($file) {
    return preg_match('/\.(png|gif|jpe?g|swf|ico|zip|gz|tar)$/i', $file);
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
    'x-name'=>'name',
    'x-optional-dir'=>'options');

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
    var $options=array();

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
	$options =& $this->options;
	while (list($hash, $path) = $xoopsDB->fetchRow($res)) {
	    if ($hash=='options') $options[$path] = true;
	    elseif ($hash=='no-options') $options[$path] = false;
	    else $files[$path] = $hash;
	}
	return $res;
    }

    function loadStr($content) {
	global $meta_fileds;
	$vars = &$this->vars;
	$vdirty = &$this->vdirty;
	$options = &$this->options;
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
		if ($fname=='options') {
		    $path = preg_replace('/^\s*!\s*/', '', $value);
		    $options[$path] = ($path==$value);
		} else {
		    $vars[$fname] = $value;
		    if (!in_array($fname, $vdirty)) $vdirty[] = $fname;
		}
	    }
	}
	if (empty($vars['pname'])) return false;
	$files = &$this->files;
	while ($ln = array_shift($lines)) {
	    if (empty($ln)) continue;
	    list($hash, $path) = preg_split('/\s+/', rtrim($ln), 2);
	    $files[$path] = $hash;
	}
	return true;
    }

    function loadFile($file) {
	return $this->loadStr(file_get_contents($file));
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

    function checkFile($path, $nhash='') {
	$file = XOOPS_ROOT_PATH."/".$path;
	$hash = $this->getHash($path);
	if (!file_exists($file)) {
	    if ($nhash) {
		if ($hash == 'delete') return 'del';
		else return 'new';
	    } elseif ($hash != 'delete') return 'del';
	} else {
	    $chash = md5_file($file);
	    if ($nhash && $nhash!=$hash) return 'chg';
	    if ($chash!=$hash) return 'chg';
	}
	return false;
    }

    function regIgnore() {
	$pat = array();
	foreach ($this->options as $path => $v) {
	    if (!$v) $pat[] = preg_quote(preg_replace('/\/*$/', '/', $path), '/');
	}
	if ($pat) return '/^('.join('|', $pat).')/';
	return false;
    }

    function checkFiles($dest=null) {
	$updates = array();
	$nhash = '';
	$chk = $dest?$dest:$this;
	$pat = $this->regIgnore();
	foreach ($chk->files as $file => $hash) {
	    if ($pat && preg_match($pat, $file)) continue;
	    if ($dest) {
		$nhash = $dest->getHash($file);
	    }
	    if ($stat = $this->checkFile($file, $nhash)) {
		$updates[$file] = $stat;
	    }
	}
	return $updates;
    }

    function checkExtra() {
	$updates = array();
	$pat = $this->regIgnore();
	$dirs = array();
	$files = &$this->files;
	foreach ($files as $file => $hash) {
	    $base = dirname($file).'/';
	    if ($pat && preg_match($pat, $file, $d)) {
		$base = $d[1];
		if (empty($updates[$base])
		    && file_exists(XOOPS_ROOT_PATH."/$base")) {
		    $updates[$base] = "extra";
		}
		continue;
	    }
	    if (empty($dirs[$base])) $dirs[$base] = true;
	    if ($this->getHash($file) == 'del' && file_exists(XOOPS_ROOT_PATH."/$file")) {
		$updates[$file] = 'extra';
	    }
	}
	foreach (array_keys($dirs) as $dir) {
	    if ($dir == './') $dir = "";
	    $dh = opendir(XOOPS_ROOT_PATH."/$dir");
	    while ($fname = readdir($dh)) {
		if ($fname == '.' || $fname == '..') continue;
		$path = $dir.$fname;
		if (is_dir(XOOPS_ROOT_PATH."/$path")) $path .= '/';
		if (defined('XOOPS_VERSION')) {
		    if (preg_match('/^(templates_c|cache|uploads)\/|.~$/', $path)) continue;
		    if (preg_match('/^modules\/.*\/$/', $path)) {
			$mod = $dir.$fname;
			if (!isset($this->options[$mod])||$this->options[$mod]) continue;
		    }
		}
		if (isset($files[$path]) || isset($dirs[$path])) continue;
		$updates[$path] = "extra";
	    }
	    closedir($dh);
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
	    foreach ($this->options as $path=>$val) {
		$tag = $val?"'options'":"'no-options'";
		$xoopsDB->queryF(sprintf($sql, $tag, $xoopsDB->quoteString($path)));
	    }
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
	if (empty($ver)) $ver = $this->getVar('pversion');
	$pname = $this->getVar('pname');
	$server = get_update_server();
	$file = XOOPS_ROOT_PATH."/uploads/update/source/$pname/$ver/".$path;
	if (file_exists($file)) {
	    return file_get_contents($file);
	} elseif ($server) {
	    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
	    $url = $server."file.php?pkg=".urlencode($pname).
		"&v=".urlencode($ver)."&file=".urlencode($path);
	    $snoopy = new Snoopy;
	    $snoopy->lastredirectaddr = 1;
	    $snoopy->timed_out = 5;
	    if ($snoopy->fetch($url)) return $snoopy->results;
	    else echo "<div>Error: $url</div>";
	}
	return null;
    }

    function getDiff($path, $target="") {
	if ($this->files[$path]=='delete') return '';
	//if (!isset($this->modifies[$path])) return '';
	$ver = $this->getVar('origin');
	if (empty($ver)) $ver = $this->getVar('pversion');
	$prefix = $this->getVar('pname').'/'.$ver;
	$file = XOOPS_UPLOAD_PATH."/update/source/$prefix/$path";
	if (is_binary($path)) return 'file is binary';
	if (file_exists($file)) $src = file_get_contents($file);
	else $src = $this->getFile($path);
	$orig = $target?$target:XOOPS_ROOT_PATH.'/'.$path;
	
	$dest = file_exists($orig)?file_get_contents($orig):'';
	$tag = array('/\\$(Id|Date|Author|Revision):[^\\$]*\\$/', '/\r/');
	$rep = array('$\\1$','');
	return diff_str(preg_replace($tag,$rep,$src), preg_replace($tag,$rep,$dest));
    }
}

class InstallPackage extends Package {
    var $modifies = array();	// modification files info
    var $dirname = '';

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
	    $this->dirname = $this->getVar('vcheck');
	    $res = $xoopsDB->query("SELECT hash, path FROM ".UPDATE_FILE." WHERE pkgref=".$id);
	    // override modify information
	    $files =& $this->files;
	    $vars['origin'] = $this->getVar('pversion');
	    $mods = array();
	    while (list($hash, $path) = $xoopsDB->fetchRow($res)) {
		if ($hash=='options') {
		    $this->options[$path] = true;
		} elseif ($hash=='no-options') {
		    $this->options[$path] = false;
		} else {
		    if (isset($files[$path])) {
			$mods[$path] = $files[$path];
		    }
		    $files[$path] = $hash;
		}
	    }
	    $pattern = $this->regIgnore();
	    if ($pattern) {
		foreach ($files as $path=>$hash) {
		    if (preg_match($pattern, $path)) {
			$mods[$path]=$hash;
			unset($files[$path]);
		    }
		}
	    }
	    $this->modifies = $mods;
	    $this->vars = $vars;
	    if ($this->dirname == $vars['vcheck']) $this->dirname = '';
	}
	return $res;
    }

    function modifyFiles() {
	$mods = array();
	$files =& $this->files;
	$pat = $this->regIgnore();
	foreach ($this->modifies as $path=>$hash) {
	    if ($pat && preg_match($pat, $path)) continue;
	    $mods[$path] = ($files[$path]=='delete')?'del':'chg';
	}
	ksort($mods);
	return $mods;
    }

    function setModify($path) {
	global $xoopsDB;
	$file = $this->getRealPath($path);
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

    function getFile($path, $type='orig') {
	global $xoopsModuleConfig;
	$ver = $this->getVar('origin');
	if (empty($ver)) $ver = $this->getVar('pversion');
	return parent::getFile($path, $ver);
    }

    function setOptions($path, $value) {
	global $xoopsDB;
	if ($this->options[$path] == $value) return false; // not changed
	$me = $this->getVar('pkgid');
	$qpath = $xoopsDB->quoteString($path);
	$res = $xoopsDB->query("SELECT fileid FROM ".UPDATE_FILE." WHERE path=$qpath AND pkgref=$me");
	if ($xoopsDB->getRowsNum($res)>0) {
	    list($fileid) = $xoopsDB->fetchRow($res);
	    $xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE fileid=".$fileid);
	} else {
	    $hash = $value?"'options'":"'no-options'";
	    $xoopsDB->query("INSERT INTO ".UPDATE_FILE."(pkgref, hash, path) VALUES($me, $hash, $qpath)");
	}
	$this->options[$path] = $value;
    }

    // return array(filename=>method);
    //   method: new, replace: write 
    function checkUpdates($dstpkg) {
	global $xoopsModuleConfig;
	$files = $dstpkg->checkFiles();	// changing file sets
	foreach ($files as $file=>$stat) {
	    $method = '';
	    if (!$this->getHash($file)) $method = 'new';
	    else {
		if ($this->files[$file] == 'delete') $method = 'skip';
		else {
		    $diff = $this->dbDiff($file);
		    if (empty($diff) ||
			(count(preg_split('/\n/', $diff)<6) &&
			 preg_match('/\n .*\n-\n$/', $diff))) {	// no change
			$method = 'replace';
		    } else {	// changed
			$ostat = $this->checkFile($file);
			$adiff = $dstpkg->getDiff($file);
			if (empty($adiff)) $method = 'replace';
			elseif ($diff == $adiff) $method = 'patch';
			elseif (!$ostat) { // nochange, do replaced
			    if (is_binary($file)) $method = 'skip';
			    else $method = $xoopsModuleConfig['update_method'];
			} else die("$ostat: unknown modification in $file");
		    }
		}
	    }
	    if ($method) $updates[$file] = $method;
	}
	return $updates;
    }

    function unchecked() {
	global $xoopsDB;
	$xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET mtime=0 WHERE pkgid=".$this->getVar('pkgid'));
    }

    function updatePackage($dstpkg, $dir="new") {
	$work = XOOPS_UPLOAD_PATH."/update/work/$dir";
	foreach ($this->checkUpdates($dstpkg) as $path => $method) {
	    $file = "$work/".$this->getRealPath($path, false);
	    if ($method == 'skip') continue;
	    if (!mkdir_p(dirname($file))) die("can't mkdir with $file");
	    switch ($method) {
	    case 'new':
	    case 'replace':
		file_put_contents($file, $dstpkg->getFile($path));
		break;
	    case 'patch':
		$tag = '/\\$(Id|Date|Author|Revision):?[^\\$]*\\$/';
		$rep = array('$\\1$','');
		if (is_binary($path)) break;
		$diff = $this->dbDiff($path);
		$text = $dstpkg->getFile($path);
		if (preg_match($tag, $diff)) {
		    $text = preg_replace($tag, '$\\1$', $text);
		}
		file_put_contents($file, $text);
		$fp = popen("patch '$file'", "w");
		fwrite($fp, $diff);
		pclose($fp);
		if (file_exists("$file.orig")) unlink("$file.orig");
		if (file_exists("$file.rej")) echo "<div>patch failed: $path</div>";
		break;
	    default:
		echo "<div>$method: $file<div>\n";
	    }
	}
	// force checking again
	$this->unchecked();
	return true;
    }

    function backupPackage($dstpkg, $dir="backup") {
	$work = XOOPS_UPLOAD_PATH."/update/work/$dir";
	foreach ($this->checkUpdates($dstpkg) as $path => $method) {
	    $file = "$work/$path";
	    if ($method == 'skip') continue;
	    if (!mkdir_p(dirname($file))) die("can't mkdir with $file");
	    $src = $this->getRealPath($path);
	    if (file_exists($src) && !link($src, $file)) {
		if (!copy($src, $file)) echo "<div>copy fail: $file<div>\n";
	    }
	}
	return true;
    }

    function getRealPath($path, $abs=true) {
	if ($this->dirname) {
	    $file = preg_replace('/^'.preg_quote("modules/".$this->dirname.'/', '/').'/', 'modules/'.$this->getVar('vcheck').'/', $path);
	} else {
	    $file = $path;
	}
	return $abs?XOOPS_ROOT_PATH."/".$file:$file;
    }

    function checkFile($path, $nhash='') {
	$file = $this->getRealPath($path);
	$hash = $this->getHash($path);
	if (!file_exists($file)) {
	    if ($nhash) {
		if ($hash == 'delete') return 'del';
		else return 'new';
	    } elseif ($hash != 'delete') return 'del';
	} else {
	    $chash = md5_file($file);
	    if ($nhash && $nhash!=$hash) return 'chg';
	    if ($chash!=$hash) return 'chg';
	}
	return false;
    }

    function getDiff($path) {
	return parent::getDiff($path, $this->getRealPath($path));
    }
}

function mkdir_p($path) {
    if (is_dir($path)) return true;
    $p = dirname($path);
    if (!file_exists($p)) if (!mkdir_p($p)) return false;
    if (is_dir($p) && is_writable($p)) return mkdir($path);
    return false;
}

function count_modify_files($pkgid) {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT count(fileid) FROM ".UPDATE_FILE." WHERE pkgref=$pkgid AND NOT (hash LIKE '%options')");
    list($ret) = $xoopsDB->fetchRow($res);
    return $ret;
}

function get_pkg_info($pkgid, $name='*') {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT $name FROM ".UPDATE_PKG." WHERE pkgid=$pkgid");
    if ($name == '*') return $xoopsDB->fetchArray($res);
    list($ret) = $xoopsDB->fetchRow($res);
    return $ret;
}

function get_current_version($pname, $vcheck) {
    global $xoopsDB, $xoopsConfig;
    $ver = false;
    switch ($vcheck) {
    case '':
	return preg_replace(array('/^XOOPS /', '/ /'), array('','-'), XOOPS_VERSION);
    default:
	$vfile = XOOPS_ROOT_PATH."/modules/$vcheck/xoops_version.php";
	if (!file_exists($vfile)) return false;
	$modpath = dirname($vfile);
	$lang = $modpath."/language/".$xoopsConfig['language']."/modinfo.php";
	if (file_exists($lang)) include_once $lang;
	else include_once $modpath."/language/english/modinfo.php";
	include $vfile;
	return round($modversion['version'], 2);
    }
    return false;
}

class PackageList {
    var $pkgs = array();

    function PackageList() {
	global $xoopsDB;
	$xoops = array('contact', 'mydownloads', 'mylinks', 'newbb', 'news',
		       'sections', 'system', 'xoopsfaq', 'xoopsheadline',
		       'xoopsmembers', 'xoopspartners', 'xoopspoll');
	$res = $xoopsDB->query("SELECT dirname FROM ".$xoopsDB->prefix('modules')." WHERE isactive ORDER BY dirname");
	$pkgs =& $this->pkgs;
	$pkgs[''] = array();	// XOOPS core slot
	while (list($dirname) = $xoopsDB->fetchRow($res)) {
	    if (in_array($dirname, $xoops)) continue;
	    $pkgs[$dirname]=array();
	}
    }

    function load() {
	$this->addServerList();
	$this->addLocalList();
    }

    function getVar($dirname) {
	if (isset($this->pkgs[$dirname])) {
	    $apkg =& $this->pkgs[$dirname];
	    if (count($apkg)) return $apkg[0];
	}
	return false;
    }

    function getAllPackages() {
	$lists = array();
	foreach ($this->pkgs as $pkg) {
	    if (count($pkg)) {
		$apkg = $pkg[0];
		if (empty($apkg['dirname'])) {
		    $apkg['dirname'] = $apkg['vcheck'];
		}
		$lists[$apkg['pname']] = $apkg;
	    }
	}
	return $lists;
    }

    function addServerList($pname='all') {
	$server = get_update_server();
	if (empty($server)) return;
	$url = $server."list.php?pkg=$pname";
	$list = file_get_url($url);
	$pkgs =& $this->pkgs;
	foreach (preg_split('/\n/', $list) as $ln) {
	    $ln = trim($ln);
	    if (empty($ln)) continue;
	    $F = preg_split('/,/', trim($ln), 5);
	    $dirname = $F[3];
	    $dstr = $F[2];
	    $dtime = strtotime($dstr);
	    if (preg_match('/ ([\+\-])(\d\d)(\d\d)$/', $dstr, $d)) {
		$zone=($d[1]."1")*($d[2]*3600+$d[3]*60);
		$dtime -= $zone;
	    }
	    $pkg = array('pname'=> $F[0],
			 'pversion'=> $F[1],
			 'dtime'=> $dtime,
			 'vcheck'=> $dirname,
			 'name'=> $F[4]);
	    if (isset($pkgs[$dirname])) {
		$pkgs[$dirname][] = $pkg;
	    }
	}
    }

    function addLocalList() {
	global $xoopsDB;
	$res = $xoopsDB->query("SELECT h.pkgid, h.pname,p.pversion,p.dtime,h.vcheck,p.name,p.vcheck dirname
FROM ".UPDATE_PKG." h LEFT JOIN ".UPDATE_PKG." p ON h.parent=p.pkgid 
WHERE h.pversion='HEAD' ORDER BY pname,dtime DESC");
	echo $xoopsDB->error();
	$dirname = "/";
	$pkgs =& $this->pkgs;
	while ($pkg = $xoopsDB->fetchArray($res)) {
	    if ($pkg['vcheck'] != $dirname) {
		$dirname = $pkg['vcheck'];
		if (isset($pkgs[$dirname])) {
		    $found = false;
		    foreach ($pkgs[$dirname] as $k=>$pre) {
			if (isset($pkg['pkgid'])&&$pre['pname'] == $pkg['pname']) {
			    $pkgs[$dirname][$k]['pkgid']=$pkg['pkgid'];
			    if ($pre['dtime']>=$pkg['dtime']) {
				$found = true;
				break;
			    }
			}
		    }
		    if (!$found) $pkgs[$dirname][] = $pkg;
		}
	    }
	}
    }

    function selectPackage($dirname) {
	global $xoopsDB;
	$file = XOOPS_ROOT_PATH."/modules/$dirname/xoops_version.php";
	if (!file_exists($file)) return false;
	$hash=md5_file($file);
	$res=$xoopsDB->query("SELECT pkgid,pname,pversion,dtime,name
FROM ".UPDATE_FILE.",".UPDATE_PKG." p WHERE pkgref=pkgid
  AND path LIKE '%/xoops_version.php' AND hash=".$xoopsDB->quoteString($hash));
	if ($xoopsDB->getRowsNum($res)) {
	    $pkg = $xoopsDB->fetchArray($res);
	    $pkg['vcheck'] = $dirname;
	    return $pkg;
	}
	return false;
    }
}

function get_packages($pname='all', $local=true) {
    $pkgs = new PackageList;
    $pkgs->load();
    $lists = array();
    foreach ($pkgs->pkgs as $dir => $pkg) {
	if (count($pkg)) {
	    $lists[$dir]=$pkg[0];
	}
    }
    if (!$local) return $lists;
    $module_handler =& xoops_gethandler('module');
    foreach ($pkgs->pkgs as $dir => $pkg) {
	if (count($pkg)==0) {
	    $ppkg = $pkgs->selectPackage($dir);
	    if ($ppkg) {
		unset($ppkg['pkgid']);
		$lists[$dir] = $ppkg;
	    } else {
		$module =& $module_handler->getByDirname($dir);
		$ver = $module->getVar('version')*0.01;
		$lists[$dir]=array(
		    'name'=>$module->getVar('name')." ".$ver, 'pname'=> $dir,
		    'pversion'=> '', 'dtime'=> 0, 'vcheck'=>$dir);
	    }
	}
    }
    return $lists;
}

function import_new_package($pname, $ver) {
    global $xoopsModuleConfig, $xoopsDB;
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pname=".
$xoopsDB->quoteString($pname)." AND pversion=".$xoopsDB->quoteString($ver));
    if ($res && $xoopsDB->getRowsNum($res)>0) {
	return new Package($xoopsDB->fetchArray($res));
    }
    $server = get_update_server();
    if (empty($server)) return null;
    $url = $server."manifesto.php?pkg=".urlencode($pname)."&v=".urlencode($ver);
    $content = file_get_url($url);
    if (empty($content)) echo "None";
    if (empty($content)) return false;

    $pkg = new Package();
    if ($pkg->loadStr($content)) $pkg->store();
    else return false;
    return $pkg;
}

function get_update_server() {
    global $xoopsModuleConfig;
    $server = $xoopsModuleConfig['update_server'];
    if (preg_match('/^\w+:/', $server)) return $server."/modules/server/";
    return '';
}

global $sudouser;
$sudouser=null;

function mysystem($cmd) {
    global $sudouser, $xoopsModule;
    $util = XOOPS_ROOT_PATH.'/modules/'.$xoopsModule->getVar('dirname').'/fileutil.sh';
    if (empty($sudouser)) {
	$pw = posix_getpwuid(fileowner($util));
	$sudouser = $pw['name'];
    }
    $fp = popen("sudo -u '$sudouser' '$util' $cmd", 'r');
    $result = "";
    while ($ln = fgets($fp)) {
	$result .= $ln;
    }
    pclose($fp);
    return $result;
}

function temp_put_contents($str) {
    $tmp = tempnam("/tmp", "diff");
    $fp = fopen($tmp, "w");
    fwrite($fp, $str);
    fclose($fp);
    return $tmp;
}

function diff_str($str0, $str1) {
    $tmp0 = temp_put_contents($str0);
    $tmp1 = temp_put_contents($str1);
    $diff = `diff -u '$tmp0' '$tmp1'`;
    unlink($tmp0);
    unlink($tmp1);
    return preg_replace('/^[^\n]*\n[^\n]*\n/', '', $diff);
}
?>