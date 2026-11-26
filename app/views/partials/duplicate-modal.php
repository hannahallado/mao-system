<?php
/**
 * Generic "possible duplicate" warning modal. Filled by public/assets/js/duplicate-guard.js from a JSON
 * payload (see DuplicateProfileService::toPayload). Reuse it for any screen that must warn before saving
 * (e.g. duplicate assistance in 5.0): include this partial + duplicate-guard.js and put
 * data-duplicate-check="<url>" on the form.
 */
?>
<div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateModalTitle" data-dup="title"></h5>
                <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" data-dup="message"></p>
                <div class="match-list" data-dup="matches"></div>
                <div class="mt-3" data-dup="reasonBox">
                    <label class="form-label" for="dupReason" data-dup="reasonLabel"></label>
                    <textarea class="form-control" id="dupReason" rows="2" data-dup="reason"></textarea>
                    <div class="invalid-feedback" data-dup="reasonError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Back to form</button>
                <button type="button" class="btn btn-primary btn-sm" data-dup="confirm"></button>
            </div>
        </div>
    </div>
</div>
