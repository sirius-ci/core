<?php

use Sirius\Admin\Manager;

class ModuleAdminController extends Manager
{
    public $moduleTitle = 'Modüller';
    public $module = 'module';
    public $table = 'modules';
    public $model = 'module';

    // Arama yapılacak kolonlar.
    public $search = array('title', 'name');

    // Filtreleme yapılacak querystring/kolonlar.
    // public $filter = array('type');

    public $actions = array(
        'records' => 'list',
        'update' => 'update',
        'delete' => 'delete',
        'order' => 'order',
    );


    /**
     * @todo menuPattren linkPattern olarak değiştirilecek.
     * @todo ön yüzdeki aktif modülde linkPattern verileri kullanarak otomatik link oluşturtulacak.
     */
    public function update()
    {
        if (! $record = $this->appmodel->name($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            foreach ($record->arguments as $argument) {
                if (! empty($argument->arguments)) {
                    $this->form_validation->set_rules($argument->name, "Lütfen {$argument->title} geçerli bir değer veriniz.", implode('|', array_keys($argument->arguments)));
                }
            }

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->update($record);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');
                    redirect(clink(array($this->module, 'update', $record->name)));
                }
                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->load->vars('public', array('js' => array(
            '../public/admin/plugin/ckeditor/ckeditor.js',
            '../public/admin/plugin/ckfinder/ckfinder.js'
        )));

        $this->breadcrumb("{$record->title}: Düzenle");

        $this->viewData['record'] = $record;

        $this->render('update');
    }


} 