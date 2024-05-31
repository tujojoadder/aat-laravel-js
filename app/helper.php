<?php
if (!function_exists('cleanInput')) {
    function cleanInput($data)
    {
        $data = trim(preg_replace('/\s+/',' ',$data));
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
        
    }
}

if (!function_exists('dbug')) {
    function dbug()
    {
        dd("Hello");
        
    }
}
