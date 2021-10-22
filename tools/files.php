<?php
    function createFileIfNotPresent($file){
        if(!is_file($file)){
            $contents = '';           // Some simple example content.
            file_put_contents($file, $contents);     // Save our content to the file.
        }
    }
?>