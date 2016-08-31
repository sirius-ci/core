<?php

namespace Sirius\Admin;

abstract class Model extends \CI_Model
{

    /**
     * Filtreleme koşullarını ekler
     *
     * @param null|string $table
     */
    protected function setFilter($table = null)
    {
        if ($table !== null) {
            $table = $table.'.';
        }


        if (isset($this->search)) {
            if (strlen($this->input->get('search')) > 0) {
                $where = array();

                foreach ($this->search as $column) {
                    if (strpos($column, '.') === false) {
                        $column = $table . $column;
                    }
                    $where[] = "$column LIKE '" . $this->input->get('search') ."'";
                }

                $this->db->where('('. implode(' OR ', $where) .')');
            }
        }

        if (isset($this->filter) && is_array($this->filter)) {
            foreach ($this->filter as $filter) {
                if (strlen($this->input->get($filter)) > 0) {
                    $value = $this->input->get($filter);

                    if ($value == 'true') {
                        $value = 1;
                    } elseif ($value == 'false') {
                        $value = 0;
                    }

                    if (strpos($filter, '.') === false) {
                        $filter = $table . $filter;
                    }

                    $this->db->where($filter, $value);
                }
            }
        }
    }


    /**
     * Sayfalama koşullarını ekler
     *
     * @param array $paginate
     */
    protected function setPaginate($paginate)
    {
        if (! empty($paginate['limit'])) {
            $this->db->limit($paginate['limit'], empty($paginate['offset']) ? 0 : $paginate['offset']);
        }
    }


    /**
     * Post değerlerine göre slug oluşturur.
     *
     * @param string $slugInput
     * @param string $defaultInput
     * @return string
     */
    protected function makeSlug($slugInput = 'slug', $defaultInput = 'title')
    {
        return makeSlug($this->input->post($slugInput) ? $this->input->post($slugInput) : $this->input->post($defaultInput));
    }


    protected function makeLastOrder($condition = array(), $column = 'order')
    {
        if ($condition) {
            $this->db->where($condition);
        }

        $order = 1;
        $lastOrder = $this->db
            ->from($this->table)
            ->where('language', $this->language)
            ->order_by('order', 'desc')
            ->limit(1)
            ->get()
            ->row();

        if ($lastOrder) {
            $order = $lastOrder->$column + 1;
        }

        return $order;
    }



    protected function setMeta(&$data, $language = false)
    {
        $metas = array(
            'metaTitle' => $this->input->post('metaTitle'),
            'metaDescription' => $this->input->post('metaDescription'),
            'metaKeywords' => $this->input->post('metaKeywords')
        );

        if ($language === true) {
            $metas['language'] = $this->language;
        }

        $data = array_merge($data, $metas);
    }


    protected function setMetaAndLang(&$data)
    {
        $data = $this->setMeta($data, true);
    }


} 