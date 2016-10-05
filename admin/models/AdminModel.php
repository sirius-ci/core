<?php
namespace Admin\Models;


use Sirius\Admin\Model;

class AdminModel extends Model
{

    /**
     * Kayıt silme.
     *
     * @param string $table Tablo adı.
     * @param array|object $data Id'ler yada silinecek kayıt
     * @param bool|false $returnRecords İşlem başarılı olduğunda kayıtları döndür.
     * @return array|bool
     */
    protected function delete($table, $data, $returnRecords = false)
    {
        if (! is_array($data) && ! is_object($data)) {
            return false;
        }

        if (is_object($data)) {
            $data = array($data->id);
        }

        $records = array();

        if ($returnRecords === true) {
            $records = $this->db
                ->from($table)
                ->where_in('id', $data)
                ->get()
                ->result();
        }

        $success = $this->db
            ->where_in('id', $data)
            ->delete($table);

        if ($returnRecords === true && $success) {
            return $records;
        }

        return $success;
    }


    /**
     * Sıralama işlemleri.
     *
     * @param string $table
     * @param array $ids
     * @return bool|int
     */
    protected function order($table, $ids)
    {
        if (! is_array($ids)) {
            return false;
        }

        $records = $this->db
            ->from($table)
            ->where_in('id', $ids)
            ->order_by('order', 'asc')
            ->order_by('id', 'desc')
            ->get()
            ->result();

        $firstOrder = 1;
        $affected = 0;

        foreach ($records as $record) {
            if ($firstOrder === 0) {
                $firstOrder = $record->order;
            }

            $order = array_search($record->id, $ids) + $firstOrder;

            if ($record->order != $order) {
                $this->db
                    ->where('id', $record->id)
                    ->update($table, array('order' => $order));

                if ($this->db->affected_rows() > 0) {
                    $affected++;
                }
            }

        }

        return $affected;
    }
}