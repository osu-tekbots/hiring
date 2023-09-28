<?php
include_once '../../bootstrap.php';

use Model\Candidate;
use Model\Feedback;
use DataAccess\CandidateDao;
use DataAccess\CandidateFileDao;
use DataAccess\FeedbackDao;
use DataAccess\FeedbackFileDao;
use DataAccess\FeedbackForQualDao;
use DataAccess\PositionDao;
use DataAccess\QualificationDao;
use DataAccess\QualificationStatusDao;
use DataAccess\QualificationForRoundDao;
use DataAccess\RoundDao;


if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions(['admin', 'user']), 'It looks like you\'re not signed in. Please sign in before submitting feedback.', true);
allowIf(!is_null($_REQUEST['id']), 'It looks like your request failed to specify a candidate to submit feedback for.', true);

$title = 'Review Candidate';
include_once PUBLIC_FILES . '/modules/header.php';

$qualificationStatusDao = new QualificationStatusDao($dbConn, $logger);
$candidateDao = new CandidateDao($dbConn, $logger);
$candidateFileDao = new CandidateFileDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$qualificationDao = new QualificationDao($dbConn, $logger);
$positionDao = new PositionDao($dbConn, $logger);
$qfrDao = new QualificationForRoundDao($dbConn, $logger);
$ffqDao = new FeedbackForQualDao($dbConn, $logger);
$feedbackDao = new FeedbackDao($dbConn, $logger);
$feedbackFileDao = new FeedbackFileDao($dbConn, $logger);

$candidateID = $_REQUEST['id'];
$candidate = $candidateDao->getCandidateById($candidateID);

allowIf($candidate, 'It looks like you tried to review a candidate that doesn\'t exist.', true);

$position = $positionDao->getPosition($candidate->getPositionID());
$rounds = $roundDao->getAllRoundsByPositionId($position->getID());
$candidateFiles = $candidateFileDao->getAllFilesForCandidate($candidateID);

allowIf(checkRoleForPosition('Any', $position?->getID()), 'It looks like you\'re not on the committee for that position. Please speak to the committee\'s search chair if you believe you should be added.', true); // Implicitly verifies that position exists
// Prevent unverified users from accessing the whole site
allowIf($position->getStatus() == 'Interviewing' || $position->getStatus() == 'Closed' || verifyPermissions('admin'), "It looks like this position hasn't started interviewing yet. Please ask the committee's search chair to update the position's status.", true);

include_once PUBLIC_FILES."/modules/breadcrumb.php";
renderBreadcrumb(["./pages/user/dashboard.php"=>"Dashboard", ("./pages/user/viewPosition.php?id=".$position->getID())=>$position->getTitle()], $title);
?>

<br><br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border-bottom: 2px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center;"><?php echo $candidate->getFirstName().' '.$candidate->getLastName() ?></h2>
        </div>
        <div class="col">
            <h6>Email: <button id="emailCandidate" class="btn btn-link" type="button" data-toggle="modal" data-target="#emailModal"><?php echo $candidate->getEmail() ?></button></h6>
            <h6>Phone: <a href="tel:<?php echo $candidate->getPhoneNumber() ?>"><?php echo $candidate->getPhoneNumber() ?></a></h6>
            <!-- <h6>Location: <a target="_blank" href="https://www.google.com/maps?q=<?php echo $candidate->getLocation() ?>"><?php echo $candidate->getLocation() ?></a></h6> -->
            <?php
                if(count($candidateFiles)) {
                    echo "<h6>Files: ";
                    foreach($candidateFiles as $index=>$file) {
                        echo "<a target='_blank' href='./uploads/candidate/".$file->getFileName()."'>".$file->getPurpose()."</a>".($index < count($candidateFiles) - 1 ? ", " : "");
                    }
                    echo "</h6>";
                }
            ?>
        </div>
    </div>

    <?php
    foreach($rounds as $index=>$round) {
        // Get the feedback for this round, or create a new one
        $feedback = $feedbackDao->getFeedbackForUser($_SESSION['userID'], $candidateID, $round->getID());
        if(!$feedback) {
            $feedback = new Feedback();
            $feedback->setUserID($_SESSION['userID']);
            $feedback->setCandidateID($candidateID);
            $feedback->setRoundID($round->getID());
            $feedback->setNotes(NULL);
            $feedback->setLastUpdated(NULL);
            $feedback->setID($feedbackDao->addFeedback($feedback));
        }

        // Output general round info
        $output = "
            <div class='row py-3'>
                <div class='col-sm py-1 my-auto' style='vertial-align: middle'>
                    <h4>".($index+1).": ".$round->getName()."</h4>
                </div>
                <div class='col-sm-2 py-1 my-auto'>
                    <button data-toggle='collapse' data-target='#round". $index+1 ."' type='button' class='btn btn-outline-dark float-right'><i class='fas fa-chevron-down'></i></button>
                </div>
            
                <div id='round". $index+1 ."' class='collapse container".(isset($_REQUEST['round'])&&$_REQUEST['round']==$round->getID() ? ' show' : '')."'>";
                    /* <div class='row d-block p-2 mb-3 mx-1 rounded' style='background: #ccc;'>
                        <p>Round Name: ".$round->getName()."</p>"; */ // Removed in favor of showing name in header
        if($round->getInterviewQuestionLink()) {
            $output .= "
                    <div class='row d-block p-2 mb-3 mx-1 rounded' style='background: #ccc;'>
                        <p>Interview Questions: <a target='_blank' href='".$round->getInterviewQuestionLink()."'>".$round->getInterviewQuestionLink()."</a></p>
                    </div>";
        }
                    
        // $output .= "</div>"; // Info about the round 


        // Output qualification evaluation for this round
        $qualifications = $qfrDao->getAllQualificationsForRound($round->getID());
        foreach($qualifications as $qualification) {
            $ffq = $ffqDao->getFeedbackForQual($feedback->getID(), $qualification->getID());
            $qualificationStatuses = $qualificationStatusDao->getAllStatuses();

            $output .= "
                <div class='row p-2 m-3 rounded bg-light border rounded'>
                    <div class='col-md'>
                        <button type='button' data-toggle='modal' data-target='#qualModal".$qualification->getID()."' class='btn btn-link text-primary text-left py-2".($qualification->getLevel() == 'Minimum' ? " font-weight-bold" : "")."'>
                            ".$qualification->getDescription()."
                        </button>
                    </div>
                    <div class='col-sm'>";

            if($ffq) {
                $output .= "<select id='score".$feedback->getID().','.$qualification->getID()."' class='custom-select text-center' onchange=\"updateFFQ(this, '".$feedback->getID()."','".$qualification->getID()."')\">";
            } else {
                $output .= "<select id='score".$feedback->getID().','.$qualification->getID()."' class='custom-select text-center' onchange=\"addFFQ(this, '".$feedback->getID()."','".$qualification->getID()."')\">";
            }
        
            foreach($qualificationStatuses as $qualificationStatus) {
                $output .= "<option ".($ffq && $ffq->getFeedbackQualificationStatusID() == $qualificationStatus->getID() ? 'selected' : '').
                    " value='".$qualificationStatus->getID()."'>".$qualificationStatus->getName()."</option>";
            }

            $output .= "
                        </select>
                    </div>
            "; // <hr>

            $output .= '</div>';
            
        } // Input for each qualification; connects to feedbackForQual table

        /* Notes & files section */
        $feedbackFiles = $feedbackFileDao->getAllFilesForFeedback($feedback->getID());
        $output .= "
            <div class='row p-2 mb-3 rounded'>
                <div class='col-sm-8 mt-2'>
                    <h6>My Notes</h6>
                    <textarea class='form-control' rows='5' placeholder='Notes for this round' onchange='updateNotes(this, \"".$feedback->getID()."\")'>".$feedback->getNotes()."</textarea>
                </div>
                <div class='col-sm-4 mt-2'>
                    <h6>My Uploads</h6>
                    <div class='custom-file'>
                        <label for='uploadFileInput".$feedback->getID()."' class='custom-file-label'>Upload New Feedback File</label>
				        <input type='file' id='uploadFileInput".$feedback->getID()."' class='custom-file-input' name='uploadFileInput".$feedback->getID()."' onchange='uploadFeedbackFile(this, \"".$feedback->getID()."\");' accept='.jpeg,.jpg,.png,.bmp,.JPG,.JPEG,.PNG,.BMP,.heic,.HEIC,.pdf,.PDF,.docx,.DOCX' multiple>
                        <label id='fileFeedback\"".$feedback->getID()."\"'></label>
                    </div>
                    <ul id='feedbackFiles".$feedback->getID()."'>";
        if($feedbackFiles) {
            foreach($feedbackFiles as $feedbackFile) {
                $output .= "<li id='feedbackFile".$feedbackFile->getID()."'>
                                <a target='_blank' href='uploads/feedback/".$feedbackFile->getFileName()."'>".$feedbackFile->getFileName()."</a>
                                <button class='float-right btn btn-danger mx-auto' type='button' onclick='removeFeedbackFile(this,".$feedbackFile->getID().");' id='delete".$feedbackFile->getID()."'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </li>\n";
            }
        }
        $output .= "</ul>
                </div>
            </div>";
            
        $output .= "</div></div>"; // End of this round

        if($index < count($rounds) - 1) {
            $output .= "<hr style='border-color: black; margin: 0px'>";
        } // Add line to seperate rounds

        echo $output;
    }
    
    ?>

</div>

<?php include_once PUBLIC_FILES . '/modules/qualificationModal.php'; ?>

<?php include_once PUBLIC_FILES . '/modules/emailModal.php'; ?>

<script>
    const CANDIDATE_ID = (new URL(window.location.href)).searchParams.get('id');

    function addFFQ(thisVal, feedbackID, qualificationID) {
        let status = document.getElementById(`score${feedbackID},${qualificationID}`).value;
        
        if(status == '--')
            return false;
        
        let data = {
            action: 'addFFQ',
            feedbackID: feedbackID,
            qualificationID: qualificationID,
            qualificationStatus: status
        };

        thisVal.disabled = true;

        api.post('/feedback.php', data).then(res => {
            // Update event listener
            thisVal.setAttribute('onchange', `updateFFQ(this, '${feedbackID}','${qualificationID}')`);

            snackbar(res.message, 'success');
        })/* .catch(err => {
            snackbar(err.message, 'error');
        }) */.finally(() => thisVal.disabled = false);
    }

    function updateFFQ(thisVal, feedbackID, qualificationID) {
        let status = document.getElementById(`score${feedbackID},${qualificationID}`).value;
        // Removed because we recieved feedback that it was annoying to have to approve it each time
        // if(!confirm('Are you sure you want to update your feedback? You should only do this if your committee has not yet discussed feedback for this round.'))
            // return false;
        
        let data = {
            action: 'updateFFQ',
            feedbackID: feedbackID,
            qualificationID: qualificationID,
            qualificationStatus: status
        };

        thisVal.disabled = true;

        api.post('/feedback.php', data).then(res => {
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

    function updateNotes(thisVal, feedbackID) {
        let data = {
            action: 'updateNotes',
            feedbackID: feedbackID,
            note: thisVal.value
        };

        api.post('/feedback.php', data).then(res => {
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        });
    }

    function uploadFeedbackFile(thisVal, feedbackID) {
        var isValidFile = false;
        var dbFileName = "";
		var file_data = $('#uploadFileInput'+feedbackID).prop('files')[0]
		var form_data = new FormData();
		form_data.append('file', file_data);
		form_data.append('action', 'uploadFeedbackFile');

        snackbar('Uploading feedback file', 'info');
		$.ajax({
			url: './ajax/Handler.php',
			type: 'POST',
			dataType: 'json',
			/* what we are expecting back */
			contentType: false,
			processData: false,
			data: form_data,
			success: function(result) {
                console.log("-- AJAX Result", result)
				if (result["successful"] == 1) {
					isValidFile = true;
					dbFileName = result["path"];
				} else if (result["successful"] == 0) {
					isValidFile = false;
					snackbar(result["string"], 'error');
				}
                if (isValidFile) {
                    saveFeedbackFileToDb(dbFileName, feedbackID);
                    console.log("File successfully uploaded");
                }
			},
			error: function(result) {
				isValidFile = false;
				snackbar(result["string"], 'error');
			}
		});
	}

    function removeFeedbackFile(thisVal, fileID) {
        let data = {
            action: 'removeFeedbackFile',
            fileID: fileID,
        }

        thisVal.disabled = true;
        api.post('/feedbackFile.php', data).then(res => {
            document.getElementById('feedbackFile'+fileID).remove();
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

    function saveFeedbackFileToDb(dbFileName, feedbackID) {
        let data = {
            action: 'createFeedbackFile',
            feedbackID: feedbackID,
            filename: dbFileName
        }

        api.post('/feedbackFile.php', data).then(res => {
            document.getElementById('uploadFileInput'+feedbackID).value = null;
            document.getElementById('feedbackFiles'+feedbackID).insertAdjacentHTML('beforeend', `
                <li id='feedbackFile${res.content}'>
                    <a target='_blank' href='uploads/feedback/${dbFileName}'>${dbFileName}</a>
                    <button class='float-right btn btn-danger mx-auto' type='button' onclick='removeFeedbackFile(this, ${res.content});' id='delete${res.content}'>
                        <i class='fas fa-trash'></i>
                    </button>
                </li>
            `);
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        });
    }
</script>

<?php include_once PUBLIC_FILES . '/modules/footer.php'; ?>
