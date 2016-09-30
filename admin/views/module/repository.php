
<?php echo $this->alert->flash(['error', 'success']); ?>

<div class="panel panel-default">
    <div class="panel-heading"><i class="fa fa-table"></i> <?php echo $this->moduleTitle ?></div>
    <table class="table table-bordered table-hover">
        <thead>
        <tr>
            <th width="40" class="text-center"><i class="fa fa-ellipsis-v"></i></th>
            <th width="70" class="text-center">Mevcut</th>
            <th>Modül</th>
            <th width="100" class="text-right">İşlem</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $item): ?>
            <tr>
                <td class="text-center"><input type="checkbox" class="checkall-item" value="<?php echo $item->id ?>" /></td>
                <td class="text-center">
                    <?php if ($item->exists === true): ?>
                        <span class="text-success"><i class="fa fa-check"></i></span>
                    <?php endif; ?>
                </td>
                <td><?php echo $item->name ?> Modülü</td>
                <td class="text-right">
                    <?php if ($this->isRoot()): ?>
                        <a class="btn btn-xs btn-success" data-toggle="tooltip" data-placement="top" title="Yükle" href="<?php echo $this->module ?>/init/<?php echo $item->id ?>"><i class="fa fa-cloud-upload"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (! empty($pagination)): ?>
        <div class="panel-footer">
            <?php echo $pagination ?>
        </div>
    <?php endif; ?>
</div>