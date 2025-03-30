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
            
            // Extension für die Ausgabe registrieren
            rex_extension::register('PAGE_MEDIAPOOL_HEADER', function (rex_extension_point $ep) {
                // Hier könnten wir mehr machen, wenn nötig
                return '';
            }, rex_extension::EARLY);
            
            // Unseren eigenen Output vor dem MediaPool registrieren
            rex_extension::register('PAGE_MEDIAPOOL_OUTPUT', function (rex_extension_point $ep) {
                $rexFileCategory = rex_request('rex_file_category', 'int', -1);
                $PERMALL = rex::getUser()->getComplexPerm('media')->hasCategoryPerm(0);
                if (!$PERMALL && !rex::getUser()->getComplexPerm('media')->hasCategoryPerm($rexFileCategory)) {
                    $rexFileCategory = 0;
                }
                
                $cats_sel = new rex_media_category_select();
                $cats_sel->setStyle('class="form-control"');
                $cats_sel->setSize(1);
                $cats_sel->setName('rex_file_category');
                $cats_sel->setId('rex-mediapool-category');
                $cats_sel->addOption(rex_i18n::msg('pool_kats_no'), '0');
                $cats_sel->setSelected($rexFileCategory);
                
                $resize = $this->getConfig('image-resize-checked') ? 'checked' : '';
                $filenameAsTitle = $this->getConfig('filename-as-title-checked') ? 'checked' : '';
                
                $form = '
                <section class="rex-page-section">
                    <div class="panel panel-edit">
                        <div class="panel-body">
                            <form id="fileupload" action="' . $this->getProperty('endpoint') . '" method="POST" enctype="multipart/form-data">
                                <fieldset>
                                    <legend>Datei-Informationen</legend>
                                    <dl class="rex-form-group form-group preserve append-meta-after">
                                        <dt>
                                            <label for="rex-mediapool-title">Titel</label>
                                        </dt>
                                        <dd>
                                            <input class="form-control" type="text" name="ftitle" value="" id="rex-mediapool-title">
                                        </dd>
                                    </dl>
                                    <dl class="rex-form-group form-group preserve">
                                        <dt>
                                            <label for="rex-mediapool-category">' . rex_i18n::msg('pool_file_category') . '</label>
                                        </dt>
                                        <dd>
                                            ' . $cats_sel->get() . '
                                        </dd>
                                    </dl>
                                </fieldset>
                                
                                <fieldset>
                                    <legend>Dateien hochladen</legend>
                                    <div id="uploader-dropzone" class="uploader-dropzone dropzone"></div>
                                    <div class="uploader-options">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" ' . $resize . ' id="resize-images"> 
                                                        ' . $this->i18n('buttonbar_resize_images') . '
                                                    </label>
                                                </div>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" ' . $filenameAsTitle . ' id="filename-as-title" name="filename-as-title" value="1"> 
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
                            </form>
                        </div>
                    </div>
                </section>
                
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
                </div>
                
                <style>
                /* Verberge das originale Medienpool-Formular */
                #rex-js-page-main .rex-page-section {
                    display: none;
                }
                #rex-js-page-main .rex-page-section:first-of-type {
                    display: block;
                }
                </style>
                ';
                
                return $form;
            }, rex_extension::EARLY);
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
            });
        }
    }
}, rex_extension::LATE);
