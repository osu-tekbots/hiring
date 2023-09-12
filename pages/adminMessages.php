<?php
include_once '../bootstrap.php';

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

<div class="admin-content" id="content-wrapper">
<br><br><br>
<div class="container">
    <div class="alert alert-danger">
        I haven't had a chance to make the <kbd>Update</kbd> and <kbd>Test Stored Email</kbd> buttons work yet; I got pulled away to higher-priority
        changes throughout the site.<br>
        -- Nate
    </div>
    <?php
        foreach ($messages as $m) {
            $message_id = $m->getID();
            $subject = $m->getSubject();
            $body = $m->getBody();
            $usage = $m->getPurpose();
            
            echo '<div class="admin-paper">
                    <h6>Message ID: '.$message_id.'<BR>Purpose: '.$usage.'</h6>
                    <form>
                        <div id="row'.$message_id.'" style="padding-left:4px;padding-right:4px;margin-top:4px;margin-bottom:4px;" oninput="setActive(this, true)">
                            <div class="form-group row">
                                <label for="subject'.$message_id.'" class="col-sm-1 col-form-label">Subject</label>
                                <div class="col-sm-11"><input type="text" class="form-control" id="subject'.$message_id.'" value="'.$subject.'"></div>
                            </div>
                            <div class="form-group row">
                                <label for="body'.$message_id.'" class="col-sm-1 col-form-label">Body</label>
                                <div class="col-sm-8">
                                    <textarea rows="6" type="text" class="form-control" id="body'.$message_id.'" style="min-height: 100%">'.$body.'</textarea>
                                </div>
                                <div class="col-sm-3">
                                    <strong>Inserts</strong><BR>
                                    {{name}}: Full Name<BR>
                                    {{role}}: Role for Position<BR>
                                    {{position}}: Position Name<BR>
                                    {{positionID}}: Position ID<BR>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-10">
                                    <button type="button" class="btn btn-primary" onclick="updateMessage(\''.$message_id.'\');" disabled>Saved</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="sendTestMessage(\''.$message_id.'\');">Test Stored Email</button>
                                </div>
                            </div>
                        </div>
                    </form>
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
            subject: subject,
            body: body,
            format: format,
            message_id: id
        }
        
        api.post('/message.php', content).then(res => {
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
                action: 'sendMessage',
                email: email,
                message_id: id
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