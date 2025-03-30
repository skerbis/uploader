<?php
$resize = $this->getConfig('image-resize-checked') == 'true' ? 'checked' : '';
$filenameAsTitle = $this->getConfig('filename-as-title-checked') == 'true' ? 'checked' : '';

// Kontext-spezifische Templates
if ($this->getProperty('context') == 'mediapool_upload') {
    $tmp = '
    <!-- Dropzone Template für Medienpool -->
    <div id="dropzone-preview-template" style="display: none;">
      <div class="dz-preview dz-file-preview">
        <div class="dz-image"><img data-dz-thumbnail /></div>
        <div class="dz-details">
          <div class="dz-size"><span data-dz-size></span></div>
          <div class="dz-filename"><span data-dz-name></span></div>
        </div>
        <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
        <div class="dz-error-message"><span data-dz-errormessage></span></div>
        <div class="dz-success-mark">
          <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg">
            <circle cx="27" cy="27" r="25" fill="white" stroke="#000" stroke-width="2"/>
            <path fill="none" stroke="#228B22" stroke-width="4" d="M14,27 L22,35 L42,15"/>
          </svg>
        </div>
        <div class="dz-error-mark">
          <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg">
            <circle cx="27" cy="27" r="25" fill="white" stroke="#000" stroke-width="2"/>
            <path fill="none" stroke="#D50000" stroke-width="4" d="M17,17 L37,37 M37,17 L17,37"/>
          </svg>
        </div>
      </div>
    </div>
    
    <div id="uploader-buttonbar-template" style="display: none">
        <dl class="rex-form-group form-group preserve" id="uploader-row">
        <dt></dt>
        <dd>
        <!-- The table listing the files available for upload/download -->
        <div class="uploader-dropzone"><span class="hint">' . $this->i18n('buttonbar_dropzone') . '</span>
        <ul role="presentation" class="uploader-queue files"></ul>
        </div>
        <div class="row fileupload-buttonbar">
            <div class="col-lg-7">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class="btn btn-success fileinput-button" style="display:none">
                    <i class="glyphicon glyphicon-plus"></i>
                    <span>' . $this->i18n('uploader_buttonbar_add_files') . '</span>
                    <input type="file" name="files[]" multiple="">
                </span>
                <button type="button" class="btn btn-primary start">
                    <i class="glyphicon glyphicon-upload"></i>
                    <span>' . $this->i18n('uploader_buttonbar_start_upload') . '</span>
                </button>
                <button type="reset" class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>' . $this->i18n('uploader_buttonbar_cancel') . '</span>
                </button>
            </div>
            <!-- The global progress state -->
            <div class="col-lg-5 fileupload-progress fade">
                <!-- The global progress bar -->
                <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar progress-bar-success" style="width:0%;"></div>
                </div>
                <!-- The extended global progress state -->
                <div class="progress-extended">&nbsp;</div>
            </div>
        </div>
        <div class="row fileupload-options">
            <div class="col-lg-12">
                <label><input type="checkbox" '.$resize.' id="resize-images"> ' . $this->i18n('buttonbar_resize_images') . '</label>
            </div>
            <div class="col-lg-12">
                <label><input type="checkbox" '.$filenameAsTitle.' id="filename-as-title" name="filename-as-title" value="1"> ' . $this->i18n('buttonbar_filename_as_title') . '</label>
            </div>
        </div>
        </dd>
        </dl>
    </div>';
} else {
    $tmp = '
    <div id="dropzone-preview-template" style="display: none;">
      <div class="dz-preview dz-file-preview">
        <div class="dz-image"><img data-dz-thumbnail /></div>
        <div class="dz-details">
          <div class="dz-size"><span data-dz-size></span></div>
          <div class="dz-filename"><span data-dz-name></span></div>
        </div>
        <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
        <div class="dz-error-message"><span data-dz-errormessage></span></div>
        <div class="dz-success-mark">
          <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg">
            <circle cx="27" cy="27" r="25" fill="white" stroke="#000" stroke-width="2"/>
            <path fill="none" stroke="#228B22" stroke-width="4" d="M14,27 L22,35 L42,15"/>
          </svg>
        </div>
        <div class="dz-error-mark">
          <svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg">
            <circle cx="27" cy="27" r="25" fill="white" stroke="#000" stroke-width="2"/>
            <path fill="none" stroke="#D50000" stroke-width="4" d="M17,17 L37,37 M37,17 L17,37"/>
          </svg>
        </div>
      </div>
    </div>';
}
return $tmp;
