# EECS Search Progress Tracker

The Search Progress Tracker provides a central, accessible location for OSU hiring committees to track candidates through the hiring process. The committee's Search Chair has the ability to create and modify all information related to the position they're hiring for. Once they've filled out this information, the interviewing process can begin. For each interview round *(eg Resume Review, Phone interview, etc)* everyone on the committee can submit their notes about each candidate and score them for how well they meet each qualification for the position. Once a final decision has been made for a candidate, the Search Chair can update their status based on options provided by OSU HR.

**Initial Development**: Summer 2023 - (Development still ongoing)

**Contributors**
- Nate Baird (bairdn@oregonstate.edu)
- Travis Hudson (hudsontr@oregonstate.edu)

## Development

Deployment URL: https://eecs.engineering.oregonstate.edu/education/hiring

The following resources provide information about how to develop the website locally and the workflow for pushing
changes to the staging area and subsequently deploying them to production.


*Not yet implemented on this site*
  - [Local Development Setup](./docs/dev-setup.md)
  - [Development Workflow](./docs/dev-workflow.md)

In addition, **create a pre-commit hook** that will ensure file permissions are set correctly before you commit
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
Please see the [Server Documentation](./docs/server.md) for details on the server configuration of the site.

## Structural Overview
Please see the [Structure Documentation](./docs/structure.md) for a detailed structural overview of this site.
  

## User Roles
**Users**
1. Can submit feedback as they review candidates with their committees.
2. Can create new positions to review with their committees.
3. Can modify positions (qualifications, rounds, and candidates) that they are the Search Chair for.
3. *In the future*, may recieve additional permissions based on their role.

**Admins**
1. Must approve each position before the Search Chair can fill out information or upload files.
2. Can edit any position.
3. Can take on the role of any user to diagnose issues (with heavy tracking).
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