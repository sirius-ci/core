<?php
use Admin\Controllers\AdminController;

class HomeAdminController extends AdminController
{
    public $moduleTitle = 'Home';
    public $module = 'home';
    public $model = 'home';
    public $defaultAction = 'dashboard';

    public $actions = array(
        'dashboard'         => 'list',
        'logout'            => 'list',
        'denied'            => 'list',
        'language'          => 'list',
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

        $this->utils->breadcrumb('Önizleme');
        $this->render('dashboard');
    }


    public function options()
    {
        $options = $this->appmodel->options();
        $rules = array();

        if ($this->input->post()) {
            foreach ($options as $option) {
                if (! empty($option->arguments)) {
                    $rules[$option->name] = array(implode('|', array_keys($option->arguments)), "Lütfen {$option->title} geçerli bir değer veriniz.");
                }
            }

            $this->validate($rules);

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->optionsUpdate($options);

                if ($success) {
                    $this->alert->set('success', 'Kayıt düzenlendi.');
                    redirect(moduleUri('options'));
                }
                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->assets->importEditor();
        $this->utils->breadcrumb("Site Ayarları");

        $this->viewData['options'] = $options;

        $this->render('options');
    }


    public function password()
    {
        if (! $record = $this->appmodel->user($this->user->id)) {
            show_404();
        }

        if ($this->input->post()) {
            $this->validate([
                'password' => ['required', 'Lüfen parola yazın.']
            ]);

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->passwordChange($record);

                if ($success) {
                    $this->alert->set('success', 'Kayıt düzenlendi.');
                    redirect(moduleUri('password', $record->id));
                }

                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->utils->breadcrumb('Parola Değiştir');
        $this->viewData['record'] = $record;

        $this->render('password');
    }


    public function users()
    {
        $records = array();
        $paginate = null;
        $recordCount = $this->appmodel->userCount();

        if ($recordCount > 0) {
            $paginate = $this->paginate($recordCount);
            $records = $this->appmodel->userAll($paginate);
        }

        $this->utils->breadcrumb('Kullanıcılar', moduleUri('users'));
        $this->utils->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['paginate'] = $paginate;

        $this->render('users/records');
    }


    public function userInsert()
    {
        if ($this->input->post()) {
            $this->validate([
                'username' => array('required', 'Lüfen kullanıcı adı yazın.'),
                'password' => array('required', 'Lüfen parola yazın.'),
                'group' => array('required|numeric', 'Lüfen kullanıcı grubu seçin.'),
            ]);

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->userInsert($this->modelData);

                if ($success) {
                    $this->alert->set('success', 'Kayıt eklendi.');
                    redirect(moduleUri('userUpdate', $success));
                }
            }
        }

        $this->utils->breadcrumb('Kullanıcılar', moduleUri('users'));
        $this->utils->breadcrumb('Kayıt ekle');

        $this->render('users/insert');
    }


    public function userUpdate()
    {
        if (! $record = $this->appmodel->user($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->validate([
                'username' => array('required', 'Lüfen kullanıcı adı yazın.'),
                'group' => array('required|numeric', 'Lütfen kullanıcı grubu seçin.'),
            ]);

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->userUpdate($record, $this->modelData);

                if ($success) {
                    $this->alert->set('success', 'Kayıt düzenlendi.');
                    redirect(moduleUri('userUpdate', $record->id));
                }
                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->utils->breadcrumb('Kullanıcılar', moduleUri('users'));
        $this->utils->breadcrumb('Kayıt Düzenle');

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
                $this->alert->set('error', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }

            $success = $this->appmodel->userDelete($ids);

            if ($success) {
                $this->alert->set('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            die();
        }

        // Normal sorgu ise tekli silme uygulanır
        if (! $record = $this->appmodel->user($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->userDelete($record);

        if ($success) {
            $this->alert->set('success', "Kayıt kaldırıldı. (#{$record->id})");
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->alert->set('error', 'Kayıt kaldırılamadı.');
        redirect($this->input->server('HTTP_REFERER'));

    }


    public function groups()
    {

        $records = array();
        $paginate = null;
        $recordCount = $this->appmodel->groupCount();

        if ($recordCount > 0) {
            $paginate = $this->paginate($recordCount);
            $records = $this->appmodel->groupAll($paginate);
        }

        $this->utils->breadcrumb('Gruplar', moduleUri('groups'));
        $this->utils->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['paginate'] = $paginate;

        $this->render('groups/records');

    }


    public function groupInsert()
    {
        if ($this->input->post()) {
            $this->validate([
                'name' => array('required', 'Lüfen grup adı yazın.')
            ]);

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->groupInsert($this->modelData);

                if ($success) {
                    $this->alert->set('success', 'Kayıt eklendi.');
                    redirect(moduleUri('groupUpdate', $success));
                }
            }
        }


        $this->utils->breadcrumb('Gruplar', moduleUri('groups'));
        $this->utils->breadcrumb('Kayıt ekle');


        $this->render('groups/insert');

    }


    public function groupUpdate()
    {
        if (! $record = $this->appmodel->group($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->validate([
                'name' => array('required', 'Lüfen grup adı yazın.')
            ]);

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->groupUpdate($record, $this->modelData);

                if ($success) {
                    $this->alert->set('success', 'Kayıt düzenlendi.');
                    redirect(moduleUri('groupUpdate', $record->id));
                }
                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->utils->breadcrumb('Gruplar', moduleUri('groups'));
        $this->utils->breadcrumb('Kayıt Düzenle');

        $this->viewData['record'] = $record;
        $this->viewData['modules'] = $this->appmodel->getModules();

        $this->assets->js('public/admin/js/module/home.js');
        $this->render('groups/update');
    }


    public function groupDelete()
    {
        // Ajax sorgusu  ise toplu silme uygulanır
        if ($this->input->is_ajax_request()) {
            $ids = $this->input->post('ids');

            if (count($ids) == 0) {
                $this->alert->set('error', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }

            $success = $this->appmodel->groupDelete($ids);

            if ($success) {
                $this->alert->set('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            die();
        }

        // Normal sorgu ise tekli silme uygulanır
        if (! $record = $this->appmodel->group($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->groupDelete($record);

        if ($success) {
            $this->alert->set('success', "Kayıt kaldırıldı. (#{$record->id})");
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->alert->set('error', 'Kayıt kaldırılamadı.');
        redirect($this->input->server('HTTP_REFERER'));

    }


    public function groupPermsUpdate()
    {
        if (! $record = $this->appmodel->group($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->groupPermsUpdate($record);

        if ($success) {
            $this->alert->set('success', 'Yetkiler düzenlendi.');
        } else {
            $this->alert->set('warning', 'Yetkilerde değişiklik olmadı.');
        }

        redirect(moduleUri('groupUpdate', $record->id));
    }


    public function login()
    {
        if ($this->session->userdata('adminlogin') === true) {
            redirect(moduleUri('dashboard'));
        }

        if ($this->input->post()) {

            $this->validate([
                'username' => array('required', 'Lütfen kullanıcı adı yazın.'),
                'password' => array('required', 'Lüfen parola yazın.')
            ]);

            if ($this->alert->has('error')) {
                $this->alert->clear('error');
                $this->alert->set('error', 'Kullanıcı yada Parola hatalı.');
            } else {
                $user = $this->db
                    ->from('admin_users')
                    ->where('username', $this->input->post('username'))
                    ->where('password', md5($this->input->post('password')))
                    ->get()
                    ->row();

                if ($user) {
                    $this->session->set_userdata('adminlogin', true);
                    $this->session->set_userdata('adminuser', $user);

                    redirect(moduleUri('dashboard'));
                } else {
                    $this->alert->set('error', 'Kullanıcı yada Parola hatalı.');
                }
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

        redirect(moduleUri('login'));
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