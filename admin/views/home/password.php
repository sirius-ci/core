
<div class="row">
    <form action="" method="post" enctype="multipart/form-data">

        <div class="col-md-8">
            <?php echo $this->utils->alert(); ?>

            <div class="panel panel-default">
                <div class="panel-heading"><i class="fa fa-edit"></i> Parola Değiştir: <?php echo $record->username ?></div>
                <div class="panel-body">

                    <?php echo bsFormPassword('password', 'Parola', array('required' => true)) ?>

                </div>

                <div class="panel-footer">
                    <button class="btn btn-success" type="submit">Gönder</button>
                </div>

            </div>
        </div>

    </form>
</div>