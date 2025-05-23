<?php
class EnvLoader {
    public static function load($file) {
        
      
        if (!file_exists($file)) {
            return false;
        }
        
    
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Ignora comentários
            }
            list($name, $value) = array_pad(explode('=', $line, 2), 2, null);
            if ($name && $value !== null) {
                putenv(trim($name) . '=' . trim($value));
            }
        }
        return true;
    }
}