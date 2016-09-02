<?php

use Admin\Models\AdminModel;

class Home extends AdminModel
{

    public function widgets()
    {
        $this->load->config('widgets');

        $widgets = $this->config->item('widgets');
        $results = array();

        foreach ($widgets as $widget) {
            if (isset($widget['module'])) {
                $module = $this->db
                    ->from('modules')
                    ->where('name', $widget['module'])
                    ->get()
                    ->row();
            }

            if (! empty($module)) {
                $count = 0;

                if (isset($widget['where'])) {
                    $this->db->where($widget['where']);

                    $count = $this->db
                        ->from($module->table)
                        ->count_all_results();
                }

                $total = $this->db
                    ->from($module->table)
                    ->count_all_results();

                $widget['title'] = $module->title;
                $widget['url'] = clink(array($widget['module'], 'records'));
                $widget['count'] = $count;
                $widget['total'] = $total;

                $results[] = (object) $widget;
            }
        }

        return $results;
    }


    public function options()
    {
        $results = $this->db
            ->from('options')
            ->where('language', $this->language)
            ->or_where('language', null)
            ->get()
            ->result();

        foreach ($results as $result) {
            $result->arguments = json_decode($result->arguments, true);
            $result->arguments['value'] = $result->value;
        }

        return $results;
    }

    public function optionsUpdate($options, $data = array())
    {
        $affected = 0;

        foreach ($options as $option) {
            if ($this->input->post($option->name) !== false) {
                $this->db
                    ->where('id', $option->id)
                    ->update('options', array(
                        'value' => $this->input->post($option->name)
                    ));

                if ($this->db->affected_rows() > 0) {
                    $affected++;
                }
            }
        }

        return $affected;
    }

    public function passwordChange($record, $data = array())
    {
        $this->db
            ->where('id', $record->id)
            ->update('admin_users', array(
                'password' => md5($this->input->post('password')),
            ));

        return $this->db->affected_rows();
    }


    public function user($id)
    {
        return $this->db
            ->from('admin_users')
            ->where('id', $id)
            ->get()
            ->row();
    }

    public function userAll($paginate = [])
    {
        $this->setFilter();
        $this->setPaginate($paginate);

        return $this->db
            ->select("admin_users.*, admin_groups.name groupName")
            ->from('admin_users')
            ->join('admin_groups', "admin_groups.id = admin_users.groupId")
            ->where("admin_users.groupId IS NOT NULL")
            ->order_by("admin_users.id", 'asc')
            ->get()
            ->result();
    }


    public function userCount()
    {
        $this->setFilter();

        return $this->db
            ->from('admin_users')
            ->where('groupId IS NOT NULL')
            ->count_all_results();
    }


    public function userInsert($data = array())
    {
        $this->db->insert('admin_users', array(
            'username' => $this->input->post('username'),
            'password' => md5($this->input->post('password')),
            'groupId' => $this->input->post('group'),
        ));

        return $this->db->insert_id();

    }



    public function userUpdate($record, $data = array())
    {
        $password = $this->input->post('password');

        $this->db
            ->where('id', $record->id)
            ->update('admin_users', array(
                'username' => $this->input->post('username'),
                'password' => !empty($password) ? md5($password) : $record->password,
                'groupId' => $this->input->post('group')
            ));

        return $this->db->affected_rows();
    }



    public function userDelete($data)
    {
        return parent::delete('admin_users', $data);
    }


    public function getGroups()
    {
        return $this->db
            ->from('admin_groups')
            ->order_by('id', 'asc')
            ->get()
            ->result();
    }

    public function getModules()
    {
        return $this->db
            ->from('modules')
            ->order_by('id', 'asc')
            ->get()
            ->result();
    }




    public function group($id)
    {
        $record = $this->db
            ->from('admin_groups')
            ->where('id', $id)
            ->get()
            ->row();

        $record->perms = array();

        if ($record) {
            $perms = $this->db
                ->from('admin_perms')
                ->where('groupId', $record->id)
                ->order_by('groupId', 'asc')
                ->get()
                ->result();

            foreach ($perms as $perm) {
                $record->perms[$perm->module][] = $perm->perm;
            }
        }

        return $record;
    }




    public function groupAll($paginate = [])
    {
        $this->setPaginate($paginate);

        return $this->db
            ->from('admin_groups')
            ->order_by('id', 'asc')
            ->get()
            ->result();
    }


    public function groupCount()
    {
        return $this->db
            ->from('admin_groups')
            ->count_all_results();
    }



    public function groupInsert($data = array())
    {
        $this->db->insert('admin_groups', array(
            'name' => $this->input->post('name'),
        ));

        return $this->db->insert_id();

    }



    public function groupUpdate($record, $data = array())
    {
        $this->db
            ->where('id', $record->id)
            ->update('admin_groups', array(
                'name' => $this->input->post('name')
            ));

        return $this->db->affected_rows();
    }



    public function groupDelete($data)
    {
        return parent::delete('admin_groups', $data);
    }


    public function groupPermsUpdate($group)
    {
        $permissions = $this->input->post('perms');

        $this->db
            ->where('groupId', $group->id)
            ->delete('admin_perms');

        foreach ($permissions as $module => $perms) {
            foreach ($perms as $perm){
                $this->db->insert('admin_perms', array(
                    'groupId'	=> $group->id,
                    'module'	=> $module,
                    'perm'		=> $perm
                ));
            }
        }

        return $this->db->affected_rows();
    }


} 