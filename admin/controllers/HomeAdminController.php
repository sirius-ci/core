<?php
use Sirius\Admin\Manager;

class HomeAdminController extends Manager
{
    public $moduleTitle = 'Home';
    public $module = 'home';
    public $table = 'admin_users';
    public $model = 'home';
    public $type = null;
    public $defaultAction = 'dashboard';

    public $actions = array(
        'options'           => 'options',
        'password'          => 'password',
        'users'             => 'user-list',
        'userInsert'        => 'user-insert',
        'userUpdate'        => 'user-update',
        'userDelete'        => 'user-delete',
        'groups'            => 'group-list',
        'groupInsert'       => 'group-insert',
        'groupUpdate'       => 'group-update',
        'groupDelete'       => 'group-delete',
        'groupPermsUpdate'  => 'group-update',
    );

    public function dashboard()
    {
        $this->viewData['widgets'] = $this->appmodel->widgets();

        $this->breadcrumb('Önizleme');
        $this->render('dashboard');
    }

    public function options()
    {
        $options = $this->appmodel->options();

        if ($this->input->post()) {
            foreach ($options as $option) {
                if (! empty($option->arguments)) {
                    $this->form_validation->set_rules($option->name, "Lütfen {$option->title} geçerli bir değer veriniz.", implode('|', array_keys($option->arguments)));
                }
            }


            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->optionsUpdate($options);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');
                    redirect(clink(array($this->module, 'options')));
                }
                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->load->vars('public', array('js' => array(
            '../public/admin/plugin/ckeditor/ckeditor.js',
            '../public/admin/plugin/ckfinder/ckfinder.js'
        )));


        $this->breadcrumb("Site Ayarları");

        $this->viewData['options'] = $options;

        $this->render('options');
    }




    public function password()
    {
        if (! $record = $this->appmodel->user($this->user->id)) {
            show_404();
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('password', 'Lüfen parola yazın.', 'required');

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->passwordChange($record);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');
                    redirect(clink(array($this->module, 'password', $record->id)));
                }

                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->breadcrumb('Parola Değiştir');
        $this->viewData['record'] = $record;

        $this->render('password');
    }





    public function users()
    {
        $records = array();
        $pagination = null;
        $recordCount = $this->appmodel->userCount();

        if ($recordCount > 0) {
            $config = array(
                'base_url' => clink(array($this->module, 'users', 'records')),
                'total_rows' => $recordCount,
                'per_page' => 20
            );

            $this->load->library('pagination');
            $this->pagination->initialize($config);


            $records = $this->appmodel->userAll($this->pagination->per_page, $this->pagination->offset);
            $pagination = $this->pagination->create_links();
        }

        $this->breadcrumb('Kullanıcılar', clink(array($this->module, 'users')));
        $this->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['pagination'] = $pagination;

        $this->render('users/records');
    }


    public function userInsert()
    {
        if ($this->input->post()) {
            $this->form_validation->set_rules('username', 'Lüfen kullanıcı adı yazın.', 'required');
            $this->form_validation->set_rules('password', 'Lüfen parola yazın.', 'required');
            $this->form_validation->set_rules('group', 'Lütfen kullanıcı grubu seçin.', 'required|numeric');

            if ($this->form_validation->run() === false) {
                $this->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->userInsert($this->modelData);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt eklendi.');
                    redirect(clink(array($this->module, 'userUpdate', $success)));
                }
            }
        }

        $this->breadcrumb('Kullanıcılar', clink(array($this->module, 'users')));
        $this->breadcrumb('Kayıt ekle');

        $this->render('users/insert');
    }


    public function userUpdate()
    {
        if (! $record = $this->appmodel->user($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('username', 'Lüfen kullanıcı adı yazın.', 'required');
            $this->form_validation->set_rules('group', 'Lütfen kullanıcı grubu seçin.', 'required|numeric');

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->userUpdate($record, $this->modelData);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');
                    redirect(clink(array($this->module, 'userUpdate', $record->id)));
                }
                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->breadcrumb('Kullanıcılar', clink(array($this->module, 'users')));
        $this->breadcrumb('Kayıt Düzenle');

        $this->viewData['record'] = $record;
        $this->viewData['groups'] = $this->appmodel->getGroups();

        $this->render('users/update');
    }


    public function userDelete()
    {
        // Ajax sorgusu  ise toplu silme uygulanır
        if ($this->input->is_ajax_request()) {
            $ids = $this->input->post('ids');

            if (count($ids) == 0) {
                $this->utils->setAlert('danger', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }

            $success = $this->appmodel->userDelete($ids);

            if ($success) {
                $this->utils->setAlert('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            return true;
        }

        // Normal sorgu ise tekli silme uygulanır
        if (! $record = $this->appmodel->user($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->userDelete($record);

        if ($success) {
            $this->utils->setAlert('success', "Kayıt kaldırıldı. (#{$record->id})");
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->utils->setAlert('danger', 'Kayıt kaldırılamadı.');
        redirect($this->input->server('HTTP_REFERER'));

    }


    public function groups()
    {
        $records = array();
        $pagination = null;
        $recordCount = $this->appmodel->groupCount();

        if ($recordCount > 0) {
            $config = array(
                'base_url' => clink(array($this->module), 'groups'),
                'total_rows' => $recordCount,
                'per_page' => 20
            );

            $this->load->library('pagination');
            $this->pagination->initialize($config);


            $records = $this->appmodel->groupAll($this->pagination->per_page, $this->pagination->offset);
            $pagination = $this->pagination->create_links();
        }

        $this->breadcrumb('Gruplar', clink(array($this->module, 'groups')));
        $this->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['pagination'] = $pagination;

        $this->render('groups/records');
    }


    public function groupInsert()
    {

        if ($this->input->post()) {
            $this->form_validation->set_rules('name', 'Lüfen grup adı yazın.', 'required');

            if ($this->form_validation->run() === false) {
                $this->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->groupInsert($this->modelData);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt eklendi.');
                    redirect(clink(array($this->module, 'groupUpdate', $success)));
                }
            }
        }

        $this->breadcrumb('Gruplar', clink(array($this->module, 'groups')));
        $this->breadcrumb('Kayıt ekle');


        $this->render('groups/insert');

    }


    public function groupUpdate()
    {
        if (! $record = $this->appmodel->group($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('name', 'Lüfen grup adı yazın.', 'required');

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->groupUpdate($record, $this->modelData);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');
                    redirect(clink(array($this->module, 'groupUpdate', $record->id)));
                }
                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->breadcrumb('Gruplar', clink(array($this->module, 'groups')));
        $this->breadcrumb('Kayıt Düzenle');

        $this->viewData['record'] = $record;
        $this->viewData['modules'] = $this->appmodel->getModules();

        $this->render('groups/update');
    }


    public function groupDelete()
    {
        // Ajax sorgusu  ise toplu silme uygulanır
        if ($this->input->is_ajax_request()) {
            $ids = $this->input->post('ids');

            if (count($ids) == 0) {
                $this->utils->setAlert('danger', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }

            $success = $this->appmodel->groupDelete($ids);

            if ($success) {
                $this->utils->setAlert('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            return true;
        }

        // Normal sorgu ise tekli silme uygulanır
        if (! $record = $this->appmodel->group($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->groupDelete($record);

        if ($success) {
            $this->utils->setAlert('success', "Kayıt kaldırıldı. (#{$record->id})");
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->utils->setAlert('danger', 'Kayıt kaldırılamadı.');
        redirect($this->input->server('HTTP_REFERER'));

    }


    public function groupPermsUpdate()
    {
        if (! $record = $this->appmodel->group($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->groupPermsUpdate($record);

        if ($success) {
            $this->utils->setAlert('success', 'Kayıt düzenlendi.', 'perms');
        } else {
            $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.', 'perms');
        }

        redirect(clink(array($this->module, 'groupUpdate', $record->id)));
    }


    public function login()
    {
        if ($this->session->userdata('adminlogin') === true) {
            redirect(clink(array($this->module, 'dashboard')));
        }



        if ($this->input->post()) {
            $this->form_validation->set_rules('username', '', 'required');
            $this->form_validation->set_rules('password', '', 'required');


            if ($this->form_validation->run() == true) {
                $user = $this->db
                    ->from('admin_users')
                    ->where('username', $this->input->post('username'))
                    ->where('password', md5($this->input->post('password')))
                    ->get()
                    ->row();

                if ($user) {
                    $this->session->set_userdata('adminlogin', true);
                    $this->session->set_userdata('adminuser', $user);

                    redirect(clink(array($this->module, 'dashboard')));
                } else {
                    $this->utils->setAlert('danger', 'Kullanıcı yada Parola hatalı.');
                }
            } else {
                $this->utils->setAlert('danger', 'Kullanıcı yada Parola hatalı.');
            }
        }

        $this->load->view('helpers/master', array(
            'view' => 'helpers/home/login'
        ));
    }


    public function logout()
    {
        $this->session->unset_userdata('adminlogin');
        $this->session->unset_userdata('adminuser');

        redirect(clink(array($this->module, 'login')));
    }


    public function denied()
    {
        $this->load->view('helpers/master', array(
            'view' => 'helpers/home/denied'
        ));
    }


    public function language()
    {
        $languages = $this->config->item('languages');
        $segment = $this->uri->segment(3);
        $reference = $this->input->get('ref');

        if ($languages && $segment) {
            if (array_key_exists($segment, $languages)) {
                $this->session->set_userdata('language', $segment);
            }
        }

        if (! empty($reference)) {
            redirect($reference);
        } else {
            redirect('/');
        }
    }



} 