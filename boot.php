<?php
// Datei: boot.php
$addon = rex_addon::get('uploader');

if (rex::isBackend() && rex::getUser()) {
    rex_perm::register('uploader[]');
    rex_perm::register('uploader[page]');

    if (!rex::getUser()->hasPerm('uploader[page]')) {
        $page = $this->getProperty('page');
        $page['hidden'] = 'true';
        $this->setProperty('page', $page);
    }
}    

rex_extension::register('PACKAGES_INCLUDED', function () {
    if (rex::isBackend() && rex::getUser() && rex::getUser()->hasPerm('uploader[]')) {
        $include_assets = 0;
        
        // Prüfen, ob wir uns im Medienpool oder im Uploader befinden
        if (rex_get('page', 'string') == 'mediapool/upload') {
            $this->setProperty('context', 'mediapool_upload');
            $include_assets = 1;
        }
        elseif (rex_get('page', 'string') == 'uploader/upload') {
            $this->setProperty('context', 'addon_upload');
            $include_assets = 1;
        }

        if ($include_assets) {
            // Dropzone.js und CSS per CDN einbinden
            rex_view::addCssFile('https://unpkg.com/dropzone@5/dist/min/dropzone.min.css');
            rex_view::addJsFile('https://unpkg.com/dropzone@5/dist/min/dropzone.min.js');
            
            // Eigene CSS- und JS-Dateien
            rex_view::addCssFile($this->getAssetsUrl('uploader.css'));
            rex_view::addJsFile($this->getAssetsUrl('uploader.js'));

            rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $ep) {
                // JavaScript-Variablen vor dem schließenden head-Tag einfügen
                $vars = include(rex_path::addon('uploader') . 'inc/vars.php');
                $ep->setSubject(str_replace('</head>', $vars . '</head>', $ep->getSubject()));
                
                // Wenn wir im Medienpool sind, benötigen wir ein zusätzliches Template für die Integration
                if (rex_get('page', 'string') == 'mediapool/upload') {
                    // Hier fügen wir den Dropzone-Container in den Medienpool ein
                    $search = '<div class="panel-body">';
                    $replace = '<div class="panel-body">
                    <!-- Dropzone-Vorlagen-Template -->
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
                    
                    // Wir suchen nach der Form und fügen unseren Dropzone-Container ein
                    $search2 = '<div class="form-group" id="rex-mediapool-choose-file">';
                    $replace2 = '<!-- Uploader Integration -->
                    <fieldset>
                        <legend>Dateien hochladen</legend>
                        <div id="uploader-dropzone" class="uploader-dropzone dropzone"></div>
                        <div class="uploader-options">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" ' . ($this->getConfig('image-resize-checked') ? 'checked' : '') . ' id="resize-images"> 
                                            ' . $this->i18n('buttonbar_resize_images') . '
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" ' . ($this->getConfig('filename-as-title-checked') ? 'checked' : '') . ' id="filename-as-title" name="filename-as-title" value="1"> 
                                            ' . $this->i18n('buttonbar_filename_as_title') . '
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-primary start">
                                        <i class="rex-icon rex-icon-upload"></i>
                                        ' . $this->i18n('uploader_buttonbar_start_upload') . '
                                    </button>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Original Medienpool Upload -->
                    <div class="form-group hidden" id="rex-mediapool-choose-file">';
                    
                    $content = $ep->getSubject();
                    $content = str_replace($search, $replace, $content);
                    $content = str_replace($search2, $replace2, $content);
                    $ep->setSubject($content);
                }
            });
        }
    }
}, rex_extension::LATE);
