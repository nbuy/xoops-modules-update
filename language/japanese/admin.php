<?php
# Update Administration language resources
# $Id: admin.php,v 1.13 2010/01/09 07:44:28 nobu Exp $

// update and cheking
define('_AM_CHECK_LIST', '検査パッケージ一覧');
define('_AM_UPDATE_PKGS', 'パッケージの更新');
define('_AM_UPDATE_SUBMIT', '更新を実行する');
define('_AM_UPDATE_ROLLBACK', '更新を元に戻す');
define('_AM_UPDATE_TIME', '更新時間');
define('_AM_UPDATE_EXPIRE', '有効期限');
define('_AM_NOUPDATE', '更新はありません');
define('_AM_UPDATE_NEWZIP', '更新ファイルのアーカイブ');
define('_AM_UPDATE_CLEAR', '変更登録解除');
define('_AM_UPDATE_TO', ' → ');

define('_AM_CHECKSUM_TITLE', '検査対象の指定');
define('_AM_MASTER_URL', '検査リスト');
define('_AM_VERBOSE', '詳細を表示する');
define('_AM_CHECKING', '検査を実行');

// options
define('_AM_OPTS_TITLE', '%sパッケージ詳細設定');
define('_AM_OPTS_RENAME', 'モジュール名称の変更');
define('_AM_OPTS_DESC', '選択したパス名以下を更新対象とします');
define('_AM_OPTS_NONE', '選択オプションはありません');
define('_AM_OPTS_PATH', 'パス名');

// packages administration
define('_AM_PKG_FILEIMPORT', 'パッケージの登録');

// 
define('_AM_REG_PACKAGES', '更新情報の登録');
define('_AM_REG_DETAIL', '更新情報の登録');
define('_AM_REG_SUBMIT', '登録する');
define('_AM_REG_DESCRIPTION', '更新検査を実施するパッケージをチェック (選択) して、「'._AM_REG_SUBMIT.'」ボタンで登録してください。チェックを外すと、登録を解除します。なお、パッケージ情報が登録されていないモジュールは選択できないようになっています。');

define('_AM_AUTH_REGISTER', '更新サーバーで認証登録を行う');
define('_AM_AUTH_NEWPASS', 'サーバーの認証パスワードを登録');
define('_AM_AUTH_DOMAIN', 'ドメイン/パス');
define('_AM_AUTH_MYPASS', '初期パスワード');
define('_AM_AUTH_SUBMIT', '登録する');
define('_AM_AUTH_SESSION_NONE', 'サーバー認証は設定されていません');
define('_AM_AUTH_SESSION_OK', 'サーバー認証は正常です (設定の必要はありません)');
define('_AM_AUTH_SESSION_NG', 'サーバー認証に失敗しました');

define('_AM_PKG_GETLISTFAIL', 'パッケージ一覧の取得ができません');
define('_AM_PKG_PNAME', '検査情報名称');
define('_AM_PKG_CURRENT_PNAME', '設定(推奨)名');
define('_AM_PKG_NAME', '名称');
define('_AM_PKG_CURRENT', '導入版');
define('_AM_PKG_NEW', '最新版');
define('_AM_PKG_DTIME', '配布日時');
define('_AM_PKG_CTIME', '登録日時');
define('_AM_PKG_VERSION', '版');
define('_AM_PKG_DIRNAME', 'フォルダ');
define('_AM_PKG_SOURCE', 'ソース');
define('_AM_PKG_REGISTER', '更新情報を登録してください');
define('_AM_PKG_NOCURRENT', '更新情報名が検出できません');
define('_AM_PKG_NEEDUPDATE', '更新してください');
define('_AM_PKG_NOTINSTALL', '未導入です');

define('_AM_REGIST_LIST', '登録済みパッケージ');
define('_AM_REGIST_SUBMIT', '変更ファイルを登録する');
define('_AM_IMPORT_FILE_MAX', '最大サイズ');
define('_AM_DESCRIPTION', '説明');
define('_AM_NODATAINFILE', 'データがありません');
define('_AM_PKG_NOTFOUND', 'パッケージ情報が見付かりません');
define('_AM_FETCH_DESCRIPTION', '手動で、更新パッケージを入手して登録する場合、<a href="{SERVER}">こちらの配布ページ</a>からダウンロードして上記のフォームから登録します。');


define('_AM_UPDATE_BACKUP', '差分バックアップ');
define('_AM_UPDATE_ARCHIVE', '更新差分を作成');
define('_AM_UPDATE_SUCC', 'パッケージの更新を行いました');
define('_AM_UPDATE_ERROR', 'パッケージの更新に問題が生じた可能性があります。');
define('_AM_UPDATE_ERROR_DESC', 'メッセージを確認して対処を行ってください。');

define('_AM_STATUS',  '状態');
define('_AM_OK',  '良好');
define('_AM_CHG', '変更');
define('_AM_DEL', '削除');
define('_AM_NEW', '新規');
define('_AM_EXTRA', '余分');

define('_AM_FILE',  'ファイル');
define('_AM_BYTES',  'バイト');
define('_AM_DETAIL', '詳細');
define('_AM_MODIFY', '調整');
define('_AM_CHANGES', '変更数');
define('_AM_MODIFYS', '調整済');
define('_AM_HAS_CHANGE', '導入ファイルに変更があります');
define('_AM_NODATA', 'データがありません');
define('_AM_FILE_ALL', 'ファイル総数');
define('_AM_VIEW_SCRIPT', '変更スクリプトのみ表示');
define('_AM_VIEW_ALL', '全てのファイルを表示');
define('_AM_DIFF_DIFF', '差分の違い');
define('_AM_DIFF_RAW', '直接表示');
define('_AM_DIFF_FETCH_ERROR', 'オリジナルファイルの取得エラー');

define('_AM_FILE_DIFF',  '差分');
define('_AM_FILE_DBDIFF',  '保存差分');
define('_AM_FILE_SAME',  '変更はありません (CVS タグの変更のみ)');
define('_AM_FILE_SAMEDIFF',  '保存差分と同じ内容です');

define("_AM_DBUPDATED", "更新しました");
define("_AM_DBUPDATE_FAIL", "更新に失敗しました");
?>