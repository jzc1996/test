<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Hook;
use think\Validate;
use app\admin\controller\Im;

/**
 * 后台首页
 * @internal
 */
class Index //extends Backend
{

    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['index', 'logout'];
    protected $layout = '';

    public function _initialize()
    {
        //parent::_initialize();
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
    }

    /**
     * 后台首页
     */
    public function index()
    {
        //左侧菜单
        list($menulist, $navlist, $fixedmenu, $referermenu) = $this->auth->getSidebar([
            'dashboard' => 'hot',
            'addon'     => ['new', 'red', 'badge'],
            'auth/rule' => __('Menu'),
            'general'   => ['new', 'purple'],
        ], $this->view->site['fixedpage']);
        $action = $this->request->request('action');
        if ($this->request->isPost()) {
            if ($action == 'refreshmenu') {
                $this->success('', null, ['menulist' => $menulist, 'navlist' => $navlist]);
            }
        }
        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('fixedmenu', $fixedmenu);
        $this->view->assign('referermenu', $referermenu);
        $this->view->assign('title', __('Home'));
        return $this->view->fetch();
    }
    public function ceshi()
    {
        $data = [98, 96, 95, 96, 99, 96];
        for ($i = 0; $i < count($data); $i++) {
            for ($j = $i; $j < count($data) - 1; $j++) {
                if ($data[$i] > $data[$j + 1]) {
                    $num = $data[$i];
                    $data[$i] = $data[$j + 1];
                    $data[$j + 1] = $num;
                }
            }
        }
        print_r($data);
    }
    public function im($data)
    {
        $im = new Im(1400591945, 'b7d7ce4ed9a4fbadaaae1dd475a405e39053c74f26cb34e27f001b0e0b62b91a');
        $sig = $im->genUserSig($data);
        return  $sig;
    }
    /**
     * 管理员登录
     */
    public function login()
    {
        $url = $this->request->get('url', 'index/index');
        if ($this->auth->isLogin()) {
            $this->success(__("You've logged in, do not login again"), $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'require|token',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                '__token__' => $token,
            ];
            if (Config::get('fastadmin.login_captcha')) {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }
            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);
            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            AdminLog::setTitle(__('Login'));
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                $this->error($msg, $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            $this->redirect($url);
        }
        $background = Config::get('fastadmin.login_background');
        $background = $background ? (stripos($background, 'http') === 0 ? $background : config('site.cdnurl') . $background) : '';
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("admin_login_init", $this->request);
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        if ($this->request->isPost()) {
            $this->auth->logout();
            Hook::listen("admin_logout_after", $this->request);
            $this->success(__('Logout successful'), 'index/login');
        }
        $html = "<form id='logout_submit' name='logout_submit' action='' method='post'>" . token() . "<input type='submit' value='ok' style='display:none;'></form>";
        $html .= "<script>document.forms['logout_submit'].submit();</script>";

        return $html;
    }

    public function getList()
    {
        $userSig = $this->im('administrator');
        $rand = rand(0, 4294967295);
        $url = 'https://console.tim.qq.com/v4/recentcontact/get_list?sdkappid=1400591945&identifier=administrator&usersig=' . $userSig . '&random=' . $rand . '&contenttype=json';
        $data = [
            'From_Account' => '11',
            'TimeStamp' => 0,
            'StartIndex' => 0,
            'TopTimeStamp' => 0,
            'TopStartIndex' => 0,
            'AssistFlags' => 7,
        ];
        $data = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $ret = curl_exec($ch);
        // print_r($ret);
        // die;
        $ret = json_decode($ret);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($ret->ErrorCode == 0 && $ret->ActionStatus == 'OK') {
            $num = count($ret->SessionItem);
            // foreach ($ret->SessionItem as $k => $v) {

            // }
        } else {
            $num = 0;
        }
    }

    public function ceshiRedis()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        for ($i = 1; $i <= 10; $i++) {
            $redis->lPush('data1', $i);
        }
    }

    public function buy()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        // 随机用户名，无意义，仅做标记
        $username = '用户';
        $num = $goodsId = $redis->lpop('data1');
        dump($num);
        echo 'oo';
        if ($num) {
            // 购买成功
            $res =  $redis->hset('buy_success', $goodsId, $username);
            dump($res);
        } else {
            // 购买失
            $redis->incr('buy_fail');
        }
    }
}
