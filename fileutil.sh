#!/bin/sh
# $Id: fileutil.sh,v 1.2 2006/08/10 08:45:05 nobu Exp $
# file operation utility to use via sudo.

PATH=/sbin:/usr/sbin:/bin:/usr/bin
group=apache
owner=apache
umask 027

case $# in
    0)
	echo "exec on `whoami`"
	echo "usage: sudo -u user $0 [option] ope args .."
	exit
esac

root=`dirname $0`
case "$root" in .) root=`pwd` ;; esac
base="$root/homes"

while true
do
  case $1 in
      -d) base="$base/$2" shift 2;;
      -f) base=$2 shift 2;;
      -g) group=$2 shift 2;;
      *) break;;
  esac
done

umask 000;
case $1 in
    check) echo Yes; whoami;;
    copy) tar cfC - "$2" . | tar xfC - "$3";;
    rollback) tar xfCz "$2" "$3";;
esac
