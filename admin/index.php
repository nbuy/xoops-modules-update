<?php
# ScriptUpdate - Management
# $Id: index.php,v 1.6 2006/08/01 07:01:33 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';
include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';

$myts =& MyTextSanitizer::getInstance();

$op = isset($_GET['op'])?$_GET['op']:'';
$file_state = array('del'=>_AM_DEL, 'chg'=>_AM_CHG,
		    'new'=>_AM_NEW, 'ok'=>_AM_OK);
define('ROLLBACK', XOOPS_UPLOAD_PATH."/update/work/backup-rollback.tar.gz");

if (isset($_POST['import'])) {
    redirect_result(import_file(), 'index.php?op=pkgs', _AM_NODATAINFILE);
} elseif(isset($_POST['pkgdel'])) {
    redirect_result(delete_package(), 'index.php?op=pkgs');
} elseif(isset($_POST['regpkg'])) {
    redirect_result(reg_set_packages(), 'index.php');
} elseif(isset($_POST['accept'])) {
    redirect_result(modify_package(), 'index.php');
} elseif(isset($_POST['clear'])) {
    $pkgid = intval($_POST['pkgid']);
    redirect_result(clear_package($pkgid), 'index.php?op=detail&pkgid='.$pkgid);
} elseif ($op == 'rollback') {
    redirect_result(rollback_update(), 'index.php');
}

xoops_cp_header();
echo mystyle();
include 'mymenu.php';
switch ($op) {
default:
    check_packages();		// checking regsiterd packages
    break;

case 'regpkg':			// package managiment
    reg_packages();		// checking regsiterd packages
    break;

case 'pkgs':			// package managiment
    import_form();
    reglist_packages();
    $svr = get_update_server();
    if ($svr) {
	echo "<hr/>\n";
	echo "<a href='$svr'>"._AM_PKG_FETCH."</a>";
    }
    break;

case 'detail':
    $view = isset($_GET['view'])?$_GET['view']:false;
    $new = isset($_GET['new'])?intval($_GET['new']):0;
    detail_package(intval($_GET['pkgid']), $view, $new);
    break;
}
xoops_cp_footer();

// bind current installed and HEAD
function check_packages() {
    global $xoopsDB, $xoopsModuleConfig, $xoopsConfig;
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pversion='HEAD' ORDER BY pkgid");
    if (!$res) die($xoopsDB->error());
    $pkgs = get_packages('all', true);
    if ($xoopsDB->getRowsNum($res)==0) {
	redirect_header('index.php?op=regpkg', 1, _AM_PKG_REGISTER);
	return false;
    }

    echo "<h3>"._AM_CHECK_LIST."</h3>\n";
    echo "<table cellspacing='1' class='outer'>\n";
    echo "<tr><th>"._AM_PKG_PNAME."</th><th>"._AM_PKG_CURRENT."</th><th>"._AM_PKG_NEW."</th><th>"._AM_PKG_DTIME."</th><th>"._AM_CHANGES."</th><th></th></tr>\n";
    $n = 0;
    $update = false;	// find update package?
    $modify = false;
    $errors = array();
    while ($data = $xoopsDB->fetchArray($res)) {
	$pname = $data['pname'];
	$bg = $n++%2?'even':'odd';
	$id = $data['pkgid'];
	$newpkg = isset($pkgs[$pname])?$pkgs[$pname]:array();
	$newver = isset($newpkg['pversion'])?$newpkg['pversion']:'';
	$curver = get_current_version($pname, $newpkg['vcheck']);
	if (empty($data['parent']) ||
	    get_pkg_info($data['parent'], 'pversion')!=$curver) {
	    $par = import_new_package($pname, $curver);
	    if (!$par) {
		$errors[] = "$pname $curver: "._AM_PKG_NOTFOUND;
	    } else {
		$pid = $data['parent'] = $par->getVar('pkgid');
		$pnm = $data['name'] = $par->getVar('name');
		$ctm = $data['ctime'] = time();
		$xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET parent=$pid, name=".$xoopsDB->quoteString($pnm).", mtime=0, ctime=$ctm WHERE pkgid=$id");
	    }
	}
	if (!empty($data['parent'])) {
	    $pversion = get_pkg_info($data['parent'], 'pversion');
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
	if (isset($pkgs[$pname])) {
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

	echo "<tr class='$bg'><td>".htmlspecialchars($pname).
	    "</td><td>".htmlspecialchars($pversion).
	    "</td><td>".$newver.
	    "</td><td>$newdate</td><td align='right'>".
	    $count." ($mcount)</td><td>$op</td></tr>\n";
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
	$tm = _AM_UPDATE_TIME.' '.formatTimestamp($ctime, 'm');
	$expire = $ctime+$xoopsModuleConfig['cache_time'];
	$until = _AM_UPDATE_EXPIRE.' '.formatTimestamp($expire, 'H:i');
	echo "<table cellpadding='5'>
  <tr><td>
    <form action='index.php?op=rollback' method='post'>
    <input type='submit' value='"._AM_UPDATE_ROLLBACK."'></form></td>
    <td>$tm ($until)</td></tr>
</table>\n";
    }
    if ($errors) {
	echo "<br/><div class='errorMsg'>\n";
	foreach ($errors as $msg) {
	    echo "$msg<br/>\n";
	}
	echo "</div>\n";
    }
    echo "<hr/>";

    echo "<div><a href='index.php?op=regpkg'>"._AM_REG_PACKAGES."</a></div>";
}

function delete_package() {
    global $xoopsDB;
    chdir(XOOPS_UPLOAD_PATH."/update/source");
    if (empty($_POST['pid'])) return false;
    foreach ($_POST['pid'] as $pkgid=>$v) {
	$res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pkgid=".$pkgid);
	if ($res && $xoopsDB->getRowsNum($res)) {
	    $data = $xoopsDB->fetchArray($res);
	    $pname = $data['pname'];
	    $ver = $data['pversion'];
	    $xoopsDB->query("DELETE FROM ".UPDATE_PKG." WHERE pkgid=".$pkgid);
	    if (!empty($pname) && !empty($ver)) {
		$manifesto = "manifesto/$pname-$ver.md5";
		system("rm -rf '$pname/$ver' '$manifesto'");
		$xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE pkgref=".$pkgid);
	    }
	}
    }
    return true;
}

function reg_set_packages() {
    global $xoopsDB;
    $active = get_active_list();
    $pkgs = get_packages('all', true);
    if (file_exists(ROLLBACK)) unlink(ROLLBACK); // expired
    $ins = "INSERT INTO ".UPDATE_PKG."(pname, pversion, vcheck) VALUES(%s,'HEAD',%s)";
    $del = "DELETE FROM ".UPDATE_PKG." WHERE pkgid=%u";
    $sel = "SELECT pkgid FROM ".UPDATE_PKG." WHERE pname=%s AND pversion='HEAD'";
    $succ = 0;
    $pnames = isset($_POST['pname'])?$_POST['pname']:array();
    foreach ($pkgs as $pkg) {
	$pname=$pkg['pname'];
	$qtag = $xoopsDB->quoteString($pname);
	if (in_array($pname, $pnames)) { // checked
	    if (!isset($active[$pname])) {	// is change?
		$qchk = $xoopsDB->quoteString($pkg['vcheck']);
		if ($xoopsDB->query(sprintf($ins, $qtag, $qchk))) $succ++;
	    }
	} else {		// unchecked
	    if (isset($active[$pname])) {	// is change?
		$res = $xoopsDB->query(sprintf($sel, $qtag));
		if ($res && $xoopsDB->getRowsNum($res)) {
		    list($pkgid) = $xoopsDB->fetchRow($res);
		    
		    if ($xoopsDB->query(sprintf($del, $pkgid))) {
			$xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE pkgref=$pkgid");
			$succ++;
		    }
		}
	    }
	    //$xoopsDB->query("SELECT pkgref FROM ".UPDATE_DIFF." LEFT JOIN ".UPDATE_FILE." ON fileid=fileref WHERE fileid IS NULL");
	}
    }
    return $succ;
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
    $xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE pkgref=$pid");
    return true;
}

function detail_package($pid, $vmode=false, $new=0) {
    global $file_state;
    $pkg = new InstallPackage($pid);
    $title = $pkg->getVar('name');
    if ($new) {
	$newpkg = new Package($new);
	$title .= _AM_UPDATE_TO.$newpkg->getVar('name');
	$files = $pkg->checkFiles($newpkg);
	$id = $new;
	//$files = $pkg->checkPackage($newpkg);
    } else {
	if ($vmode) $files = $pkg->modifyFiles();
	else $files = $pkg->checkFiles();
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
    if (!$vmode) echo "<th align='center'>$checkall</th>";
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
	    if (empty($diff)) {
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
	    $ck = "<td><input type='checkbox' name='conf[]' value='$file'/></td>";
	}
	echo "<tr class='$bg'>$ck";
	echo "<td>$slabel</td><td class='$stat'>$file</td></tr>\n";
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

function get_active_list() {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT pname,name FROM ".UPDATE_PKG." WHERE pversion='HEAD'");
    $active = array();
    while (list($pname, $name) = $xoopsDB->fetchRow($res)) {
	$active[$pname] = $name;
    }
    return $active;
}

function reg_packages() {
    echo "<h3>"._AM_REG_PACKAGES."</h3>\n";
    echo "<p class='desc'>"._AM_REG_DESCRIPTION."</p>\n";
    $active = get_active_list();
    $pkgs = get_packages('all', true);
    if (count($pkgs)) {
	$input = "<input type='checkbox' name='pname[]' value='%s'%s/>";
	echo "<form method='POST' name='PackageSelect'>\n";
	echo "<table cellspacing='1' class='outer'>\n";
	$checkall = "<input type='checkbox' id='allpname' name='allpname' onclick='xoopsCheckAll(\"PackageSelect\", \"allpname\")'/>";
	echo "<tr><th align='center'>$checkall</th><th>"._AM_PKG_PNAME.
	    "</th><th>"._AM_PKG_VERSION.
	    "</th><th>"._AM_PKG_NAME.
	    "</th><th>"._AM_PKG_CTIME.
	    "</th></tr>\n";
	$n = 0;
	foreach ($pkgs as $pkg) {
	    $pname = $pkg['pname'];
	    $ck = isset($active[$pname])?" checked='checked'":"";
	    $ckbox = 
	    $bg = $n++%2?'even':'odd';
	    $qname = htmlspecialchars($pname);
	    if (empty($pkg['pversion'])) {
		$check = '-';
		$date = '';
	    } else {
		$check = sprintf($input, $qname, $ck);
		$date = formatTimestamp($pkg['dtime']);
	    }
	    echo "<tr class='$bg'><td align='center'>".
		$check."</td><td>".$qname."</td><td>".
		htmlspecialchars($pkg['pversion'])."</td><td>".
		htmlspecialchars($pkg['name'])."</td><td>".
		$date."</td></tr>\n";
	}
	echo "</table>\n";
	echo "<table cellpadding='5'>
<tr><td>
 <input name='regpkg' type='submit' value='"._AM_REG_SUBMIT."'/>
</td></tr>
</table>\n";
	echo "</form>\n";
    } else {
	echo _AM_PKG_GETLISTFAIL;
    }
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

    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE parent=0 AND pversion<>'HEAD' ORDER BY pname,ctime");

    if (!$res || $xoopsDB->getRowsNum($res)==0) return;

    echo "<h3>"._AM_REGIST_LIST."</h3>\n";
    echo "<form method='POST' name='RegPkg'>\n";
    echo "<table cellspacing='1' class='outer'>\n";
    echo "<tr><th></th><th>"._AM_PKG_PNAME."</th><th>"._AM_PKG_NAME.
	"</th><th>"._AM_PKG_VERSION."</th><th>"._AM_PKG_DTIME.
	"</th><th>"._AM_PKG_SOURCE."</th></tr>\n";
    $n = 0;
    while ($data=$xoopsDB->fetchArray($res)) {
	$bg = $n++%2?'even':'odd';
	$pname = $data['pname'];
	$pversion = $data['pversion'];
	if (isset($curpkg[$pname])) {
	    if ($curpkg[$pname]['name'] == $data['name']) $bg = 'up';
	}
	$input = "<input type='checkbox' name='pid[".$data['pkgid']."]'/>";
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
	$bdir = basename($dir);
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

function redirect_result($ret, $dest='', $err=_AM_DBUPDATE_FAIL) {
    if (empty($dest)) $dist = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'index.php';
    if ($ret) {
	redirect_header($dest, 1, _AM_DBUPDATED);
    } else {
	redirect_header($dest, 3, $err);
    }
    exit;
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
    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
    $snoopy = new Snoopy;
    $snoopy->lastredirectaddr = 1;
    $pkg = false;
    if ($snoopy->fetch($url)) {
	$content =& $snoopy->results;
	if (empty($content) || preg_match('/^\s*</', $content)) return false;
	$pkg = new Package();
	$pkg->loadStr($content);
	$pkg->store();
    }
    return $pkg;
}
?>