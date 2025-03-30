<?php
echo rex_view::title('Uploader');
$addon = rex_addon::get('uploader');

$rex_file_category = rex_request('rex_file_category', 'int', -1);
$PERMALL = rex::getUser()->getComplexPerm('media')->hasCategoryPerm(0);
if (!$PERMALL && !rex::getUser()->getComplexPerm('media')->hasCategoryPerm($rex_file_category))
{
    $rex_file_category = 0;
}
$cats_sel = new rex_media_category_select();
$cats_sel->setStyle('class="form-control"');
$cats_sel->setSize(1);
$cats_sel->setName('rex_file_category');
$cats_sel->setId('rex-mediapool-category');
$cats_sel->addOption(rex_i18n::msg('pool_kats_no'), '0');
$cats_sel->setSelected($rex_file_category);

$resize = $addon->getConfig('image-resize-checked') ? 'checked' : '';
$filenameAsTitle = $addon->getConfig('filename-as-title-checked') ? 'checked' : '';
?>

<section class="rex-page-section">
    <div class="panel panel-edit">
        <div class="panel-body">
            <form id="fileupload" action="<?php echo $addon->getProperty('endpoint'); ?>" method="POST" enctype="multipart/form-data">
                <fieldset>
                    <!-- Meta-Informationen für Dateien -->
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
                            <label for="rex-mediapool-category"><?php echo rex_i18n::msg('pool_file_category'); ?></label>
                        </dt>
                        <dd>
                            <?php echo $cats_sel->get(); ?>
                        </dd>
                    </dl>
                </fieldset>
                
                <fieldset>
                    <!-- Upload-Bereich -->
                    <legend>Dateien hochladen</legend>
                    
                    <!-- Dropzone-Container -->
                    <div id="uploader-dropzone" class="uploader-dropzone dropzone"></div>
                    
                    <!-- Upload-Optionen -->
                    <div class="uploader-options">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" <?php echo $resize; ?> id="resize-images"> 
                                        <?php echo $addon->i18n('buttonbar_resize_images'); ?>
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" <?php echo $filenameAsTitle; ?> id="filename-as-title" name="filename-as-title" value="1"> 
                                        <?php echo $addon->i18n('buttonbar_filename_as_title'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-primary start">
                                    <i class="rex-icon rex-icon-upload"></i>
                                    <?php echo $addon->i18n('uploader_buttonbar_start_upload'); ?>
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
