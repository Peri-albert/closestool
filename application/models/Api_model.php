<?php
defined('BASEPATH') or exit('no direct script access allowed');
/**
 * Created by PhpStorm.
 * User: zhaokun
 * Date: 2018/6/25
 * Time: 20:42
 */

class Api_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function log_session($openid, $sess, $sess_k)
    {
        $this->db->select('id');
        $this->db->where('openid', $openid);
        $res = $this->db->get('user')->row_array();
        if (empty($res)) {
            $uid = $this->create_account($openid);
            $this->save_session($sess, $sess_k, $openid);
            exit(msg(0, $sess));
        }
    }

    public function get_session($openid)
    {
        $this->db->select('sess,time');
        $this->db->where('openid', $openid);
        $res = $this->db->get('session')->row_array();
        if (empty($res)) {
            exit(msg(1, 'no info'));
        }
        return $res;
    }

    public function save_session($sess, $sess_k, $openid)
    {
        $data = array('openid' => $openid, 'session_key' => $sess_k, 'sess' => $sess, 'time' => time());
        $this->db->where('openid', $openid);
        $res = $this->db->get('session')->row_array();
        if (empty($res)) {
            $this->db->insert('session', $data);
        } else {
            $this->db->where('openid', $openid);
            $this->db->update('session', $data);
        }
    }

    private function create_account($openid)
    {
        $data = array('openid' => $openid, 'status' => 1, 'createtime' => time());
        $this->db->insert("user", $data);
        return $this->db->insert_id();
    }

    public function get_user_info($openid)
    {
        return $this->db->select("weight,height,age,sex,heart_rate,blood_pressure")
            ->where("openid", $openid)
            ->get("user_info")
            ->row_array();
    }

    public function save_user_info($data){
        $row = $this->db->select("id")
            ->where("openid", $data['openid'])
            ->get("user_info")
            ->result_array();

        if(empty($row)){
            $this->db->insert("user_info", $data);
        }else{
            $this->db->where("openid", $data['openid'])
                ->update("user_info", $data);
        }
    }

    public function start_log($openid)
    {
        $count = $this->db->select("id")
            ->where("openid", $openid)
            ->where("status", 1)
            ->count_all_results("use_log");
        if($count > 0){
            return false;
        }

        $data = array(
            "openid"    =>  $openid,
            "start"     =>  time(),
            "type"      =>  1,
            "status"    =>  1,
        );
        $this->db->insert("use_log", $data);
    }

    public function end_log($openid)
    {
        $count = $this->db->select("id")
            ->where("openid", $openid)
            ->where("status", 1)
            ->count_all_results("use_log");
        if($count <= 0){
            return false;
        }

        $data = array(
            "end"       =>  time(),
            "status"    =>  10,
        );
        $this->db->where("openid", $openid)
            ->update("use_log", $data);
    }

    public function get_use_log($openid){
        return $this->db->select("*")
            ->where("openid", $openid)
            ->where("type", 1)
            ->order_by("id", "desc")
            ->get("use_log")
            ->result_array();
    }
}