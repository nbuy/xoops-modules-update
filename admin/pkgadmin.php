<?php
# package bind administrator
# $Id: pkgadmin.php,v 1.6 2007/07/16 05:18:30 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';
include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';

$myts =& MyTextSanitizer::getInstance();
$op = isset($_GET['op'])?$_GET['op']:'';

if(isset($_POST['regpkg'])) {
    redirect_result(reg_set_packages(), 'index.php');
} elseif(isset($_POST['custom'])) {
    redirect_result(register_detail($myts->stripSlashesGPC($_POST['pname']),
				    $myts->stripSlashesGPC($_POST['dirname'])),
		    'pkgadmin.php');
}

xoops_cp_header();

echo mystyle();
include 'mymenu.php';

switch ($op) {
default:
case 'regpkg':			// package managiment
    list_packages();		// checking regsiterd packages
    break;
case 'detail':			// set detail package
    setup_package($myts->stripSlashesGPC($_GET['dir']));
    break;
}
xoops_cp_footer();

function get_active_list() {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT pkgid, vcheck FROM ".UPDATE_PKG." WHERE pversion='HEAD'");
    $active = array();
    while (list($id, $dirname) = $xoopsDB->fetchRow($res)) {
	$active[$id] = $dirname;
    }
    return $active;
}

function setup_package($dirname) {
    $pkgs = new PackageList;
    $pkgs->load();

    $pkg = $pkgs->getVar($dirname);
    if (empty($pkg)) $pkg = $pkgs->selectPackage($dirname);
    $pname = $pkg?$pkg['pname']:_AM_PKG_NOCURRENT; // current package-name
    if ($dirname) {
	$modversion = get_modversion($dirname);
	if (empty($modversion)) return; // illigal dirname?
	$title = $modversion['name']." ($dirname)";
    } else {
	$title = XOOPS_VERSION;
    }
    echo "<h1>".htmlspecialchars($title)."</h1>\n";
    echo "<table class='outer' border='0' cellspacing='1'>\n";
    $n = 0;
    $form = new XoopsThemeForm(_AM_REG_DETAIL, "CustomForm", 'pkgadmin.php');
    $form->addElement(new XoopsFormHidden('dirname', $dirname));
    $form->addElement(new XoopsFormLabel( _AM_PKG_NAME, $pkg['name']));
    $form->addElement(new XoopsFormLabel( _AM_PKG_DIRNAME, $dirname));
    $form->addElement(new XoopsFormLabel( _AM_PKG_CURRENT_PNAME, $pname));

    $select = new XoopsFormSelect(_AM_PKG_PNAME, 'pname');
    foreach ($pkgs->getAllPackages() as $pn=>$apkg) {
	$select->addOption($pn);
    }
    $select->setValue($pname);
    $form->addElement($select);
    $form->addElement(new XoopsFormButton("", "custom", _AM_REG_SUBMIT , "submit"));
    echo $form->render();
}

function list_packages() {
    echo "<h3>"._AM_REG_PACKAGES."</h3>\n";
    echo "<p class='desc'>"._AM_REG_DESCRIPTION."</p>\n";
    $active = get_active_list();
    $pkgs = get_packages('all');
    if (count($pkgs)) {
	$input = "<input type='checkbox' name='pname[]' value='%s'%s/>";
	echo "<form method='POST' name='PackageSelect'>\n";
	echo "<table cellspacing='1' class='outer'>\n";
	$checkall = "<input type='checkbox' id='allpname' name='allpname' onclick='xoopsCheckAll(\"PackageSelect\", \"allpname\")'/>";
	echo "<tr><th align='center'>$checkall</th><th>"._AM_PKG_PNAME.
	    "</th><th>"._AM_PKG_VERSION.
	    "</th><th>"._AM_PKG_NAME.
	    "</th><th>"._AM_PKG_DIRNAME.
	    "</th><th>"._AM_PKG_CTIME.
	    "</th><th></th></tr>\n";
	$n = 0;
	foreach ($pkgs as $dirname => $pkg) {
	    if (!is_dir(XOOPS_ROOT_PATH."/modules/".$dirname)) continue;
	    $pname = $pkg['pname'];
	    $bg = $n++%2?'even':'odd';
	    $qname = htmlspecialchars($pname);
	    $ck =array_search($pkg['vcheck'], $active)?" checked='checked'":"";
	    $date = empty($pkg['dtime'])?"":formatTimestamp($pkg['dtime']);
	    if (empty($pkg['pversion']) && empty($ck)) {
		$check = '-';
	    } else {
		$id = htmlspecialchars($dirname);
		$check = sprintf($input, $id, $ck);
	    }
	    if (isset($pkg['vhash'])) $qname .= " (*)";
	    echo "<tr class='$bg'><td align='center'>".
		$check."</td><td>$qname</td><td>".
		htmlspecialchars($pkg['pversion'])."</td><td>".
		htmlspecialchars($pkg['name'])."</td><td>$dirname</td><td>".
		$date."</td><td><a href='?op=detail&dir=$dirname'>"._AM_DETAIL."</a></td></tr>\n";
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

function reg_set_packages() {
    global $xoopsDB;
    $active = get_active_list();
    $pkgs = get_packages('all');
    if (file_exists(ROLLBACK)) unlink(ROLLBACK); // expired
    $del = "DELETE FROM ".UPDATE_PKG." WHERE pkgid=%u";
    $sel = "SELECT pkgid FROM ".UPDATE_PKG." WHERE vcheck=%s AND pversion='HEAD'";
    $succ = 0;
    $pnames = isset($_POST['pname'])?$_POST['pname']:array();
    foreach ($pkgs as $pkg) {
	$dirname = $pkg['vcheck'];
	if (array_search($dirname, $active)) {
	    $res = $xoopsDB->query(sprintf($sel, $xoopsDB->quoteString($dirname)));
	    $pkgid = 0;
	    if ($xoopsDB->getRowsNum($res) > 0) {
		list($pkgid) = $xoopsDB->fetchRow($res);
	    }
	    $pname=$pkg['pname'];
	    if ($pkgid && !in_array($dirname, $pnames)) { // unchecked? (removed)
		if ($xoopsDB->query(sprintf($del, $pkgid))) {
		    $xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE pkgref=$pkgid");
		    $succ++;
		}
	    }
	    clean_pkginfo();	// garbege collection
	}
    }
    $ins = "INSERT INTO ".UPDATE_PKG."(parent,pname,pversion,vcheck) VALUES(%u,%s,'HEAD',%s)";
    foreach ($pnames as $dirname) {
	if (!in_array($dirname, $active)) {
	    if (isset($pkgs[$dirname])) {
		$pkg = $pkgs[$dirname];
	    } else {
		$pkg = PackageList::selectPackage($dirname);
	    }
	    $par = empty($pkg['pkgid'])?0:$pkg['pkgid'];
	    if (register_detail($pkg['pname'], $dirname)) $succ++;
	}
    }
    if ($succ) clear_get_cache(0, 'list');
    return $succ;
}

function register_detail($pname, $dirname) {
    global $xoopsDB;
    $qdir = $xoopsDB->quoteString($dirname);
    $res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE vcheck=$qdir AND pversion='HEAD'");
    if ($xoopsDB->getRowsNum($res)) { // exist current
	$pkg = $xoopsDB->fetchArray($res);
	if ($pkg['pname']==$pname) return false; // unchanged
	clean_pkginfo($pkg['pkgid']);
    }
    $qname = $xoopsDB->quoteString($pname);
    $curver = get_current_version($pname, $dirname);
    $par = import_new_package($pname, $curver[1]);
    $pid = $par?$par->getVar('pkgid'):0;
    $name = $xoopsDB->quoteString($par?$par->getVar('name'):'');
    return $xoopsDB->query("INSERT INTO ".UPDATE_PKG."(pname,pversion,vcheck,parent,name,ctime) VALUES($qname,'HEAD',$qdir,$pid,$name,".time().")");
}

function clean_pkginfo($pkgid=0) {
    global $xoopsDB;
    if ($pkgid) {
	$xoopsDB->query("DELETE FROM ".UPDATE_PKG." WHERE pkgid=$pkgid");
	$xoopsDB->query("DELETE FROM ".UPDATE_FILE." WHERE pkgref=$pkgid");
    }
    $res = $xoopsDB->query("SELECT fileref FROM ".UPDATE_DIFF." LEFT JOIN ".UPDATE_FILE." ON fileid=fileref WHERE fileid IS NULL");
    $gids = array();
    while (list($id)=$xoopsDB->fetchRow($res)) {
	$gids[] = $id;
    }
    $xoopsDB->query("DELETE FROM ".UPDATE_DIFF." WHERE fileref IN (".join(',', $gids).")");
}

?>