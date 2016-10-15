<?php

/**
 * Modül yapısına göre link yapıcı.
 *
 * @param $segments array Uri parametreleri
 * @param null $query Querystring parametreleri
 * @param bool|false $saveQuery Önceki querystring'leri korur
 * @return array|string
 */
function clink($segments, $query = null, $saveQuery = false)
{
    if (! is_array($segments) && strpos($segments, "http") === 0 ) {
        return $segments;
    }

    if (! is_array($segments)) {
        $segments = explode('/', $segments);
    }

    if (get_instance()->config->item('language') != 'tr') {
        array_unshift($segments, get_instance()->language);
    }

    $segments = implode('/', array_map('reservedUri', $segments));

    if (is_array($query)) {
        $gets = http_build_query($saveQuery ? array_merge($_GET, $query) : $query);
    } elseif ($saveQuery) {
        $gets = http_build_query($_GET);
    }

    return $segments . (! empty($gets) ? '?'.$gets : '');
}


/**
 * Rezerve edilmiş modül url'lerini dile göre karşılığını verir.
 *
 * @param $uri
 * @return mixed
 */
function reservedUri($uri)
{
    static $uriParam = array();

    if (empty ($uriParam)) {
        $uriList = get_instance()->config->item(get_instance()->language, 'reservedUri');
        $uriParam['keys'] = array();
        $uriParam['values'] = array();

        if ($uriList) {
            $uriParam['keys'] = array_keys($uriList);
            $uriParam['values'] = array_values($uriList);
        }
    }

    return str_replace($uriParam['keys'], $uriParam['values'], $uri);
}


/**
 * @param $array Obje dizisi.
 * @param $keyColumn Objeden key degeri alınacak olan kolon.
 * @param $valueColumn Objeden value değeri alınacak olan kolon.
 * @param null $prepend Dizide ilk paremetre olarak verilecek değer.
 * @return array
 */
function prepareForSelect($array, $keyColumn, $valueColumn, $prepend = null)
{
    if (! is_null($prepend)) {
        $result = ! is_array($prepend) ? array('' => $prepend) : $prepend;
    } else {
        $result = array();
    }

    foreach ($array as $item) {
        $result[$item->$keyColumn] = $item->$valueColumn;
    }

    return $result;
}


/**
 * Özel karakterleri dönüştürüp temizler.
 *
 * @param $str
 * @return mixed|string
 */
function makeSlug($str)
{
    $str_src = array(' ','Ç','ç','Ğ','ğ','İ','ı','Ö','ö','Ş','ş','Ü','ü');
    $str_rep = array('-','c','c','g','g','i','i','o','o','s','s','u','u');
    $str = preg_replace('/\s+/', ' ', trim($str));
    $str = str_replace($str_src, $str_rep, $str);
    $str = preg_replace('/[^A-Za-z0-9\-]/', '', $str);
    $str = preg_replace('/-+/', '-', trim($str));
    $str = strtolower($str);
    return $str;
}


function uploadPath($file, $path = '', $width = 480, $height = null, $text = '?')
{
    $fullpath = 'public/upload/'. (empty($path) ?: "$path/") . $file;
    if (is_file($fullpath)) {
        return $fullpath;
    }

    if (empty($height)) {
        $height = $width;
    }

    return 'http://fakeimg.pl/'. $width .'x'. $height .'/?text='. $text;
}


/**
 * Para formatlama kuruşları 2 hane olarak yuvarlar.
 *
 * @param $number
 * @param bool $fractional
 * @return mixed|string
 */
function money($number, $fractional = false)
{
    if ($fractional){
        $number = sprintf('%.2f', $number);
    }
    while (true){
        $replaced = preg_replace('/(-?\d+)(\d\d\d)/', '$1,$2', $number);
        if ($replaced != $number) {
            $number = $replaced;
        } else {
            break;
        }
    }
    return $number;
}



function lang($line, $convert = null)
{
    $slug = (! is_null($convert) ? $convert.'-':'') . makeSlug($line);
    $trans = get_instance()->lang->line($slug);

    if (empty($trans)) {
        $trans = (! is_null($convert) ? strConvert($line, $convert, get_instance()->language) : $line);
        $filepath = APPPATH .'/language/'. get_instance()->language .'/site_lang.php';
        $file = fopen($filepath, FOPEN_WRITE_CREATE);
        $data = '$lang[\''. $slug .'\'] = \''. $trans .'\';'. PHP_EOL;

        flock($file, LOCK_EX);
        fwrite($file, $data);
        flock($file, LOCK_UN);
        fclose($file);

        return $trans;
    }

    return $trans;
}


function strConvert($string, $to = null, $lang = 'tr')
{
    switch ($to) {
        case 'upper':
            $string = mb_strtoupper($lang === 'tr' ? str_replace('i', 'İ', $string) : $string, 'UTF-8');
            break;
        case 'lower':
            $string = mb_strtolower($lang === 'tr' ? str_replace('i', 'İ', $string) : $string, 'UTF-8');
            break;
        case 'ucwords':
            $string = ltrim(mb_convert_case($lang === 'tr' ? str_replace(array( ' I', ' ı', ' İ', ' i' ), array( ' I', ' I', ' İ', ' İ' ), ' '.$string) : $string, MB_CASE_TITLE, 'UTF-8'));
            break;
        case 'ucfirst':
            $first = mb_substr($string, 0, 1, 'UTF-8');
            $string = mb_strtoupper($lang === 'tr' ? str_replace('i', 'İ', $first) : $first, 'UTF-8') . mb_substr($string, 1, mb_strlen($string, 'UTF-8') - 1, 'UTF-8');
            break;
    }

    return $string;
}