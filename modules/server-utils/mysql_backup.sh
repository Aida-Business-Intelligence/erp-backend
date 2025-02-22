#!/bin/bash
#==============================================================================
#TITLE:            mysql_backup.sh
#DESCRIPTION:      script for automating the daily mysql backups on development computer
#AUTHOR:           TurnSaaS
#DATE:             2022-04-15
#VERSION:          0.1
#USAGE:            ./mysql_backup.sh -b bucketobjects:bucket/backups -d ./tmp/backups -l "saas_db_1 saas_db_2 ..."
#CRON:
  # example cron for daily db backup @ 9:15 am
  # min  hr mday month wday command
  # 15   9  *    *     *    /var/www/server-utils/mysql_backup.sh

############################################################
# Help                                                     #
############################################################
Help()
{
   # Display Help
   echo "Usage guide."
   echo
   echo "Syntax: scriptTemplate [-h|b|d|l]"
   echo "options:"
   echo "b     The bucket directory where backups are stored i.e bucketobjects:bucket/backups"
   echo "d     The existing local directory where backups will be extracted to and log to be stored i.e ./restore_tmp"
   echo "h     Print this Help."
   echo "l     The database backup names to restore i.e 'saas_db_1 saas_db_2' "
   echo
}

#==============================================================================
# CUSTOM SETTINGS
#==============================================================================

# Local directory to put the backup files
BACKUP_DIR=/tmp

#Cloud directory to store backup files
CLOUD_BACKUP_FOLDER=

# MYSQL Parameters
MYSQL_UNAME=root
MYSQL_PWORD=

# Don't backup databases with these names 
# Example: starts with mysql (^mysql) or ends with _schema (_schema$) or with name sys
IGNORE_DB="(^mysql|_schema$|sys)"

#list of database to backup
DB_LIST=

# include mysql and mysqldump binaries for cron bash user
PATH=$PATH:/usr/local/bin/mysql

# Number of days to keep backups
KEEP_BACKUPS_FOR=14 #days

# YYYY-MM-DD-HH-MM-SS
TIMESTAMP=$(date +%F-%H-%M-%S)

#1-7 , 1 is monday
TIMESTAMP_DAY_OF_WEEK=$(date +%u)

############################################################
# Process the input options. Add options as needed.        #
############################################################
# Get the options
while getopts ":h:l:d:b:" option; do
    case $option in
        h) # display Help
            Help
            exit;;
        \d) # backup directory
            BACKUP_DIR=$OPTARG;;

        \b) # bucket backup directory
            CLOUD_BACKUP_FOLDER=$OPTARG;;

        \l) # list of database to backup
            DB_LIST=$OPTARG;;
        
        \?) # Invalid option
            echo "Error: Invalid option. use -h to see option usage."
            exit;;
   esac
done

if [ -z "$CLOUD_BACKUP_FOLDER" ] || [ -z "$BACKUP_DIR" ]; then
    echo 'Missing -b or -d' >&2
    echo 'Cloud object directory is required.' >&2
    exit 1
fi

#require restore dir exist for extraction and local writing
if ! test -d $BACKUP_DIR; then
    echo "Restore temporary working directory does not exist: $RESTORE_DIR" >&2
    exit 1
fi


#==============================================================================
# METHODS
#==============================================================================
function delete_old_backups()
{
  echo "Deleting $BACKUP_DIR/*.sql.gz older than $KEEP_BACKUPS_FOR days"
  find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +$KEEP_BACKUPS_FOR -exec rm {} \;
}

function mysql_login() {
  local mysql_login="-u $MYSQL_UNAME" 
  if [ -n "$MYSQL_PWORD" ]; then
    local mysql_login+=" -p$MYSQL_PWORD" 
  fi
  echo $mysql_login
}

function database_list() {
  local show_databases_sql="SHOW DATABASES WHERE \`Database\` NOT REGEXP '$IGNORE_DB'"
  echo $(mysql $(mysql_login) -e "$show_databases_sql"|awk -F " " '{if (NR!=1) print $1}')
}

function echo_status(){
  printf '\r'; 
  printf ' %0.s' {0..100} 
  printf '\r'; 
  printf "$1"'\r'
}

function backup_database(){
    extension="sql.gz"
    backup_file="$BACKUP_DIR/$TIMESTAMP.$database.$extension" 
    output+="$database => $backup_file\n"
    echo_status "...backing up $count of $total databases: $database"
    $(mysqldump $(mysql_login) $database | gzip -9 > $backup_file)
    echo_status "...copying $backup_file to cloud"
    backup_to_cloud $backup_file $database $extension
}

function backup_databases(){

    local databases=
    if [ -z "$DB_LIST" ]; then
        databases=$(database_list)
    else 
        databases=$DB_LIST
    fi

    local total=$(echo $databases | wc -w | xargs)
    local output=""
    local count=1
    for database in $databases; do
        backup_database
        local count=$((count+1))
    done
    echo -ne $output | column -t
}

#==============================================================================
# UPLOAD BAKCUP TO SERVER
#$1 absolute file path
#$2 database name
#$3 $1 file extension
#==============================================================================
function backup_to_cloud(){

    if rclone copyto $1 "$CLOUD_BACKUP_FOLDER/$2/$TIMESTAMP_DAY_OF_WEEK.$3" && rclone copyto $1 "$CLOUD_BACKUP_FOLDER/$2/latest.$3" ; then
        echo "Saved to cloud successfully. $CLOUD_BACKUP_FOLDER/$2/$TIMESTAMP_DAY_OF_WEEK.$3"
    else
        echo "Error saving to cloud."
    fi
}

function hr(){
  printf '=%.0s' {1..100}
  printf "\n"
}

#==============================================================================
# RUN SCRIPT
#==============================================================================
echo_status "...Deleting old backups files above: $KEEP_BACKUPS_FOR days"
delete_old_backups
hr

echo_status "...Backing up databases"
backup_databases
hr
printf "All backed up!\n\n"