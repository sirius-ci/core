<?php

function insert() {
    $this->utils
        ->setPlupload()
        ->uploadInput('file')
        ->minSizes(300, 225)
        ->addProcessSize('thumb', 300, 225, 'product/image/thumb', 'thumbnail')
        ->addProcessSize('normal', 900, 900, 'product/image/normal', 'fit');

    $this->modelData['image'] = $this->utils->imageUpload(true);

    $success = $this->appmodel->imageInsert($this->modelData);

    if ($success) {
        echo json_encode(array(
            'jsonrpc'	=> '2.0',
            'error'		=> array(),
            'id'		=> 'id'
        ));

        return;
    }
}