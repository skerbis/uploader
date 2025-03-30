<?php
// Datei: lib/rex_mediapool_upload.php

/**
 * Überschreibt die Standard-Uploadform des Medienpools mit dem Dropzone-Uploader
 */
function uploader_mediapool_uploadform($rex_file_category_id, $file_id = 1, $error = '', $warning = '', $args = [])
{
    $addon = rex_addon::get('uploader');
    
    $rex_file_category_id = (int) $rex_file_category_id;

    $cats_sel = new rex_media_category_select();
    $cats_sel->setStyle('class="form-control"');
    $cats_sel->setSize(1);
    $cats_sel->setName('rex_file_category');
    $cats_sel->setId('rex_file_category_' . $file_id);
    $cats_sel->addOption(rex_i18n::msg('pool_kats_no'), '0');
    $cats_sel->setSelected($rex_file_category_id);

    $arg_fields = '';
    foreach ($args as $arg_name => $arg_value) {
        $arg_fields .= '<input type="hidden" name="args[' . rex_escape($arg_name) . ']" value="' . rex_escape($arg_value) . '" />' . "\n";
    }

    $panel = '';
    $formElements = [];

    $e = [];
    $e['label'] = '<label for="rex-mediapool-title-' . $file_id . '">' . rex_i18n::msg('pool_file_title') . '</label>';
    $e['field'] = '<input class="form-control" type="text" id="rex-mediapool-title-' . $file_id . '" name="ftitle" value="" />';
    $formElements[] = $e;

    $e = [];
    $e['label'] = '<label for="rex_file_category_' . $file_id . '">' . rex_i18n::msg('pool_file_category') . '</label>';
    $e['field'] = $cats_sel->get();
    $formElements[] = $e;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $panel .= $fragment->parse('core/form/form.php');

    // Dropzone-Elemente für den Upload
    $resize = $addon->getConfig('image-resize-checked') ? 'checked' : '';
    $filenameAsTitle = $addon->getConfig('filename-as-title-checked') ? 'checked' : '';
    
    $dropzone_html = <<<EOT
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
    
    <fieldset>
        <legend>Dateien hochladen</legend>
        <div id="uploader-dropzone" class="uploader-dropzone dropzone"></div>
        <div class="uploader-options">
            <div class="row">
                <div class="col-md-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" {$resize} id="resize-images"> 
                            {$addon->i18n('buttonbar_resize_images')}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" {$filenameAsTitle} id="filename-as-title" name="filename-as-title" value="1"> 
                            {$addon->i18n('buttonbar_filename_as_title')}
                        </label>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-primary start">
                        <i class="rex-icon rex-icon-upload"></i>
                        {$addon->i18n('uploader_buttonbar_start_upload')}
                    </button>
                </div>
            </div>
        </div>
    </fieldset>
    
    <!-- Verstecktes Original-Upload-Feld -->
    <div style="display:none">
    <fieldset>
        <input type="hidden" name="media_method" value="add_file" />
EOT;

    $e = [];
    $e['label'] = '<label for="rex-mediapool-choose-file-' . $file_id . '">' . rex_i18n::msg('pool_file_file') . '</label>';
    $e['field'] = '<input id="rex-mediapool-choose-file-' . $file_id . '" type="file" name="file_new" />' . $arg_fields;
    $formElements[] = $e;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $panel .= $dropzone_html . $fragment->parse('core/form/form.php') . '
    </fieldset>
    </div>';

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', rex_i18n::msg('pool_file_insert'), false);
    $fragment->setVar('body', $panel, false);
    $fragment->setVar('buttons', [
        [
            'label' => rex_i18n::msg('pool_file_upload'),
            'attributes' => [
                'class' => ['btn-save'],
                'type' => 'submit',
                'name' => 'save',
                'value' => 'true',
            ],
        ],
        [
            'label' => rex_i18n::msg('pool_file_upload_get'),
            'attributes' => [
                'class' => ['btn-apply'],
                'type' => 'submit',
                'name' => 'saveandexit',
                'value' => 'true',
            ],
        ],
    ], false);
    
    $output = $fragment->parse('core/page/section.php');
    
    $output = '
    <form action="' . rex_url::backendPage('mediapool/media', ['csrf_token' => $addon->getCsrfToken()], false) . '" method="post" enctype="multipart/form-data">
        ' . $output . '
    </form>
    ';
    
    return $output;
}
