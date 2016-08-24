<?php

use Sirius\Admin\Manager;

class ModuleAdminController extends Manager
{
    private $repositoryPath = '../vendor/sirius-ci';
    private $backupPath = '../backup';


    public $moduleTitle = 'Modüller';
    public $module = 'module';
    public $table = 'modules';
    public $model = 'module';

    // Arama yapılacak kolonlar.
    public $search = array('title', 'name');

    // Filtreleme yapılacak querystring/kolonlar.
    // public $filter = array('type');

    public $actions = array(
        'records' => 'list',
        'update' => 'update',
        'delete' => 'delete',
        'order' => 'order',
        'repository' => 'root',
        'init' => 'root',
    );


    /**
     * @todo menuPattren linkPattern olarak değiştirilecek.
     * @todo ön yüzdeki aktif modülde linkPattern verileri kullanarak otomatik link oluşturtulacak.
     */
    public function update()
    {
        if (! $record = $this->appmodel->name($this->uri->segment(3))) {
            show_404();
        }

        if ($this->input->post()) {
            foreach ($record->arguments as $argument) {
                if (! empty($argument->arguments)) {
                    $this->form_validation->set_rules($argument->name, "Lütfen {$argument->title} geçerli bir değer veriniz.", implode('|', array_keys($argument->arguments)));
                }
            }

            if ($this->form_validation->run() === false) {
                $this->utils->setAlert('danger', $this->form_validation->error_string('<div>&bull; ', '</div>'));
            }

            if (! $this->utils->isAlert()) {
                $success = $this->appmodel->update($record);

                if ($success) {
                    $this->utils->setAlert('success', 'Kayıt düzenlendi.');
                    redirect(clink(array($this->module, 'update', $record->name)));
                }
                $this->utils->setAlert('warning', 'Kayıt düzenlenmedi.');
            }
        }

        $this->load->vars('public', array('js' => array(
            '../public/admin/plugin/ckeditor/ckeditor.js',
            '../public/admin/plugin/ckfinder/ckfinder.js'
        )));

        $this->breadcrumb("{$record->title}: Düzenle");

        $this->viewData['record'] = $record;

        $this->render('update');
    }




    public function repository()
    {
        $detectModules = $this->detectModules();
        $modules = $this->appmodel->all();

        foreach ($modules as $module) {
            if (isset($detectModules[$module->name])) {
                $detectModules[$module->name]->exists = true;
            }
        }

        $this->breadcrumb('Yüklenebilir Modüller');

        $this->viewData['records'] = $detectModules;

        $this->render('repository');
    }


    public function init()
    {
        $module = $this->uri->segment(3);
        $detectModules = $this->detectModules();

        if (! isset($detectModules[$module])) {
            $this->utils->setAlert('danger', 'Modül repository bulunamadı.');
            redirect($this->input->server('HTTP_REFERER'));
        }

        $this->initRepository($detectModules[$module]->path, '..', array(
            '.git',
            '.gitignore',
            'README.md'
        ));

        $this->utils->setAlert('success', 'Modül başarıyla kopyalandı.');
        redirect($this->input->server('HTTP_REFERER'));
    }


    private function initRepository($source, $target, $ignoreFiles = array())
    {
        $backupPath = $this->backupPath .'/'. time();

        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath);
            chmod($this->backupPath, 0777);
        }

        foreach ($ignoreFiles as &$file) {
            $file = $source .'/'. $file;
        }

        $this->copyFiles($source, $target, $backupPath, $ignoreFiles);

        @rmdir($backupPath);
    }



    private function copyFiles($source, $target, $backup = false, $ignoreFiles = array())
    {
        foreach ($ignoreFiles as $file) {
            if (strpos($source, $file) !== false) {
                return false;
            }
        }

        // Kaynak dosya ise yedekleme ve kopyapama işlemini yap.
        if (is_file($source)) {
            if (is_file($target) && $backup !== false) {
                copy($target, $backup);
            }

            return copy($source, $target);
        }

        // Kaynak dizinse ve hedef dizinse, hedef dizinin yedeklenmesi gerekmekte, yedekle.
        if (is_dir($source) && is_dir($target) && $backup !== false) {
            mkdir($backup);
            chmod($backup, 0777);
        }

        // Hedef dizin yoksa hedef dizini oluştur. Üstte dizinse yedekle işlemi yapılmıştı.
        if (! is_dir($target)) {
            mkdir($target);
            chmod($target, 0777);
        }

        $sourceIterator = new \DirectoryIterator($source);

        foreach ($sourceIterator as $iteratorFile) {
            // Dizin elemanlarının klasör olup olmadığı kontrol edilir.
            if (! $iteratorFile->isDot()) {
                $dirName = $iteratorFile->getFilename();
                $backupPath = false;

                if ($target !== "$source/$dirName") {
                    if ($backup !== false) {
                        $backupPath = "$backup/$dirName";
                    }

                    $this->copyFiles("$source/$dirName", "$target/$dirName", $backupPath, $ignoreFiles);
                }
            }
        }
    }

    /**
     * Oluşturulan module kaynaklarını saptar.
     *
     * @throws \Exception
     */
    private function detectModules()
    {
        $modules = array();

        // Module dizin kontrolü yapılır.
        if (! file_exists($this->repositoryPath)){
            throw new \Exception('Repository dizini bulunamadi.');
        }

        $moduleIterator = new \DirectoryIterator($this->repositoryPath);

        foreach ($moduleIterator as $iteratorFile) {
            // Dizin elemanlarının klasör olup olmadığı kontrol edilir.
            if ($iteratorFile->isDir() && ! $iteratorFile->isDot()) {

                // Dizin ismini döndürür.
                $moduleName = $iteratorFile->getFilename();
                $modulePath = $iteratorFile->getPathname();

                $modules[$moduleName] = (object) array(
                    'id' => $moduleName,
                    'name' => ucfirst($moduleName),
                    'path' => $modulePath,
                    'exists' => false
                );
            }
        }

        return $modules;
    }



} 