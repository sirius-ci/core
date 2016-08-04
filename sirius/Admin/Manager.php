<?php

namespace Sirius\Admin;


abstract class Manager extends \CI_Controller
{
    public $user;
    public $permissions = array();
    public $modelData = array();
    public $viewData = array();
    public $breadcrumb = array();
    public $defaultAction = 'records';
    public $language;
    public $siteOptions;


    public function __construct()
    {
        /**
         * Modül değişkenleri kontrol edilir
         */
        if (! empty($this->module)) {
            if (empty($this->moduleTitle) || empty($this->table) || empty($this->model) || empty($this->actions)) {
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
                $reservedActions = array('login', 'logout', 'dashboard', 'denied', 'language');

                if ($this->module !== 'home' || ($this->module === 'home' && (! in_array($action, $reservedActions)))) {
                    show_404();
                }
            }

            $this->breadcrumb($this->moduleTitle, "{$this->module}/{$this->defaultAction}");
        }



        /**
         * Site options verileri sisteme dahil edilir.
         */
        $this->setSiteOptions();
    }


    /**
     * Tüm kayıtları sayfalama yaparak listeler.
     */
    public function records()
    {
        $records = array();
        $pagination = null;
        $recordCount = $this->appmodel->count();

        if ($recordCount > 0) {
            $config = array(
                'base_url' => clink(array($this->module, 'records')),
                'total_rows' => $recordCount,
                'per_page' => 19
            );

            $this->load->library('pagination');
            $this->pagination->initialize($config);


            $records = $this->appmodel->all($this->pagination->per_page +1, $this->pagination->offset);
            $pagination = $this->pagination->create_links();
        }

        $this->callMethod('recordsRequest');

        $this->breadcrumb('Kayıtlar');

        $this->viewData['records'] = $records;
        $this->viewData['pagination'] = $pagination;

        $this->render('records');
    }


    /**
     * Yeni kayıt ekleme
     */
    public function insert()
    {
        if ($this->input->post()) {
            $this->callMethod('insertValidateRules');
            $this->callMethod('insertBeforeValidate');

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            $this->callMethod('insertAfterValidate');

            if (! $this->utils->isAlert()) {
                $this->callMethod('insertBefore');
                $success = $this->appmodel->insert($this->modelData);

                if ($success) {
                    $this->callMethod('insertAfter');
                    $this->utils->setAlert('success', 'Kayıt eklendi.');

                    if ($this->input->post('redirect')) {
                        $redirect = $this->input->post('redirect');
                    } else {
                        $redirect = clink(array($this->module, 'update', $success));
                    }

                    redirect($redirect);
                }
            }
        }

        $this->callMethod('insertRequest');

        $this->breadcrumb('Yeni kayıt');

        $this->render('insert');
    }


    /**
     * Kayıt güncelleme
     */
    public function update()
    {
        if (! $record = $this->appmodel->id($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            $this->callMethod('updateValidateRules');
            $this->callMethod('updateBeforeValidate', $record);

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            $this->callMethod('updateAfterValidate', $record);

            if (! $this->utils->isAlert()) {
                $this->callMethod('updateBefore', $record);
                $success = $this->appmodel->update($record, $this->modelData);

                if ($success) {
                    $this->callMethod('updateAfter', $record);
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');

                    if ($this->input->post('redirect')) {
                        $redirect = $this->input->post('redirect');
                    } else {
                        $redirect = clink(array($this->module, 'update', $record->id));
                    }

                    redirect($redirect);
                }
                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }
        $this->callMethod('updateRequest', $record);

        $this->breadcrumb('Kayıt Düzenle');

        $this->viewData['record'] = $record;

        $this->render('update');
    }


    /**
     * Kayıt(lar) silme
     * @return bool
     */
    public function delete()
    {
        /**
         * Ajax sorgusu  ise toplu silme uygulanır
         */
        if ($this->input->is_ajax_request()) {
            $ids = $this->input->post('ids');

            if (count($ids) == 0) {
                $this->utils->setAlert('danger', 'Lütfen kayıt seçiniz.');
                echo $this->input->server('HTTP_REFERER');
            }
            $success = $this->appmodel->delete($ids);

            if ($success) {
                $this->utils->setAlert('success', "Kayıtlar başarıyla silindi.");
                echo $this->input->server('HTTP_REFERER');
            }

            return true;
        }

        /**
         * Normal sorgu ise tekli silme uygulanır
         */
        if (! $record = $this->appmodel->id($this->uri->segment(3))) {
            show_404();
        }

        $success = $this->appmodel->delete($record);

        if ($success) {
            $this->utils->setAlert('success', "Kayıt kaldırıldı. (#{$record->id})");
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->utils->setAlert('danger', 'Kayıt kaldırılamadı.');
        redirect($this->input->server('HTTP_REFERER'));

    }


    /**
     * Sıralama işlemi yapar
     */
    public function order()
    {
        $ids = explode(',', $this->input->post('ids'));

        if (count($ids) == 0){
            $this->utils->setAlert('danger', 'Lütfen kayıt seçiniz.');
        }

        $success = $this->appmodel->order($ids);

        if ($success){
            $this->utils->setAlert('success', "Kayıtlar başarıyla sıralandı.");
        }
    }


    /**
     * View dosyasını layout ile birlikte yükler
     * @param $file
     */
    protected function render($file)
    {
        if (is_array($file)) {
            $file = implode('/', $file);
        }

        $this->load->view('layout', array(
            'view' => $this->module .'/'. $file,
            'data' => $this->viewData
        ));
    }


    /**
     * Metodları tetikler
     * @param $method
     * @param array $args
     */
    protected function callMethod($method, $args = array())
    {
        if (! is_array($args)) {
            $args = array($args);
        }

        if (method_exists($this, $method)) {
            call_user_func_array(array($this, $method), $args);
        }
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
                $module->table !== $this->table ||
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
                'table' => $this->table,
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

        if ($this->user->groupId === null){
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


    public function isRoot()
    {
        if ($this->user->groupId === null){
            return true;
        }
        return false;
    }

    /**
     * Navigasyon elemanı tanımlar
     *
     * @param string $title
     * @param string $url
     */
    protected function breadcrumb($title, $url = '')
    {
        $this->breadcrumb[] = array('title' => $title, 'url' => $url);
    }


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



    public function siteOption($name)
    {
        if (isset($this->siteOptions->$name)) {
            return $this->siteOptions->$name;
        }

        return false;
    }


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