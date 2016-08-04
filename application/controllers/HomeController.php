<?php


class HomeController  extends CI_Controller
{

    public $module = 'home';

    public function index()
    {
        $this->load->view('master', array(
            'view' => 'home'
        ));
    }



} 