<?php

use Admin\Controllers\AdminController;

class MenuAdminController extends AdminController
{
    public $moduleTitle = 'Menü Yönetimi';
    public $module = 'menu';
    public $model = 'menu';


    // Arama yapılacak kolonlar.
    public $search = array('title', 'hint');


    public $actions = array(
        'records' => 'list',
        'insert' => 'insert',
        'update' => 'update',
        'delete' => 'delete',
        'order' => 'list',
        'childs' => 'list',
        'module' => 'insert',
        'groupInsert' => 'list',
        'groupUpdate' => 'list',
        'groupDelete' => 'list',
    );


    public function records()
    {
        parent::records();
        $this->render('records');
    }


    public function insert()
    {
        $response = array('success' => false, 'html' => 'Kayıt bulunamadı.');
        $parent = $this->appmodel->find($this->uri->segment(3));

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
                $response['success'] = true;
            }
        }

        $this->json($response);
    }


    public function update()
    {
        parent::update();
        $this->render('update');
    }


    public function updateRequest($record)
    {
        $this->setParentsBread($record);
    }


    public function updateValidation()
    {
        $this->validate([
            'title' => ['required', 'Lütfen Başlık yazınız.'],
            'hint' => ['required', 'Lütfen Alt Başlık yazınız.'],
            'link' => ['required', 'Lütfen Link yazınız.'],
        ]);
    }


    public function delete()
    {
        parent::delete();
    }


    public function order()
    {
        parent::order();
    }


    public function childs()
    {
        if (! $parent = $this->appmodel->find($this->uri->segment(3))) {
            show_404();
        }

        $records = array();
        $paginate = null;
        $recordCount = $this->appmodel->childCount();

        if ($recordCount > 0) {
            $paginate = $this->paginateForOrder($recordCount);
            $records = $this->appmodel->all($paginate);
        }

        $this->setParentsBread($parent);

        $this->utils->breadcrumb('Kayıtlar');

        $this->viewData['parent'] = $parent;
        $this->viewData['modules'] = $this->appmodel->moduleAll();
        $this->viewData['records'] = $records;
        $this->viewData['paginate'] = $paginate;

        $this->assets->js('public/admin/js/module/menu.js');
        $this->render('childs');
    }


    private function setParentsBread($record)
    {
        $parents = $this->appmodel->parents($record->id);

        foreach ($parents as $bread){
            $this->utils->breadcrumb($bread['title'], $bread['url']);
        }
    }


    public function module()
    {
        $response = array('success' => false, 'html' => 'Kayıt bulunamadı.');
        $module = $this->appmodel->module($this->uri->segment(3));

        if ($module) {
            $response['success'] = true;
            $response['html'] = $this->load->view(clink(array($this->module, 'links')), array(
                'records' => $this->appmodel->moduleLinks($module)
            ), true);
        }

        $this->json($response);
    }


    private function groupValidation()
    {
        $this->validate([
            'name' => array('required', 'Lütfen etiket yazınız.'),
            'title' => array('required', 'Lütfen başlık yazınız.'),
        ]);
    }


    public function groupInsert()
    {
        if (! $this->isRoot()) {
            redirect('home/denied');
        }

        if ($this->input->post()) {
            $this->groupValidation();

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->groupInsert($this->modelData);

                if ($success) {
                    $this->alert->set('success', 'Kayıt eklendi.');
                    $this->makeRedirect(moduleUri('groupUpdate', $success));
                }
            }
        }

        $this->utils->breadcrumb('Yeni Menü Grubu Ekle');
        $this->render('group/insert');
    }



    public function groupUpdate()
    {
        if (! $this->isRoot()) {
            redirect('home/denied');
        }

        if (! $record = $this->appmodel->find($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->groupValidation();

            if (! $this->alert->has('error')) {
                $success = $this->appmodel->groupUpdate($record, $this->modelData);

                if ($success) {
                    $this->alert->set('success', 'Kayıt düzenlendi.');
                    $this->makeRedirect(moduleUri('groupUpdate', $record->id));
                }

                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->utils->breadcrumb('Menü Grubu Düzenle');
        $this->viewData['record'] = $record;
        $this->render('group/update');
    }



    public function groupDelete()
    {
        if (! $this->isRoot()) {
            if ($this->input->is_ajax_request()) {
                echo 'home/denied';
            } else {
                redirect('home/denied');
            }
        }

        parent::delete(array('delete' => 'groupDelete'));

    }



} 