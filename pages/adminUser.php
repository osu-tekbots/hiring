<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions('admin'), 'index.php');

$title = 'Dashboard';
$css = array(array('defer' => 'true', 'href' => 'assets/css/sb-admin.min.css'), 'assets/css/admin.css');
include_once PUBLIC_FILES . '/modules/header.php';

echo '<div id="wrapper">';
$active = 'Users';
include_once PUBLIC_FILES . '/modules/adminSidebar.php';

use DataAccess\UserDao;

$userDao = new UserDao($dbConn, $logger);
?>

<div id="content-wrapper">
<br><br><br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center">All Users</h2>
        </div>
    </div>
    <?php
        $users = $userDao->getAllUsers();

        if(count($users)) {
            foreach($users as $user) {
                echo "
                <div class='row py-3' style='border: 1px solid black'>
                    <div class='col my-auto'>
                        <h4>".$user->getFirstName()." ".$user->getLastName()."</h4>
                    </div>
                    <div class='col-3 my-auto'>";
                if($user->getAccessLevel() == 'User')
                    echo "<button type='button' id='level".$user->getID()."' onclick='makeAdmin(\"".$user->getID()."\")' class='btn btn-outline-success float-right mx-2'>Make Admin</a>";
                else
                    echo "<button type='button' id='level".$user->getID()."' onclick='makeUser(\"".$user->getID()."\")' class='btn btn-outline-danger float-right mx-2'>Make User</a>";
                echo "</div>
                </div>";
            }
        } else {
            echo "
            <div class='row py-3' style='border: 1px solid black'>
                <h4 class='col text-center'>No users.</h4>
            </div>";
        }
    ?>
</div>

</div> <!-- Close wrapper for main contents -->
</div> <!-- Close wrapper for sidebar & main contents -->

<script>
    function makeAdmin(id) {
        data = {
            action: 'changeAccessLevel',
            id: id,
            level: 'Admin'
        }
        
        document.getElementById('level'+id).disabled = true;

        api.post('/user.php', data).then(res => {
            document.getElementById('level'+id).classList.remove('btn-outline-success');
            document.getElementById('level'+id).classList.add('btn-outline-danger');
            document.getElementById('level'+id).setAttribute('onclick', `makeUser('${id}')`);
            document.getElementById('level'+id).textContent = 'Make User';
            snackbar(res.message, 'success');
        }).catch(err => {
            document.getElementById('level'+id).disabled = false;
            snackbar(err.message, 'error');
        }).finally(() => document.getElementById('level'+id).disabled = false);
    }
    
    function makeUser(id) {
        data = {
            action: 'changeAccessLevel',
            id: id,
            level: 'User'
        }
        
        document.getElementById('level'+id).disabled = true;

        api.post('/user.php', data).then(res => {
            document.getElementById('level'+id).classList.add('btn-outline-success');
            document.getElementById('level'+id).classList.remove('btn-outline-danger');
            document.getElementById('level'+id).setAttribute('onclick', `makeAdmin('${id}')`);
            document.getElementById('level'+id).textContent = 'Make Admin';
            snackbar(res.message, 'success');
        }).catch(err => {
            document.getElementById('level'+id).disabled = false;
            snackbar(err.message, 'error');
        }).finally(() => document.getElementById('level'+id).disabled = false);
    }
</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>