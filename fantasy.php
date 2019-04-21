<?php

class Fantasy {
    // HTTPS 转换
    static function convert_https($url){
        return preg_replace("/^http:/", "https:", $url);
    }
    
    static function get_img($url){
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_TIMEOUT,0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_REFERER, "http://www.bilibili.com/");
        $str = curl_exec ($ch);
        $info = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
        curl_close ($ch);
        $httpContentType = $info['content_type'];
        $base_64 = base64_encode($str);
        
        return "data:{$httpContentType};base64,{$base_64}";
    }

    // 追番
    static function bangumi($t){
        $uid = Typecho_Widget::widget('Widget_Options') -> bgm_user;
        $uid = $uid ? $uid : 742725;
        $bgm = file_get_contents("https://api.bilibili.com/x/space/bangumi/follow/list?type=1&pn=1&vmid=" . $uid);
        $bgm = json_decode($bgm);

        if($bgm){
            $data = $bgm->data;
            $pn = $bgm-> pn;
            $ps = $bgm-> ps;
            $total = $bgm-> total;
            foreach($data->list as $item){
                $bid  = $item -> season_id;
                $name = $item -> title;
                $seem = 1;
                $image = self::convert_https($item -> cover);
                $total = $item -> stat -> season_status;
                $width = (int)$seem / $total * 100;
                $img = self::get_img($image);
                ?>

                <div class="col-6 col-m-4">
                <a class="bangumi-item" target="_blank" href="https://www.bilibili.com/bangumi/play/ss<?php echo $bid ?>">
                <div class="bangumi-img" style="background-image: url(<?php echo $img ?>)">
                <div class="bangumi-status">
                <div class="bangumi-status-bar" style="width: <?php echo $width ?>%"></div>
                <p>进度：<?php echo $seem ?> / <?php echo $total ?></p>
                </div>
                </div>
                <h3><?php echo $name ?></h3>
                </a>
                </div>
<?php
            }
        }
        else{
?>
                    <div class="col-12">
                        <p>追番数据获取失败，请检查如下细节：</p>
                        <ul>
                            <li>用户 ID 是否正确？</li>
                            <li>该用户是否在“在看”添加了番剧？</li>
                            <li>服务器能否正常连接 <code>api.bgm.tv</code> ？</li>
                        </ul>
                    </div>
<?php
        }

        unset($bid, $name, $seem, $total, $img, $width);
    }

    // 时间转换
    static function tran_time($ts){
        $dur = time() - $ts;

        if($dur < 0){
            return $ts;
        }
        else if($dur < 60){
            return $dur . ' 秒前';
        }
        else if($dur < 3600){
            return floor($dur / 60) . ' 分钟前';
        }
        else if($dur < 86400){
            return floor($dur / 3600) . ' 小时前';
        }
        else if($dur < 604800){ // 七天内
            return floor($dur / 86400) . ' 天前';
        }
        else if($dur < 2592000){ // 一个月内
            return floor($dur / 604800) . " 周前";
        }

        else{
            return date("y.m.d", $ts);
        }
    }

    // 上次登录
    static function get_last_login(){
        $db = Typecho_Db::get();
        $query = $db -> select() -> from('table.users');
        $logged = $db -> fetchRow($query)["logged"];

        return self::tran_time($logged);
    }
}
