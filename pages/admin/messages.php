<?php
include_once '../../bootstrap.php';

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions('admin'), 'index.php');

$title = 'Messages';
$css = array(array('defer' => 'true', 'href' => 'assets/css/sb-admin.min.css'), 'assets/css/admin.css');
include_once PUBLIC_FILES . '/modules/header.php';

echo '<div id="wrapper">';
$active = 'Messages';
include_once PUBLIC_FILES . '/modules/adminSidebar.php';

use DataAccess\MessageDao;
use DataAccess\UserDao;

$messageDao = new MessageDao($dbConn, $logger);
$userDao = new UserDao($dbConn, $logger);

$user = $userDao->getUserByID($_SESSION['userID']);
$messages = $messageDao->getAllMessages();
?>

<div id="content-wrapper">
<br><br><br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center">Messages</h2>
        </div>
    </div>
    <?php
        foreach ($messages as $m) {
            $message_id = $m->getID();
            $subject = $m->getSubject();
            $body = $m->getBody();
            $usage = $m->getPurpose();
            $inserts = $m->getInserts();
            
            echo '<div class="row" style="border: 1px solid black">
                    <div id="row'.$message_id.'" class="container m-1 p-2" oninput="setActive(this, true)">
                        <div class="row m-1">
                            <h5>'.$usage.'</h5>
                        </div>
                        <div class="row m-1">
                            <div class="col-sm-9">
                                <div class="input-group p-1">
                                    <div class="input-group-prepend"><label for="subject'.$message_id.'" class="input-group-text">Subject</label></div>
                                    <input type="text" class="form-control" id="subject'.$message_id.'" value="'.$subject.'">
                                </div>
                                <div class="input-group p-1">
                                    <div class="input-group-prepend"><label for="body'.$message_id.'" class="input-group-text">Body</label></div>
                                    <textarea rows="6" type="text" class="form-control" id="body'.$message_id.'" style="min-height: 100%">'.$body.'</textarea>
                                </div>
                                <div class="d-flex justify-content-end p-1">
                                    <div style="flex-grow: 1"><h6>Message ID: '.$message_id.'</h6></div>
                                    <button type="button" class="btn btn-primary mx-1" onclick="updateMessage(\''.$message_id.'\');" disabled>Saved</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="sendTestMessage(\''.$message_id.'\');">Test Stored Email</button>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <strong>Inserts</strong><BR>
                                '.$inserts.'
                            </div>
                        </div>
                    </div>
                </div>';
        }

            
        
    ?>

</div> <!-- Close wrapper for main contents -->
</div> <!-- Close wrapper for sidebar & main contents -->

<script>
    /**
     *  Function for making buttons active/unactive based on input.
     */
    function setActive(element, value) {
        let button = element.getElementsByTagName('button')[0];

        if(value) {
            button.innerText = 'Update';
            button.disabled = false;
        } else {
            button.innerText = 'Saved';
            button.disabled = true;
        }
    }

    /**
     * Updates the content of a message.
     */
    function updateMessage(id) {
        var subject = document.getElementById('subject'+id).value;
        var body = document.getElementById('body'+id).value;
        var format = 1;
        
        let content = {
            action: 'updateMessage',
            id: id,
            subject: subject,
            body: body
        }
        
        api.post('/message.php', content).then(res => {
            setActive(document.getElementById('row'+id), false);
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        });
    }

    /**
     * Sends a test message to the current user
     */
    function sendTestMessage(id) {
        let email = "<?php echo $user->getEmail();?>"
        if(confirm('Confirm that a test email will be sent to your email address (' + email + ')?')) {
            let content = {
                action: 'sendTestMessage',
                id: id,
                email: email
            }
            
            api.post('/message.php', content).then(res => {
                snackbar(res.message, 'success');
            }).catch(err => {
                snackbar(err.message, 'error');
            });
        } else {
            return false;
        }
    }
</script>

<?php 
include_once PUBLIC_FILES . '/modules/footer.php' ; 
?>