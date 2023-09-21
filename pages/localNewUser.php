<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    session_start();
}

$isLoggedIn = verifyPermissions(['user', 'admin']);
if ($isLoggedIn) {
    // Redirect to their profile page
    $redirect = $configManager->getBaseUrl() . 'pages/myProfile.php';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

$title = 'Create Account';
include_once PUBLIC_FILES . '/modules/header.php';

?>

<section>
    <form action="./auth/localEndpoint.php" method="POST">
        <input type="hidden" name="action" value="addUser">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        You will set your password after creating an account and verifying your email address.
                    </div>
                    <div class="card shadow-2-strong bg-light" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">
                            <h3 class="mb-5">Create Local Account</h3>
							<div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="userFirst">First Name</label></div>
                                <input type="text" required name="userFirst" id="userFirst" class="form-control form-control-lg" />
                            </div>
							<div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="userLast">Last Name</label></div>
                                <input type="text" required name="userLast" id="userLast" class="form-control form-control-lg" />
                            </div>
                            <div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="userEmail">Email Address</label></div>
                                <input type="email" required name="userEmail" id="userEmail" class="form-control form-control-lg" />
                            </div>
                            <button class="btn btn-primary btn-lg btn-block" type="submit">Create Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<?php include_once PUBLIC_FILES . '/modules/footer.php'; ?>
