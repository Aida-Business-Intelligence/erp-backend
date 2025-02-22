#!/bin/bash
#==============================================================================
#TITLE:            sync.sh
#DESCRIPTION:      script for syncing source to cloud
#AUTHOR:           TurnSaaS
#DATE:             2022-04-15
#VERSION:          0.1
#USAGE:            ./sync.sh -b bucketobjects:bucket/backups -d /var/www/html/uploads
#CRON:
  # example cron for daily db backup @ 9:15 am
  # min  hr mday month wday command
  # 15   9  *    *     *    /var/www/server-utils/sync.sh

############################################################
# Help                                                     #
############################################################
Help()
{
   # Display Help
   echo "Usage guide."
   echo
   echo "Syntax: scriptTemplate [-h|b|d]"
   echo "options:"
   echo "b     The bucket directory where to sync to i.e bucketobjects:bucket/source_code"
   echo "d     The existing local directory to sync from i.e ./"
   echo "h     Print this Help."
   echo
}

#==============================================================================
# CUSTOM SETTINGS
#==============================================================================

# TMP directory to put the backup files and log for restoration
DIR=./

#Cloud directory to store backup files
CLOUD_BACKUP_FOLDER=

#1-7 , 1 is monday
TIMESTAMP=$(date +%s)


############################################################
# Process the input options. Add options as needed.        #
############################################################
# Get the options
while getopts ":h:d:b:" option; do
    case $option in
        h) # display Help
            Help
            exit;;
        \d) # restore directory
            DIR=$OPTARG;;

        \b) # bucket backup directory
            CLOUD_BACKUP_FOLDER=$OPTARG;;

        \?) # Invalid option
            echo "Error: Invalid option. use -h to see option usage."
            exit;;
   esac
done

if [ -z "$CLOUD_BACKUP_FOLDER" ] || [ -z "$DIR" ]; then
    echo 'Missing -b or -d' >&2
    echo 'Cloud object directory is required.' >&2
    exit 1
fi

#require restore dir exist for extraction and local writing
if ! test -d $DIR; then
    echo "Backup directory does not exist: $DIR" >&2
    exit 1
fi


#==============================================================================
# METHODS
#==============================================================================
function echo_status(){
  printf '\r'; 
  printf ' %0.s' {0..100} 
  printf '\r'; 
  printf "$1"'\r'
}


function hr(){
  printf '=%.0s' {1..100}
  printf "\n"
}

#==============================================================================
# cloud methods
#==============================================================================
function sync_to_cloud(){
    
    local fileName="backupFile_$TIMESTAMP.zip"
    cd $DIR
    echo_status "..archiving to /archives"
    zip $fileName * -r

    echo_status "...moving archive"
    rclone moveto $fileName  "$CLOUD_BACKUP_FOLDER/archives/$fileName"

    echo_status "...syncing folder to /files"
    rclone sync $DIR "$CLOUD_BACKUP_FOLDER/files"
}

#==============================================================================
# RUN SCRIPT
#==============================================================================

sync_to_cloud
hr
printf "Done syncing!\n\n"

hr
hr
printf "To remove older archives older than 7days Run: \n\n"
printf "rclone --min-age 7d delete $CLOUD_BACKUP_FOLDER/archives \n\n"
hr
hr