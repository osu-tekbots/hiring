<?php
include_once '../../bootstrap.php';


if(!isset($_SESSION)) {
    session_start();
}

use DataAccess\UserDao;
use Model\User;
use Model\UserAuthProvider;

$userDao = new UserDao($dbConn, $logger);

$title = 'Reset Password';
include_once PUBLIC_FILES . '/modules/header.php';

?>

<section class="vh-100">
    <form action="./auth/localEndpoint.php" method="POST">
        <input type="hidden" name="action" value="resetPassword">
		<div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-2-strong bg-light" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">
                            <h3 class="mb-5">Reset Password</h3>
                            <div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="userEmail">Email Address</label></div>
                                <input type="text" required name="userEmail" class="form-control form-control-lg" value="<?php echo $_REQUEST['email'] ?? ""; ?>">
                            </div>
                            <div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="resetCode">Reset Code</label></div>
                                <input type="text" required name="resetCode" class="form-control form-control-lg" value="<?php echo $_REQUEST['resetCode'] ?? ""; ?>">
                            </div>
                            <br>
                            <div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="newUserPassword">New Password</label></div>
                                <input type="password" required name="newUserPassword" id="newUserPassword" class="form-control form-control-lg" />
                                <div class="w-100 mt-1"><div class="float-right d-block"><input class="my-2 mx-1" type="checkbox" onclick="togglePasswordVisibility()">Show Password</div></div>
                            </div>
                            <button class="btn btn-primary btn-lg btn-block" type="submit">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
    function togglePasswordVisibility() {
        var x = document.getElementById("newUserPassword");
        if (x.type === "password") {
            x.type = "text";
        } else {
            x.type = "password";
        }
    }

</script>

<?php include_once PUBLIC_FILES . '/modules/footer.php'; ?>