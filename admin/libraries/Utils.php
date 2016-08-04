<?php


class Utils
{

    private $upload = array(
        'input' => 'file',
        'temp' => '../public/upload/temp/',
        'dir' => '../public/upload/',
        'minWidth' => 0,
        'minHeight' => 0
    );

    private $processSizes = array();
    private $plupload = false;
    private $assets = array();

    private $ci;



    public function __construct(array $config = array())
    {
        $this->ci =& get_instance();
    }


    public function setPlupload()
    {
        $this->plupload = true;
        return $this;
    }


    public function uploadInput($name)
    {
        $this->upload['input'] = $name;
        return $this;
    }

    public function uploadTemp($path)
    {
        $this->upload['temp'] = $path;
        return $this;
    }

    public function minSizes($width = null, $height = null)
    {
        if ($width > 0) {
            $this->upload['minWidth'] = $width;
        }

        if ($height > 0) {
            $this->upload['minHeight'] = $height;
        }

        return $this;
    }


    public function addProcessSize($name, $width, $height, $path, $process)
    {
        $this->processSizes[$name] = array(
            'width' => $width,
            'height' => $height,
            'path' => trim($path, '/') . '/',
            'process' => $process
        );
        return $this;
    }


    public function imageUpload($require, $image = null)
    {
        if ($require === false && empty($_FILES[$this->upload['input']]['name'])) {
            return array('name' => $image);
        }

        $upload = $this->upload(array('allowed_types' => 'gif|jpg|png'));

        if ($this->checkSize($upload['width'], $upload['height'])) {
            $this->processImage($upload, $image);

            return $upload;
        } else {
            $this->deleteFile($upload['path']);
        }

        return false;
    }


    public function imageDownload($require, $url, $image = null)
    {
        if ($require === false && empty($url)) {
            return array('name' => $image);
        }

        $info = @getimagesize($url);

        if (empty($info)) {
            $this->setAlert('danger', 'Resim bilgisi alınamadı.');
            return false;
        }

        $mimes = array('image/png' => 'png', 'image/x-png' => 'png', 'image/jpg' => 'jpg' , 'image/jpe' => 'jpg', 'image/jpeg' => 'jpg', 'image/pjpeg' => 'jpg', 'image/gif' => 'gif');

        if (! isset($mimes[$info['mime']])){
            $this->setAlert('danger', 'Geçersiz resim dosyası.');
            return false;
        }


        $name = md5(microtime());
        $ext = '.'.$mimes[$info['mime']];
        $content = file_get_contents($url);

        file_put_contents($this->upload['temp'] . $name . $ext, $content);

        $upload = array(
            'name' => $name . $ext,
            'path' => $this->upload['temp'] . $name . $ext,
            'ext' => $ext,
            'width' => $info[0],
            'height' => $info[1]
        );


        if ($this->checkSize($upload['width'], $upload['height'])) {
            $this->processImage($upload, $image);

            return $upload;
        } else {
            $this->deleteFile($upload['path']);
        }
    }



    /**
     * Resim yükler
     * @param array $params
     * @return boolean
     */
    public function upload($params = null)
    {
        $config = array(
            'upload_path' => $this->upload['temp'],
            'encrypt_name' => true
        );

        if (is_array($params)) {
            $config = array_merge($config, $params);
        }

        $this->ci->load->library('upload');
        $this->ci->upload->initialize($config);

        if (! $this->ci->upload->do_upload($this->upload['input'])) {

            if ($this->plupload === true) {
                echo json_encode(array(
                    'jsonrpc'	=> '2.0',
                    'error'		=> array('code' => '500', 'message' => $this->ci->upload->display_errors('', '')),
                    'id'		=> 'id'
                ));
                die();
            }

            $this->setAlert('danger', $this->ci->upload->display_errors('<div>&bull; ', '</div>'));
            return false;
        }

        $data = $this->ci->upload->data();

        return array(
            'name' => $data['file_name'],
            'path' => $data['full_path'],
            'ext' => $data['file_ext'],
            'width' => $data['image_width'],
            'height' => $data['image_height']
        );
    }



    /**
     * Upload işlemi sonrası config parametrelerine göre resim boyut kontrolü yapar.
     *
     * @param int $width
     * @param int $height
     * @return boolean
     */
    public function checkSize($width, $height)
    {
        if ($width < $this->upload['minWidth'] || $height < $this->upload['minHeight']) {

            if ($this->plupload === true) {
                echo json_encode(array(
                    'jsonrpc'	=> '2.0',
                    'error'		=> array('code' => '500', 'message' => 'Resim boyutları en az '. $this->upload['minWidth'] .'x'. $this->upload['minHeight'] .'px olmalı.'),
                    'id'		=> 'id'
                ));
                die();
            }

            $this->setAlert('danger', '<div>&bull; Resim boyutları en az '. $this->upload['minWidth'] .'x'. $this->upload['minHeight'] .'px olmalı.</div>');
            return false;
        }

        return true;
    }





    /**
     * Config dosyasındaki parametrelere göre resim kırpma işlemi yapar.
     *
     * @param array $upload Codeigniter upload veri dizisi
     * @param null $deleteFile Silinecek dosya adı.
     * @return mixed
     */
    public function processImage(&$upload, $deleteFile = null)
    {
        if (empty($this->processSizes)) {
            $this->setAlert('danger', 'Resim boyutları belirtilmemiş');
            $this->deleteFile($upload['path']);
            return false;
        }

        $this->ci->load->library('SimpleImage');

        foreach ($this->processSizes as $size) {
            if (! empty($deleteFile)) {
                $this->deleteFile($this->upload['dir'] . $size['path'] . $deleteFile);
            }

            $image = $this->ci->simpleimage->load($upload['path']);

            switch ($size['process']) {
                case 'thumbnail':
                    $image->thumbnail($size['width'], $size['height']);
                    break;
                case 'fit':
                    $image->best_fit($size['width'], $size['height']);
                    break;
                case 'fit-width':
                    $image->fit_to_width($size['width']);
                    break;
                case 'fit-height':
                    $image->fit_to_height($size['height']);
                    break;
                case 'normal':
                    break;
            }

            // İlk size da resim ismi değiştiğinden ikinci size da tekrardan isim değiştirilmez.
            if (file_exists($this->upload['dir'].$size['path'] . $upload['name'])) {
                $upload['name'] = str_replace($upload['ext'], '', $upload['name']) . uniqid() . $upload['ext'];
            }

            $image->save($this->upload['dir'].$size['path'] . $upload['name']);
        }

        $this->deleteFile($upload['path']);
    }



    /**
     * Belirtilen dosyaları siler.
     *
     * @param string $path
     */
    public function deleteFile($path)
    {
        if (is_array($path)){
            foreach ($path as $p){
                @unlink($p);
            }
        } else {
            @unlink($path);
        }
    }





    public function search()
    {
        if (! isset($this->ci->search)) {
            return false;
        }

        if ($this->ci->input->get('search')) {
            $where = array();

            foreach ($this->ci->search as $column) {
                if (strpos($column, '.') === false) {
                    $column = $this->ci->table . ".$column";
                }
                $where[] = "$column LIKE '" . $this->ci->input->get('search') ."'";
            }

            $this->ci->db->where('('. implode(' OR ', $where) .')');
        }
    }


    public function filter()
    {
        if (isset($this->ci->search)) {
            if (strlen($this->ci->input->get('search')) > 0) {
                $where = array();

                foreach ($this->ci->search as $column) {
                    if (strpos($column, '.') === false) {
                        $column = $this->ci->table . ".$column";
                    }
                    $where[] = "$column LIKE '" . $this->ci->input->get('search') ."'";
                }

                $this->ci->db->where('('. implode(' OR ', $where) .')');
            }
        }

        if (isset($this->ci->filter) && is_array($this->ci->filter)) {
            foreach ($this->ci->filter as $filter) {
                if (strlen($this->ci->input->get($filter)) > 0) {
                    $value = $this->ci->input->get($filter);

                    if ($value == 'true') {
                        $value = 1;
                    } elseif ($value == 'false') {
                        $value = 0;
                    }

                    if (strpos($filter, '.') === false) {
                        $filter = $this->ci->table . ".$filter";
                    }

                    $this->ci->db->where($filter, $value);
                }
            }
        }
    }

    function alert($suffix = '')
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

            return false;
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
