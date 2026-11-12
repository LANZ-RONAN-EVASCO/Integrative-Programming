<?php
//184

//185
if (!function_exists('brand_logo')) {
    function brand_logo(): string
    {
        $dir = dirname(__DIR__) . '/assets/images/';
        $files = is_dir($dir) ? scandir($dir) : [];
        foreach ($files as $file) {
            if (preg_match('/^logo\.(png|jpe?g|webp|svg|gif)$/i', $file)) {
                $url = (defined('BASE_URL') ? BASE_URL : '') . '/assets/images/' . rawurlencode($file);
                return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="Logo" '
                     . 'style="width:100%;height:100%;object-fit:contain;border-radius:12px">';
            }
        }
        return icon('home');   //186
    }
}

//187
//188
if (!function_exists('hero_photo_attr')) {
    function hero_photo_attr(): string
    {
        $info = function_exists('site_info') ? site_info() : [];
        $path = trim((string)($info['hero_image'] ?? ''));
        if ($path === '' || !str_starts_with($path, 'uploads/')) {
            return '';
        }
        $file = dirname(__DIR__) . '/' . $path;
        if (!is_file($file)) {
            return '';
        }
        $url = (defined('BASE_URL') ? BASE_URL : '') . '/' . $path . '?v=' . filemtime($file);
        return ' style="background-image:linear-gradient(rgba(11,27,51,.68),rgba(11,27,51,.5)),url(\''
             . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
             . '\');background-size:cover;background-position:center"';
    }
}
