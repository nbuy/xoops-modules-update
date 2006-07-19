<?php
# ScriptUpdate - Management
# $Id: auth.php,v 1.1 2006/07/19 12:47:48 nobu Exp $

include '../../../include/cp_header.php';
include '../functions.php';

$myts =& MyTextSanitizer::getInstance();

if (isset($_POST['pass'])) {
    $pass = $xoopsDB->quoteString($myts->stripSlashesGPC($_POST['pass']));
    $res = $xoopsDB->query("UPDATE ".FTBL." SET hash=$pass WHERE pkgref=0 AND path=''");
    redirect_header('index.php', 1, _AM_DBUPDATED);
    exit;
}

xoops_cp_header();
include 'mymenu.php';
echo "<h2>"._AM_AUTH_NEWPASS."</h2>";
$server = $xoopsModuleConfig['update_server'];
if (preg_match('/^\w+:/', $server)) {
    $url = $server.'/modules/server/authme.php?url='.XOOPS_URL;
    if (session_auth_server()) {
	echo "<p style='font-weight: bold;'>"._AM_AUTH_SESSION_OK."</p>";
    } else {
	echo "<p style='color: #c00; font-weight: bold;'>"._AM_AUTH_SESSION_NONE."</p>";
    }
    echo "<div><a href='$url'>"._AM_AUTH_REGISTER."</a></div>\n";
}

$pass = htmlspecialchars(isset($_GET['pass'])?$myts->stripSlashesGPC($_GET['pass']):'');
$domain = preg_replace('/\/*/i','',preg_replace('/^https?:\/\//i','',XOOPS_URL));

if ($xoopsDB->getRowsNum($res)==0) {
    $xoopsDB->queryF("INSERT INTO ".FTBL."(pkgref, path) VALUES (0, '')");
    $hash = '';
} else {
    list($hash) = $xoopsDB->fetchRow($res);
}

echo "<form action='auth.php' method='POST'>
<table cellspacing='1' cellpadding='5' class='outer'>
<tr><td class='head'>"._AM_AUTH_DOMAIN."</td><td class='even'>$domain</td></tr>
<tr><td class='head'>"._AM_AUTH_MYPASS."</td><td class='odd'><input name='pass' value='$pass'/></td></tr>
<tr><td class='head'></td><td class='even'><input type='submit' value='"._AM_AUTH_SUBMIT."'/></td></tr>
</table>
</form>";

xoops_cp_footer();

?>