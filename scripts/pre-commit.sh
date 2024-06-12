#!/bin/sh
#
# This script enforces some rules before changes are committed. For now, it just
# sets the file permissions for all files and directories so they are accessible
# from the browser. Copy this file as `pre-commit` into `.git/hooks` and make it
# executable.
#
clear

echo "Starting pre-commit..."

echo "Making sure permissions for files and directories are public"

find . -not -path "./crontab.txt" -not -path "./uploads/*" -not -path "./scripts/*" -not -path "./.private/*" -not -path "./docs/*" -not -path "*/composer*" -not -path "./vendor/*" -not -path "./.*" -not -path "*.md"  | while read f;
do
    if [ "$f" = "." ] || [ "$f" = "./.git" ] || [ "$f" = "./uploads" ] || [ "$f" = "./scripts" ] || [ "$f" = "./.private" ] || [ "$f" = "./docs" ]; then
        continue
    fi

    FILE_PERMISSIONS=$(stat -c "%a" "$f")

    if [ -d "$f" ] && [ "$FILE_PERMISSIONS" != '2775' ]; then
        echo
        echo "ERROR: Found directory '$f' with incorrect permissions '$FILE_PERMISSIONS'"
        echo "Would you like to update this directory's permissions to rwxrwxr-x??"
        read -p "y/n: " yn < /dev/tty

        if [ "$yn" = "y" ] || [ "$yn" = "yes" ]; then
            chmod 775 "$f"
        else
            echo "Run 'chmod 775 \"$f\"' (or 'sh scripts/allow.sh' to fix all permissions) from the repository root before committing."
            echo "Update 2024: scripts/allow.sh is out of date. Must be updated before being used."
            exit 1
        fi
    
    elif [ -f "$f" ] && [ "$FILE_PERMISSIONS" != '775' ]; then
        echo
        echo "ERROR: Found file '$f' with incorrect permissions '$FILE_PERMISSIONS'"
        echo "Would you like to update this file's permissions to rwxrwxr-x?"
        read -p "y/n: " yn < /dev/tty

        if [ "$yn" = "y" ]; then
            chmod 775 "$f"
        else
            echo "Run 'chmod 775 \"$f\"' (or 'sh scripts/allow.sh' to fix all permissions) from the repository root before committing."
            echo "Update 2024: scripts/allow.sh is out of date. Must be updated before being used."
            exit 1
        fi
    fi
done

echo "Pre-commit done."