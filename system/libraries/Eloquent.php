<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent {

    public function __construct()
    {
        $db = null;
        include (APPPATH.'config/database.php');

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => $db['default']['dbdriver'],
            'host'      => $db['default']['hostname'],
            'database'  => $db['default']['database'],
            'username'  => $db['default']['username'],
            'password'  => $db['default']['password'],
            'charset'   => $db['default']['char_set'],
            'collation' => $db['default']['dbcollat'],
            'prefix'    => $db['default']['dbprefix'],
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

}


