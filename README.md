# EECS Hiring Web Application

The Hiring Web Application provides a central, accessible location for OSU hiring committees to track candidates through the hiring process. The committee's Search Chair has the ability to create and modify all information related to the position they're hiring for. Once they've filled out this information, the interviewing process can begin. For each interview round *(eg Resume Review, Phone interview, etc)* everyone on the committee can submit their notes about each candidate and score them for how well they meet each qualification for the position. Once a final decision has been made for a candidate, the Search Chair can update their status based on options provided by OSU HR.

**Initial Development**: Summer 2023 - (Development still ongoing)

**Contributors**
- Nate Baird (bairdn@oregonstate.edu)
- Travis Hudson (hudsontr@oregonstate.edu)

## Development

Deployment URL: https://eecs.engineering.oregonstate.edu/education/hiring

The following resources provide information about how to develop the website locally and the workflow for pushing
changes to the staging area and subsequently deploying them to production.


** Outdated
- [Local Development Setup](./docs/dev-setup.md)
- [Development Workflow](./docs/dev-workflow.md)

In addition, **create a pre-commit hook** that will ensure fill permissions are set accordingly before you commit
code. To do this, copy the `scripts/pre-commit.sh` file and save it as `pre-commit` in your local `.git/hooks`
directory. Also ensure it is executable.

```sh
cp scripts/pre-commit.sh .git/hooks/pre-commit
chmod a+x .git/hooks/pre-commit
```

## Configuration
### Database
There should be an INI file located in the private files for this site (not in the repository) with the following
contents:

```ini
host = 
user =
password = 
db_name = 
```

### Server
Server configuration is now inside of a `config.ini` file at the root of the repository. This file is **NOT** to be
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
from_address = ; main from address used when sending email from the server
admin_address = ; email address for admins that need important site notifications

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
RewriteCond %{REQUEST_URI} !/(api|assets|images|auth|pages|masq)
RewriteRule ^(.*)$ pages/$1
```

Notice the `<CHANGEME>` text above. This should be changed to be the root URI of the website hosting the application.
For example, if the website is hosted at `http://eecs.oregonstate.edu/education/capstone/`, then you would replace
`<CHANGEME>` with `/education/capstone/`. **The trailing and leading slashes are required**.

## Structural Overview
- All HTML pages are rendered inside of PHP files in the `pages/` folder.

- All database management is handled by database access objects in the `lib/classes/DataAccess/` and 
  `lib/shared/classes/DataAccess/` directories. Any additional queries required to accomplish site functionality
  should be included in these DAOs (or in a new DAO in the same namespace/file location).

- All database configuration is located in a private directory *outside this repository* in a `database.ini` file.

- Third-party authentication provider IDs and secrets are located *outside this repository* in a `auth.ini` file.

- All external CSS and JS files are located in the `assets/css/` and `assets/js/` respectively. An internal CSS 
  file called `assets/css/capstone.css` contains customized CSS proporties relevant to this application.

   > Please be aware that this CSS file is global and will modify the entire application to adhere to its standards. 
   > (EX: modifying the background color of the "body" element will modify all "body" elements of all pages, not just
   > a single one.) Please create new classes whenever applicable.

- The `modules/header.php` file contains all references to external CSS and JS files. The `header.php` and 
  `footer.php` files should be included in all files in the `pages/` directory.
  
- The `modules/` folder contains encapsulated code that is shared between multiple files in the `pages/` folder. 
  Whenever possible , please consolidate duplicate functionality into a single module or folder. For example, the 
  `modules/cards.php` will contain functions utilized in `pages/browseProjects.php` and 
  `pages/myProjects.php` to render project cards with different attributes.
  

## User Roles
**Users**
1. create new projects.
2. edit projects.
3. submit projects for approval.
4. review student applications.

**Admins**
1. Must approve each position before the Search Chair can fill out information or upload files.
2. Can edit any position.
3. *eventually* Can take on the role of any user to diagnose issues (with heavy tracking).
4. Can grant other users admin functionality.

## Database Architecture
Authentication data is located in a `database.ini` file **outside this repository**. The Tekbots Web Dev Team's shared
Google Drive contains documentation on the internal structure of database tables used in this site.

Database Name: `eecs_tekbots`
Server Name: `engr-db Groups`

## Login Authentication
Within `pages/login.php`, the `auth/[authenticator].php` script is executed on login button click. 
Login credentials required to interface with the authenticator are:
- redirect_uri
- client_id
- client_secret

Each authenticator will provide different user info configurations but will have sufficient data needed to create a 
new user. All new users are defaulted as Students and are re-directed to `pages/login.php` with a new portal section.

Users must contact an administrator of this application in order to be given the access level of admin.


## Session Variables
Session variables are used to persist user data throughout the course of a user's active session. The instantiation 
of these variables occur in the following workflow:
  
1. The user visits the `pages/login.php` page. 
2. The user selects a login authentication type (EX: Google, Microsoft).
3. After successful authentication, the following session variables are instantiated and can be used in PHP throughout the entire application: 
   - `$_SESSION['userID']`: This variable is a string of numbers. 
   - `$_SESSION['userAccessLevel']`: This variable is a string that can be either: 
      - "User"
      - "Admin"
   - `$_SESSION['newUser']`: This variable is a boolean (either true or false).

## Troubleshooting and Helpful Notes

### Problem
  
#### Solution 
		 
## Screenshots 
