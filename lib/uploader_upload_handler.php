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
        // Die Logik des ursprünglichen handle_file_upload verwenden, aber mit der temporären Datei als Quelle
        $file = new \stdClass();
        $file->name = $this->get_file_name($temp_uploaded_file, $name, $size, $type, $error, $index, $content_range);
        $file->size = $this->fix_integer_overflow((int)$size);
        $file->type = $type;
        
        if ($this->validate($temp_uploaded_file, $file, $error, $index, $content_range)) {
            $this->handle_form_data($file, $index);
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $file_path = $this->get_upload_path($file->name);
            
            // Datei kopieren oder verschieben
            if (copy($temp_uploaded_file, $file_path)) {
                $file_size = $this->get_file_size($file_path);
                if ($file_size === $file->size) {
                    // Die REDAXO-spezifische Verarbeitung
                    $file->upload_complete = 1;
                    $old_name = basename($file_path);
                    $path_parts = pathinfo($file_path);
                    $new_name = $path_parts['filename'];
                    $do_subindexing = false;
                    
                    // dateiname endet mit " (jfucounterXjfucounter)" -> vom uploader hochgezaehlt
                    preg_match('/(.+)( \(jfucounter\d+jfucounter\))/', $new_name, $matches);
                    if ($matches) {
                        $new_name = $matches[1];
                    }
                    
                    // dateiname genauso fertig machen wie im medienpoolupload/ -sync
                    $new_name = rex_string::normalize($new_name, '_', '-.');
                    if (isset($path_parts['extension'])) {
                        // ---- ext checken - alle scriptendungen rausfiltern
                        if (in_array($path_parts['extension'], rex_addon::get('mediapool')->getProperty('blocked_extensions'))) {
                            $new_name .= $path_parts['extension'];
                            $path_parts['extension'] = 'txt';
                        }
                        
                        // ---- multiple extension check
                        foreach (rex_addon::get('mediapool')->getProperty('blocked_extensions') as $ext) {
                            $new_name = str_replace($ext . '.', $ext . '_.', $new_name);
                        }
                        $new_name = $new_name . '.' . $path_parts['extension'];
                    }
                    
                    // es gibt schon eine datei mit dem neuen namen, mp muss hochzaehlen
                    if ($new_name != $old_name && is_file(rex_path::media($new_name))) {
                        $do_subindexing = true;
                    }
                    
                    // finalen namen holen
                    $new_name = rex_mediapool_filename($new_name, $do_subindexing);
                    $file->name = $new_name;
                    $file_path = rex_path::media($new_name);
                    
                    // datei umbenennen und synchronisieren
                    rename(rex_path::media($old_name), rex_path::media($new_name));
                    $catid = rex_post('rex_file_category');
                    $title = rex_post('ftitle', 'string', '');

                    if(rex_post("filename-as-title", "int", "") === 1) {
                        $title = $path_parts['filename'];
                    }

                    $success = rex_mediapool_syncFile($file->name, $catid, $title);
                    $mediaFile = rex_media::get($success['filename']);

                    //vorläufiger Bugfix wegen überschriebener Daten aus MEDIA_ADDED / MEDIA_UPDATED
                    //gilt solange, wie der PR 5852 nicht gmerged wurde (https://github.com/redaxo/redaxo/pull/5852)
                    $mediaMetaSql = rex_sql::factory();
                    $mediaMetaResult = $mediaMetaSql->getArray('SELECT column_name AS column_name FROM information_schema.columns WHERE table_name = "rex_media" AND column_name LIKE "med_%"');
                    $metainfos = [];

                    if(!isset($this->savedPostVars)) {
                        $this->savedPostVars = $_POST;
                    }

                    if ($mediaMetaSql->getRows() > 0) {
                        foreach ($mediaMetaResult as $metaField) {
                            if (!isset($metaField['column_name'])) {
                                continue;
                            }

                            $metaName = $metaField['column_name'];
                            $value = $mediaFile->getValue($metaName); //Bereits erfasster Wert durch MEDIA_ADDED/MEDIA_UPDATED
                            if(isset($this->savedPostVars[$metaName]) && mb_strlen($this->savedPostVars[$metaName]) > 0) {
                                //Uploader-Feature: Nutze angegebene Daten für alle Dateien
                                $value = $this->savedPostVars[$metaName];
                            }

                            $metainfos[$metaName] = $value;
                            $_POST[$metaName] = $value;
                        }
                    }

                    // merge metainfos with success array
                    $success = array_merge($success, $metainfos);
                    //ENDE vorläufiger Bugfix wegen überschriebener Daten aus MEDIA_ADDED / MEDIA_UPDATED
                    
                    // metainfos schreiben
                    uploader_meta::save($success);
                    
                    $file->url = $this->get_download_url($file->name);
                    if ($this->has_image_file_extension($file->name)) {
                        $this->handle_image_file($file_path, $file);
                    }
                } else {
                    $file->size = $file_size;
                    if ($this->options['discard_aborted_uploads']) {
                        unlink($file_path);
                        $file->error = $this->get_error_message('abort');
                    }
                }
                $this->set_additional_file_properties($file);
            } else {
                $file->error = $this->get_error_message('move_error');
            }
        }
        
        return $file;
    }
    
    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
        // Wenn es sich um einen Chunk-Upload handelt, verwende die spezielle Chunk-Verarbeitung
        if ($this->is_chunked_upload()) {
            return $this->handle_chunk_upload($uploaded_file, $name, $size, $type, $error, $index, $content_range);
        }
        
        // Standard-Verarbeitung aus dem Original-Handler
        $file = new \stdClass();
        $file->name = $this->get_file_name($uploaded_file, $name, $size, $type, $error, $index, $content_range);
        $file->size = $this->fix_integer_overflow((int)$size);
        $file->type = $type;
        if ($this->validate($uploaded_file, $file, $error, $index, $content_range)) {
            $this->handle_form_data($file, $index);
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $file_path = $this->get_upload_path($file->name);
            $append_file = $content_range && is_file($file_path) &&
                $file->size > $this->get_file_size($file_path);
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen($this->options['input_stream'], 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = $this->get_file_size($file_path, $append_file);
            if ($file_size === $file->size) {
                // iw patch start
                $file->upload_complete = 1;
                $old_name              = basename($file_path);
                $path_parts            = pathinfo($file_path);
                $new_name              = $path_parts['filename'];
                // initial auf false, ansonsten wuerde der mediapool immer eins hochgezaehlen
                $do_subindexing = false;
                
                // dateiname endet mit " (jfucounterXjfucounter)" -> vom uploader hochgezaehlt
                preg_match('/(.+)( \(jfucounter\d+jfucounter\))/', $new_name, $matches);
                if ($matches) {
                    $new_name = $matches[1];
                }
                
                // dateiname genauso fertig machen wie im medienpoolupload/ -sync
                $new_name = rex_string::normalize($new_name, '_', '-.');
                if (isset($path_parts['extension'])) {
                    // ---- ext checken - alle scriptendungen rausfiltern
                    if (in_array($path_parts['extension'], rex_addon::get('mediapool')->getProperty('blocked_extensions'))) {
                        $new_name                .= $path_parts['extension'];
                        $path_parts['extension'] = 'txt';
                    }
                    
                    // ---- multiple extension check
                    foreach (rex_addon::get('mediapool')->getProperty('blocked_extensions') as $ext) {
                        $new_name = str_replace($ext . '.', $ext . '_.', $new_name);
                    }
                    $new_name = $new_name . '.' . $path_parts['extension'];
                }
                
                // es gibt schon eine datei mit dem neuen namen, mp muss hochzaehlen
                if ($new_name != $old_name && is_file(rex_path::media($new_name))) {
                    $do_subindexing = true;
                }
                
                // finalen namen holen
                $new_name   = rex_mediapool_filename($new_name, $do_subindexing);
                $file->name = $new_name;
                $file_path  = rex_path::media($new_name);
                
                // datei umbenennen und synchronisieren
                rename(rex_path::media($old_name), rex_path::media($new_name));
                $catid   = rex_post('rex_file_category');
                $title   = rex_post('ftitle', 'string', '');

                if(rex_post("filename-as-title", "int", "") === 1) {
                    $title = $path_parts['filename'];
                }

                $success = rex_mediapool_syncFile($file->name, $catid, $title);
                $mediaFile = rex_media::get($success['filename']);

                //vorläufiger Bugfix wegen überschriebener Daten aus MEDIA_ADDED / MEDIA_UPDATED
                //gilt solange, wie der PR 5852 nicht gmerged wurde (https://github.com/redaxo/redaxo/pull/5852)
                $mediaMetaSql = rex_sql::factory();
                $mediaMetaResult = $mediaMetaSql->getArray('SELECT column_name AS column_name FROM information_schema.columns WHERE table_name = "rex_media" AND column_name LIKE "med_%"');
                $metainfos = [];

                if(!isset($this->savedPostVars)) {
                    $this->savedPostVars = $_POST;
                }

                if ($mediaMetaSql->getRows() > 0) {
                    foreach ($mediaMetaResult as $metaField) {
                        if (!isset($metaField['column_name'])) {
                            continue;
                        }

                        $metaName = $metaField['column_name'];
                        $value = $mediaFile->getValue($metaName); //Bereits erfasster Wert durch MEDIA_ADDED/MEDIA_UPDATED
                        if(isset($this->savedPostVars[$metaName]) && mb_strlen($this->savedPostVars[$metaName]) > 0) {
                            //Uploader-Feature: Nutze angegebene Daten für alle Dateien
                            $value = $this->savedPostVars[$metaName];
                        }

                        $metainfos[$metaName] = $value;
                        $_POST[$metaName] = $value;
                    }
                }

                // merge metainfos with success array
                $success = array_merge($success, $metainfos);
                //ENDE vorläufiger Bugfix wegen überschriebener Daten aus MEDIA_ADDED / MEDIA_UPDATED
                
                // metainfos schreiben
                uploader_meta::save($success);
                
                // iw patch end
                
                $file->url = $this->get_download_url($file->name);
                if ($this->has_image_file_extension($file->name)) {
                    if ($content_range && !$this->validate_image_file($file_path, $file, $error, $index)) {
                        unlink($file_path);
                    } else {
                        $this->handle_image_file($file_path, $file);
                    }
                }
            } else {
                $file->size = $file_size;
                if (!$content_range && $this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file->error = $this->get_error_message('abort');
                }
            }
            $this->set_additional_file_properties($file);
        }
        return $file;
    }
}
