<?php

namespace Admin\Controllers;

use Sirius\Admin\Controller;


abstract class AdminController extends Controller
{

    /**
     * Tüm kayıtları sayfalama yaparak listeler.
     */
    protected function records()
    {
        $records = array();
        $paginate = null;
        $recordCount = $this->appmodel->count();

        if ($recordCount > 0) {
            $paginate = $this->paginateForOrder($recordCount);
            $records = $this->appmodel->all($paginate);
        }


        $this->callMethod('recordsRequest');
        $this->utils->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['paginate'] = $paginate;

    }

    /**
     * Yeni kayıt ekleme
     */
    protected function insert()
    {
        if ($this->input->post()) {
            $this->callMethodBreak(['insertValidation', 'validation']);

            if (! $this->alert->has('error')) {
                $this->callMethodBreak(['insertValidationAfter', 'validationAfter']);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod('insertBefore');
                $success = $this->appmodel->insert($this->modelData);

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
     */
    protected function update()
    {
        if (! $record = $this->appmodel->find($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->callMethodBreak(['updateValidation', 'validation'], $record);

            if (! $this->alert->has('error')) {
                $this->callMethodBreak(['updateValidationAfter', 'validationAfter'], $record);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod('updateBefore', $record);
                $success = $this->appmodel->update($record, $this->modelData);

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
     * @return void
     */
    protected function delete()
    {
        /**
         * Ajax sorgusu  ise toplu silme uygulanır
         */
        if ($this->input->is_ajax_request()) {
            $ids = $this->input->post('ids');

            if (count($ids) == 0) {
                $this->alert->set('error', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }
            $success = $this->appmodel->delete($ids);

            if ($success) {
                $this->alert->set('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            die();
        }

        /**
         * Normal sorgu ise tekli silme uygulanır
         */
        if (! $record = $this->appmodel->find($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->delete($record);

        if ($success) {
            $this->alert->set('success', "Kayıt kaldırıldı. (#{$record->id})");
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->utils->setAlert('error', 'Kayıt kaldırılamadı.');
        redirect($this->input->server('HTTP_REFERER'));

    }

    /**
     * Sıralama işlemi yapar
     */
    protected function order()
    {
        $ids = explode(',', $this->input->post('ids'));

        if (count($ids) == 0){
            $this->alert->set('error', 'Lütfen kayıt seçiniz.');
        }

        $success = $this->appmodel->order($ids);

        if ($success){
            $this->alert->set('success', "Kayıtlar başarıyla sıralandı.");
        }
    }


} 