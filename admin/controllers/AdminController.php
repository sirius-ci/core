<?php

namespace Admin\Controllers;

use Sirius\Admin\Controller;


abstract class AdminController extends Controller
{

    /**
     * Tüm kayıtları sayfalama yaparak listeler.
     *
     * @param array $methods
     * @param bool|false $ignoreDefaults Varsayılan metodları kullanma
     */
    protected function records($methods = array(), $ignoreDefaults = false)
    {
        if ($ignoreDefaults !== true) {
            $methods = array_merge(array(
                'count' => [$this->appmodel, 'count'],
                'all' => [$this->appmodel, 'all'],
                'recordsRequest' => 'recordsRequest',
            ), $methods);
        }


        $records = array();
        $paginate = null;
        $recordCount = $this->callMethod($methods['count']);

        if ($recordCount > 0) {
            $paginate = $this->paginateForOrder($recordCount);
            $records = $this->callMethod($methods['all'], [$paginate]);
        }

        $this->callMethod($methods['recordsRequest']);
        $this->utils->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['paginate'] = $paginate;
    }




    /**
     * Yeni kayıt ekleme
     *
     * @param array $methods
     * @param bool|false $ignoreDefaults Varsayılan metodları kullanma
     */
    protected function insert($methods = array(), $ignoreDefaults = false)
    {
        if ($ignoreDefaults !== true) {
            $methods = array_merge(array(
                'insert' => [$this->appmodel, 'insert'],
                'validation' => 'validation',
                'validationAfter' => 'validationAfter',
                'insertBefore' => 'insertBefore',
                'insertAfter' => 'insertAfter',
                'insertRequest' => 'insertRequest',
                'redirect' => ['update', '@id']
            ), $methods);
        }

        if ($this->input->post()) {
            $this->callMethod($methods['validation'], ['insert']);

            if (! $this->alert->has('error')) {
                $this->callMethod($methods['validationAfter'], ['insert']);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod($methods['insertBefore']);

                $success = $this->callMethod($methods['insert'], [$this->modelData]);

                if ($success) {
                    $this->callMethod($methods['insertAfter']);
                    $this->alert->set('success', 'Kayıt eklendi.');

                    $this->makeRedirect($methods['redirect'], $success);
                }
            }
        }

        $this->callMethod($methods['insertRequest']);
        $this->utils->breadcrumb('Yeni kayıt');
    }


    /**
     * Kayıt güncelleme
     *
     * @param array $methods
     * @param bool|false $ignoreDefaults Varsayılan metodları kullanma
     */
    protected function update($methods = array(), $ignoreDefaults = false)
    {
        if ($ignoreDefaults !== true) {
            $methods = array_merge(array(
                'update' => [$this->appmodel, 'update'],
                'find' => [$this->appmodel, 'find'],
                'validation' => 'validation',
                'validationAfter' => 'validationAfter',
                'updateBefore' => 'updateBefore',
                'updateAfter' => 'updateAfter',
                'updateRequest' => 'updateRequest',
                'redirect' => ['update', '@id']
            ), $methods);
        }

        if (! $record = $this->callMethod($methods['find'], $this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->callMethod($methods['validation'], ['update', $record]);

            if (! $this->alert->has('error')) {
                $this->callMethod($methods['validationAfter'], ['update', $record]);
            }

            if (! $this->alert->has('error')) {
                $this->callMethod($methods['updateBefore'], [$record]);
                $success = $this->callMethod($methods['update'], [$record, $this->modelData]);

                if ($success) {
                    $this->callMethod($methods['updateAfter'], [$record]);
                    $this->alert->set('success', 'Kayıt düzenlendi.');

                    $this->makeRedirect($methods['redirect'], $success);
                }

                $this->alert->set('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->callMethod($methods['updateRequest'], [$record]);
        $this->utils->breadcrumb('Kayıt Düzenle');

        $this->viewData['record'] = $record;
    }

    /**
     * Kayıt(lar) silme
     *
     * @param array $methods
     * @param bool|false $ignoreDefaults Varsayılan metodları kullanma
     */
    protected function delete($methods = array(), $ignoreDefaults = false)
    {
        if ($ignoreDefaults !== true) {
            $methods = array_merge(array(
                'delete' => [$this->appmodel, 'delete'],
                'find' => [$this->appmodel, 'find'],
            ), $methods);
        }

        /**
         * Ajax sorgusu  ise toplu silme uygulanır
         */
        if ($this->input->is_ajax_request()) {
            $ids = $this->input->post('ids');

            if (count($ids) == 0) {
                $this->alert->set('error', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }

            $success = $this->callMethod($methods['delete'], [$ids]);

            if ($success) {
                $this->alert->set('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            die();
        }

        /**
         * Normal sorgu ise tekli silme uygulanır
         */
        if (! $record = $this->callMethod($methods['find'], $this->uri->segment(3))) {
            show_404();
        }

        $success = $this->callMethod($methods['delete'], [$record]);

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
     * @param bool|false $ignoreDefaults Varsayılan metodları kullanma
     */
    protected function order($methods = array(), $ignoreDefaults = false)
    {
        if ($ignoreDefaults !== true) {
            $methods = array_merge(array(
                'order' => [$this->appmodel, 'order']
            ), $methods);
        }

        $ids = explode(',', $this->input->post('ids'));

        if (count($ids) == 0){
            $this->alert->set('error', 'Lütfen kayıt seçiniz.');
        }

        $success = $this->callMethod($methods['order'], [$ids]);

        if ($success){
            $this->alert->set('success', "Kayıtlar başarıyla sıralandı.");
        }
    }


}