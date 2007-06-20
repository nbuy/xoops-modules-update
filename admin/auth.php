<?php
# ScriptUpdate - Management
# $Id: auth.php,v 1.4 2007/06/20 15:52:58 nobu Exp $

include '../../../include/cp_header.php';
include '../functions.php';

$myts =& MyTextSanitizer::getInstance();

if (isset($_POST['pass'])) {
    $pass = $xoopsDB->quoteString($myts->stripSlashesGPC($_POST['pass']));
    $res = $xoopsDB->query("UPDATE ".UPDATE_FILE." SET hash=$pass WHERE pkgref=0 AND path=''");
    package_expire();
    redirect_header('auth.php', 1, _AM_DBUPDATED);
    exit;
}

xoops_cp_header();
include 'mymenu.php';
echo "<h2>"._AM_AUTH_NEWPASS."</h2>";
$server = $xoopsModuleConfig['update_server'];
if (preg_match('/^\w+:/', $server)) {
    $url = $server.'/modules/server/authme.php?url='.urlencode(XOOPS_URL);
    if (session_auth_server()) {
	echo "<p style='font-weight: bold;'>"._AM_AUTH_SESSION_OK."</p>";
    } else {
	echo "<p style='color: #c00; font-weight: bold;'>"._AM_AUTH_SESSION_NONE."</p>";
    }
    echo "<div><a href='$url'>"._AM_AUTH_REGISTER."</a></div>\n";
}

$pass = htmlspecialchars(isset($_GET['pass'])?$myts->stripSlashesGPC($_GET['pass']):'');
$domain = auth_domain_name();

$res = $xoopsDB->query("SELECT fileid FROM ".UPDATE_FILE." WHERE pkgref=0 AND path=''");
if ($xoopsDB->getRowsNum($res)==0) {
    $xoopsDB->queryF("INSERT INTO ".UPDATE_FILE."(pkgref, path) VALUES (0, '')");
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