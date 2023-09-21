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

[client]
base_url = ; base URL used by the frontend (e.g. http://eecs.oregonstate.edu/education/hiring/)

[logger]
log_file = ; .private/, or another name pointing to the log file directory
level = ; trace|info|warn|error

[database]
config_file = ; database.ini, or another name pointing to the database configuration file (see above for contents)
```

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