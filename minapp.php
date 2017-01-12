<?php

/**
 * author         : luffy<luffy@comicool.cn>
 * creatdate    : 17-1-9 下午11:02
 * description :
 */

require_once 'init.php';

class Minapp extends BaseSpider
{

    public function __construct()
    {
        parent::__construct();
        $this->logfile = 'Minapp' . date('Y-m-d') . '.log';
        $this->domain = 'https://minapp.com';
        $this->spider_url = $this->domain . '/api/v3/trochili/miniapp/?&limit=1000';
        $this->host = 'minapp.com';
        $this->ref = 'https://minapp.com/miniapp/';

    }

    public function run()
    {
        $urls = $this->getApp($this->user_id);
        if (count($urls)) {
            $rs = $this->postBaidu($urls);
            $this->debug("百度提交返回信息：\r\n" . $rs);
        }
    }

    /**
     * 采集知晓程序app列表
     */
    private function getApp($user_id)
    {
        $i = 0;
        $post_urls = array();

        $app_list = http_request($this->spider_url);
        $data_list = json_decode($app_list, 1);

        $is_next = $data_list['meta']['next'];

        foreach (array_reverse($data_list['objects']) as $key => $value) {
            _pushMsg('应用=> ' . $value['name'] . '  正在入库...', 0);

            $wxapps['user_id'] = $user_id;
            $wxapps['title'] = $value['name'];
            $wxapps['description'] = $value['description'];
            $wxapps['source'] = 'minapp';
            $wxapps['source_id'] = $value['id'];
            $wxapps['icon'] = $this->upImg2Qin($value['icon']['image']);
            $wxapps['qrcode'] = $this->upImg2Qin($value['qrcode']['image'], null, 'qrcode');
            $wxapps['tags'] = trim(array_reduce($value['tag'], function ($ids, $res) {
                return $ids . $res['name'] . ',';
            }), ",");

            $wxapps['screens'] = trim(array_reduce($value['screenshot'], function ($ids, $res) {
                return $ids . $this->upImg2Qin($res['image'], null, 'screenshot') . ',';
            }), ",");
            $status = $this->postData('wxapp', $this->token, $wxapps);
            $jsonp = json_decode($status, 1);

            if (isset($jsonp['status']) && $jsonp['status'] == 'success') {
                $stat = $jsonp['status'] . '=>id#' . $jsonp['data']['id'];
                $post_urls[] = $this->basehost . 'xiaochengxu/' . $jsonp['data']['id'] . '/' . $jsonp['data']['name'];
                $i++;
            } else {
                $stat = $status;
            }
            _pushMsg(' 状态：' . $stat);
            sleep(2);
            $this->debug('应用=> ' . $value['name'] . '  正在入库...状态：' . $stat);
            $this->debug('返回', $jsonp);
        }
        if ($is_next != null)
            $this->app_url = $this->domain . $is_next;

        _pushMsg('所有应用已采集入库，本次入库 【' . $i . '】  条记录...');
        return $post_urls;

    }

}

$caiji = new Minapp();
$caiji->run();

