<?php
    /**
     * Generates an email modal that allows users to write up an email (subject and body) to send from the committee's
     *      email address to a given candidate. The modal can be opened by a button with the attributes
     *          ` type="button" data-toggle="modal" data-target="#emailModal" data-candidate-id="<CANDIDATE_ID_HERE>" `
     *      with <CANDIDATE_ID_HERE> replaced with the ID of the candidate that should be selected as the default email recipient.
     */
?>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header" style="flex-direction: column">
            <h5 class="modal-title w-100 text-center">Email Candidate</h5>
            <p class="col text-center mb-0">This will send an email as <?= $position->getCommitteeEmail() ?> and CC that email address on all emails.</p>
        </div>
        <div class="modal-body">
            <div class='input-group my-2'>
                <div class='input-group-prepend'><h6 class='input-group-text'>Recipient</h6></div>
                <select id='emailRecipient' class='custom-select form-control-lg'>";
                    <?php
                        $_candidates = $candidateDao->getCandidatesByPositionId($position->getId(), 'name');
                            
                        foreach($_candidates as $candidate) {
                            echo "<option value='".$candidate->getID()."'>".$candidate->getFirstName()." ".$candidate->getLastName()."</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="input-group my-2">
                <div class="input-group-prepend"><h6 class="input-group-text">Subject</h6></div>
                <input id="emailSubject" class="form-control form-control-lg">
            </div>
            <textarea id="emailBody" class="form-control" rows="6"></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-outline-danger" onclick="clearEmail()">Clear</button>
            <button type="button" class="btn btn-primary" onclick="sendEmail(this)">Send</button>
        </div>
    </div>
  </div>
</div>

<script>
    $('#emailModal').on('show.bs.modal', function (event) {
        const id = $(event.relatedTarget).data('candidate-id')
        const modal = $(this)
        modal.find('#emailRecipient').val(id);
    })

    function sendEmail(thisVal) {
        let data = {
            action: 'sendEmail',
            candidateID: document.getElementById('emailRecipient').value,
            subject: document.getElementById('emailSubject').value,
            body: document.getElementById('emailBody').value,
        };

        thisVal.disabled = true;

        api.post('/email.php', data).then(res => {
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

    function clearEmail() {
        document.getElementById('emailSubject').value = '';
        document.getElementById('emailBody').value = '';
    }
</script>