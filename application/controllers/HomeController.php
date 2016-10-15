<?php

use Sirius\Application\Controller;

class HomeController  extends Controller
{

    public $module = 'home';

    public function index()
    {
        $this->load->view('master', array(
            'view' => 'home'
        ));
    }



} 