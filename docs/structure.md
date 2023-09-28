## Structural Overview
- All HTML pages are rendered inside of PHP files in the `pages/` folder.

- All classes are contained in the `lib/classes` directory, in a subdirectory that matches the namespace of the class.

- All database management is handled by database access objects in the `lib/classes/DataAccess/` directory. Any 
  additional queries required to accomplish site functionality should be included in these DAOs (or a new DAO in the 
  same location).

- All database configuration is located in a private directory *outside this repository* in a `database.ini` file.

- Third-party authentication provider IDs and secrets will be located *outside this repository* in a `auth.ini` file.

- All external CSS and JS files are located in the `assets/css/` and `assets/js/` respectively. 
    - An internal CSS file called `assets/css/capstone.css` contains customized CSS proporties relevant to this 
      application, and an internal CSS file called `assets/css/capstoneMobile.css` contains a few modifications for 
      mobile devices.

       > Please be aware that this CSS file is global and will modify the entire application to adhere to its standards. 
       > (EX: modifying the background color of the "body" element will modify all "body" elements of all pages, not just
       > a single one.) Please create new classes whenever applicable.

    - An internal JS file called `assets/js/api.js` wraps HTTP requests in a Promise and automatically parses JSON 
      responses from our webserver. For example, this allows HTTP POST requests to be made in the format
        ```JS
            api.post('/path_to_api_endpoint.php', data).then(resolutionObject => {
                // Code to execute after successful API call
            }).catch(error => {
                // Code to execute after failed API call
            });
        ```
      where `data` is a JS object containing the parameters of the HTTP request, `resolutionObject` is the parsed JSON
      resolution response from the server, and error is the parsed JSON error response from the server (if the HTTP 
      response code is 4XX or 5XX) or the native JS error object if the error occurs on the client side

    - An internal JS file called `assets/js/snackbar.js` generates a Material Design snackbar for displaying messages to
      the user, with `assets/css/snackbar.css` used to style the messages. Snackbars can be created in the format
        ```JS
            snackbar('message', 'level');
        ```
      where `message` is the message to display, and `level` determines the snackbar styling and is either:
        - `info`
        - `success`
        - `warn`
        - `error`

    - An internal JS file called `assets/js/error.js` handles uncaught client-side errors by generating an email with 
      helpful diagnostic information to the site's admins. To add your email address to this list, modify 
      `handleErrorEmail()` in `lib/classes/Api/EmailActionHandler.php`.

- The `bootstrap.php` and `bootstrapApi.php` files should be included in all files in the `pages/` directory `api/` 
  directory respectively. These files contain the logic necessary for all pages/API endpoints, such as initializing the
  logger and database connection. The only difference between the two is whether they return error messages (such as a
  failed database connection) in HTML or JSON format.

- The `modules/header.php` file contains all references to external CSS and JS files. The `header.php` and 
  `footer.php` files should be included in all files in the `pages/` directory.
  
- The `modules/` folder contains encapsulated code that is shared between multiple files in the `pages/` folder. 
  Whenever possible , please consolidate duplicate functionality into a single module or folder. For example, the 
  `modules/emailModal.php` will contain code utilized in `pages/user/reviewCandidate.php` and 
  `pages/user/viewCandidateSummary.php` to add a modal that lets users email candidates directly from the web app.

