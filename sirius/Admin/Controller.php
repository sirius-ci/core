<?php

namespace Sirius\Admin;


abstract class Controller extends Manager
{
    /**
     * Form Validate.
     *
     * @param array $rules
     * @return bool
     */
    public function validate($rules = array())
    {
        foreach ($rules as $name => $rule) {
            $this->form_validation->set_rules($name, $rule[1], $rule[0]);
        }

        if ($this->form_validation->run() === false) {
            $this->alert->set('error', $this->form_validation->errors());

            return false;
        }

        return true;
    }

    /**
     * Yönlendirme işlemleri.
     *
     * @param $params
     * @param null $record
     */
    public function makeRedirect($params, $record = null)
    {
        if ($this->input->post('redirect')) {
            $params = explode('/', $this->input->post('redirect'));
        } else {
            $params = ! is_array($params) ? array($params) : $params;
        }



        if ($record !== null) {
            foreach ($params as &$param) {
                if ($param[0] === '@') {
                    $column = str_replace('@', '', $param);
                    isset($record->$column) && $param = $record->$column;
                }
            }
        }

        redirect(call_user_func_array('moduleUri', $params));
    }

    /**
     * Sayfalama.
     *
     * @param $count
     * @param int $limit
     * @param null $url
     * @param bool|false $forOrder
     * @return array
     */
    public function paginate($count, $limit = 20, $url = null, $forOrder = false)
    {
        $this->load->library('pagination');
        $this->pagination->initialize([
            'base_url' => empty($url) ? current_url() : $url,
            'total_rows' => $count,
            'per_page' => $forOrder === true ? ($limit - 1) : $limit
        ]);

        return [
            'limit' => $limit,
            'offset' => $this->pagination->offset,
            'pagination' => $this->pagination->create_links()
        ];
    }

    /**
     * Sıralama baz alarak sayfalama.
     *
     * @param $count
     * @param int $limit
     * @param null $url
     * @return array
     */
    public function paginateForOrder($count, $limit = 20, $url = null)
    {
        return $this->paginate($count, $limit, $url, true);
    }

    /**
     * View dosyasını layout ile birlikte yükler.
     * @param $file
     */
    public function render($file)
    {
        if (is_array($file)) {
            $file = implode('/', $file);
        }

        $this->load->view('layout', array(
            'view' => $this->module .'/'. $file,
            'data' => $this->viewData
        ));
    }

    public function json($data)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }



    public function createActuator()
    {
        $instance = new Actuator();
        return $instance;
    }


    public function validOrCreateActuator(&$instance = null)
    {
        if ($instance instanceof Actuator) {
            return $instance;
        }

        return $instance = $this->createActuator();
    }

} 