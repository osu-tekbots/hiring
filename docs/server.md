## Configuring Site Constants
Server configuration is inside of `config.ini` at the root of the repository. This file is **NOT** to be
checked into source control. The file should have the following contents:

```ini
; All files referenced through the configuration are relative to this private path
private_files = ; directory containing private files (outside the web root)

[server]
display_errors = ; yes|no
display_errors_severity = ; all|warning|error
auth_providers_config_file = ; auth.ini

[email]
subject_tag = ; optional tag to prepend all email subjects with
admin_address = ; email address for admins that need important site notifications
admin_subject_tag = SPT Admin ; subject tag for emails sent from the site admins
bounce_address = ; email address to send bounced emails to

[client]
base_url = ; base URL used by the frontend (e.g. http://eecs.oregonstate.edu/education/hiring/)

[logger]
log_file = ; .private/, or another name pointing to the log file directory
level = ; trace|info|warn|error

[database]
config_file = ; database.ini, or another name pointing to the database configuration file (see above for contents)
```

## Setting .htaccess
The `.htaccess` file has also been removed from the repository to further simplify configuration and is being ignored
by Git. When used, place the `.htaccess` file at the root of the repository with the following configuration:

```apacheconf
# Deny access to files with specific extensions
<FilesMatch "\.(ini|sh|sql)$">
Order allow,deny
Deny from all
</FilesMatch>

# Deny access to filenames starting with dot(.)
<FilesMatch "^\.">
Order allow,deny
Deny from all
</FilesMatch>

RewriteEngine On

RewriteBase <CHANGEME>

# If the requested file is not a directory or a file, we need to append .php
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} (pages|auth|api|masq)/
RewriteRule ^([^\.]+)$ $1.php [NC,L]

# Prepend `pages/` to the URI if it needs it
RewriteCond %{REQUEST_URI} !/(api|assets|auth|ajax|docs|lib|modules|pages|scripts|uploads)
RewriteRule ^(.*)$ pages/$1
```

Notice the `<CHANGEME>` text above. This should be changed to be the root URI of the website hosting the application.
For example, if the website is hosted at `http://eecs.oregonstate.edu/education/capstone/`, then you would replace
`<CHANGEME>` with `/education/capstone/`. **The trailing and leading slashes are required**.

## Initializing Cronjobs
Finally, this project includes a cronjob for deleting old test positions (and eventually, old positions). To set this up, create a `crontab.txt` file in the repository root with the following contents:
```crontab
EMAIL=""
24 0 * * */14 /bin/wget -O /dev/null -o /dev/null <CHANGEME>/scripts/deleteExamples.php

```
Notice the `<CHANGEME>` text above. This should be changed to the root URL of the website hosting the application.
For example, if the website is hosted at `https://eecs.engineering.oregonstate.edu/education/hiring`, then you would replace `<CHANGME>` with `https://eecs.engineering.oregonstate.edu/education/hiring`.

Additionally, this cronjob is set to execute every other week at 12:24 AM. If you choosed to modify this time, you may find [this tool](https://crontab.guru) helpful for double-checking the timing you settle on.

Please note that there are only two lines of text (`24 0 ... deleteExamples.php` is all one line) and that the file ends with a blank line.

After the file is created, open the terminal and enter the following shell command to start the cronjob:

```bash
crontab crontab.txt
```