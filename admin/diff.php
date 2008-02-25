<?php
# ScriptUpdate - Management
# $Id: diff.php,v 1.5 2008/02/25 15:09:56 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';

$myts =& MyTextSanitizer::getInstance();

$pkgid = intval($_GET['pkgid']);
$file = $myts->stripSlashesGPC($_GET['file']);
$type = isset($_GET['type'])?$_GET['type']:"";
$pkg = new InstallPackage($pkgid);
$reverse = false;
if (empty($pkg->dirname)) {	// future version?
    $res = $xoopsDB->query("SELECT vcheck FROM ".UPDATE_PKG." WHERE pversion='HEAD' AND pname=".$xoopsDB->quoteString($pkg->getVar('pname')));
    list($dirname) = $xoopsDB->fetchRow($res);
    $pkg->dirname = $pkg->getVar('vcheck');
    $pkg->setVar('vcheck', $dirname);
    $pkg->reverse = true;
}

$diff = $pkg->dbDiff($file);
if ($type == "raw") {
    $name = basename($file).".diff";
    header("Content-Type: text/plain; charset"._CHARSET);
    header("Content-Disposition: inline; filename=$name");
    echo $diff;
    exit;
}

$chg = $pkg->checkFile($file);
$atitle = htmlspecialchars($pkg->getVar('name').": ".$pkg->getRealPath($file, false));

header("Content-Type: text/html; charset"._CHARSET);
echo "<!DOCTYPE html PUBLIC '//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._LANGCODE.'" lang="'._LANGCODE.'">
<head>
<meta http-equiv="content-type" content="text/html; charset='._CHARSET.'" />
<title>'.$atitle.'</title>
</head>
<body>';
echo "<style>
.del { color: blue; }
.add { color: red; }
.info { color: grey; }
.msg { font-size: 14px; font-weight: bold; color: red; }
.chg { background-color: #fef; }
.db  { background-color: #eff; }
h1 { font-size: 16px; }
pre { font-size: 12px; padding: 2px;}
</style>\n";

$res = $xoopsDB->query("SELECT name FROM ".UPDATE_FILE.", ".UPDATE_DIFF.",
".UPDATE_PKG." WHERE path=".$xoopsDB->quoteString($file)." AND fileid=fileref AND pkgref=pkgid AND pkgid=".$pkgid);
list($name) = $xoopsDB->fetchRow($res);
$title = htmlspecialchars("$name: $file");

if ($chg) {
    echo "<h1>"._AM_FILE_DIFF." - $atitle</h1>";

    $adiff = $pkg->getDiff($file);
    if (empty($adiff)) echo "<p>"._AM_FILE_SAME."</p>";
    else if ($adiff == $diff) echo "<p>"._AM_FILE_SAMEDIFF."</p>";
    else {
	$bdiff = diff_str($diff, $adiff);
	if (strlen($bdiff)<strlen($adiff)) {
	    echo "<div class='msg'>"._AM_DIFF_DIFF." $name - ".$pkg->getVar('pversion')."</div>";
	    echo "<pre class='chg'>".colorDiff($bdiff)."</pre>";
	} else {
	    echo "<div class='msg'>"._AM_HAS_CHANGE.' - '.$pkg->getVar('pversion')."</div>";
	    if ($adiff) echo "<pre class='chg'>".colorDiff($adiff)."</pre>";
	}
    }
}

if ($diff!=false) {
    echo "<h1>"._AM_FILE_DBDIFF." - $title</h1>";

    if ($diff) {
	echo "<a href='diff.php?pkgid=$pkgid&file=$file&type=raw'>"._AM_DIFF_RAW."</a>";
	echo "<pre class='db'>".colorDiff($diff)."</pre>";
    } else {
	echo "<p>"._AM_FILE_SAME."</p>";
    }
}

echo "</body>
</html>";

function colorDiff($diff) {
    $diff = htmlspecialchars($diff);
    if (empty($diff)) return '';
    $out = '';
    foreach (preg_split('/\n/', $diff) as $ln) {
	switch (substr($ln, 0, 1)) {
	case '+': $out .= "<span class='add'>$ln</span>"; break;
	case '-': $out .= "<span class='del'>$ln</span>"; break;
	case '@': $out .= "<span class='info'>$ln</span>"; break;
	default:
	    $out .= $ln;
	}
	$out .= "\n";
    }
    return $out;
}
?>