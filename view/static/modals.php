<?php
if (!empty($CurrentUser->id) || $CurrentUser->id > 0) { ?>
    <!-- Image Modal -->
    <div class="image-modal" style="z-index: 1100;display: none">
        <span class="image-modal-close">&times;</span>
        <div class="d-flex flex-column h-100">
            <img class="image-modal-content">
            <div class="image-caption"></div>
        </div>
    </div>



<?php } ?>
