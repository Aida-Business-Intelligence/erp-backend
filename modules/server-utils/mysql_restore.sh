#!/bin/bash
#==============================================================================
#TITLE:            mysql_restore.sh
#DESCRIPTION:      script for automating the restore of mysql backups
#AUTHOR:           TurnSaaS
#DATE:             2022-04-15
#VERSION:          0.1
#USAGE:            ./mysql_restore.sh -p latest -b bucketobjects:bucket/backups -l "saas_db_1 saas_db_2" -d ./tmp/restore
#CRON:
  # example cron for daily db backup @ 9:15 am
  # min  hr mday month wday command
  # 15   9  *    *     *   /var/www/server-utils/mysql_restore.sh

############################################################
# Help                                                     #
############################################################
Help()
{
   # Display Help
   echo "Usage guide."
   echo
   echo "Syntax: scriptTemplate [-h|b|d|p|l]"
   echo "options:"
   echo "b     The bucket directory where backups are stored i.e bucketobjects:bucket/backups"
   echo "d     The existing local directory where backups will be extracted to and log to be stored i.e ./restore_tmp"
   echo "h     Print this Help."
   echo "p     The backup point to use i.e 1-7 or latest where 1 is Monday"
   echo "l     The database backup names to restore i.e 'saas_db_1 saas_db_2' "
   echo
}

#==============================================================================
# CUSTOM SETTINGS
#==============================================================================

# TMP directory to put the backup files and log for restoration
RESTORE_DIR=/tmp

#Cloud directory to store backup files
CLOUD_BACKUP_FOLDER=

#List of database backups to restore. should match with cloud tenant backup folder name (ie. db name)
CLOUD_BACKUP_LIST=

# MYSQL Parameters
MYSQL_UNAME=root
MYSQL_PWORD=

# Don't backup databases with these names 
# Example: starts with mysql (^mysql) or ends with _schema (_schema$) or with name sys
IGNORE_DB="(^mysql|_schema$|sys)"


# include mysql and mysqldump binaries for cron bash user
PATH=$PATH:/usr/local/bin/mysql

# Number of days to keep restore logs
KEEP_LOGS_FOR=14d #days i.e rclone age format i.e 2d 10m 5h e.t.c

# YYYY-MM-DD-HH-MM-SS
TIMESTAMP=$(date +%F-%H-%M-%S)

#1-7 , 1 is monday
TIMESTAMP_DAY_OF_WEEK=$(date +%u)

#Backup to use 1-7 or latest
RESTORE_POINT=latest


############################################################
# Process the input options. Add options as needed.        #
############################################################
# Get the options
while getopts ":h:p:d:b:l:" option; do
    case $option in
        h) # display Help
            Help
            exit;;
        \p) # restore point
            RESTORE_POINT=$OPTARG;;
        
        \d) # restore directory
            RESTORE_DIR=$OPTARG;;

        \b) # bucket backup directory
            CLOUD_BACKUP_FOLDER=$OPTARG;;

        \l) # list of backups to restore
            CLOUD_BACKUP_LIST=$OPTARG;;

        \?) # Invalid option
            echo "Error: Invalid option. use -h to see option usage."
            exit;;
   esac
done

if [ -z "$CLOUD_BACKUP_FOLDER" ] || [ -z "$RESTORE_DIR" ]; then
    echo 'Missing -b or -d' >&2
    echo 'Cloud object directory is required.' >&2
    exit 1
fi

#require restore dir exist for extraction and local writing
if ! test -d $RESTORE_DIR; then
    echo "Restore temporary working directory does not exist: $RESTORE_DIR" >&2
    exit 1
fi


#==============================================================================
# METHODS
#==============================================================================
function mysql_login() {
  local mysql_login="-u $MYSQL_UNAME" 
  if [ -n "$MYSQL_PWORD" ]; then
    local mysql_login+=" -p$MYSQL_PWORD" 
  fi
  echo $mysql_login
}

function echo_status(){
  printf '\r'; 
  printf ' %0.s' {0..100} 
  printf '\r'; 
  printf "$1"'\r'
}

function clear_log(){
    echo "" > "$RESTORE_DIR/$TIMESTAMP-$1.log"
}

function write_log(){
    echo -e "$1" >> "$RESTORE_DIR/$TIMESTAMP-$2.log"
}

function drop_database() {
  local drop_database_sql="DROP DATABASE $1"
  echo $(mysql $(mysql_login) -e "$drop_database_sql")
}

function create_database() {
  local create_database_sql="CREATE DATABASE $1"
  echo $(mysql $(mysql_login) -e "$create_database_sql")
}

function database_list() {
  local show_databases_sql="SHOW DATABASES WHERE \`Database\` NOT REGEXP '$IGNORE_DB'"
  echo $(mysql $(mysql_login) -e "$show_databases_sql"|awk -F " " '{if (NR!=1) print $1}')
}

function cloud_database_list() {
  echo $(rclone lsf $CLOUD_BACKUP_FOLDER --dirs-only | awk -F " " '{gsub("\/",""); if (NR!=1) print $1}')
}

function restore_database(){
    
    for existing in $databases; do     
        #echo "Checking database: $dbname to $existing"
        if [ "$database" = "$existing" ]; then
            drop_database $database
        fi
    done
        
    #create database
    create_database $database

    #backup file from local
    filename="$RESTORE_DIR/$database.sql"

    #import file to sql
    if $(mysql $(mysql_login) $database < $filename); then
        write_log "$database Done." success
    else
        write_log "$database restore failed" failed
    fi
}

function restore_databases(){

    local cloud_databases=

    if [ -z "$CLOUD_BACKUP_LIST" ]; then
        cloud_databases=$(cloud_database_list)
    else 
        cloud_databases=$CLOUD_BACKUP_LIST
    fi

    #require backups not empty
    if [ -z "$cloud_databases" ]; then
        echo "No backup files found in: $CLOUD_BACKUP_FOLDER" >&2
        exit 1
    fi

    local databases=$(database_list)
    local total=$(echo $cloud_databases | wc -w | xargs)
    local output=""
    local count=1
    
    for database in $cloud_databases; do
        
        #exclude db pattern for cloud files i.e database name not in excluded pattern
        if ! [[ $database =~ $IGNORE_DB ]]; then
            
            #get file from cloud to local
            if fetch_database_backup_from_cloud; then
             
                #run restore
                restore_database
                local count=$((count+1))
            fi
        fi
    done

    rm $RESTORE_DIR/*.sql

    echo -ne $output | column -t
}



function hr(){
  printf '=%.0s' {1..100}
  printf "\n"
}

#==============================================================================
# cloud methods
#==============================================================================
function fetch_database_backup_from_cloud(){
    extension="sql.gz"
    backup_file="$CLOUD_BACKUP_FOLDER/$database/$RESTORE_POINT.$extension"
    backup_file_local="$RESTORE_DIR/$database.$extension"
    
    echo_status "...restoring $count of $total databases: $database"

    rclone copyto $backup_file "$backup_file_local"

    if test -f "$backup_file_local" && gzip -d $backup_file_local -f; then
        write_log "Fetched $database $RESTORE_POINT backup" info
        output+="$database => $backup_file\n"
    else
        echo "No file found for $database"
        write_log "$database restore file $RESTORE_POINT not found $(date +%FT%T)" failed
        #exit 1
    fi
}

function move_log_to_cloud(){

    if rclone moveto $RESTORE_DIR "$CLOUD_BACKUP_FOLDER/_restore_logs_schema" --include "*.log"; then 

        #clean local log file
        if test -f "$RESTORE_DIR/*"; then 
            rm $RESTORE_DIR/*
        fi
    else
        echo "Error moving log to cloud.."
    fi
}

function clear_old_log_from_cloud(){

    rclone delete "$CLOUD_BACKUP_FOLDER/_restore_logs_schema" --min-age $KEEP_LOGS_FOR
}

#==============================================================================
# RUN SCRIPT
#==============================================================================

echo_status "...Clearing logs"
clear_log failed
clear_log info
clear_log success
clear_old_log_from_cloud
hr

echo_status "...Restoring dbs"
restore_databases
hr

echo_status "...Moving logs to cloud"
move_log_to_cloud
hr
printf "All backups restored!\n\n"
