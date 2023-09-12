<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title w-100 text-center">Email Candidate</h5>
        </div>
        <div class="modal-body">
            <div class="input-group my-2">
                <div class="input-group-prepend"><h6 class="input-group-text">Subject</h6></div>
                <input id="emailSubject" class="form-control form-control-lg">
            </div>
            <textarea id="emailBody" class="form-control" rows="6"></textarea>
        </div>
        <div class="modal-footer">
            <button id="closeEmailModal" type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="sendEmail(this)">Send</button>
        </div>
    </div>
  </div>
</div>

<script>
    function sendEmail(thisVal) {
        let data = {
            action: 'sendEmail',
            candidateID: CANDIDATE_ID,
            subject: document.getElementById('emailSubject').value,
            body: document.getElementById('emailBody').value,
        };

        thisVal.disabled = true;

        api.post('/email.php', data).then(res => {
            document.getElementById('closeEmailModal').click();
            document.getElementById('emailSubject').value = '';
            document.getElementById('emailBody').value = '';
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }
</script>