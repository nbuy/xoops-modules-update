# $Id: mysql.sql,v 1.3 2007/07/16 05:18:30 nobu Exp $

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
    fileref	integer NOT NULL PRIMARY KEY,
    ctime	integer NOT NULL default 0,
    mtime	integer NOT NULL default 0,
    diff	text NOT NULL default ''
);

CREATE TABLE update_cache (
  cacheid  varchar(65) NOT NULL,
  mtime    integer NOT NULL default 0,
  content  text,
  PRIMARY KEY  (cacheid)
);
