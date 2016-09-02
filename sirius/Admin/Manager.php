<?php

namespace Sirius\Admin;


abstract class Manager extends \CI_Controller
{
    public $user;
    public $permissions = array();
    public $modelData = array();
    public $viewData = array();
    public $breadcrumb = array();
    public $language;
    public $siteOptions;


    /**
     * Module başlığı.
     * @var string
     */
    public $moduleTitle;

    /**
     * Module adı.
     * @var string
     */
    public $module;

    /**
     * Model adı.
     * @var string
     */
    public $model;

    /**
     * Module tipi. null veya public. Public modulun dışardan erişilebilir olduğunu ifade eder.
     * @var null|string
     */
    public $type = null;

    /**
     * Module varsayılan metodu.
     * @var string
     */
    public $defaultAction = 'records';

    /**
     * Modulun erişilebilir metodlarını ve yetki isimlerini tanımalar.
     * 'action' => 'permission'
     * @var array
     */
    public $actions;


    /**
     * Module menü biçimini tanımlar.
     * array(
     *      'title' => 'column',
     *      'hint' => 'column',
     *      'link' => array('column', 'column'),
     *      'language' => true
     * )
     *
     * @var array
     */
    public $menuPattern = array(
        'title' => 'title',
        'hint' => 'title',
        'link' => array('slug', 'id'),
        'language' => true
    );


    public function __construct()
    {
        /**
         * Modül değişkenleri kontrol edilir
         */
        if (! empty($this->module)) {
            if (empty($this->moduleTitle) || empty($this->model) || empty($this->actions)) {
                throw new \Exception('Tanimlamalar hatali.');
            }
        }

        parent::__construct();

        /**
         * Modül belirtildiyse modül verileri kontrol edilir ve güncellenir.
         */
        if (! empty($this->module)) {
            /**
             * Kurulumun yapılıp yapılmadığını kontrol eder.
             * Kurulum yapılmadıysa kurulum ekranına geçer.
             */
            $this->isReady();

            $this->checkModuleConfig();
            $this->load->model($this->model, 'appmodel');
        }

        /**
         * Kullanıcı kontrolü yapılır.
         * Kullanıcı oturumu açıksa yetkilendirmeler atanır.
         */
        $this->loginControl();
        $this->user	= $this->session->userdata('adminuser');

        if ($this->user) {
            $this->permissions();
        }

        /**
         * Dil işlemleri
         */
        $languages = $this->config->item('languages');
        $session = $this->session->userdata('language');

        if ($languages && $session) {
            if (array_key_exists($session, $languages)) {
                $language = $session;
            }
        }

        if (! empty($language)){
            $this->language = $language;
        } else {
            $this->language = $this->config->item('language');
        }

        /**
         * Modül belirtildiyse actionlar ve yetkilen kontrol edilir.
         * Hata durumunda 404 veya denied sayfasına yönlendirilir.
         *
         */
        if (! empty($this->module)) {
            $action = $this->uri->segment(2);

            if (empty($action)) {
                redirect('home/dashboard');
            }

            if (isset($this->actions[$action])) {
                $this->permission($this->actions[$action], true);
            } else {
                if ($action !== 'login') {
                    show_404();
                }
            }

            // $this->breadcrumb($this->moduleTitle, "{$this->module}/{$this->defaultAction}");
        }

        /**
         * Site options verileri sisteme dahil edilir.
         */
        $this->setSiteOptions();
    }

    /**
     * Metodları tetikler.
     *
     * @param $methods
     * @param array $args
     * @param bool $break
     * @return bool
     */
    protected function callMethod($methods, $args = array(), $break = false)
    {
        if (! is_array($args)) {
            $args = array($args);
        }

        if (! is_array($methods)) {
            $methods = array($methods);
        }

        foreach ($methods as $method) {
            if (method_exists($this, $method)) {
                call_user_func_array(array($this, $method), $args);

                if ($break === true) {
                    break;
                }
            }
        }
    }

    /**
     * Metodları tetikler.
     * Var olan bir method çalıştırıldığında diğerleri çalıştırılmaz.
     *
     * @param $methods
     * @param array $args
     */
    protected function callMethodBreak($methods, $args = array())
    {
        $this->callMethod($methods, $args, true);
    }

    /**
     * Kurulumun yapılıp yapılmadığını kontrol eder.
     * Kurulum yapılmadıysa kurulum ekranına geçer.
     */
    public function isReady()
    {
        if (! $this->db->table_exists('modules')) {
            redirect('install');
        }
    }

    /**
     * Modül konfigrasyonlarını kontrol edip database kaydını/güncellemesini yapar
     */
    private function checkModuleConfig()
    {
        $module = $this->db->from('modules')->where('name', $this->module)->get()->row();
        $moduleUpdate = $module ? false : true;
        $reflector = new \ReflectionClass($this);
        $lastModified = filemtime($reflector->getFileName());
        $controller = $reflector->getName();
        $permissions = implode(',', array_unique($this->actions));

        if (! $moduleUpdate) {
            if (
                $module->title !== $this->moduleTitle ||
                $module->name !== $this->module ||
                $module->modified < $lastModified ||
                $module->permissions !== $permissions ||
                $module->controller !== $controller
            ) {
                $moduleUpdate = true;
            }
        }

        if ($moduleUpdate) {
            $data = array(
                'title' => $this->moduleTitle,
                'name' => $this->module,
                'modified' => $lastModified,
                'permissions' => $permissions,
                'type' => isset($this->type) ? $this->type : null,
                'icon' => isset($this->icon) ? $this->icon : null,
                'menuPattern' => isset($this->menuPattern) ? serialize($this->menuPattern) : null,
                'controller' => $controller,
            );

            if ($module) {
                $this->db->where('id', $module->id)->update('modules', $data);
            } else {
                $this->db->insert('modules', $data);
            }
        }

    }

    /**
     * Module argumanlarının kullanılıp kullanılmadığına bakar.
     *
     * @return bool
     */
    public function haveModuleArguments()
    {
        if (! empty($this->module)) {
            $count = $this->db
                ->from('module_arguments')
                ->where('module', $this->module)
                ->where('language', $this->language)
                ->count_all_results();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Login kontrolünü yapar
     */
    private function loginControl()
    {
        if ($this->uri->segment(2) !== 'login' && $this->uri->segment(2) !== 'logout') {
            if ($this->session->userdata('adminlogin') !== true){
                redirect('home/login');
            }

        }
    }

    /**
     * Yetkilendime verilerini çeker.
     */
    private function permissions()
    {
        $records = $this->db
            ->from('admin_perms')
            ->where('groupId', $this->user->groupId)
            ->order_by('groupId', 'asc')
            ->get()
            ->result();

        foreach ($records as $record) {
            $this->permissions[$record->module][] = $record->perm;
        }
    }

    /**
     * Yetki kontrolü yapar
     * @param $perm
     * @param bool $redirect
     * @param null $module
     * @return bool
     */
    public function permission($perm, $redirect = false, $module = null)
    {
        if (! in_array($perm, $this->actions)) {
            return false;
        }

        if ($this->isRoot()){
            return true;
        }

        if (empty($module)) {
            $module = $this->module;
        }

        if (isset($this->permissions[$module]) && in_array($perm, $this->permissions[$module])){
            return true;
        }

        if ($redirect === true){
            if ($this->input->is_ajax_request()) {
                echo 'home/denied';
            } else {
                redirect('home/denied');
            }
        }

        return false;
    }

    /**
     * Root kullanıcı kontrolü.
     *
     * @return bool
     */
    public function isRoot()
    {
        if ($this->user->groupId === null){
            return true;
        }
        return false;
    }


    /**
     * Modül parametrelerine göre link oluşturur.
     *
     * @param $record
     * @return bool|string
     */
    public function createModuleLink($record)
    {
        if (! isset($this->menuPattern['link'])) {
            return false;
        }

        $link = array();
        foreach ($this->menuPattern['link'] as $column){
            $link[] = $record->$column;
        }

        return '@'.$this->module .'/'. implode('/', $link);
    }

    /**
     * Site genel ayarları yükler.
     */
    protected function setSiteOptions()
    {
        $records = $this->db
            ->from('options')
            ->where('language', $this->language)
            ->or_where('language', null)
            ->get()
            ->result();

        $this->siteOptions = new \stdClass();
        foreach ($records as $record) {
            $this->siteOptions->{$record->name} = $record->value;
        }
    }

    /**
     * İlgili site ayarını döndürür.
     *
     * @param $name
     * @return bool
     */
    public function siteOption($name)
    {
        if (isset($this->siteOptions->$name)) {
            return $this->siteOptions->$name;
        }

        return false;
    }

    /**
     * Tüm modülleri döndürür.
     *
     * @param array $excepts
     * @return mixed
     */
    public function getModules($excepts = array())
    {
        if (! empty($excepts)) {
            $this->db->where_not_in('name', $excepts);
        }

        return $this->db
            ->from('modules')
            ->order_by('order', 'asc')
            ->order_by('id', 'asc')
            ->get()
            ->result();
    }

} 