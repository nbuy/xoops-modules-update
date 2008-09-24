<?php
# ScriptUpdate - Management
# $Id: index.php,v 1.24 2008/09/24 00:18:38 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';
include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';

$myts =& MyTextSanitizer::getInstance();

$op = isset($_GET['op'])?$_GET['op']:'';
$file_state = array('del'=>_AM_DEL, 'chg'=>_AM_CHG,
		    'new'=>_AM_NEW, 'ok'=>_AM_OK,
		    'extra'=>_AM_EXTRA);

if (isset($_POST['import'])) {
    redirect_result(import_file(), 'index.php?op=pkgs', _AM_NODATAINFILE);
} elseif(isset($_POST['pkgdel'])) {
    redirect_result(delete_package(), 'index.php?op=pkgs');
} elseif(isset($_POST['accept'])) {
    redirect_result(modify_package(), 'index.php');
} elseif(isset($_POST['opts_select'])) {
    redirect_result(options_setting(), 'index.php');
} elseif(isset($_POST['clear'])) {
    $pkgid = intval($_POST['pkgid']);
    redirect_result(clear_package($pkgid), 'index.php?op=detail&pkgid='.$pkgid);
} elseif ($op == 'rollback') {
    redirect_result(rollback_update(), 'index.php');
}

if( ! empty( $_GET['lib'] ) ) {
    global $mydirpath;
    $mydirpath = dirname(dirname(__FILE__));
    $mydirname = basename($mydirpath);
    // common libs (eg. altsys)
    $lib = preg_replace( '/[^a-zA-Z0-9_-]/' , '' , $_GET['lib'] ) ;
    $page = preg_replace( '/[^a-zA-Z0-9_-]/' , '' , @$_GET['page'] ) ;
    
    if( file_exists( XOOPS_TRUST_PATH.'/libs/'.$lib.'/'.$page.'.php' ) ) {
	include XOOPS_TRUST_PATH.'/libs/'.$lib.'/'.$page.'.php' ;
	} else if( file_exists( XOOPS_TRUST_PATH.'/libs/'.$lib.'/index.php' ) ) {
	include XOOPS_TRUST_PATH.'/libs/'.$lib.'/index.php' ;
    } else {
	die( 'wrong request' ) ;
    }
    exit;
}

// number of checking packages
$res = $xoopsDB->query("SELECT count(pkgid) FROM ".UPDATE_PKG." WHERE pversion='HEAD'");
list($npkg) = $xoopsDB->fetchRow($res);
if (empty($op) && $npkg==0) {
    redirect_header('pkgadmin.php', 1, _AM_PKG_REGISTER);
    exit;
}

xoops_cp_header();
echo mystyle();
include 'mymenu.php';
switch ($op) {
default:
    clear_get_cache(3600*48);
    check_packages();		// checking regsiterd packages
    break;

case 'pkgs':			// package managiment
    echo "<fieldset>\n";
    import_form();
    $svr = get_update_server();
    if ($svr) {
	$src = array("{SERVER}");
	$dst = array($svr);
	echo "<p>".str_replace($src, $dst, _AM_FETCH_DESCRIPTION)."</p>";
    }
    echo "</fieldset>\n";
    reglist_packages();
    break;

case 'detail':
    $view = isset($_GET['view'])?$_GET['view']:false;
    $new = isset($_GET['new'])?intval($_GET['new']):0;
    detail_package(intval($_GET['pkgid']), $view, $new);
    break;

case 'opts':			// options select in a package
    options_form();
    break;
}
xoops_cp_footer();

// bind current installed and HEAD
function check_packages() {
    global $xoopsDB, $xoopsModuleConfig, $xoopsConfig;
    $res = $xoopsDB->query("SELECT p1.*, p2.vcheck dirname, p2.pversion ppversion FROM ".UPDATE_PKG." p1 LEFT JOIN ".UPDATE_PKG." p2 ON p1.parent=p2.pkgid WHERE p1.pversion='HEAD' ORDER BY p1.vcheck");
    $pkgs = get_packages('all', false);
    echo "<h3>"._AM_CHECK_LIST."</h3>\n";
    echo "<table cellspacing='1' class='outer'>\n";
    echo "<tr><th>"._AM_PKG_PNAME."</th><th>"._AM_PKG_CURRENT."</th><th>".
	_AM_PKG_DIRNAME."</th><th>"._AM_PKG_NEW."</th><th>".
	_AM_PKG_DTIME."</th><th>"._AM_CHANGES."</th><th>".
	_AM_MODIFYS."</th><th></th></tr>\n";
    $n = 0;
    $update = false;	// find update package?
    $modify = false;
    $errors = array();
    $module_handler =& xoops_gethandler('module');
    while ($data = $xoopsDB->fetchArray($res)) {
	$pname = $data['pname'];
	$dirname = $data['vcheck'];	// real dirname
	$pdir = empty($data['dirname'])?$dirname:$data['dirname'];
	$bg = $n++%2?'even':'odd';
	$id = $data['pkgid'];
	$newpkg = isset($pkgs[$pdir])?$pkgs[$pdir]:array();
	if (empty($newpkg)) {
	    foreach ($pkgs as $pkg) {	// find by name
		if ($pname == $pkg['pname']) {
		    $newpkg = $pkg;
		    break;
		}
	    }
	}
	$newver = isset($newpkg['pversion'])?$newpkg['pversion']:'';
	$curver = get_current_version($pname, $dirname);
	$modver = $curver[1];	// version string (0: normalize, 1: raw)
	if (empty($data['parent']) ||
	    !in_array($data['ppversion'], $curver)) {

	    $par = import_new_package($pname, $modver, $pdir);
	    if (empty($par)) {
		$errors[] = "$pname $modver: "._AM_PKG_NOTFOUND;
	    } else {
		$pid = $data['parent'] = $par->getVar('pkgid');
		$pnm = $data['name'] = $par->getVar('name');
		$ctm = $data['ctime'] = time();
		$data['ppversion'] = $par->getVar('pversion');
		$xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET parent=$pid, name=".$xoopsDB->quoteString($pnm).", mtime=0, ctime=$ctm WHERE pkgid=$id");
	    }
	}
	if (!empty($data['ppversion'])) {
	    $pversion = $data['ppversion'];
	    $past = time()-$data['mtime'];
	    if ($past>$xoopsModuleConfig['cache_time']) {
		$pkg = new InstallPackage($data);
		$pkg->load();
		$count = count($pkg->checkFiles());
		if (!$count) $xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET mtime=".time()." WHERE pkgid=".$id);
	    } else {
		$count = 0;
	    }
	    $opt = "&pkgid=$id";
	    if (!$count) $opt .= "&view=yes";
	    $op = "<a href='index.php?op=detail$opt'>".($count?_AM_MODIFY:_AM_DETAIL)."</a>";
	} else {		// no manifesto
	    $pversion = "";
	    $op = "";
	    $count = 0;
	}
	if ($count) $modify = true;
	if (!empty($newpkg)) {
	    $newver = $newpkg['pversion'];
	    $newdate = formatTimestamp($newpkg['dtime'], "m");
	} else {
	    $newver = '-';
	    $newdate = '-';
	}
	$mcount = count_modify_files($data['pkgid']);
	if ($pversion != $newver) {
	    $bg = 'up';
	    $uppkg = import_new_package($pname, $newver);
	    if ($uppkg) {
		$pid = $uppkg->getVar('pkgid');
		$newver = "<a href='index.php?op=detail&pkgid=$id&new=$pid&view=yes'>".htmlspecialchars($newver)."</a>";
		$update = true;
	    }
	} else {
	    $newver = htmlspecialchars($newver);
	}

	if ($count) $bg = 'fix';

	// check module update
	$vers = htmlspecialchars($modver);
	if (!empty($dirname)) {
	    $module = $module_handler->getByDirname($dirname);
	    if (is_object($module)) { // installed?
		$mver = $module->getVar("version")/100;
		if (!in_array($mver, $curver)) {
		    $url = get_system_url("ModuleUpdate", $dirname);
		    $vers = "<a href='$url' title='"._AM_PKG_NEEDUPDATE."'>$vers ($mver)</a>";
		}
	    } else {
		$url = get_system_url("ModuleInstall", $dirname);
		$vers = "<a href='$url' title='"._AM_PKG_NOTINSTALL."'>$vers (-)</span>";
	    }
	}
	echo "<tr class='$bg'><td><a href='index.php?op=opts&pkgid=$id'>".
	    htmlspecialchars($pname)."</a></td><td>".
	    $vers."</td><td>$dirname</td><td>".$newver.
	    "</td><td>$newdate</td><td align='right'>$count</td><td align='right'>$mcount</td><td>$op</td></tr>\n";
    }
    echo "</table>\n";
    if ($update && !$modify) {
	echo "<table cellpadding='5'>
<tr>
  <td><a href='pack.php?op=backup'>"._AM_UPDATE_BACKUP."</a></td>
  <td><a href='pack.php?op=update'>"._AM_UPDATE_ARCHIVE."</a></td>
  <td>";
	if (preg_match('/^Yes/', mysystem("check"))) {
	    echo "
    <form action='pack.php?op=exec' method='post'>
    <input type='submit' value='"._AM_UPDATE_SUBMIT."'>
    </form>";
	}
	echo "
  </td>
</tr>
</table>\n";
    }
    $rollback = ROLLBACK;
    if (file_exists($rollback)) {
	$ctime = filectime($rollback);
	$expire = $ctime+$xoopsModuleConfig['cache_time'];
	if ($expire < time()) unlink($rollback);
	else {
	    $tm = _AM_UPDATE_TIME.' '.formatTimestamp($ctime, 'm');
	    $until = _AM_UPDATE_EXPIRE.' '.formatTimestamp($expire, 'H:i');
	echo "<table cellpadding='5'>
  <tr><td>
    <form action='index.php?op=rollback' method='post'>
    <input type='submit' value='"._AM_UPDATE_ROLLBACK."'></form></td>
    <td>$tm ($until)</td></tr>
</table>\n";
	}
    }
    if ($errors) {
	echo "<br/><div class='errorMsg'>\n";
	foreach ($errors as $msg) {
	    echo "$msg<br/>\n";
	}
	echo "</div>\n";
    }
}

function delete_package() {
    global $xoopsDB;
    $base = XOOPS_UPLOAD_PATH."/update/source";
    $srcdir = is_dir($base);
    if ($srcdir) chdir($base);
    if (empty($_POST['pid'])) return false;
    foreach ($_POST['pid'] as $pkgid=>$v) {
	$res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pkgid=".$pkgid);
	if ($res && $xoopsDB->getRowsNum($res)) {
	    $data = $xoopsDB->fetchArray($res);
	    $pname = $data['pname'];
	    $ver = $data['pversion'];
	    $xoopsDB->query("DELETE FROM ".UPDATE_PKG." WHERE pkgid=".$pkgid);
	    if (!empty($pname) && !empty($ver)) {
		if ($srcdir) {
		    $manifesto = "manifesto/$pname-$ver.md5";
		    system("rm -rf '$pname/$ver' '$manifesto'");
		}
		$xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE pkgref=".$pkgid);
	    }
	}
    }
    return true;
}


function clear_package($pid) {
    global $xoopsDB;
    // check 'HEAD' record.
    $res = $xoopsDB->query("SELECT pkgid FROM ".UPDATE_PKG." WHERE pkgid=$pid AND pversion='HEAD'");
    if (!$res || $xoopsDB->getRowsNum($res)==0) return false;
    $res = $xoopsDB->query("SELECT fileid FROM ".UPDATE_FILE." WHERE pkgref=$pid");
    $fids = array();
    while (list($fid) = $xoopsDB->fetchRow($res)) {
	$fids[] = $fid;
    }
    $xoopsDB->query("DELETE FROM ".UPDATE_DIFF." WHERE fileref IN (".join(',',$fids).")");
    $xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE pkgref=$pid AND NOT (hash LIKE '%options')");
    $xoopsDB->query("UPDATE ".UPDATE_PKG." SET mtime=0 WHERE pkgid=$pid");
    return true;
}

function detail_package($pid, $vmode=false, $new=0) {
    global $file_state, $xoopsModuleConfig;
    $pkg = new InstallPackage($pid);
    $dirname = $pkg->getVar('vcheck');
    $title = $pkg->getVar('name');
    if ($dirname) $title .= " ($dirname)";
    if ($new) {
	$newpkg = new Package($new);
	$title .= _AM_UPDATE_TO.$newpkg->getVar('name');
	$files = $pkg->checkFiles($newpkg);
	$id = $new;
	//$files = $pkg->checkPackage($newpkg);
    } else {
	if ($vmode) {
	    $files = $pkg->modifyFiles();
	    if ($xoopsModuleConfig['check_extra']) {
		$files = array_merge($files, $pkg->checkExtra());
		ksort($files);
	    }
	} else $files = $pkg->checkFiles();
	$id = $pid;
    }
    if (count($files)==0) {
	echo _AM_NODATA;
	return;
    }
    echo mystyle();
    echo "<h3>$title</h3>";
    if (!$vmode) {
	echo "<form method='POST' name='FileMark'>\n";
	echo "<input type='hidden' name='pkgid' value='$pid'/>\n";
    }
    $dels = count(array_keys($files, 'del'));
    if ($dels && $vmode) {
	$sw = " &nbsp; <a href='index.php?op=detail&pkgid=$pid&view=";
	if ($vmode=='yes') $sw .= "all'>"._AM_VIEW_ALL;
	else $sw .= "yes'>"._AM_VIEW_SCRIPT;
	$sw .= "</a>";
	$fm = "<form method='post'>
<input name='clear' type='submit' value='"._AM_UPDATE_CLEAR."'/>
<input name='pkgid' type='hidden' value='$pid'/>
</form>";
    } else $sw = $fm = "";
    echo "<table cellspacing='5'>\n<tr>\n<td>"._AM_FILE_ALL." ".count($pkg->files)."</td>\n<td>".
	_AM_CHG." ".count(array_keys($files, 'chg'))."</td><td>".
	_AM_DEL." ".$dels."</td><td>".$sw."</td>\n</tr>\n</table>\n";
    echo "<table cellspacing='1' class='outer'>\n";
    $checkall = "<input type='checkbox' id='allconf' name='allconf' onclick='xoopsCheckAll(\"FileMark\", \"allconf\")'/>";
    echo "<tr>";
    if (!$vmode) echo "<th align='center' class='ckbox'>$checkall</th>";
    echo "<th>"._AM_STATUS."</th><th>"._AM_FILE."</th></tr>\n";
    $n = 0;
    foreach ($files as $file=>$stat) {
	if ($vmode=='yes') {
	    if ($stat=='del') continue;
	    if (is_binary($file) || preg_match('/\\.css$/', $file)) continue;
	}
	$bg = $n++%2?'even':'odd';
	$ck = "<input type='checkbox' name='conf[]' value='$file'/>";
	$slabel = $file_state[$stat];
	switch ($stat) {
	case 'chg':
	    $slabel = "<a href='diff.php?pkgid=$id&file=$file' target='diff'>$slabel</a>";
	    break;
	case 'new':
	    $slabel = "<b>$slabel</b>";
	    break;
	}

	if ($vmode) {
	    $diff = $pkg->dbDiff($file);
	    if ($stat == 'extra') {
	    } elseif (empty($diff)) {
		$stat = 'same';
		$adiff = ($new)?$newpkg->getDiff($file):"";
		if (empty($adiff)) $file .= " =";
	    } elseif (count(preg_split('/\n/', $diff)<6) &&
		      preg_match('/\n .*\n-\n$/', $diff)) {
		$stat = 'same';
		$file .= " +";
	    } elseif ($new) {
		$adiff = $newpkg->getDiff($file);
		if ($adiff == $diff) {
		    $stat = 'mod';
		    $file .= " *";
		}
	    }
	    $ck = "";
	} else {
	    $ck = "<td class='ckbox'><input type='checkbox' name='conf[]' value='$file'/></td>";
	}
	$myfile = $pkg->getRealPath($file, false);
	echo "<tr class='$bg'>$ck";
	echo "<td>$slabel</td><td class='$stat'>$myfile</td></tr>\n";
    }
    echo "</table>\n";
    if (!$vmode) {
	echo "<input type='submit' name='accept' value='"._AM_REGIST_SUBMIT."'/>\n";
	echo "</form>\n";
    }
    if ($fm && count($files) && !$new) {
	echo "<hr/>".$fm;
    }
}

function modify_package() {
    global $xoopsDB, $myts;
    $pkg = new InstallPackage(intval($_POST['pkgid']));
    $n = 0;
    if (empty($_POST['conf'])) return $n;
    foreach ($_POST['conf'] as $path) {
	$path = $myts->stripSlashesGPC($path);
	if ($pkg->setModify($path)) $n++;
    }
    if ($n && file_exists(ROLLBACK)) unlink(ROLLBACK); // expired
    return $n;
}

function current_pkgs() {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pversion='HEAD' ORDER BY pkgid");
    $curpkg = array();
    while ($data = $xoopsDB->fetchArray($res)) {
	$curpkg[$data['pname']] = $data;
    }
    return $curpkg;
}

function reglist_packages() {
    global $xoopsDB;
    $curpkg = current_pkgs();

    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE parent=0 AND pversion<>'HEAD' ORDER BY pname,dtime DESC");

    if (!$res || $xoopsDB->getRowsNum($res)==0) return;

    echo "<h3>"._AM_REGIST_LIST."</h3>\n";
    echo "<form method='POST' name='RegPkg'>\n";
    echo "<table cellspacing='1' class='outer'>\n";
    echo "<tr><th></th><th>"._AM_PKG_PNAME."</th><th>"._AM_PKG_NAME.
	"</th><th>"._AM_PKG_VERSION."</th><th>"._AM_PKG_DTIME.
	"</th><th>"._AM_PKG_SOURCE."</th></tr>\n";
    $n = 0;
    $prename = '-';
    $pretime = 0;
    while ($data=$xoopsDB->fetchArray($res)) {
	$bg = $n++%2?'even':'odd';
	$pname = $data['pname'];
	if (isset($curpkg[$pname])) {
	    if ($curpkg[$pname]['name'] == $data['name']) $bg = 'up';
	}
	$ck = '';
	if ($pname != $prename || $bg == 'up') {
	    $prename = $pname;
	    $pretime = $data['dtime'];
	} elseif ($pretime > $data['dtime']) {
	    $ck = ' checked';
	}
	$pversion = $data['pversion'];
	$input = "<input type='checkbox' name='pid[".$data['pkgid']."]'$ck/>";
	$src = is_dir(XOOPS_UPLOAD_PATH."/update/source/$pname/$pversion")?_YES:_NO;
	echo "<tr class='$bg'><td>$input</td><td>".
	    htmlspecialchars($data['name']).
	    "</td><td>".htmlspecialchars($pname).
	    "</td><td>".htmlspecialchars($pversion).
	    "</td><td>".formatTimestamp($data['dtime']).
	    "</td><td>$src</td></tr>\n";
    }
    if ($pname) echo "</td></tr>\n";
    echo "</table>\n";
	echo "<table cellpadding='5'>
<tr><td>
 <input name='pkgdel' type='submit' value='"._DELETE."'/>
</td></tr>
</table>\n";
    echo "</form>\n";
}

function strtobytes($str) {
    if (preg_match('/^\d+K/i', $str)) return $str * 1024;
    if (preg_match('/^\d+M/i', $str)) return $str * 1024 * 1024;
    if (preg_match('/^\d+G/i', $str)) return $str * 1024 * 1024 * 1024;
    return intval($str);
}

function import_form() {
    $max = ini_get('upload_max_filesize');
    echo '
<form name="ImportForm" id="ImportForm" action="index.php" method="post" onsubmit="return xoopsFormValidate_ImportForm();" enctype="multipart/form-data">
<div>'._AM_PKG_FILEIMPORT.'
  <input name="MAX_FILE_SIZE" value="'.strtobytes($max).'" type="hidden">
  <input name="file" id="file" type="file" size="30">
  <input class="formButton" name="import" id="import" value="'._GO.'" type="submit">
  '._AM_IMPORT_FILE_MAX.' '.$max.' '._AM_BYTES.'
</div>
</form>

<!-- Start Form Vaidation JavaScript //-->
<script type="text/javascript">
<!--//
function xoopsFormValidate_ImportForm() {
    myform = window.document.ImportForm;
    if (myform.file.value=="") {
        window.alert("ファイルを指定してください");
        myform.file.focus();
        return false;
    }
    return true;
}
//--></script>';
    return;
}

function rollback_update() {
    $file = ROLLBACK;
    if (!file_exists($file)) return false;
    $base = XOOPS_ROOT_PATH;
    mysystem("rollback '$file' '$base'");
    package_expire();
    @unlink($file);
    return true;
}

function import_file() {
    $file = $_FILES['file']['name'];
    $temp = $_FILES['file']['tmp_name'];
    $dir = XOOPS_UPLOAD_PATH."/update/source";
    if (!is_dir($dir)) {
	$bdir = dirname($dir);
	if (!is_dir($bdir)) mkdir($bdir);
	mkdir($dir);
    }
    chdir($dir);
    if (preg_match('/\\.md5$/', $file)) return import_manifesto($temp);
    if (preg_match('/\\.tar\\.gz$/', $file)) return import_package($temp);
    return false;
}

function import_manifesto($file) {
    if (empty($file) || !file_exists($file)) return false;
    $pkg = new Package();
    if ($pkg->loadFile($file)) return $pkg->store();
    return false;
}

function import_package($file) {
    $fp = popen("tar tfz '$file' manifesto", "r");
    $manifesto = "";
    while ($ln = fgets($fp)) {
	if (preg_match('/\\.md5$/', $ln)) $manifesto = trim($ln);
    }
    pclose($fp);
    if (empty($manifesto)) {
	redirect_header("index.php?op=pkgs", 3, _AM_NODATA);
	exit;
    }
    system("tar xfz '$file'");
    import_manifesto($manifesto);
    return true;
}

function show_info_table($pkg) {
    $ppkg = new Package($pkg->getVar('parent'));
    $info = array('name'=>_AM_PKG_NAME, 'pname'=>_AM_PKG_PNAME,
		  'pversion'=>_AM_PKG_CURRENT, 'dtime'=>_AM_PKG_DTIME,
		  'ctime'=>_AM_PKG_CTIME, 'vcheck'=>_AM_PKG_DIRNAME);
    $buf = "<table cellspacing='0'>\n";
    $dirname = $pkg->getVar('vcheck');
    $module_handler =& xoops_gethandler('module');
    $module = $dirname?$module_handler->getByDirname($dirname):false;
    list($mver, $iver) = get_current_version($pkg->getVar('pname'), $pkg->getVar('vcheck'));
    $n = 0;
    foreach ($info as $name => $label) {
	$bg = $n++%2?"even":"odd";
	$val = $ppkg->getVar($name);
	if (preg_match('/time$/', $name)) {
	    $val = formatTimestamp($val);
	} elseif ($name == 'pversion') {
	    if ($iver != $val) $val .= " ($iver)";
	    if (is_object($module)) {
		$cver = $module->getVar('version')/100;
		if ($cver != $mver) $val .= " [$cver"._AM_UPDATE_TO."$mver]";
	    }
	} elseif ($name == 'vcheck') {
	    if ($dirname != $val) $val = "$dirname ($val)";
	}
	$val = htmlspecialchars($val);
	$buf .= "<tr class='$bg'><th>$label</th><td>$val</td></tr>\n";
    }
    return $buf."</table>\n";

}

function options_form() {
    $id = intval($_GET['pkgid']);
    $pkg = new InstallPackage($id);
    echo "<h2>".sprintf(_AM_OPTS_TITLE, $pkg->getVar('pname'))."</h2>\n";
    echo show_info_table($pkg);
    $options = $pkg->options;
    if (empty($options)) {
	echo _AM_OPTS_NONE;
	return;
    }
    echo _AM_OPTS_DESC;
    echo "<form method='post' name='Opts'>\n";
    $dirname = $pkg->getVar('vcheck');
    if ($dirname) {
	//echo "<div>"._AM_OPTS_RENAME." <input name='rename' value='$dirname'/></div>\n";
    }
    $checkall = "<input type='checkbox' id='allconf' name='allconf' onclick='xoopsCheckAll(\"Opts\", \"allconf\")'/>";
    echo "<table class='outer' border='0' cellspacing='1'>\n";
    echo "<tr><th align='center' class='ckbox'>$checkall</th><th>"._AM_OPTS_PATH."</th></tr>\n";
    $n = 0;
    foreach ($options as $path=>$v) {
	$ck = $v?" checked":"";
	$path = htmlspecialchars($path);
	$bg = $n++%2?"even":"odd";
	echo "<tr class='$bg'><td align='center' class='ckbox'><input type='checkbox' name='optdir[]' value='$path'$ck/></td><td>$path</td></td>\n";
    }
    echo "</table>\n";
    echo "<input type='hidden' name='pkgid' value='$id'/>\n";
    echo "<input type='submit' name='opts_select' value='"._SUBMIT."'/>\n";
    echo "</form>";
}

function options_setting() {
    global $myts;
    $id = intval($_POST['pkgid']);
    $pkg = new InstallPackage($id);
    $dirs = array();
    if (isset($_POST['optdir'])) {
	foreach ($_POST['optdir'] as $dir) {
	    $dirs[$myts->stripSlashesGPC($dir)]=true;
	}
    }
    $nchg = 0;
    foreach ($pkg->options as $path=>$v) {
	if ($v) {
	    $chg = empty($dirs[$path]);
	} else {
	    $chg = isset($dirs[$path]);
	}
	if ($chg) {
	    $pkg->setOptions($path, !$v);
	    $nchg++;
	}
    }
    if ($nchg) package_expire($pkg->getVar('pname'));
    return $nchg;
}
?>