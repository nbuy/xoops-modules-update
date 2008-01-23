# $Id: mysql.sql,v 1.5 2008/01/23 05:58:01 nobu Exp $

CREATE TABLE update_package (
    pkgid	integer NOT NULL PRIMARY KEY auto_increment,
    parent	integer NOT NULL default 0,
    pname	varchar(20) NOT NULL,
    pversion    varchar(20) NOT NULL,
    name	varchar(40) NOT NULL default '',
    dtime	integer NOT NULL default 0,
    ctime	integer NOT NULL default 0,
    mtime	integer NOT NULL default 0,
    vcheck	varchar(20) NOT NULL default '',
    KEY (pname,pversion)
);

CREATE TABLE update_file (
    fileid	integer NOT NULL PRIMARY KEY auto_increment,
    pkgref	integer NOT NULL default 0,
    hash	varchar(40) NOT NULL default '',
    path	varchar(128) NOT NULL default '',
    KEY (path)
);

CREATE TABLE update_diff (
    fileref	integer NOT NULL,
    ctime	integer NOT NULL default 0,
    mtime	integer NOT NULL default 0,
    diff	longblob default NULL,
    PRIMARY KEY  (fileref)
);

CREATE TABLE update_cache (
  cacheid  varchar(65) NOT NULL,
  mtime    integer NOT NULL default 0,
  content  longblob default NULL,
  PRIMARY KEY  (cacheid)
);
