<?php
# XoopsUpdate - enable blocks
# $Id: oninstall.php,v 1.1 2006/08/08 06:46:29 nobu Exp $

global $xoopsDB;
$dirname = basename(dirname(__FILE__));
// enable block display
$xoopsDB->query("UPDATE ".$xoopsDB->prefix('newblocks')." SET visible=1
WHERE show_func='b_update_notice' AND dirname='$dirname'");
?>