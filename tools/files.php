<?php
    function createFileIfNotPresent($file){
        if(!is_file($file)){
            file_put_contents($file, "");
        }
    }
	function createFolderIfNotPresent($folder){
		if (!file_exists($folder)) {
			mkdir('$folder', 0133, true);
		}
	}
?>