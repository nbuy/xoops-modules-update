<?php
# $Id: modinfo.php,v 1.7 2006/12/05 03:15:52 nobu Exp $
define('_MI_UPDATE_NAME', 'XOOPSアップデート');
define('_MI_UPDATE_DESC', 'XOOPSおよびモジュールの更新支援を行う');

// admin menus
define('_MI_UPDATE_ADCHECK', '更新検査');
define('_MI_UPDATE_REGISTER', '更新登録');
define('_MI_UPDATE_ADPKG', '更新情報');
define('_MI_UPDATE_AUTH', 'サーバー認証');
define('_MI_UPDATE_ABOUT', 'Update について');

// Configs
define('_MI_UPDATE_SERVER', '更新サーバ URL');
define('_MI_UPDATE_SERVER_DESC', '更新情報を提供するサーバの URL を指定する');
define('_MI_UPDATE_SERVER_DEF', 'http://www.scriptupdate.jp');
define('_MI_UPDATE_CACHETIME', '更新検査のキャッシュ時間');
define('_MI_UPDATE_CACHETIME_DESC', '更新情報の取得などで保持するキャッシュの有効時間を秒数で指定する');
define('_MI_UPDATE_METHOD', '更新時の衝突回避方法');
define('_MI_UPDATE_METHOD_DESC', '更新に矛盾が検出された場合の取り扱い方法を指定する (個別の詳細指定が行われない場合)');
define('_MI_UPDATE_METHOD_SKIP', '旧ファイルを残す');
define('_MI_UPDATE_METHOD_REPLACE', '新ファイルで上書きする');
define('_MI_UPDATE_METHOD_PATCH', '強制的に更新を適用する');
define('_MI_UPDATE_CHECKEXTRA', '余分な追加ファイルを表示する');
define('_MI_UPDATE_CHECKEXTRA_DESC', '配布ファイルが置かれるフォルダに、余分なファイルが置かれていたら詳細画面で表示する');

# Blocks
define('_MI_UPDATE_NOTICE', 'アップデート通知');
define('_MI_UPDATE_NOTICE_DESC', 'パッケージの更新状況を通知する');
?>