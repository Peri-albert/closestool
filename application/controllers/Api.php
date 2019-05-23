<?php
defined('BASEPATH') or exit('no direct script access allowed');
/**
 * Created by PhpStorm.
 * User: zhaokun
 * Date: 2018/6/25
 * Time: 20:32
 */

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper("wx");
        $this->load->model("api_model", "api");
        date_default_timezone_set("PRC");
    }

    public function wx_login()
    {
        $code = $this->input->get('code');
        if ($code == '') {
            exit(msg(302, "code不能为空"));
        }
        $appid = "wx268b00162520ecb0";
        $secret = "5e594b55a7795c53947a2f5e2a36dfcb";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($curl));
        curl_close($curl);
        if (isset($res->errcode)) {
            exit(msg(1, $res->errccode, "微信服务器错误"));
        }
        $openid = $res->openid;
        $session_key = $res->session_key;

        $sess = (string)time();
        $this->api->log_session($openid, $sess, $session_key);

        $old_session_info = $this->api->get_session($openid);
        $old_time = $old_session_info['time'];
        $old_sess = $old_session_info['sess'];
        if ($old_time + 7200 >= time()) {
            exit(msg(0, $old_sess));
        }

        $this->api->save_session($sess, $session_key, $openid);
        exit(msg(0, $sess));
    }

    public function get_user_info()
    {
        $key = is_session($this->input->get_post("key"));
        $data = $this->api->get_user_info($key['openid']);
        if(empty($data)){
            $data = array(
                "weight"    =>  0,
                "height"    =>  0,
                "age"       =>  0,
                "sex"       =>  "未设置",
                "heart_rate"=>  0,
                "blood_pressure"=>0,
                "BMI"       =>  0,
            );
        }else{
            if($data['sex'] == 1){
                $data['sex'] = "男";
            }elseif($data['sex'] == 2) {
                $data['sex'] = "女";
            }else{
                $data['sex'] = "未知";
            }
            $data['BMI'] = 0;
        }

        exit(msg(0, $data));
    }

    public function save_user_info()
    {
        $key = is_session($this->input->get_post("key"));

        $data = $this->input->get_post("data");
        $data = json_decode($data, true);
        $data['openid'] = $key['openid'];
        if($data['sex'] == "男"){
            $data['sex'] = 1;
        }elseif($data['sex'] == "女"){
            $data['sex'] = 2;
        }else{
            $data['sex'] = 3;
        }
        unset($data["BMI"]);

        $this->api->save_user_info($data);
        $this->get_user_info();
    }

    public function use_log_action()
    {
        $key = is_session($this->input->get_post("key"));

        $flag = $this->input->get_post("flag");
        $code = 1;
        $msg = "未知的服务器错误，请重试";
        if($flag == 1){
            $res = $this->api->start_log($key['openid']);
            if($res) {
                $code = 0;
                $msg = "开始记录";
            }else{
                $msg = "已有正在进行的记录";
            }
        }elseif ($flag == 2){
            $res = $this->api->end_log($key['openid']);
            if($res) {
                $code = 0;
                $msg = "结束记录";
            }else{
                $msg = "没有正在进行的记录";
            }
        }

        exit(msg($code, "", $msg));
    }

    public function get_use_log()
    {
        $key = is_session($this->input->get_post("key"));
        $data = $this->api->get_use_log($key['openid']);

        foreach ($data as $k => $v){
            if($v['status'] == 10) {
                $data[$k]['time'] = $v['end'] - $v['start'];
                if ($data[$k]['time'] < 60) {
                    $data[$k]['time'] = $data[$k]['time'] . "秒";
                } else {
                    $data[$k]['time'] = (int)($data[$k]['time'] / 60) . "分";
                }
                $data[$k]['start'] = date("Y-m-d H:i:s", $v['start']);
                $data[$k]['end'] = date("Y-m-d H:i:s", $v['end']);
                $data[$k]['status'] = "已完成";
            }else {
                $data[$k]['time'] = "--";
                $data[$k]['start'] = date("Y-m-d H:i:s", $v['start']);
                $data[$k]['end'] = "--";
                $v['status'] = "进行中";
            }
        }

        exit(msg(0, $data, ""));
    }
}