#!/bin/sh

# This file is part of the FreeSentral Project http://freesentral.null.ro
# A free web interface to set up a PBX based on the Yate telephony engine.
# Copyright (C) 2008 Null Team
#
# Yet Another Telephony Engine - a fully featured software PBX and IVR
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.

readopt()
{
    read -p "$1: [$2] " tmp
    case "x$tmp" in
	xn|xno|xN|xNO|xNo)
	    ;;
	x)
	    echo "$2"
	    ;;
	*)
	    echo "$tmp"
	    ;;
    esac
}
timezone="Europe/London"
upload_dir="/var/tmp"
enable_logging="on"

showhelp()
{
    cat <<EOF
    $version usage:
$0
    [--config dir] [--scripts dir] [--prompts dir] [--webpage dir] [--upload_dir dir]
    [--psql executable]
    [--dbhost host] [--dbname name]
    [--dbuser user] [--dbpass password]
	[--enable_logging on/off]
	[--timezone localtimezone]
    [--quiet]
        or one of the following unique parameters:
    help version tarball tgz tbz
EOF
}

maketarball()
{
    wd=`pwd|sed 's,^.*/,,'`
    mkdir -p tarballs
    cd ..
    tar "c${1}f" "${wd}/tarballs/${2}" $tarexclude "${wd}"
}

confdata()
{
cat << EOF
<?
/* File created by $version */

/* Settings for connecting to the PostgreSQL database */

/* Host where the database server is running - use "localhost" for local */
\$db_host = "$dbhost";
/* Name of the database (a server may have many independent databases) */
\$db_database = "$dbname";
/* Database username to use when connecting */
\$db_user = "$dbuser";
/* Password for the database access */
\$db_passwd = "$dbpass";

date_default_timezone_set('$timezone');

EOF
if [ "x$1" = "xweb" ]; then
cat << EOF
\$target_path = "$prompts";
\$do_not_load = array();        //modules that are inserted here won't be loaded
\$limit = 20;  //max number to display on page
\$enable_logging = "$enable_logging"; // possible values: "on"/"off", true/false, "yes"/"no" 
\$upload_path = "$upload_dir";     // path where file for importing extensions will be uploaded

?>
EOF
else
cat << EOF
\$conn  = pg_connect("host='\$db_host' dbname='\$db_database' user='\$db_user' password='\$db_passwd'")
    or die("Could not connect to the postgresql database");

\$vm_base = "$prompts";
\$no_groups = false;
\$no_pbx = false;
\$uploaded_prompts = "$prompts";
\$vm_base = "$prompts";
\$query_on = false;
\$max_resets_conn = 5;
?>
EOF
fi
}

pkgname="freesentral"
pkglong="FreeSentral"
shortver="1"
version="$pkglong v$shortver"
interactive="yes"
tarexclude="--exclude CVS --exclude .cvsignore --exclude .svn --exclude .xvpics --exclude tarballs --exclude config.php"

dbhost="localhost"
dbname="$pkgname"
dbuser="postgres"
dbpass=""
timezone="Europe/London"
upload_dir="/var/tmp"
enable_logging="on"

configs="`yate-config --config 2>/dev/null`"
scripts="`yate-config --scripts 2>/dev/null`"
prompts="/var/spool/voicemail"
case "x`yate-config --version 2>/dev/null`" in
    x2.*|x3.*|x4.*|x5.*|x6.*|x7.*|x8.*|x9.*)
	;;
    *)
	scripts=""
	configs=""
	prompts=""
	;;
esac

webpage="/var/www/html"
if [ -d "$webpage" ]; then
    webpage="$webpage/$pkgname"
else
    webpage=""
fi

psqlcmd="`which psql`"

if [ "$#" = "1" ]; then
    case "x$1" in
	xtarball|xtargz|xtgz)
	    maketarball z "$pkgname-$shortver.tar.gz"
	    exit
	    ;;
	xtarbz2|xtarbz|xtbz)
	    maketarball j "$pkgname-$shortver.tar.bz2"
	    exit
	    ;;
	x*.tar.gz|x*.tgz)
	    maketarball z "$1"
	    exit
	    ;;
	x*.tar.bz2|x*.tbz)
	    maketarball j "$1"
	    exit
	    ;;
	x*.tar)
	    maketarball "" "$1"
	    exit
	    ;;
	xhelp)
	    showhelp
	    exit
	    ;;
	xversion)
	    echo "$pkglong $shortver"
	    exit
	    ;;
    esac
fi

while [ "$#" != "0" ]; do
    cmd="$1"
    shift
    case "x$cmd" in
	x--help|x-h)
	    showhelp
	    exit
	    ;;
	x--version|x-V)
	    echo "$pkglong $shortver"
	    exit
	    ;;
	x--quiet|x-q)
	    interactive="no"
	    ;;
	x--config|x-c)
	    configs="$1"
	    shift
	    ;;
	x--scripts|x-s)
	    scripts="$1"
	    shift
	    ;;
	x--prompts|x-p)
	    prompts="$1"
	    shift
	    ;;
	x--webpage|x-w)
	    webpage="$1"
	    shift
	    ;;
	x--psql)
	    psqlcmd="$1"
	    shift
	    ;;
	x--dbhost)
	    dbhost="$1"
	    shift
	    ;;
	x--dbname)
	    dbname="$1"
	    shift
	    ;;
	x--dbuser)
	    dbuser="$1"
	    shift
	    ;;
	x--dbpass)
	    dbpass="$1"
	    shift
	    ;;
	x--upload_dir)
		upload_dir="$1"
		shift
		;;
	x--timezone)
		timezone="$1"
		shift
		;;
	x--enable_logging)
		enable_logging="$1"
		shift
		;;
	*)
	    echo "Unexpected parameter: $cmd" >&2
	    showhelp >&2
	    exit
	    ;;
    esac
done

echo "Installer for $version"

if [ "x$interactive" != "xno" ]; then
    echo "At the following prompts you can enter the word 'no' to disable defaults"
    configs=`readopt "Install Yate config file in" "$configs"`
    scripts=`readopt "Install Yate scripts in" "$scripts"`
    prompts=`readopt "Install IVR prompts in" "$prompts"`
    webpage=`readopt "Install Web pages in" "$webpage"`
    dbhost=`readopt "Database host" "$dbhost"`
    if [ -n "$dbhost" ]; then
	dbname=`readopt "Database name" "$dbname"`
	dbuser=`readopt "Database user" "$dbuser"`
	dbpass=`readopt "Database password" "$dbpass"`
	psqlcmd=`readopt "PostgreSQL command" "$psqlcmd"`
    else
	dbname=""
	dbuser=""
	dbpass=""
	psqlcmd=""
    fi
fi

if [ "x$webpage$dbhost" = "x" ]; then
    echo "Nothing to do!"
    exit
fi

if [ -n "$psqlcmd" ]; then
    if "$psqlcmd" --version 2> /dev/null | grep -q ' [89\.]'; then
	/bin/true
    else
	echo "Invalid or too old PostgreSQL client: $psqlcmd" >&2
	psqlcmd=""
    fi
fi

echo "Install options"
cat <<EOF
    Config file in '$configs'
    Scripts dir in '$scripts'
    IVR prompts in '$prompts'
    Web pages in   '$webpage'
    Database:
        Host     '$dbhost'
        Name     '$dbname'
        User     '$dbuser'
        Password '$dbpass'
    PosgreSQL tool '$psqlcmd'
EOF

if [ "x$interactive" != "xno" ]; then
    if [ -z `readopt "Proceed with installation?" "yes"` ]; then
	echo "Aborting..."
	exit
    fi
fi


if [ -n "$configs" ]; then
    echo "Installing Yate configuration files"

	# extmodule.conf
    fe="$configs/extmodule.conf";
    e="
[scripts]
register.php=
"

    if [ -e "$fe" ]; then
	if [ -z `readopt "Overwrite existing extmodule.conf ?" "yes"` ]; then
	    echo "Please edit file $fe like follows:"
	    echo "$e"
	    fe=""
	fi
    fi
    if [ -n "$fe" ]; then
	echo "Creating extmodule configuration file"
	mkdir -p "$configs"
	echo "; File created by $version
$e" > "$fe"
    fi

	# moh.conf
    fe="$configs/moh.conf";
    e="
[mohs]
madplay=while true; do madplay -q --no-tty-control -m -R 8000 -o raw:- -z \${mohlist}; done
"

    if [ -e "$fe" ]; then
	if [ -z `readopt "Overwrite existing moh.conf ?" "yes"` ]; then
	    echo "Please edit file $fe like follows:"
	    echo "$e"
	    fe=""
	fi
    fi
    if [ -n "$fe" ]; then
	echo "Creating moh configuration file"
	mkdir -p "$configs"
	echo "; File created by $version
$e" > "$fe"
    fi

	# pgsqldb.conf
    fe="$configs/pgsqldb.conf";
    e="
[freesentral]
host=$dbhost
database=$dbname
user=$dbuser
password=$dbpass
"

    if [ -e "$fe" ]; then
	if [ -z `readopt "Overwrite existing pgsqldb.conf ?" "yes"` ]; then
	    echo "Please edit file $fe like follows:"
	    echo "$e"
	    fe=""
	fi
    fi
    if [ -n "$fe" ]; then
	echo "Creating pgsqldb configuration file"
	mkdir -p "$configs"
	echo "; File created by $version
$e" > "$fe"
    fi	

	# queues.conf
    fe="$configs/queues.conf";
    e="
[general]
; General settings of the queues module

; account: string: Name of the database account used in queries
account=freesentral
; priority: int: Priority of message handlers
priority=20
; rescan: int: Period of polling for available operators, in seconds
;rescan=5
; mintime: int: Minimum time between queries, in milliseconds
;mintime=500

[queries]
; SQL queries that get data about the queue and operators

; queue: string: Query to pick queue parameters, returns zero or one row
; Relevant substitutions:
;  \${queue}: string: Name of the queue as obtained from routing
; Relevant returned params:
;  mintime: int: Minimum time between queries, in milliseconds
;  length: int: Maximum queue length, will declare congestion if grows larger
;  maxout: int: Maximum number of simultaneous outgoing calls to operators
;  greeting: string: Resource to be played initially as greeting
;  onhold: string: Resource to be played while waiting in queue
;  maxcall: int: How much to call the operator, in milliseconds
;  prompt: string: Resource to play to the operator when it answers
;  notify: string: Target ID for notification messages about queue activity
;  detail: bool: Notify when details change, including call position in queue
;  single: bool: Make just a single delivery attempt for each queued call
queue=SELECT mintime, length, maxout, greeting, 'moh/madplay' as onhold, maxcall, prompt, detail FROM groups WHERE groups.group_id='\${queue}'

; avail: string: Query to fetch operators to which calls can be distributed
; Relevant substitutions:
;  \${queue}: string: Name of this queue
;  \${required}: int: Number of operators required to handle incoming calls
;  \${current}: int: Number of calls to operators currently running
;  \${waiting}: int: Total number of calls waiting in this queue (assigned or not)
; Mandatory returned params:
;  location: string: Resource where the operator is located
;  username: string: User name of the operator
; Relevant returned params:
;  maxcall: int: How much to call the operator, in milliseconds
;  prompt: string: Resource to play to the operator when it answers
avail=SELECT extensions.location, extensions.extension as username FROM extensions, group_members WHERE extensions.extension_id=group_members.extension_id AND group_members.group_id='\${queue}' AND extensions.location IS NOT NULL AND coalesce(extensions.inuse_count,0)=0 ORDER BY extensions.inuse_last LIMIT \${required} 

[channels]
; Resources that will be used to handle incoming and outgoing calls
; incoming: string: Target that will handle incoming calls while queued
incoming=external/nodata/queue_in.php
; outgoing: string: Target that will be called to make calls to operators
outgoing=external/nodata/queue_out.php
"

    if [ -e "$fe" ]; then
	if [ -z `readopt "Overwrite existing queues.conf ?" "yes"` ]; then
	    echo "Please edit file $fe like follows:"
	    echo "$e"
	    fe=""
	fi
    fi
    if [ -n "$fe" ]; then
	echo "Creating queues configuration file"
	mkdir -p "$configs"
	echo "; File created by $version
$e" > "$fe"
    fi
fi

if [ -n "$scripts" ]; then
    echo "Installing Yate scripts"
    mkdir -p "$scripts"
    # this is a convenient way to filter what we copy
    (cd scripts; tar cf - $tarexclude *) | tar xf - -C "$scripts/"
    confdata > "$scripts/config.php"
fi

if [ -n "$prompts" ]; then
    echo "Installing IVR prompts"
    mkdir -p "$prompts"
    (cd prompts; tar cf - $tarexclude *) | tar xf - -C "$prompts/"
fi

if [ -n "$webpage" ]; then
    echo "Installing Web application"
    mkdir -p "$webpage"
    (cd web; tar cf - $tarexclude *) | tar xf - -C "$webpage/"
    if [ -n "$dbhost" ]; then
	echo "Creating configuration file"
	confdata web > "$webpage/config.php"
    fi
fi

if [ -n "$psqlcmd" -a -n "$dbhost" ]; then
    echo "Initializing the database"
    if [ -n "$dbpass" ]; then
	tty -s && echo "At the password prompt please enter: $dbpass"
	export PGPASSWORD="$dbpass"
    fi
    "$psqlcmd" -h "$dbhost" -U "$dbuser" -d template1 -c "CREATE DATABASE $dbname"
    unset PGPASSWORD
fi

# need to make sure apache is allowed to upload/modify files in the $prompts dir: moh, prompts for auto attendant, voicemail messages
# chmod -R 777 $prompts
