<?php
# ScriptUpdate - Management
# $Id: diff.php,v 1.2 2006/07/12 18:33:55 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';

$myts =& MyTextSanitizer::getInstance();

$pkgid = intval($_GET['pkgid']);
$file = $myts->stripSlashesGPC($_GET['file']);
$pkg = new InstallPackage($pkgid);

$chg = $pkg->checkFile($file);
$atitle = htmlspecialchars($pkg->getVar('name').": $file");

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
".UPDATE_PKG." WHERE path=".$xoopsDB->quoteString($file)." AND fileid=fileref AND pkgref=pkgid");
list($name) = $xoopsDB->fetchRow($res);
$title = htmlspecialchars("$name: $file");

$diff = $pkg->dbDiff($file);

if ($chg) {
    echo "<h1>"._AM_FILE_DIFF." - $atitle</h1>";

    $adiff = $pkg->getDiff($file);
    if ($adiff == $diff) echo "<p>"._AM_FILE_SAMEDIFF."</p>";
    else {
	$ddif = &new Text_Diff(split("\n", $diff), split("\n", $adiff));
	$renderer = &new Text_Diff_Renderer_unified();
	$bdiff = $renderer->render($ddif);
	if (strlen($bdiff)<strlen($adiff)) {
	    echo "<div class='msg'>"._AM_DIFF_DIFF." $name - ".$pkg->getVar('pversion')."</div>";
	    echo "<pre class='chg'>".colorDiff($bdiff)."</pre>";
	} else {
	    echo "<div class='msg'>"._AM_HAS_CHANGE.' - '.$pkg->getVar('pversion')."</div>";
	    if ($adiff) echo "<pre class='chg'>".colorDiff($adiff)."</pre>";
	    else echo "<p>"._AM_FILE_SAME."</p>";
	}
    }
}

echo "<h1>"._AM_FILE_DBDIFF." - $title</h1>";

if ($diff) echo "<pre class='db'>".colorDiff($diff)."</pre>";
elseif ($diff==='') echo "<p>"._AM_FILE_SAME."</p>";

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