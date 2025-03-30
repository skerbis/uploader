<?php

class uploader_iw_upload_handler extends uploader_upload_handler
{
    /**
     * Postvars cache
     * @var array
     */
    private $savedPostVars;

    /**
     * Chunk details
     * @var array
     */
    private $chunkVars;

    public function __construct($options = null, $initialize = true, $error_messages = null) {
        parent::__construct($options, $initialize, $error_messages);
        
        // Chunk-Variablen aus dem Request erfassen
        $this->chunkVars = [
            'chunk' => isset($_REQUEST['dzchunkindex']) ? (int) $_REQUEST['dzchunkindex'] : null,
            'chunks' => isset($_REQUEST['dztotalchunkcount']) ? (int) $_REQUEST['dztotalchunkcount'] : null,
            'uuid' => isset($_REQUEST['dzuuid']) ? $_REQUEST['dzuuid'] : null,
            'original_filename' => isset($_REQUEST['dzfilename']) ? $_REQUEST['dzfilename'] : null
        ];
    }

    public function generate_response($content, $print_response = true)
    {
        $this->response = $content;
        if ($print_response) {
            // iw patch redaxo thumbnails laden
            
            foreach ($content['files'] as $v) {
                if (isset($v->upload_complete)) {
                    $media = rex_media::get($v->name);
                    if ($media->isImage()) {
                        $v->thumbnailUrl = 'index.php?rex_media_type=rex_mediapool_preview&rex_media_file=' . $v->name;
                        if (rex_file::extension($v->name) == 'svg') {
                            $v->thumbnailUrl = '/media/' . $v->name;
                        }
                    } else {
                        $file_ext         = substr(strrchr($v->name, '.'), 1);
                        $icon_class       = '';
                        $v->icon          = 1;
                        $v->iconclass     = $icon_class;
                        $v->iconextension = $file_ext;
                    }
                } else {
                    $file_ext         = substr(strrchr($v->name, '.'), 1);
                    $icon_class       = ' rex-mime-error';
                    $v->icon          = 1;
                    $v->iconclass     = $icon_class;
                    $v->iconextension = $file_ext;
                }
            }
            $json     = json_encode($content);
            $redirect = stripslashes((string)$this->get_post_param('redirect'));
            if ($redirect && preg_match($this->options['redirect_allow_target'], $redirect)) {
                $this->header('Location: ' . sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if ($this->get_server_var('HTTP_CONTENT_RANGE')) {
                $files = isset($content[$this->options['param_name']]) ?
                    $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    $this->header('Range: 0-' . (
                            $this->fix_integer_overflow((int)$files[0]->size) - 1
                        ));
                }
            }
            $this->body($json);
        }
        
        return $content;
    }
    
    protected function upcount_name_callback($matches)
    {
        $index = isset($matches[1]) ? ((int)$matches[1]) + 1 : 1;
        $ext   = isset($matches[2]) ? $matches[2] : '';
        
        return ' (jfucounter' . $index . 'jfucounter)' . $ext;
    }
    
    protected function upcount_name($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(jfucounter([\d]+)jfucounter\))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'),
            $name,
            1
        );
    }
    
    /**
     * Prüft, ob ein Chunk-Upload stattfindet
     */
    protected function is_chunked_upload() {
        return isset($this->chunkVars['chunk']) && isset($this->chunkVars['chunks']) && $this->chunkVars['chunks'] > 1;
    }
    
    /**
     * Gibt den temporären Dateinamen für einen Chunk zurück
     */
    protected function get_chunk_file_path($chunk_file_name) {
        $temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploader-chunks';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }
        return $temp_dir . DIRECTORY_SEPARATOR . $chunk_file_name;
    }
    
    /**
     * Verarbeitet einen Chunk-Upload
     */
    protected function handle_chunk_upload($uploaded_file, $name, $size, $type, $error, $index, $content_range) {
        $file = new \stdClass();
        $file->name = $this->chunkVars['original_filename'] ?: $name;
        $file->size = $this->fix_integer_overflow((int)$size);
        $file->type = $type;
        
        // Prüfen, ob der Chunk gültig ist
        if (!$this->validate($uploaded_file, $file, $error, $index, $content_range)) {
            return $file;
        }
        
        // Chunk-Verarbeitung
        $chunk = $this->chunkVars['chunk'];
        $chunks = $this->chunkVars['chunks'];
        $uuid = $this->chunkVars['uuid'];
        
        // Generiere einen temporären Namen für diesen Chunk
        $chunk_file_name = $uuid . '_' . $file->name;
        $chunk_file_path = $this->get_chunk_file_path($chunk_file_name);
        
        // Ist dies der erste Chunk? Dann Datei erstellen bzw. überschreiben
        if ($chunk === 0) {
            if (file_exists($chunk_file_path)) {
                unlink($chunk_file_path);
            }
            move_uploaded_file($uploaded_file, $chunk_file_path);
        } else {
            // Chunk an vorhandene Datei anhängen
            if (file_exists($chunk_file_path)) {
                $chunk_data = file_get_contents($uploaded_file);
                file_put_contents($chunk_file_path, $chunk_data, FILE_APPEND);
            } else {
                // Fehler: Vorherige Chunks fehlen
                $file->error = 'Chunk sequence error';
                return $file;
            }
        }
        
        // Wenn dies der letzte Chunk ist, verarbeite die komplette Datei
        if ($chunk === $chunks - 1) {
            // Original-Dateiname für die finale Verarbeitung
            $temp_uploaded_file = $chunk_file_path;
            
            // Größe der zusammengefügten Datei ermitteln
            $file->size = $this->get_file_size($temp_uploaded_file);
            
            // Datei in den Medienpool verschieben und verarbeiten
            $result_file = $this->handle_final_file($temp_uploaded_file, $file->name, $file->size, $file->type, $error, $index, $content_range);
            
            // Temporäre Chunk-Datei löschen
            if (file_exists($temp_uploaded_file)) {
                unlink($temp_uploaded_file);
            }
            
            return $result_file;
        }
        
        // Chunk wurde erfolgreich gespeichert, aber wir warten auf weitere Chunks
        $file->chunk = $chunk;
        $file->chunks = $chunks;
        
        return $file;
    }
    
    /**
     * Verarbeitet die fertige Datei nach dem Chunk-Upload
     */
    protected function handle_final_file($temp_uploaded_file, $name, $size, $type, $error, $index, $content_range) {
        // Verwende rex_media_service für den Upload
        $file = new \stdClass();
        $file->name = $name;
        $file->size = $this->fix_integer_overflow((int)$size);
        $file->type = $type;
        
        if ($this->validate($temp_uploaded_file, $file, $error, $index, $content_range)) {
            // Temporär nach REDAXO-Media Ordner kopieren
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $tempname = 'chunk_' . uniqid() . '_' . $file->name;
            $file_path = $this->get_upload_path($tempname);
            
            if (copy($temp_uploaded_file, $file_path)) {
                // Nutze rex_media_service für den Upload
                $data = [
                    'title' => rex_post('ftitle', 'string', ''),
                    'category_id' => (int) rex_post('rex_file_category', 'int', 0),
                    'file' => [
                        'name' => $file->name,
                        'tmp_name' => $file_path,
                        'error' => 0
                    ]
                ];
                
                // Wenn Dateiname als Titel verwendet werden soll
                if(rex_post("filename-as-title", "int", "") === 1) {
                    $path_parts = pathinfo($file->name);
                    $data['title'] = $path_parts['filename'];
                }
                
                try {
                    // Medienpool-Service zum Hochladen verwenden
                    $result = rex_media_service::addMedia($data, true, rex_post('args', 'array'));
                    
                    // Metadaten verarbeiten
                    if(!isset($this->savedPostVars)) {
                        $this->savedPostVars = $_POST;
                    }
                    
                    $mediaMetaSql = rex_sql::factory();
                    $mediaMetaResult = $mediaMetaSql->getArray('SELECT column_name AS column_name FROM information_schema.columns WHERE table_name = "rex_media" AND column_name LIKE "med_%"');
                    
                    if ($mediaMetaSql->getRows() > 0) {
                        $metainfos = [];
                        $mediaFile = rex_media::get($result['filename']);
                        
                        foreach ($mediaMetaResult as $metaField) {
                            if (!isset($metaField['column_name'])) {
                                continue;
                            }

                            $metaName = $metaField['column_name'];
                            $value = $mediaFile->getValue($metaName);
                            if(isset($this->savedPostVars[$metaName]) && mb_strlen($this->savedPostVars[$metaName]) > 0) {
                                $value = $this->savedPostVars[$metaName];
                            }

                            $metainfos[$metaName] = $value;
                            $_POST[$metaName] = $value;
                        }
                        
                        $result = array_merge($result, $metainfos);
                        uploader_meta::save($result);
                    }
                    
                    // Erfolg zurückgeben
                    $file->upload_complete = 1;
                    $file->name = $result['filename'];
                    $file->url = $this->get_download_url($file->name);
                    
                    // Thumbnail für Bilder
                    if ($this->has_image_file_extension($file->name)) {
                        $media = rex_media::get($file->name);
                        if ($media && $media->isImage()) {
                            $file->thumbnailUrl = 'index.php?rex_media_type=rex_mediapool_preview&rex_media_file=' . $file->name;
                            if (rex_file::extension($file->name) == 'svg') {
                                $file->thumbnailUrl = '/media/' . $file->name;
                            }
                        }
                    }
                    
                    $this->set_additional_file_properties($file);
                } catch (rex_api_exception $e) {
                    $file->error = $e->getMessage();
                } finally {
                    // Temporäre Datei löschen
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            } else {
                $file->error = 'Failed to move uploaded file.';
            }
        }
        
        return $file;
    }
    
    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
        // Wenn es sich um einen Chunk-Upload handelt, verwende die spezielle Chunk-Verarbeitung
        if ($this->is_chunked_upload()) {
            return $this->handle_chunk_upload($uploaded_file, $name, $size, $type, $error, $index, $content_range);
        }
        
        // Standard-Verarbeitung für normale Uploads
        $file = new \stdClass();
        $file->name = $this->get_file_name($uploaded_file, $name, $size, $type, $error, $index, $content_range);
        $file->size = $this->fix_integer_overflow((int)$size);
        $file->type = $type;
        
        if ($this->validate($uploaded_file, $file, $error, $index, $content_range)) {
            // rex_media_service für den Upload verwenden
            if (is_uploaded_file($uploaded_file)) {
                $data = [
                    'title' => rex_post('ftitle', 'string', ''),
                    'category_id' => (int) rex_post('rex_file_category', 'int', 0),
                    'file' => [
                        'name' => $file->name,
                        'tmp_name' => $uploaded_file,
                        'error' => 0
                    ]
                ];
                
                // Wenn Dateiname als Titel verwendet werden soll
                if(rex_post("filename-as-title", "int", "") === 1) {
                    $path_parts = pathinfo($file->name);
                    $data['title'] = $path_parts['filename'];
                }
                
                try {
                    // Medienpool-Service zum Hochladen verwenden
                    $result = rex_media_service::addMedia($data, true, rex_post('args', 'array'));
                    
                    // Metadaten verarbeiten
                    if(!isset($this->savedPostVars)) {
                        $this->savedPostVars = $_POST;
                    }
                    
                    $mediaMetaSql = rex_sql::factory();
                    $mediaMetaResult = $mediaMetaSql->getArray('SELECT column_name AS column_name FROM information_schema.columns WHERE table_name = "rex_media" AND column_name LIKE "med_%"');
                    
                    if ($mediaMetaSql->getRows() > 0) {
                        $metainfos = [];
                        $mediaFile = rex_media::get($result['filename']);
                        
                        foreach ($mediaMetaResult as $metaField) {
                            if (!isset($metaField['column_name'])) {
                                continue;
                            }

                            $metaName = $metaField['column_name'];
                            $value = $mediaFile->getValue($metaName);
                            if(isset($this->savedPostVars[$metaName]) && mb_strlen($this->savedPostVars[$metaName]) > 0) {
                                $value = $this->savedPostVars[$metaName];
                            }

                            $metainfos[$metaName] = $value;
                            $_POST[$metaName] = $value;
                        }
                        
                        $result = array_merge($result, $metainfos);
                        uploader_meta::save($result);
                    }
                    
                    // Erfolg zurückgeben
                    $file->upload_complete = 1;
                    $file->name = $result['filename'];
                    $file->url = $this->get_download_url($file->name);
                    
                    // Thumbnail für Bilder
                    if ($this->has_image_file_extension($file->name)) {
                        $media = rex_media::get($file->name);
                        if ($media && $media->isImage()) {
                            $file->thumbnailUrl = 'index.php?rex_media_type=rex_mediapool_preview&rex_media_file=' . $file->name;
                            if (rex_file::extension($file->name) == 'svg') {
                                $file->thumbnailUrl = '/media/' . $file->name;
                            }
                        }
                    }
                    
                    $this->set_additional_file_properties($file);
                } catch (rex_api_exception $e) {
                    $file->error = $e->getMessage();
                }
            } else {
                $file->error = 'File is not an uploaded file.';
            }
        }
        
        return $file;
    }
}
