<?php

use Sirius\Admin\Manager;

class MenuAdminController extends Manager
{
    public $moduleTitle = 'Menü Yönetimi';
    public $module = 'menu';
    public $table = 'menus';
    public $model = 'menu';


    // Arama yapılacak kolonlar.
    public $search = array('title', 'hint');


    // Filtreleme yapılacak querystring/kolonlar.
    // public $filter = array('group');

    public $actions = array(
        'records' => 'list',
        'childs' => 'list',
        'order' => 'list',
        'module' => 'insert',
        'insert' => 'insert',
        'update' => 'update',
        'delete' => 'delete',
        'groupInsert' => 'list',
        'groupUpdate' => 'list',
        'groupDelete' => 'list',
    );


    public function childs()
    {
        if (! $parent = $this->appmodel->id($this->uri->segment(3))) {
            show_404();
        }

        $records = array();
        $pagination = null;
        $recordCount = $this->appmodel->childCount($parent);

        if ($recordCount > 0) {
            $config = array(
                'base_url' => clink(array($this->module, 'childs', $parent->id)),
                'total_rows' => $recordCount,
                'per_page' => 19
            );

            $this->load->library('pagination');
            $this->pagination->initialize($config);


            $records = $this->appmodel->childAll($parent, $this->pagination->per_page +1, $this->pagination->offset);
            $pagination = $this->pagination->create_links();
        }



        // Navigasyon eklemeleri yapılır
        $parents = $this->appmodel->parents($parent->id);

        foreach ($parents as $bread){
            $this->breadcrumb($bread['title'], $bread['url']);
        }
        $this->breadcrumb('Kayıtlar');

        $this->viewData['parent'] = $parent;
        $this->viewData['records'] = $records;
        $this->viewData['pagination'] = $pagination;
        $this->viewData['modules'] = $this->appmodel->moduleAll();


        $this->load->vars('public', array('js' => array('../public/admin/js/module/menu.js')));

        $this->render('childs');
    }


    public function module()
    {
        $json = array('success' => false, 'html' => 'Kayıt bulunamadı.');
        $module = $this->appmodel->module($this->uri->segment(3));

        if ($module) {
            $json['success'] = true;
            $json['html'] = $this->load->view(clink(array($this->module, 'links')), array(
                'records' => $this->appmodel->moduleLinks($module)
            ), true);
        }

        echo json_encode($json);
    }



    public function insert()
    {
        $json = array('success' => false, 'html' => 'Kayıt bulunamadı.');
        $parent = $this->appmodel->id($this->uri->segment(3));

        if ($parent) {

            $module = $this->input->post('module');
            $id = $this->input->post('id');

            $data = array(
                'module' => ! empty($module) ? $module : false,
                'id' => ! empty($id) ? $id : false,
                'title' => $this->input->post('title'),
                'hint' => $this->input->post('hint'),
                'link' => $this->input->post('link'),
            );

            $success = $this->appmodel->insert($parent, $data);

            if ($success) {
                $json['success'] = true;
            }
        }

        echo json_encode($json);
    }


    public function updateRequest($record)
    {
        // Navigasyon eklemeleri yapılır
        $parents = $this->appmodel->parents($record->id);

        foreach ($parents as $bread){
            $this->breadcrumb($bread['title'], $bread['url']);
        }
    }

    public function updateValidateRules()
    {
        $this->form_validation->set_rules('title', 'Lütfen Başlık yazınız.', 'required');
        $this->form_validation->set_rules('hint', 'Lütfen Alt Başlık yazınız.', 'required');
        $this->form_validation->set_rules('link', 'Lütfen Link yazınız.', 'required');
    }



    public function groupInsert()
    {
        if (! $this->isRoot()) {
            redirect('home/denied');
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('name', 'Lütfen Etiket yazınız.', 'required');
            $this->form_validation->set_rules('title', 'Lütfen Başlık yazınız.', 'required');

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->groupInsert($this->modelData);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt eklendi.');

                    if ($this->input->post('redirect')) {
                        $redirect = $this->input->post('redirect');
                    } else {
                        $redirect = clink(array($this->module, 'groupUpdate', $success));
                    }

                    redirect($redirect);
                }
            }
        }

        $this->breadcrumb('Yeni Menü Grubu Ekle');
        $this->render('group/insert');
    }



    public function groupUpdate()
    {
        if (! $this->isRoot()) {
            redirect('home/denied');
        }

        if (! $record = $this->appmodel->id($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('name', 'Lütfen Etiket yazınız.', 'required');
            $this->form_validation->set_rules('title', 'Lütfen Başlık yazınız.', 'required');

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->groupUpdate($record, $this->modelData);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');

                    if ($this->input->post('redirect')) {
                        $redirect = $this->input->post('redirect');
                    } else {
                        $redirect = clink(array($this->module, 'groupUpdate', $record->id));
                    }

                    redirect($redirect);
                }
                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->breadcrumb('Menü Grubu Düzenle');
        $this->viewData['record'] = $record;
        $this->render('group/update');
    }



    public function groupDelete()
    {
        /**
         * Ajax sorgusu  ise toplu silme uygulanır
         */
        if ($this->input->is_ajax_request()) {
            if (! $this->isRoot()) {
                echo 'home/denied';
            }

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

        if (! $this->isRoot()) {
            redirect('home/denied');
        }

        /**
         * Normal sorgu ise tekli silme uygulanır
         */
        if (! $record = $this->appmodel->id($this->uri->segment(3))) {
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



} 