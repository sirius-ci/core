<?php

class Site
{
    private $options = array();
    private $assets = array();
    private $ci;


    public function __construct()
    {
        $this->ci =& get_instance();

        /**
         * Kurulumun yapılıp yapılmadığını kontrol eder.
         * Kurulum yapılmadıysa kurulum ekranına geçer.
         */
        $this->isReady();

        /**
         * Varsayılan dil atama
         */
        $languages = $this->ci->config->item('languages');
        $segment = $this->ci->uri->segment(1);

        if ($languages && $segment) {
            if (array_key_exists($segment, $languages)) {
                $language = $segment;
            }
        }

        if (! empty($language)){
            $this->ci->config->set_item('language', $language);
            $this->ci->language = $language;
        } else {
            $this->ci->language = $this->ci->config->item('language');
        }

        $this->ci->lang->load('site');


        /**
         * Site genel ayarları atama
         */
        $result =  $this->ci->db
            ->where('language', $this->ci->language)
            ->or_where('language', null)
            ->get('options')
            ->result();

        foreach ($result as $item) {
            $this->options[$item->name] = $item->value;
        }


        /**
         * Belirtilen modüle argümanlarını atama
         */
        if (isset($this->ci->module)) {

            $module = $this->getModule($this->ci->module);

            if ($module) {
                $this->ci->module = $module;

                if (! empty($this->ci->module->arguments->metaTitle)) {
                    $this->set('metaTitle', $this->ci->module->arguments->metaTitle);
                }

                if (! empty($this->ci->module->arguments->metaDescription)) {
                    $this->set('metaDescription', $this->ci->module->arguments->metaDescription);
                }

                if (! empty($this->ci->module->arguments->metaKeywords)) {
                    $this->set('metaKeywords', $this->ci->module->arguments->metaKeywords);
                }

            } else {
                unset($this->ci->module);
            }
        }

    }


    /**
     * Ayarlardan veri çekme.
     *
     * @param $name
     * @param bool $default
     * @return bool
     */
    public function get($name, $default = false)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return $default;
    }


    /**
     * Ayarlara veri atama.
     *
     * @param $name
     * @param $value
     * @param bool $append
     */
    public function set($name, $value, $append = false)
    {
        if ($append) {
            $this->options[$name] = $value . $append . $this->get($name);
        } else {
            $this->options[$name] = $value;
        }
    }


    /**
     * Modül verilerini çeker.
     *
     * @param $name
     * @return mixed
     */
    public function getModule($name)
    {
        $module = $this->ci->db
            ->from('modules')
            ->where('name', $name)
            ->get()
            ->row();

        if ($module) {
            $arguments = $this->ci->db
                ->from('module_arguments')
                ->where('module', $module->name)
                ->where('language', $this->ci->language)
                ->get()
                ->result();

            $module->arguments = new stdClass();
            foreach ($arguments as $argument) {
                $module->arguments->{$argument->name} = $argument->value;
            }
        }

        return $module;
    }


    /**
     * Kurulumun yapılıp yapılmadığını kontrol eder.
     * Kurulum yapılmadıysa kurulum ekranına geçer.
     */
    public function isReady()
    {
        if (! $this->ci->db->table_exists('options')) {
            redirect('admin/install');
        }
    }


    /**
     * Gönderilen uyarıları çıktılar.
     *
     * @param string $suffix
     * @return string
     */
    public function alert($suffix = '')
    {
        $name = 'alert';
        if (! empty($suffix)) {
            $name = "$name-$suffix";
        }

        $alert	= $this->ci->session->userdata($name);

        if ($alert) {
            $this->ci->session->unset_userdata($name);
            return '<div class="alert alert-'. $alert['key'] .'">'. $alert['value'] .'</div>';
        }
    }


    /**
     * Gönderilen uyarı mesajının varlığını sorgular.
     *
     * @param string $suffix
     * @return bool
     */
    public function isAlert($suffix = '')
    {
        $name = 'alert';
        if (! empty($suffix)) {
            $name = "$name-$suffix";
        }

        $alert	= $this->ci->session->userdata($name);

        if ($alert) {
            return true;
        }

        return false;
    }


    /**
     * Uyarı mesajı atar.
     *
     * @param $key
     * @param $value
     * @param string $suffix
     */
    public function setAlert($key, $value, $suffix = '')
    {
        $name = 'alert';
        if (! empty($suffix)) {
            $name = "$name-$suffix";
        }
        $this->ci->session->set_userdata($name, array('key' => $key, 'value' => $value));
    }


    /**
     * Javascript ve css dosyalarını sisteme tanımlar veya dosyaları göndürür.
     *
     * @param $type
     * @param null $sources
     * @return bool
     */
    public function assets($type, $sources = null)
    {
        if ($sources === null) {
            if (isset($this->assets[$type])) {
                return $this->assets[$type];
            }

            return array();
        }

        if (! is_array($sources)) {
            $sources = array($sources);
        }

        if (! isset($this->assets[$type])) {
            $this->assets[$type] = array();
        }

        $this->assets[$type] = array_merge($this->assets[$type], $sources);

    }


} 