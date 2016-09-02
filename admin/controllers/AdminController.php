<?php

namespace Admin\Controllers;

use Sirius\Admin\Controller;


abstract class AdminController extends Controller
{

    /**
     * Tüm kayıtları sayfalama yaparak listeler.
     */
    protected function records($methods = array())
    {
        $methods = array_merge(array(
            'count' => 'count',
            'all' => 'all'
        ), $methods);

        $records = array();
        $paginate = null;
        $recordCount = $this->appmodel->$methods['count']();

        if ($recordCount > 0) {
            $paginate = $this->paginateForOrder($recordCount);
            $records = $this->appmodel->$methods['all']($paginate);
        }


        $this->callMethod('recordsRequest');
        $this->utils->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['paginate'] = $paginate;

    }

    /**
     * Yeni kayıt ekleme
     *
     * @param array $methods
     */
    protected function insert($methods = array())
    {
        $methods = array_merge(array(
            'insert' => 'insert'
        ), $methods);

        if ($this->input->post()) {
            $this->callMethodBreak(['insertValidation', 'validation']);

            if (! $this->alert->has('error')) {
                $this->callMethodBreak(['insertValidationAfter', 'validationAfter']);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod('insertBefore');
                $success = $this->appmodel->$methods['insert']($this->modelData);

                if ($success) {
                    $this->callMethod('insertAfter');
                    $this->alert->set('success', 'Kayıt eklendi.');

                    $this->makeRedirect(moduleUri('update', $success));
                }
            }
        }

        $this->callMethod('insertRequest');
        $this->utils->breadcrumb('Yeni kayıt');
    }

    /**
     * Kayıt güncelleme
     *
     * @param array $methods
     */
    protected function update($methods = array())
    {
        $methods = array_merge(array(
            'update' => 'update',
            'find' => 'find'
        ), $methods);

        if (! $record = $this->appmodel->$methods['find']($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->callMethodBreak(['updateValidation', 'validation'], $record);

            if (! $this->alert->has('error')) {
                $this->callMethodBreak(['updateValidationAfter', 'validationAfter'], $record);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod('updateBefore', $record);
                $success = $this->appmodel->$methods['update']($record, $this->modelData);

                if ($success) {
                    $this->callMethod('updateAfter', $record);
                    $this->alert->set('success', 'Kayıt düzenlendi.');

                    $this->makeRedirect(moduleUri('update', $record->id));
                }

                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->callMethod('updateRequest', $record);
        $this->utils->breadcrumb('Kayıt Düzenle');

        $this->viewData['record'] = $record;
    }

    /**
     * Kayıt(lar) silme
     *
     * @param array $methods
     */
    protected function delete($methods = array())
    {
        $methods = array_merge(array(
            'delete' => 'delete',
            'find' => 'find',
        ), $methods);


        /**
         * Ajax sorgusu  ise toplu silme uygulanır
         */
        if ($this->input->is_ajax_request()) {
            $ids = $this->input->post('ids');

            if (count($ids) == 0) {
                $this->alert->set('error', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }

            $success = $this->appmodel->$methods['delete']($ids);

            if ($success) {
                $this->alert->set('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            die();
        }

        /**
         * Normal sorgu ise tekli silme uygulanır
         */
        if (! $record = $this->appmodel->$methods['find']($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->$methods['delete']($record);

        if ($success) {
            $this->alert->set('success', "Kayıt kaldırıldı. (#{$record->id})");
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->alert->set('error', 'Kayıt kaldırılamadı.');
        redirect($this->input->server('HTTP_REFERER'));

    }

    /**
     * Sıralama işlemi yapar
     *
     * @param array $methods
     */
    protected function order($methods = array())
    {
        $methods = array_merge(array(
            'order' => 'order'
        ), $methods);

        $ids = explode(',', $this->input->post('ids'));

        if (count($ids) == 0){
            $this->alert->set('error', 'Lütfen kayıt seçiniz.');
        }

        $success = $this->appmodel->$methods['order']($ids);

        if ($success){
            $this->alert->set('success', "Kayıtlar başarıyla sıralandı.");
        }
    }


} 