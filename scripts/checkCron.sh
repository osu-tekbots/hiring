#!/bin/bash

USERNAME=$(whoami)
SERVER=$(hostname)
SITE_PATH="/nfs/ca/info/eecs_www/education/hiring"
SCRIPT=

# Allow colored output
# NOTE: Output is disabled for use in ~/.bashrc
# (COE IT recommends no output from ~/.bashrc to avoid SFTP and related errors)
# Uncomment the `echo` lines in the heredoc to enable output
GREEN='\e[0;32m'
YELLOW='\e[0;33m'
DEFAULT='\e[0m'

read -r -d '' SCRIPT <<EOF
cd "$SITE_PATH"

# Don't show the crontab program result to avoid unnecessary "no crontab for ONID" message
crontab -l > /dev/null 2>&1

# Create the cronjob or confirm that one is running
if [ \$? -ne 0 ]; then
    # No cronjob is registered; register using crontab.txt
    crontab crontab.txt > /dev/null 2>&1
    # echo -e "${GREEN}SPT crontab registered.${DEFAULT}"
else
    # echo -e "${YELLOW}Crontab was already registered. Verify that it includes the SPT cron job.${DEFAULT}"
    # Show the contents of the crontab for verification
    # crontab -l
fi

EOF

#? Only want to have cron job on 1 server, otherwise there'll be multiple calls to the 
#? cron job scripts & race conditions
if [ "${SERVER}" != "flip1.engr.oregonstate.edu" ]; then
    ssh "${USERNAME}@flip1.engr.oregonstate.edu" "${SCRIPT}"
else
    eval "${SCRIPT}"
fi