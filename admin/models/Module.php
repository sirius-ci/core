<?php

class Module extends CI_Model
{

    public function name($name)
    {
        $record = $this->db
            ->from($this->table)
            ->where('name', $name)
            ->get()
            ->row();

        if ($record) {
            $record->arguments = $this->arguments($record);
        }
        return $record;
    }


    public function id($id)
    {
        $record = $this->db
            ->from($this->table)
            ->where('id', $id)
            ->get()
            ->row();

        if ($record) {
            $record->arguments = $this->arguments($record);
        }
        return $record;
    }


    public function all($limit = null, $offset = null)
    {
        $this->utils->filter();


        if ($limit != null) {
            $this->db->limit($limit, $offset);
        }

        $records = $this->db
            ->select("{$this->table}.*, (SELECT COUNT(id) FROM module_arguments WHERE module_arguments.module = {$this->table}.name AND module_arguments.language = '{$this->language}') arguments", false)
            ->from($this->table)
            ->order_by('id', 'asc')
            ->get()
            ->result();

        foreach ($records as $record) {
            if (! file_exists(APPPATH .'controllers/'. $record->controller .'.php') || empty($record->controller)) {
                $record->controller = false;
            }
        }

        return $records;
    }


    public function count()
    {
        $this->utils->filter();

        return $this->db
            ->from($this->table)
            ->count_all_results();
    }



    public function update($record, $data = array())
    {
        $affected = 0;

        foreach ($record->arguments as $argument) {
            if ($this->input->post($argument->name) !== false) {
                $this->db
                    ->where('id', $argument->id)
                    ->update('module_arguments', array(
                        'value' => $this->input->post($argument->name)
                    ));
                if ($this->db->affected_rows() > 0) {
                    $affected++;
                }
            }
        }

        return $affected;
    }



    public function delete($data)
    {
        if (is_array($data)) {
            $success = $this->db
                ->where_in('id', $data)
                ->delete($this->table);

            return $success;
        }

        $success = $this->db
            ->where('id', $data->id)
            ->delete($this->table);

        return $success;
    }


    public function order($ids = null)
    {
        if (is_array($ids)) {
            $records = $this->db
                ->from($this->table)
                ->where_in('id', $ids)
                ->order_by('order', 'asc')
                ->order_by('id', 'desc')
                ->get()
                ->result();

            $firstOrder = 0;
            $affected = 0;

            foreach ($records as $record) {
                if ($firstOrder === 0) {
                    $firstOrder = $record->order;
                }

                $order = array_search($record->id, $ids) + $firstOrder;

                if ($record->order != $order) {
                    $this->db
                        ->where('id', $record->id)
                        ->update($this->table, array('order' => $order));

                    if ($this->db->affected_rows() > 0) {
                        $affected++;
                    }
                }

            }

            return $affected;
        }

    }



    public function arguments($record)
    {
        $results = $this->db
            ->from('module_arguments')
            ->where('module', $record->name)
            ->where('language', $this->language)
            ->get()
            ->result();

        foreach ($results as $result) {
            $result->arguments = json_decode($result->arguments, true);
            $result->arguments['value'] = $result->value;
        }

        return $results;
    }


} 