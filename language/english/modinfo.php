<?php
# $Id: modinfo.php,v 1.1 2007/11/18 18:04:32 nobu Exp $
define('_MI_UPDATE_NAME', 'XOOPS Update');
define('_MI_UPDATE_DESC', 'Support XOOPS Core and Modules update');

// admin menus
define('_MI_UPDATE_ADCHECK', 'Check and Update');
define('_MI_UPDATE_REGISTER', 'Register Update');
define('_MI_UPDATE_ADPKG', 'Update Info.');
define('_MI_UPDATE_AUTH', 'Server Auth.');
define('_MI_UPDATE_ABOUT', 'About Update');

// Configs
define('_MI_UPDATE_SERVER', 'Update Server URL');
define('_MI_UPDATE_SERVER_DESC', 'The Server URL that provide Update Information.');
define('_MI_UPDATE_SERVER_DEF', 'http://www.scriptupdate.jp');
define('_MI_UPDATE_CACHETIME', 'Update info cache Time');
define('_MI_UPDATE_CACHETIME_DESC', 'Fetched update information, that keeping cache time(unit second).');
define('_MI_UPDATE_METHOD', 'Conflict escape method');
define('_MI_UPDATE_METHOD_DESC', 'When detection conflict update, setting method to try');
define('_MI_UPDATE_METHOD_SKIP', 'Keep Old file');
define('_MI_UPDATE_METHOD_REPLACE', 'Override New file');
define('_MI_UPDATE_METHOD_PATCH', 'Force Update patch');
define('_MI_UPDATE_CHECKEXTRA', 'Show Extra files');
define('_MI_UPDATE_CHECKEXTRA_DESC', 'Display file list when there is not include distribution files in detail page.');

# Blocks
define('_MI_UPDATE_NOTICE', 'Update Notifiation');
define('_MI_UPDATE_NOTICE_DESC', 'Notification for new update packages');
?>