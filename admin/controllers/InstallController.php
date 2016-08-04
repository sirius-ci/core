<?php

class InstallController extends CI_Controller
{

    public $module;

    protected $provider;
    protected $messages = array();


    public function start()
    {
        $this->load->view('helpers/master', array(
            'view' => 'helpers/install/start'
        ));
    }


    public function install($module)
    {
        $insrallerFile = APPPATH .'/installers/'. ucfirst($module) .'/Installer.php';

        if (! file_exists($insrallerFile)) {
            throw new Exception('Kurulum (installer) dosyasi bulunamadi.');
        }

        require_once $insrallerFile;

        $this->module = $module;
        $this->provider = new Installer();

        if (! $this->isInstalled()) {


            $sqlString = '';
            if (file_exists(APPPATH .'/installers/'. ucfirst($module) .'/Database.sql')) {
                $sqlString = $this->load->file(APPPATH .'/installers/'. ucfirst($module) .'/Database.sql', true);
            }

            if ($sqlString) {
                $queries = explode(';', $sqlString);

                foreach($queries as $query) {
                    $query = trim($query);

                    if (! empty($query)) {
                        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
                        $this->db->query($query);
                        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
                    }
                }
            }

            $this->saveModule();
            $this->callMethods();
            $this->addRoutes();
            $this->addReservedUri();
        };

        $this->load->view('helpers/master', array(
            'view' => 'helpers/install/install',
            'data' => array(
                'messages' => $this->messages
            )
        ));
    }


    private function isInstalled()
    {
        if (! $this->db->table_exists('modules')) {
            return false;
        }

        $module = $this->db
            ->from('modules')
            ->where('name', $this->module)
            ->get()
            ->row();

        if ($module) {
            $this->messages[] = 'Modül kurulumu yapılmış.';

            if (isset($this->provider->tables)) {

                $missing = array();

                foreach ($this->provider->tables as $table) {
                    if (! $this->db->table_exists($table)) {
                        $missing[] = $table;
                    }
                }

                if (count($missing) > 0) {
                    $this->messages[] = 'Modül kurulumu hatalı!';
                    $this->messages[] = 'Eksik tablolar mevcut:';
                    $this->messages[] = implode('<br>', $missing);
                }
            }

            return true;
        }

        return false;
    }


    private function saveModule()
    {
        $this->db->insert('modules', array(
            'title' => '',
            'name' => $this->module,
            'table' => '',
            'modified' => 0,
            'permissions' => '',
            'type' =>  null,
            'icon' =>  null,
            'menuPattern' => null,
            'controller' => '',
        ));

        $this->messages[] = 'Modül kuruldu.';
    }


    private function callMethods()
    {
        $methods = array();

        if (! empty($this->provider->steps)) {
            foreach ($this->provider->steps as $step) {
                if (method_exists($this->provider, $step)) {
                    $methods[] = $step;
                }
            }
        }

        foreach ($methods as $method) {
            $this->provider->$method();
        }

    }


    private function addRoutes()
    {
        if (empty($this->provider->routes)) {
            return false;
        }

        $languages = $this->config->item('languages');

        foreach ($languages as $language => $label) {
            if (isset($this->provider->routes[$language]['route'])) {

                $filepath = '../application/config/routes.php';
                $file = fopen($filepath, FOPEN_WRITE_CREATE);
                $data = '';

                foreach ($this->provider->routes[$language]['route'] as $pattern => $action) {
                    $patterns = array();

                    // Aktif dil tr değilse prefix ekle.
                    if ($language !== 'tr') {
                        $patterns[] = $language;
                    }

                    // Aktif pattern'de @uri parametresi varsa uri değerini replace et.
                    if (isset($this->provider->routes[$language]['uri'])) {
                        $pattern = str_replace('@uri', $this->provider->routes[$language]['uri'], $pattern);
                    }

                    if (! empty($pattern)) {
                        $patterns[] = $pattern;
                    }

                    if (count($patterns) > 0) {
                        $data .= '$route[\''. implode('/', $patterns) .'\'] = \''. $action .'\';'. PHP_EOL;
                    }
                }

                flock($file, LOCK_EX);
                fwrite($file, $data);
                flock($file, LOCK_UN);
                fclose($file);

                $this->messages[] = 'Rotasyon yapıldı. ('. $label .')';
            }
        }
    }


    private function addReservedUri()
    {
        if (empty($this->provider->routes)) {
            return false;
        }

        $languages = $this->config->item('languages');

        foreach ($languages as $language => $label) {
            if (isset($this->provider->routes[$language]['uri'])) {

                $filepath = '../config/reservedUri.php';
                $file = fopen($filepath, FOPEN_WRITE_CREATE);
                $data = '$config[\'reservedUri\'][\''. $language .'\'][\'@'. $this->module .'\'] = \''. $this->provider->routes[$language]['uri'] .'\';'. PHP_EOL;

                flock($file, LOCK_EX);
                fwrite($file, $data);
                flock($file, LOCK_UN);
                fclose($file);


                $this->messages[] = 'Rezerve url eklendi. ('. $label .')';
            }
        }
    }


} 