<?php
include_once '../../bootstrap.php';

use DataAccess\UserDao;

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions(['user', 'admin']), 'index.php');

$title = 'Profile';
include_once PUBLIC_FILES . '/modules/header.php';

include_once PUBLIC_FILES . '/modules/breadcrumb.php';
renderBreadcrumb(NULL, $title);

$userDao = new UserDao($dbConn, $logger);

$user = $userDao->getUserByID($_SESSION['userID']);
allowIf($user, 'It looks like you\'re not signed in. Please sign in before updating your profile.', true);

$userAuthMethods = $userDao->getAuthProvidersForUserByEmail($user->getEmail());
$userAuthMethods = implode(', ', array_map('getName', $userAuthMethods));

/* Just gets the auth method's name, but necessary bc of how array_map works */
function getName($authMethod) {
    return $authMethod->getName();
}

?>

<br><br>
<div class="container">
    <div class="row">
        <div class="col-sm-6">
            <div class="panel-heading">
                <h4 class="panel-title">User Info</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col control-label" for="firstNameText">First Name</label>
                    <div class="col-sm-11">
                        <input class="form-control" id="firstNameText" name="firstName" value="<?php echo $user->getFirstName(); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col control-label" for="lastNameText">Last Name</label>
                    <div class="col-sm-11">
                        <input class="form-control" id="lastNameText" name="lastName" value="<?php echo $user->getLastName(); ?>">
                    </div>
                </div>
                <div class="container">
                    <div class="row">

                    </div>
                </div>
                <br>
                
                <div class="panel-body">
                    <br>
                    <div class="col-sm-11">
                        <button class="btn btn-large btn-block btn-primary" id="saveProfileBtn" type="button" onclick="updateUser(this);">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="panel-heading">
                <h4 class="panel-title">Contact Info</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col control-label" for="phoneText">Phone Number <i>(optional)</i></label>
                    <div class="col">
                        <input class="form-control" id="phoneText" name="phone" value="<?php echo $user->getPhone(); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col control-label" for="emailText">Email Address</label>
                    <div class="col">
                        <input class="form-control" id="emailText" name="email" readonly value="<?php echo $user->getEmail(); ?>">
                    </div>
                </div>

                <br><br>
                <div class="panel-heading">
                    <h4 class="panel-title">Account info</h4>
                </div>
                <hr class="my-4">
                <div class="form-group">
                    <p class="form-control-static">User Type: <?php echo $user->getAccessLevel() ?> </p>
                    <p class="form-control-static">Login Methods: <?php echo $userAuthMethods ?> </p>
                    <p class="form-control-static">Last Updated: <?php echo $user->getDateUpdated()->format('m/d/Y h:ia') ?> </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const USER_ID = "<?php echo $_SESSION['userID'] ?>"
    
    function updateUser(thisVal) {
        let data = {
            action: 'updateUser',
            id: USER_ID,
            firstName: document.getElementById('firstNameText').value,
            lastName: document.getElementById('lastNameText').value,
            phone: document.getElementById('phoneText').value
        }

        thisVal.disabled = true;

        api.post('/user.php', data).then(res => {
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }
</script>