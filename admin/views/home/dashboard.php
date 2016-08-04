<div class="row">
    <?php foreach ($widgets as $widget): ?>
        <div class="col-lg-2 col-md-3">
            <a class="<?php echo isset($widget->type) ? $widget->type : 'app' ?> app-info clearfix" href="<?php echo $widget->url ?>">
                <div class="app-icon"><i class="fa <?php echo $widget->icon ?>"></i></div>
                <div class="app-body">
                    <?php if ($widget->count > 0): ?>
                        <span class="label label-danger"><?php echo $widget->count .' '. $widget->info ?></span>
                    <?php endif; ?>
                    <div class="app-title">
                        <?php echo $widget->title ?>
                        <span><?php echo $widget->total ?> Kayıt</span>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>