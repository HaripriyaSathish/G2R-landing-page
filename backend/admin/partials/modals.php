<!-- Media picker -->
<div class="modal" id="mediaModal" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-head">
      <h3><i data-lucide="images"></i> Media Library</h3>
      <div class="modal-tools">
        <div class="input-icon"><i data-lucide="search"></i><input type="search" placeholder="Search images…" data-media-search></div>
        <label class="btn btn-primary btn-sm"><i data-lucide="upload"></i> Upload<input type="file" accept="image/*" multiple hidden data-media-upload></label>
      </div>
      <button class="icon-btn" data-close-modal aria-label="Close"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <div class="dropzone-inline" data-media-drop>Drop images here to upload</div>
      <div class="media-grid picker" data-media-grid></div>
    </div>
    <div class="modal-foot">
      <span class="muted" data-media-selected>Click an image to select it</span>
      <div><button class="btn btn-ghost" data-close-modal>Cancel</button> <button class="btn btn-primary" data-media-confirm disabled>Use this image</button></div>
    </div>
  </div>
</div>

<!-- Icon picker -->
<div class="modal" id="iconModal" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-head">
      <h3><i data-lucide="shapes"></i> Choose an icon</h3>
      <div class="modal-tools"><div class="input-icon"><i data-lucide="search"></i><input type="search" placeholder="Search icons…" data-icon-search></div></div>
      <button class="icon-btn" data-close-modal aria-label="Close"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body"><div class="icon-grid" data-icon-grid></div>
      <p class="muted small">Any icon name from <a href="https://lucide.dev/icons" target="_blank" rel="noopener">lucide.dev/icons</a> can be typed in the field directly.</p>
    </div>
  </div>
</div>

<!-- Centre result popup (success / error) -->
<div class="modal result-modal" id="resultModal" aria-hidden="true" role="alertdialog" aria-live="assertive">
  <div class="modal-dialog modal-sm">
    <div class="modal-body center">
      <div class="result-icon" data-result-icon>
        <svg class="checkmark" viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" fill="none"/><path fill="none" d="M15 27l7 7 15-16"/></svg>
        <svg class="crossmark" viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" fill="none"/><path fill="none" d="M18 18l16 16M34 18L18 34"/></svg>
      </div>
      <h3 data-result-title>Successfully done!</h3>
      <p class="muted" data-result-text></p>
      <button class="btn btn-primary" data-close-modal style="min-width:120px">OK</button>
      <div class="result-bar"><span></span></div>
    </div>
  </div>
</div>

<!-- Confirm -->
<div class="modal" id="confirmModal" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-body center">
      <div class="confirm-icon"><i data-lucide="alert-triangle"></i></div>
      <h3 data-confirm-title>Are you sure?</h3>
      <p class="muted" data-confirm-text>This action cannot be undone.</p>
      <div class="confirm-actions"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-danger" data-confirm-ok>Delete</button></div>
    </div>
  </div>
</div>
