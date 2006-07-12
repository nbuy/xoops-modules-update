<?php
# ScriptUpdate - Management
# $Id: index.php,v 1.2 2006/07/12 18:33:56 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';
include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';

$myts =& MyTextSanitizer::getInstance();

$op = isset($_GET['op'])?$_GET['op']:'';
$file_state = array('del'=>_AM_DEL, 'chg'=>_AM_CHG, 'ok'=>_AM_OK);

if (isset($_POST['import'])) {
    $ret = import_manifesto($_FILES['file']['tmp_name']);
    redirect_result($ret, '', _AM_NODATAINFILE);
} elseif(isset($_POST['pkgreg'])) {
    redirect_result(register_packages(), 'index.php');
} elseif(isset($_POST['accept'])) {
    redirect_result(modify_package(), 'index.php');
//} elseif(isset($_POST['import'])) {
}

xoops_cp_header();
include 'mymenu.php';
switch ($op) {
default:
    check_packages();		// checking regsiterd packages
    break;
case 'update':			// execute package updating
    update_packages();
    break;
case 'pkgreg':			// package managiment
    list_packages();		// checking regsiterd packages
    break;
case 'pkgs':			// package managiment
    reglist_packages();
    import_form();
    break;
case 'detail':
    $view = isset($_GET['view'])?$_GET['view']:false;
    $new = isset($_GET['new'])?intval($_GET['new']):0;
    detail_package(intval($_GET['pkgid']), $view, $new);
    break;

    echo "<p>"._AM_MASTER_URL." ".htmlspecialchars($url)."</p>\n";
    $verb = isset($_GET['verb']);
    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
    $snoopy = new Snoopy;
    if ($snoopy->fetch($url)) {
	echo "<style>.ng {color: #c00; font-weight: bold; } .ok { color: #0c0; }</style>";
	$func = function_exists('sha1')?'sha1_file':'md5_file';
	foreach (preg_split('/\n/', $snoopy->results) as $ln) {
	    if (empty($ln)) continue;
	    list($sum, $file) = preg_split('/\\s+/', $ln);
	    $path=XOOPS_ROOT_PATH.'/'.$file;
	}
    }
    break;
}
xoops_cp_footer();

// bind current installed and HEAD
function assign_empty_package(&$data) {
    global $xoopsDB;
    switch ($data['vcheck']) {
    case 'xoops':
	$pversion = preg_replace('/ /', '-', preg_replace('/^XOOPS\s*/', '', XOOPS_VERSION));
	break;
    case 'module':
	// find 'xoops_version.php' from update_file and check.
	break;
    }
    if (empty($pversion)) return '';
    $res = $xoopsDB->query("SELECT pkgid,name,dtime FROM ".UPDATE_PKG." WHERE parent=0 AND pversion=".$xoopsDB->quoteString($pversion));
    if ($res && $xoopsDB->getRowsNum($res)==1) { // ok there is
	list($id, $name, $dt) = $xoopsDB->fetchRow($res);
	$data['ctime']= $now = time();
	$data['dtime']=$dt;
	$data['mtime']=0;
	$data['name']=$name;
	$xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET name=".$xoopsDB->quoteString($name).", parent=$id, ctime=$now, dtime=$dt, mtime=0 WHERE pkgid=".$data['pkgid']);
    } else {			// not found, try fetch manifesto from server
	$name = "";
    }
    return $name;
}

function check_packages() {
    global $xoopsDB, $xoopsModuleConfig, $xoopsConfig;
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pversion='HEAD' ORDER BY pkgid");
    if (!$res) die($xoopsDB->error());
    $pkgs = get_packages('all', true);
    if ($xoopsDB->getRowsNum($res)==0) {
	redirect_header('index.php?op=pkgreg', 1, _AM_PKG_REGISTER);
	return false;
    }

    echo "<h3>"._AM_CHECK_LIST."</h3>\n";
    echo "<style><!--
.up td { background-color: #fcc; padding: 5px; }
--></style>";
    echo "<table cellspacing='1' class='outer'>\n";
    echo "<tr><th>"._AM_PKG_PNAME."</th><th>"._AM_PKG_CURRENT."</th><th>"._AM_PKG_NEW."</th><th>"._AM_PKG_DTIME."</th><th>"._AM_CHANGES."</th><th></th></tr>\n";
    $n = 0;
    $update = false;	// find update package?
    $modify = false;
    $errors = array();
    while ($data = $xoopsDB->fetchArray($res)) {
	$pkg = new InstallPackage($data);
	$pname = $pkg->getVar('pname');
	$bg = $n++%2?'even':'odd';
	$id = $pkg->getVar('pkgid');
	$pversion = '-';
	$ver = $pkg->getVar('pversion');
	$newpkg = $pkgs[$pname];
	if (empty($data['parent']) || $ver != $newpkg['pversion']) {
	    $newver = get_current_version($pname, $newpkg['vcheck']);
	    if (!$newver) {
		$par = import_new_package($pname, $newpkg['pversion']);
	    }
	    $par = import_new_package($pname, $newver);
	    if (!$par) {
		$errors[] = "$pname $newver: "._AM_PKG_NOTFOUND;
	    } elseif ($par->getVar('pkgid') && empty($data['parent'])) {
		$pkg->setVar('parent', $par->getVar('pkgid'));
		$pkg->setVar('name', $par->getVar('name'));
		$pkg->setVar('ctime', time());
		$pkg->setVar('mtime', 0);
		$pkg->store();
	    }
	}
	if ($pkg->getVar('parent')) {
	    $pkg->load();
	    $pversion = $pkg->getVar('origin');
	    $curver = get_current_version($pname, $pkg->getVar('vcheck'));
	    if ($curver != $pversion) {
		// change current version
		$par = import_new_package($pname, $curver);
		if (!$par) {
		    $errors[] = "$pname $newver: "._AM_PKG_NOTFOUND;
		} else {
		    $pkg->setVar('parent', $par->getVar('pkgid'));
		    $pkg->setVar('name', $par->getVar('name'));
		    $pkg->setVar('ctime', time());
		    $pkg->setVar('mtime', 0);
		    $pkg->store();
		    $pkg->load($pkg->getVar('pkgid'));
		}
	    }
	    $past = time()-$data['mtime'];
	    if ($past>$xoopsModuleConfig['cache_time']) {
		$count = count($pkg->checkFiles());
		if (!$count) $xoopsDB->queryF("UPDATE ".UPDATE_PKG." SET mtime=".time()." WHERE pkgid=".$id);
	    } else {
		$count = 0;
	    }
	    $opt = "&pkgid=$id";
	    if (!$count) $opt .= "&view=yes";
	    $op = "<a href='index.php?op=detail$opt'>".($count?_AM_MODIFY:_AM_DETAIL)."</a>";
	} else {		// no manifesto
	    $op = "";
	    $count = 0;
	}
	if ($count) $modify = true;
	if (isset($pkgs[$pname])) {
	    $new =& $pkgs[$pname];
	    $newver = $new['pversion'];
	    $newdate = formatTimestamp($new['dtime'], "m");
	} else {
	    $newver = '-';
	    $newdate = '-';
	}
	$mcount = count($pkg->modifyFiles());
	if ($pversion != $newver) {
	    $bg = 'up';
	    $uppkg = import_new_package($pname, $newver);
	    $pid = $uppkg->getVar('pkgid');
	    $newver = "<a href='index.php?op=detail&pkgid=$id&new=$pid&view=yes'>".htmlspecialchars($newver)."</a>";
	    $update = true;
	} else {
	    $newver = htmlspecialchars($newver);
	}
	echo "<tr class='$bg'><td>".htmlspecialchars($pname).
	    "</td><td>".htmlspecialchars($pversion).
	    "</td><td>".$newver.
	    "</td><td>$newdate</td><td align='right'>".
	    $count." ($mcount)</td><td>$op</td></tr>\n";
    }
    echo "</table>\n";
    if ($update && !$modify) {
	echo "<form action='index.php?op=update' method='post'>
<input type='submit' value='"._AM_UPDATE_PKGS."'>
</form>\n";
    }
    if ($errors) {
	echo "<br/><div class='errorMsg'>\n";
	foreach ($errors as $msg) {
	    echo "$msg<br/>\n";
	}
	echo "</div>\n";
    }
    echo "<hr/>";

    echo "<a href='index.php?op=pkgreg'>"._AM_PKG_REGISTER."</a>";
}

function update_packages() {
    global $xoopsDB, $xoopsModuleConfig, $xoopsConfig;
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pversion='HEAD' ORDER BY pkgid");
    if (!$res) die($xoopsDB->error());
    $pkgs = get_packages('all', true);
    if ($xoopsDB->getRowsNum($res)) {
	echo "<h3>"._AM_UPDATE_PKGS."</h3>\n";
	$n = 0;
	while ($data = $xoopsDB->fetchArray($res)) {
	    $pkg = new InstallPackage($data);
	    $pkg->load();
	    $pname = $pkg->getVar('pname');
	    $bg = $n++%2?'even':'odd';
	    $newpkg = new Package($pname, $pkgs[$pname]['pversion']);
	    if ($pkg->getVar('parent')!=$newpkg->getVar('pkgid')) {
		$pkg->updatePackage($newpkg);
		$zip = "<a href='pack.php?p=".htmlspecialchars($pkg->getVar('pname').'/new-'.$newpkg->getVar('pversion'))."'>"._AM_UPDATE_NEWZIP."</a>";
		echo "<div>Update ".$pkg->getVar('name')." to ".$newpkg->getVar('pversion')." ($zip)<div>";
	    } else {		// no manifesto
		echo "<div>Skip: $pname ".$newpkg->getVar('pversion')."<div>";
	    }
	}
    }
}

function register_packages() {
    global $xoopsDB;
    $active = get_active_list();
    $pkgs = get_packages('all', true);
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

function detail_package($pid, $vmode=false, $new=0) {
    global $file_state;
    $pkg = new InstallPackage($pid);
    $name = $pkg->getVar('name');
    if ($new) {
	$newpkg = new InstallPackage($new);
	$name = $newpkg->getVar('name');
	$files = $newpkg->checkFiles();
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
    echo "<style>
.chg { color: #c00; } 
.same { color: #00c; } 
.mod { color: #f08; }
</style>";
    echo "<h3>".$pkg->getVar('name')."</h3>";
    if (!$vmode) {
	echo "<form method='POST' name='FileMark'>\n";
	echo "<input type='hidden' name='pkgid' value='$pid'/>\n";
    }
    $dels = count(array_keys($files, 'del'));
    if ($dels && $vmode) {
	$sw = " &nbsp; <a href='index.php?op=detail&pkgid=$pid&view=";
	if ($vmode=='yes') $sw .= "all'>"._AM_VIEW_DEL;
	else $sw .= "yes'>"._AM_VIEW_CHG;
	$sw .= "</a>";
    } else $sw = "";
    echo "<div>"._AM_FILE_ALL." ".count($pkg->files)." &nbsp; ".
	_AM_CHG." ".count(array_keys($files, 'chg'))." &nbsp; ".
	_AM_DEL." ".$dels.$sw."</div>\n";
    echo "<table cellspacing='1' class='outer'>\n";
    $checkall = "<input type='checkbox' id='allconf' name='allconf' onclick='xoopsCheckAll(\"FileMark\", \"allconf\")'/>";
    echo "<tr>";
    if (!$vmode) echo "<th align='center'>$checkall</th>";
    echo "<th>"._AM_STATUS."</th><th>"._AM_FILE."</th></tr>\n";
    $n = 0;
    foreach ($files as $file=>$stat) {
	$bg = $n++%2?'even':'odd';
	$ck = "<input type='checkbox' name='conf[]' value='$file'/>";
	if ($vmode=='yes' && $stat=='del') continue;
	$slabel = $file_state[$stat];
	if ($stat=='chg') {
	    $slabel = "<a href='diff.php?pkgid=$id&file=$file' target='diff'>$slabel</a>";
	}

	if ($vmode) {
	    $diff = $pkg->dbDiff($file);
	    if (empty($diff)) {
		$stat = 'same';
		$file .= " =";
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
	echo "<input type='submit' name='accept' value='"._SUBMIT."'/>\n";
	echo "</form>\n";
    }
}

function modify_package() {
    global $xoopsDB, $myts;
    $pkg = new InstallPackage(intval($_POST['pkgid']));
    $n = 0;
    foreach ($_POST['conf'] as $path) {
	$path = $myts->stripSlashesGPC($path);
	if ($pkg->setModify($path)) $n++;
    }
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

function get_packages($pname='all', $idx=false) {
    global $xoopsModuleConfig;
    $url = $xoopsModuleConfig['update_server']."list.php?pkg=$pname";
    $list = file_get_url($url);
    $pkgs = array();
    foreach (preg_split('/\n/', $list) as $ln) {
	$ln = trim($ln);
	if (empty($ln)) continue;
	$F = preg_split('/,/', trim($ln), 5);
	$pname = $F[0];
	$pkg = array('pname'=> $pname,
		     'pversion'=> $F[1],
		     'dtime'=> strtotime($F[2]),
		     'vcheck'=> $F[3],
		     'name'=> $F[4]);
	if ($idx) $pkgs[$pname] = $pkg;
	else $pkgs[] = $pkg;
    }
    return $pkgs;
}

function list_packages() {
    $active = get_active_list();
    $pkgs = get_packages();
    if (count($pkgs)) {
	$input = "<input type='checkbox' name='pname[]' value='%s'%s/>";
	echo "<form method='POST' name='PackageSelect'>\n";
	echo "<table cellspacing='1' class='outer'>\n";
	echo "<tr><th></th><th>"._AM_PKG_PNAME.
	    "</th><th>"._AM_PKG_VERSION.
	    "</th><th>"._AM_PKG_NAME.
	    "</th><th>"._AM_PKG_CTIME.
	    "</th></tr>\n";
	$n = 0;
	foreach ($pkgs as $pkg) {
	    $pname = $pkg['pname'];
	    $ck = isset($active[$pname])?" checked":"";
	    $ckbox = 
	    $bg = $n++%2?'even':'odd';
	    $qname = htmlspecialchars($pname);
	    echo "<tr class='$bg'><td align='center'>".
		sprintf($input, $qname, $ck)."</td><td>$qname</td><td>".
		htmlspecialchars($pkg['pversion'])."</td><td>".
		htmlspecialchars($pkg['name'])."</td><td>".
		formatTimestamp($pkg['dtime'])."</td></tr>\n";
	}
	echo "</table>\n";
	echo "<input name='pkgreg' type='submit' value='"._SUBMIT."'/>";
	echo "</form>\n";
    } else {
	echo _AM_PKG_GETLISTFAIL;
    }
}

function reglist_packages() {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE parent=0 AND pversion<>'HEAD' ORDER BY pname,ctime");

    if (!$res || $xoopsDB->getRowsNum($res)==0) return;

    echo "<form method='POST' name='RegPkg'>\n";
    echo "<table cellspacing='1' class='outer'>\n";
    echo "<tr><th>"._AM_PKG_PNAME."</th><th>"._AM_PKG_VERSION."</th></tr>\n";
    $n = 0;
    $pname = "";
    while ($data=$xoopsDB->fetchArray($res)) {
	if (empty($pname) || $pname!=$data['pname']) {
	    if ($pname) echo "</td></tr>\n";
	    $pname = $data['pname'];
	    $bg = $n++%2?'even':'odd';
	    echo "<tr class='$bg'><td>".htmlspecialchars($pname)."</td><td>";
	}
	echo "<div>".htmlspecialchars($data['pversion'])."</div>";
    }
    if ($pname) echo "</td></tr>\n";
    echo "</table>\n";
    echo "<input name='pkgreg' type='submit' value='"._SUBMIT."'/>";
    echo "</form>\n<hr/>\n";
}

function import_form() {
    $form = new XoopsThemeForm(_AM_PKG_FILEIMPORT, 'ImportForm', 'index.php');
    $form->setExtra('enctype="multipart/form-data"');
    $form->addElement(new XoopsFormFile(_AM_IMPORT_FILE, 'file', 500000));
    $form->addElement(new XoopsFormButton('' , 'import', _SUBMIT, 'submit'));
    $form->display();
}

function import_manifesto($file) {
    if (empty($file) || !file_exists($file)) return false;
    $pkg = new Package();
    $pkg->loadFile($file);
    return $pkg->store();
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

function file_get_url($url) {
    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
    $snoopy = new Snoopy;
    $snoopy->lastredirectaddr = 1;
    $cache = XOOPS_CACHE_PATH.'/update'.md5($url);
    if (file_exists($cache) && (time()-filemtime($cache))<3600) {
	return file_get_contents($cache);
    }
    if ($snoopy->fetch($url)) {
	$content = $snoopy->results;
	if (empty($content)) return false;
	$fp = fopen($cache, "w");
	fwrite($fp, $content);
	fclose($fp);
	return $content;
    }
    return false;
}

function import_new_package($pname, $ver) {
    global $xoopsModuleConfig, $xoopsDB;
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pname=".
$xoopsDB->quoteString($pname)." AND pversion=".$xoopsDB->quoteString($ver));
    if ($res && $xoopsDB->getRowsNum($res)>0) {
	return new Package($xoopsDB->fetchArray($res));
    }
    $url = $xoopsModuleConfig['update_server']."manifesto.php?pkg=".urlencode($pname)."&v=".urlencode($ver);
    require_once XOOPS_ROOT_PATH.'/class/snoopy.php';
    $snoopy = new Snoopy;
    $snoopy->lastredirectaddr = 1;
    $pkg = false;
    if ($snoopy->fetch($url)) {
	if (!empty($snoopy->results)) {
	    $pkg = new Package();
	    $pkg->loadStr($snoopy->results);
	    $pkg->store();
	}
    }
    return $pkg;
}
?>