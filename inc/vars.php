<?php
// Datei: inc/vars.php
$allowed_filetypes = '""';	
$args = rex_request('args','array');
if(isset($args['types']) && trim($args['types'])) {
    $accepted_types = implode(',', rex_mediapool_getMediaTypeWhitelist($args));
    $allowed_filetypes = ".$accepted_types";
}
return '
<script>
var uploader_options = {
    messages: {
        maxNumberOfFiles: "'.rex_i18n::msg('uploader_errors_max_number_of_files').'",
        acceptFileTypes: "'.rex_i18n::msg('uploader_errors_accept_file_types').'",
        maxFileSize: "'.rex_i18n::msg('uploader_errors_max_file_size').'",
        minFileSize: "'.rex_i18n::msg('uploader_errors_min_file_size').'",
        selectFile: "'.rex_i18n::msg('uploader_filelist_add_to_widget').'"
    },
    context: "'.$this->getProperty('context').'",
    endpoint: "'.$this->getProperty('endpoint').'",
    loadImageMaxFileSize: '.((int)$this->getConfig('image-max-filesize')*1000000).',
    imageMaxWidth: '.(int)$this->getConfig('image-max-width').',
    imageMaxHeight: '.(int)$this->getConfig('image-max-height').',
    acceptFileTypes: "'.$allowed_filetypes.'"
};
</script>
';
