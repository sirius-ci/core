<?php


class Assets
{
    private $assets = [];


    /**
     * Magic method.
     * Kullanılan method ismine göre asset kaydı yapar.
     *
     * @param $name
     * @param $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        return $this->set($name, $arguments);
    }


    /**
     * Javascript ve css dosyalarını sisteme tanımlar veya dosyaları göndürür.
     *
     * @param $type
     * @param array $sources
     * @return bool
     */
    public function set($type, $sources = [])
    {
        if (empty($sources)) {
            if (isset($this->assets[$type])) {
                return $this->assets[$type];
            }

            return array();
        }

        if (! is_array($sources)) {
            $sources = array($sources);
        }

        if (! isset($this->assets[$type])) {
            $this->assets[$type] = [];
        }

        $this->assets[$type] = array_merge($this->assets[$type], $sources);

    }



    public function importEditor()
    {
        $this->set('js', [
            'public/admin/plugin/ckeditor/ckeditor.js',
            'public/admin/plugin/ckfinder/ckfinder.js'
        ]);
    }
}