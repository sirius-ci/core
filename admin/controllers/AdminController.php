<?php

namespace Admin\Controllers;

use Sirius\Admin\Controller;


abstract class AdminController extends Controller
{

    /**
     * Tüm kayıtları sayfalama yaparak listeler.
     *
     * @param array $methods
     * @param array $events
     */
    protected function records($methods = array(), $events = array())
    {
        $methods = array_merge(array(
            'count' => 'count',
            'all' => 'all'
        ), $methods);

        $events = array_merge(array(
            'recordsRequest' => 'recordsRequest',
        ), $events);

        $records = array();
        $paginate = null;
        $recordCount = $this->appmodel->$methods['count']();

        if ($recordCount > 0) {
            $paginate = $this->paginateForOrder($recordCount);
            $records = $this->appmodel->$methods['all']($paginate);
        }


        $this->callMethod($events['recordsRequest']);
        $this->utils->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['paginate'] = $paginate;

    }

    /**
     * Yeni kayıt ekleme
     *
     * @param array $methods
     * @param $events $methods
     */
    protected function insert($methods = array(), $events = array())
    {
        $methods = array_merge(array(
            'insert' => 'insert'
        ), $methods);

        $events = array_merge(array(
            'validation' => ['insertValidation', 'validation'],
            'validationAfter' => ['insertValidationAfter', 'validationAfter'],
            'insertBefore' => 'insertBefore',
            'insertAfter' => 'insertAfter',
            'insertRequest' => 'insertRequest',
        ), $events);

        if ($this->input->post()) {
            $this->callMethodBreak($events['validation']);

            if (! $this->alert->has('error')) {
                $this->callMethodBreak($events['validationAfter']);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod($events['insertBefore']);
                $success = $this->appmodel->$methods['insert']($this->modelData);

                if ($success) {
                    $this->callMethod($events['insertAfter']);
                    $this->alert->set('success', 'Kayıt eklendi.');

                    $this->makeRedirect(moduleUri('update', $success));
                }
            }
        }

        $this->callMethod($events['insertRequest']);
        $this->utils->breadcrumb('Yeni kayıt');
    }

    /**
     * Kayıt güncelleme
     *
     * @param array $methods
     */
    protected function update($methods = array(), $events = array())
    {
        $methods = array_merge(array(
            'update' => 'update',
            'find' => 'find'
        ), $methods);

        $events = array_merge(array(
            'validation' => ['updateValidation', 'validation'],
            'validationAfter' => ['updateValidationAfter', 'validationAfter'],
            'updateBefore' => 'updateBefore',
            'updateAfter' => 'updateAfter',
            'updateRequest' => 'updateRequest',
        ), $events);

        if (! $record = $this->appmodel->$methods['find']($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->callMethodBreak($events['validation'], $record);

            if (! $this->alert->has('error')) {
                $this->callMethodBreak($events['validationAfter'], $record);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod($events['updateBefore'], $record);
                $success = $this->appmodel->$methods['update']($record, $this->modelData);

                if ($success) {
                    $this->callMethod($events['updateAfter'], $record);
                    $this->alert->set('success', 'Kayıt düzenlendi.');

                    $this->makeRedirect(moduleUri('update', $record->id));
                }

                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->callMethod($events['updateRequest'], $record);
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