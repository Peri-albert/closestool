<?php
/**
 * Created by PhpStorm.
 * User: zhaokun
 * Date: 2018/6/25
 * Time: 20:40
 */

function msg($code=0, $data = "", $msg="")
{
    if($msg == ""){
        $msg = $code == 0 ? "成功" : "失败";
    }
    $msg = array(
        "code"	=>	$code,
        "data"  =>  $data,
        "msg"	=>	$msg,
    );
    return json_encode($msg, JSON_UNESCAPED_UNICODE);
}

function is_session($key)
{
    $CI = &get_instance();
    $CI->load->database();
    $CI->db->where('sess', $key);
    $res = $CI->db->get('session')->row_array();
    if(!empty($res))
    {
        $openid = $res['openid'];
        $CI->db->where('openid', $openid);
        $uid = $CI->db->get('user')->row_array();
        return array("uid" => $uid['id'], "openid" => $openid);
    }
    exit(msg(1, "", "用户未登录，请重试"));
}