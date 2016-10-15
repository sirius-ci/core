<?php

namespace Sirius\Application;


abstract class Controller extends \CI_Controller
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
     * Sayfalama.
     *
     * @param $count
     * @param int $limit
     * @param null $url
     * @return array
     */
    public function paginate($count, $limit = 20, $url = null)
    {
        $this->load->library('pagination');
        $this->pagination->initialize([
            'base_url' => empty($url) ? current_url() : $url,
            'total_rows' => $count
        ]);

        return [
            'limit' => $limit,
            'offset' => $this->pagination->offset,
            'pagination' => $this->pagination->create_links()
        ];
    }


    public function json($data)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


} 