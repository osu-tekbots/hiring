<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions(['admin', 'user']), 'It looks like you\'re not signed in. Please sign in before viewing positions.', true);
allowIf(!is_null($_REQUEST['id']), 'It looks like your request failed to specify a position to pull data for.', true);
allowIf(checkRoleForPosition('Any', $_REQUEST['id']), 'It looks like you\'re not on the committee for that position. Please speak to the committee\'s search chair if you believe you should be added.', true); // Implicitly verifies that position exists


use DataAccess\CandidateDao;
use DataAccess\PositionDao;
use DataAccess\RoundDao;
use DataAccess\FeedbackDao;
use DataAccess\QualificationForRoundDao;
use DataAccess\FeedbackForQualDao;

$positionDao = new PositionDao($dbConn, $logger);
$candidateDao = new CandidateDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$feedbackDao = new FeedbackDao($dbConn, $logger);
$qualForRoundDao = new QualificationForRoundDao($dbConn, $logger);
$feedbackForQualDao = new FeedbackForQualDao($dbConn, $logger);

$position = $positionDao->getPosition($_REQUEST['id']);

allowIf($position, 'It looks like you tried to view a position that doesn\'t exist.', true);

$candidates = $candidateDao->getCandidatesByPositionId($_REQUEST['id']);

// Prevent unverified users from accessing the whole site
allowIf($position->getStatus() != 'Requested' || verifyPermissions('admin'), "It looks like this position hasn't been approved yet. Please request approval from the site admins to view this position.", true);

$title = 'View Position';
include_once PUBLIC_FILES . '/modules/header.php';

include_once PUBLIC_FILES."/modules/breadcrumb.php";
renderBreadcrumb(["./pages/userDashboard.php"=>"Dashboard"], $position->getTitle());

/**
 * Determines the last round that the current user has finished submitting FeedbackForQuals for the given candidate for. 
 * Used to determine the status to display.
 * 
 * @param string $candidateID The candidate to check if there's FeedbackForQuals for
 * 
 * @return Model\Round|bool The last round that the user has finished filling out FeedbackForQuals for, or `false` if 
 *  they haven't finished any yet
 */
function determineLastFinishedRound($roundDao, $feedbackDao, $qualForRoundDao, $feedbackForQualDao, $candidateID) {
    $lastRound = false;
    $rounds = $roundDao->getAllRoundsByPositionId($_REQUEST['id']);

    foreach($rounds as $round) {
        $feedback = $feedbackDao->getFeedbackForUser($_SESSION['userID'], $candidateID, $round->getID());
        if(!$feedback) {
            continue;
        }
        $totalQuals = $qualForRoundDao->getAllQualificationsForRound($round->getID());
        $filledQuals = $feedbackForQualDao->getFeedbackForQualByFeedbackID($feedback->getID());
        if(count($totalQuals) == count($filledQuals)) {
            $lastRound = $round;
        }
    }

    return $lastRound;
}

/**
 * Determines the first round that the current user has not finished submitting FeedbackForQuals for the given candidate for. 
 * Used to determine the review button text to display.
 * 
 * @param string $candidateID The candidate to check if there's FeedbackForQuals for
 * 
 * @return Model\Round|bool The first round that the user has not finished filling out FeedbackForQuals for, or 
 *  `false` if they have finished all rounds already
 */
function determineNextRound($roundDao, $feedbackDao, $qualForRoundDao, $feedbackForQualDao, $candidateID) {
    $nextRound = false;
    $rounds = $roundDao->getAllRoundsByPositionId($_REQUEST['id']);

    foreach($rounds as $round) {
        $feedback = $feedbackDao->getFeedbackForUser($_SESSION['userID'], $candidateID, $round->getID());
        if(!$feedback) {
            $nextRound = $round;
            break;
        }
        $totalQuals = $qualForRoundDao->getAllQualificationsForRound($round->getID());
        $filledQuals = $feedbackForQualDao->getFeedbackForQualByFeedbackID($feedback->getID());
        if(count($totalQuals) != count($filledQuals)) {
            $nextRound = $round;
            break;
        }
    }

    return $nextRound;
}
?>

<br><br><br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col-2"></div>
        <div class="col"><h2 class="my-auto w-100 text-center">Candidates</h2></div>
        <div class="col-2">
            <?php
                if($position->getStatus() == 'Open' && checkRoleForPosition('Search Chair', $_REQUEST['id'])) {
                    echo "<button id='startInterviewingBtn' type='button' class='btn btn-outline-success float-right'>Start Interviewing</a>";
                }
            ?>
        </div>
    </div>
    <?php
        if(count($candidates)) {
            foreach($candidates as $candidate) {
                $status = $candidate->getCandidateStatus()?->getName();
                $lastRoundQuery = '';
                $statusColor = ($status == null ? "" : ($status == "Hired" ? "text-success" : "text-danger")); // Set here to all 'in progress' statuses are black
                
                if($status == null) {
                    $lastRound = determineLastFinishedRound($roundDao, $feedbackDao, $qualForRoundDao, $feedbackForQualDao, $candidate->getID());
                    if($lastRound === false) {
                        $status = "No Reviews Completed";
                    } else {
                        $status = "Completed ".$lastRound->getName();
                        $lastRoundQuery = '&round='.$lastRound->getID();
                    }
                }

                $nextRoundBtnStyle = "btn-primary";
                $nextRound = determineNextRound($roundDao, $feedbackDao, $qualForRoundDao, $feedbackForQualDao, $candidate->getID());
                $nextRoundQuery = '';
                if($nextRound === false) {
                    $nextRound = "Update Reviews";
                    $nextRoundBtnStyle = "btn-warning";
                } else {
                    $nextRoundQuery = "&round=".$nextRound->getID();
                    $nextRound = "Complete ".$nextRound->getName();
                }
                
                // NOTE: Below, date '-0001-11-30' seems random, but is the format result for the timestamp '0000-00-00 00:00:00' in the database
                $output = "
                <div class='row py-3' style='border: 1px solid black'>
                    <div class='col-sm-2 my-auto' style='vertial-align: middle'>
                        <h4>".$candidate->getFirstName()." ".$candidate->getLastName()."</h4>
                    </div>
                    <div class='col-sm my-auto'>
                        <h5 class='$statusColor'>$status</h5>
                    </div>
                    <div class='col-sm-2 my-auto'>
                        <p>".($candidate->getDateApplied()?->format('Y-m-d') && $candidate->getDateApplied()?->format('Y-m-d') != '-0001-11-30' ? 'Applied: '.$candidate->getDateApplied() ->format('Y-m-d') : '')."</p>
                    </div>
                    <div class='col-sm-2 my-auto'>";
                if($position->getStatus() == 'Interviewing' || $position->getStatus() == 'Closed')
                    $output .= "<a href='userCandidate.php?id=".$candidate->getID()."$nextRoundQuery' class='btn $nextRoundBtnStyle float-right'>$nextRound</a>";

                $output .= "
                </div>
                    <div class='col-sm-2 my-auto'>";

                if($position->getStatus() == 'Interviewing' || $position->getStatus() == 'Closed') {
                    $output .= "<a href='userCandidateSummary.php?id=".$candidate->getID()."$lastRoundQuery' class='btn btn-outline-primary float-right'>View All Reviews</a>";
                }

                $output .= "</div>
                </div>";

                echo $output;
            }
        } else {
            echo "
            <div class='row py-3' style='border: 1px solid black'>
                <h4 class='col text-center'>There are no candidates for this position.</h4>
            </div>";
        }
    ?>
</div>

<script>
    const POSITION_ID = (new URL(window.location.href)).searchParams.get('id');

    // Suppress unneeded error if button doesn't exist
    try {
        document.getElementById('startInterviewingBtn').addEventListener('click', e => {
            if(!confirm('Are you sure you want to change the position\'s status to "Interviewing"? This will restrict your options for modifying this position and allow committee members to begin submitting feedback for candidates.'))
                return false;

            let data = {
                action: 'startInterviewing',
                id: POSITION_ID
            };

            e.target.disabled = true;

            api.post('/position.php', data).then(res => {
                snackbar(res.message, 'success');
                location.reload();
            }).catch(err => {
                snackbar(err.message, 'error');
            }).finally(() => e.target.disabled = false);
        });
    } catch(e) {
    }
</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
