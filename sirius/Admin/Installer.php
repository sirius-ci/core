<?php

namespace Sirius\Admin;


abstract class Installer
{
    public $steps = array(
        'insertData'
    );

    public function __get($key)
    {
        $CI =& get_instance();
        return $CI->$key;
    }


} 