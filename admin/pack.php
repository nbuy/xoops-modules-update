<?php
# ScriptUpdate - get packed file/do update
# $Id: pack.php,v 1.7 2007/10/29 08:20:33 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';

$res = $xoopsDB->query("SELECT * FROM ".UPDATE_PKG." WHERE pversion='HEAD' ORDER BY pkgid");
if (!$res) die($xoopsDB->error());
$pkgs = get_packages('all', true);
$buf = "";
$pkgn = 0;
$op = isset($_GET['op'])?$_GET['op']:'update';
$date = formatTimestamp(time(),"Ymd");
$updatedir = "update-$date";
$backupdir = "backup-$date";
ob_start();
if ($xoopsDB->getRowsNum($res)) {
    // force clean work files
    $work = XOOPS_UPLOAD_PATH."/update/work";
    if (is_dir("$work/$updatedir")) system("rm -rf '$work/$updatedir'");
    if (is_dir("$work/$backupdir")) system("rm -rf '$work/$backupdir'");

    while ($data = $xoopsDB->fetchArray($res)) {
	$pkg = new InstallPackage($data);
	$pkg->load();
	$pname = $pkg->getVar('pname');
	$newpkg = new Package($pname, $pkgs[$pkg->dirname]['pversion']);
	if ($pkg->getVar('parent')!=$newpkg->getVar('pkgid')) {
	    if ($op == 'exec') {
		$pkg->backupPackage($newpkg, $backupdir);
		$pkg->updatePackage($newpkg, $updatedir);
		package_expire($pname);
	    } elseif ($op == 'update') {
		$pkg->updatePackage($newpkg, $updatedir);
	    } else {
		$pkg->backupPackage($newpkg, $backupdir);
	    }
	    $pkgn++;
	}
    }
}
$content = ob_get_contents();
ob_end_clean();

if ($pkgn==0) {
    xoops_cp_header();
    echo "<h3>"._AM_UPDATE_PKGS."</h3>\n";
    echo _AM_NOUPDATE;
    xoops_cp_footer();
    exit;
} elseif ($content) {
    xoops_cp_header();
    echo "<h3>"._AM_UPDATE_PKGS."</h3>\n";
    echo $content;
    xoops_cp_footer();
    exit;
}

chdir(XOOPS_UPLOAD_PATH."/update/work");

switch ($op) {
case 'exec':
    system("tar cfCz 'backup-rollback.tar.gz' '$backupdir' .; rm -rf '$backupdir'");
    $updatetrust = $updatedir."/XOOPS_TRUST_PATH";
    if (is_dir($updatetrust)) {	// exists XOOPS_TRUST_PATH
	$trust = XOOPS_TRUST_PATH;
	$out = mysystem("copy '$updatetrust' '$trust'");
	system("rm -rf '$updatetrust'");
    }
    $base = XOOPS_ROOT_PATH;
    $out = mysystem("copy '$updatedir' '$base'");
    system("rm -rf '$updatedir'");
    if (empty($out)) {
	$go = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"index.php";
	redirect_header($go, 1, _AM_UPDATE_SUCC);
    } else {
	xoops_cp_header();
	echo "<h3>"._AM_UPDATE_PKGS."</h3>\n";
	echo _AM_UPDATE_ERROR;
	echo "<p>"._AM_UPDATE_ERROR_DESC."</p>";
	echo "<pre>".htmlspecialchars($out)."</pre>";
	xoops_cp_footer();
    }
    exit;
case 'update':
    $dirname = $updatedir;
    break;
default:
    $dirname = $backupdir;
    break;
}
$fp = popen("tar cfz - '$dirname'", "r");

header("Content-type: application/octet-stream");
header("Content-Disposition: inline; filename=$dirname.tar.gz");
header("Cache-Control: public");
header("Pragma: public");

while (! feof($fp)) {
    echo fread($fp, 4096);
}
pclose($fp);
system("rm -rf '$dirname'");
?>