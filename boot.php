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
        if (rex_get('page', 'string') == 'mediapool/upload') {
            $this->setProperty('context', 'mediapool_upload');
            $include_assets = 1;
        }
        elseif (rex_get('page', 'string') == 'uploader/upload') {
            $this->setProperty('context', 'addon_upload');
            $include_assets = 1;
        }

        if ($include_assets) {
            // JavaScript-Variablen gleich direkt einbinden, nicht über den OUTPUT_FILTER
            $vars = include(rex_path::addon('uploader') . 'inc/vars.php');
            rex_view::addJsContent('uploader_options', $vars);
            
            // Dropzone.js und CSS per CDN einbinden
            rex_view::addCssFile('https://unpkg.com/dropzone@5/dist/min/dropzone.min.css');
            rex_view::addJsFile('https://unpkg.com/dropzone@5/dist/min/dropzone.min.js');
            
            // Eigene CSS- und JS-Dateien
            rex_view::addCssFile($this->getAssetsUrl('uploader.css'));
            rex_view::addJsFile($this->getAssetsUrl('uploader.js'));

            rex_extension::register('OUTPUT_FILTER', function (rex_extension_point $ep) {
                $buttonbar_template = include(rex_path::addon('uploader') . 'inc/buttonbar.php');
                $ep->setSubject(str_replace('</body>', $buttonbar_template . '</body>', $ep->getSubject()));
            });
        }
    }
}, rex_extension::LATE);
