<?php
# Update Administration language resources
# $Id: admin.php,v 1.2 2010/01/09 07:44:28 nobu Exp $

// update and cheking
define('_AM_CHECK_LIST', 'List of Check Packages');
define('_AM_UPDATE_PKGS', 'Update Packages');
define('_AM_UPDATE_SUBMIT', 'Do Update');
define('_AM_UPDATE_ROLLBACK', 'Update Revert');
define('_AM_UPDATE_TIME', 'Updated');
define('_AM_UPDATE_EXPIRE', 'Expire');
define('_AM_NOUPDATE', 'There is no updates');
define('_AM_UPDATE_NEWZIP', 'Update archives');
define('_AM_UPDATE_CLEAR', 'Reset registered');
define('_AM_UPDATE_TO', ' &rArr; ');

define('_AM_CHECKSUM_TITLE', 'Check Packages Selection');
define('_AM_MASTER_URL', 'Check List');
define('_AM_VERBOSE', 'Show Detail');
define('_AM_CHECKING', 'Do Checking');

// options
define('_AM_OPTS_TITLE', '%s package settings');
define('_AM_OPTS_RENAME', 'Rename modules');
define('_AM_OPTS_DESC', 'Select folder is follow update');
define('_AM_OPTS_NONE', 'There is no options');
define('_AM_OPTS_PATH', 'Path');

// packages administration
define('_AM_PKG_FILEIMPORT', 'Register Package');

// 
define('_AM_REG_PACKAGES', 'Register Update Check Information');
define('_AM_REG_DETAIL', 'Register Update Info');
define('_AM_REG_SUBMIT', 'Register');
define('_AM_REG_DESCRIPTION', 'Select check which you want following packages, and submit "'._AM_REG_SUBMIT.'" button. Uncheck package remove register. There is no checkbox when not provide package information.');

define('_AM_AUTH_REGISTER', 'Register Update Server Authrication');
define('_AM_AUTH_NEWPASS', 'Register Server-Auth Password');
define('_AM_AUTH_DOMAIN', 'Auth Domain/Path');
define('_AM_AUTH_MYPASS', 'Initial Password');
define('_AM_AUTH_SUBMIT', 'Submit');
define('_AM_AUTH_SESSION_NONE', 'There is No Authorication');
define('_AM_AUTH_SESSION_OK', 'Authorication Normal (No need setting)');
define('_AM_AUTH_SESSION_NG', 'Authorication Failer');

define('_AM_PKG_GETLISTFAIL', 'Package List Get Failer');
define('_AM_PKG_PNAME', 'Package Name');
define('_AM_PKG_CURRENT_PNAME', 'Package Name(Recommand)');
define('_AM_PKG_NAME', 'Name');
define('_AM_PKG_CURRENT', 'Installed');
define('_AM_PKG_NEW', 'Newest');
define('_AM_PKG_DTIME', 'Distributed');
define('_AM_PKG_CTIME', 'Created');
define('_AM_PKG_VERSION', 'Version');
define('_AM_PKG_DIRNAME', 'Folder');
define('_AM_PKG_SOURCE', 'Source');
define('_AM_PKG_REGISTER', 'Register Package Information');
define('_AM_PKG_NOCURRENT', 'Package Info can not sense');
define('_AM_PKG_NEEDUPDATE', 'Please Update');
define('_AM_PKG_NOTINSTALL', 'Not yet install');

define('_AM_REGIST_LIST', 'List of Registered Packages');
define('_AM_REGIST_SUBMIT', 'Register Changes');
define('_AM_IMPORT_FILE_MAX', 'Max file size');
define('_AM_DESCRIPTION', 'Description');
define('_AM_NODATAINFILE', 'There is No Data');
define('_AM_PKG_NOTFOUND', 'Package Information not found');
define('_AM_FETCH_DESCRIPTION', 'Package Information register by manual. It get from <a href="{SERVER}">this Server</a> and upload above form.');


define('_AM_UPDATE_BACKUP', 'Backup changes');
define('_AM_UPDATE_ARCHIVE', 'Update Archive');
define('_AM_UPDATE_SUCC', 'Update Succes');
define('_AM_UPDATE_ERROR', 'Probably Update include failer');
define('_AM_UPDATE_ERROR_DESC', 'Please fix manualy.');

define('_AM_STATUS',  'Status');
define('_AM_OK',  'Ok');
define('_AM_CHG', 'Change');
define('_AM_DEL', 'Delete');
define('_AM_NEW', 'New');
define('_AM_EXTRA', 'Extra');

define('_AM_FILE',  'File');
define('_AM_BYTES',  'Bytes');
define('_AM_DETAIL', 'Detail');
define('_AM_MODIFY', 'Modify');
define('_AM_CHANGES', 'Changes');
define('_AM_MODIFYS', 'Tuned');
define('_AM_HAS_CHANGE', 'Modification in install file');
define('_AM_NODATA', 'No Data');
define('_AM_FILE_ALL', 'Files');
define('_AM_VIEW_SCRIPT', 'View only Changes');
define('_AM_VIEW_ALL', 'View All');
define('_AM_DIFF_DIFF', 'difference of Diffs');
define('_AM_DIFF_RAW', 'Raw diff');
define('_AM_DIFF_FETCH_ERROR', 'Orignal file fetch error');

define('_AM_FILE_DIFF',  'Diff');
define('_AM_FILE_DBDIFF',  'Saved Diff');
define('_AM_FILE_SAME',  'No Changes (only CVS tags)');
define('_AM_FILE_SAMEDIFF',  'Same as saved diff');

define("_AM_DBUPDATED", "Updated");
define("_AM_DBUPDATE_FAIL", "Update Failer");
?>