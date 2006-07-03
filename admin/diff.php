<?php
# ScriptUpdate - Management
# $Id: diff.php,v 1.1 2006/07/03 03:36:16 nobu Exp $

include '../../../include/cp_header.php';
include_once '../package.class.php';

$myts =& MyTextSanitizer::getInstance();

$pkgid = intval($_GET['pkgid']);
$file = $myts->stripSlashesGPC($_GET['file']);
$pkg = new InstallPackage($pkgid);

$chg = $pkg->checkFile($file);
header("Content-Type: text/html; charset"._CHARSET);
$title = htmlspecialchars($pkg->getVar('name').': '.$file);
echo "<!DOCTYPE html PUBLIC '//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._LANGCODE.'" lang="'._LANGCODE.'">
<head>
<meta http-equiv="content-type" content="text/html; charset='._CHARSET.'" />
<title>'.$title.'</title>
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
echo "<h1>"._AM_FILE_DIFF." - $title</h1>";

$diff = $pkg->dbDiff($file);
if ($diff) echo "<pre class='db'>".colorDiff($diff)."</pre>";
elseif ($diff==='') echo "<p>"._AM_FILE_SAME."</p>";

if ($chg) {
    echo "<div class='msg'>"._AM_HAS_CHANGE.' - '.$pkg->getVar('pversion')."</div>";
    $diff = $pkg->getDiff($file);
    if ($diff) echo "<pre class='chg'>".colorDiff($diff)."</pre>";
    else echo "<p>"._AM_FILE_SAME."</p>";
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