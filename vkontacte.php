<?php

class vkontacte {

    public 
        $token = "KriUD6iDh3GFmG0ZM5in",
        $id = '5331985',
        $access_token = "ec971acba0c71422712ec2b93f4ffdd5850b01e9814b5afb0088db84f626fb1e4ead22a953978a36ebebd",
        $secret = "cc536d92c5b939c49e",
        $url = 'https://api.vk.com/method/',
        $no_https = 1;

    /*
     *  $param = array( access_token , secret )
     */

    function __construct($param, $app = null) 
    {
        $this->access_token = $param['access_token'];
        $this->secret = $param['secret'];
        if ($app) {
            $this->setApp($app);
        }
    }

    function getAuthLink()
    {
        return 'https://oauth.vk.com/authorize?client_id='
            . $this->id
            . '&display=popup&redirect_uri=https://oauth.vk.com/blank.html&scope=friends,offline,messages,nohttps,docs,photos,wall,video,groups,ads&response_type=token';
    }
    
    function setApp($app) {
        $this->id = $app;
    }

    function checkRead($to, $list) {
        $param = array(
            'message_ids' => implode(",", $list),
            'peer_id' => $to
        );
        return $re = $this->callMethod("messages.markAsRead", $param);
    }

    function sendMessage($to, $message, $attach=null) {
        $param = array(
            'message' => $message
        );
        if (is_numeric($to)) {
            $param['user_id'] = $to;
        } else {
            $param['domain'] = $to;
        }
        if($attach){
            $param['attachment'] = implode(",",$attach);
        }
        
        $re = $this->callMethod("messages.send", $param);
        
        print_r($re);
        
        return ($re['response']) ? $re['response'] : $re;
    }

    function getDialogs($message) {
        $param = array(
            'count' => 1, //user_id
            'start_message_id' => $message
        );
        $re = $this->callMethod("messages.getDialogs", $param);
        return $re;
    }

    function getLongPollServer() {
        $param = array(
            'use_ssl' => 1,
            'need_pts' => 1
        );
        $re = $this->callMethod('messages.getLongPollServer', $param);
        return $re;
    }

    function connectToLongpool($param) {
        if (isset($param['server'])) {
            $url = "http://{$param['server']}?act=a_check&key={$param['key']}&ts={$param['ts']}&wait=15&mode=2";
            $file = file_get_contents($url);
            $res = json_decode($file, 1);
            return $res;
        }
    }

    function resireilizeUpdate($history) {
        $res = null;
        foreach ($history as $it) {
            if ($it[0] == '61') {
                $res['write'][] = array(
                    'user_id' => $it[1],
                    'flags' => $it[2]
                );
            }
            if ($it[0] == '4') {
                if (!$res) {
                    $res = array('message' => array());
                }
                $item = array(
                    'message_id' => $it[1],
                    'flags' => $this->getFlags($it[2]),
                    'from_id' => $it[3],
                    'timestamp' => $it[4],
                    'subject' => $it[5],
                    'text' => $it[6],
                    'attachments' => $it[7]
                );

                if (in_array('OUTBOX', $item['flags'])) {
                    $item['to'] = 'client';
                } else {
                    $item['to'] = 'crm';
                }

                $res['message'][] = $item;
            }
        }
        return $res;
    }

    public $flags = array(
        512 => 'MEDIA', //	сообщение содержит медиаконтент
        256 => 'FIXED', //	сообщение проверено пользователем на спам
        128 => 'DELЕTЕD', //	сообщение удалено (в корзине)
        64 => 'SPAM', //	сообщение помечено как "Спам"
        32 => 'FRIENDS', //	сообщение отправлено другом
        16 => 'CHAT', //	сообщение отправлено через чат
        8 => 'IMPORTANT', //помеченное сообщение
        4 => 'REPLIED', //на сообщение был создан ответ
        2 => 'OUTBOX', //	исходящее сообщение
        1 => 'UNREAD', //сообщение не прочитано
    );

    function getFlags($mask) {
        $list = array();
        foreach ($this->flags as $num => $flag) {
            if ($mask >= $num) {
                $list[] = $flag;
                $mask -= $num;
            }
            if ($mask == 0) {
                break;
            }
        }

        return $list;
    }

    function getUser($name) {
        $param = array(
            'user_ids' => $name,
            'fields' => 'photo_50,screen_name'
        );
        $re = $this->callMethod("users.get", $param);
        if(!$re['response'][0]){
            $this->log = $re;
        }
        return ($re['response'][0]) ? $re['response'][0] : null;
    }

    function getMyInfo() {
        $info = $this->getUser("");
        $res = array();
        $res['uid'] = $info['uid'];
        $res['photo'] = $info['photo_50'];
        $res['name'] = $info['first_name'] . " " . $info['last_name'];
        $res['link'] = "https://vk.com/" . $info['screen_name'];
        return $res;
    }

    function returnInfo($info)
    {
        return array(
            'name'=>$info['first_name'] . " " . $info['last_name'],
            'link'=>"https://vk.com/" . $info['screen_name'],
            'vk'=>"https://vk.com/" . $info['screen_name'],
            'photo'=>$info['photo_50']
        );
    }
    
    function getGroup($name) {
        $param = array(
            'group_id' => $name
        );
        $re = $this->callMethod("groups.getById", $param);
        if (!$re['response'][0]) {
            dlog('error get group', $re);
        }
        return ($re['response'][0]) ? $re['response'][0] : array('error' => $re['error']);
    }

    function callMethod($method, $param) {

        $ch = curl_init();
        $param['access_token'] = $this->access_token;
        $param['v'] = '5.65';
        if($this->secret){
            $sig = md5("/method/" . $method . "?" . http_build_query($param) . $this->secret);
            $param['sig'] = $sig;
        }

        //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_URL, $this->url . $method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));

        $ret = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($ret, 1);
    }

    function getHistory($user, $start = null) {
        $param = array(
            'count' => 20,
            'user_id' => $user,
        );
        if ($start) {
            $param['start_message_id'] = $start;
            //$param['offset'] = $start;
        }
        $re = $this->callMethod('messages.getHistory', $param);
        if ($re['response']) {
            $res = array(
                'list' => $re['response'],
                'count' => $re['response'][0]
            );
            unset($res['list'][0]);
        } else {
            $res['error'] = $re;
        }
        return $res;
    }

    /*
     *  $captcha = array(captcha_sid,captcha_key)
     * 
     */
    
    function postToWall($post, $captcha = null) {

        if ($post['type'] == 'group') {
            $post['uid'] = "-" . $post['uid'];
        }
        $param = array(
            //'owner_id'=>$post['uid'],
            'message' => $post['message'],
            'from_group' => 1
        );

        if ($post['uid']) {
            $param['owner_id'] = $post['uid'];
        }

        $attachments = array();
        if ($post['attachments']) {
            $attachments = $post['attachments'];
        }
        if ($post['images']) {
            $images = $this->returnImgFromAttach($post['images']);
            $attachments = array_merge($attachments, $images);
        }

        if ($post['files']) {
            $files = $this->returnImgFromAttach($post['files']);
            $attachments = array_merge($attachments, $files);
        }

        if ($attachments) {
            $param['attachments'] = implode(',', $attachments);
        }

        if ($captcha) {
            $param['captcha_sid'] = $captcha['captcha_sid'];
            $param['captcha_key'] = $captcha['captcha_key'];
        }
        
        $re = $this->callMethod("wall.post", $param);
        if ($re['response']['post_id']) {
            return array(
                'stat' => 1,
                'id' => $re['response']['post_id']
            );
        } else {
            $er = $this->returnError($re['error']);
            return array(
                'stat' => 0,
                'error' => $er,
                'error_message' => $er['message']
            );
        }
    }

    function returnError($e,$onlyMessage=0) {
        dlog('vk error',$e);
        switch ($e['error_code']) {
            case 601:
                $e['message'] = 'Вы превысили лимит запросов в час. Попробуйте позже';
                $e['type'] = 'limit';
                break;
            case 214:
                $e['message'] = 'Для данной группы/страницы превышен лимит добавления постов (50 в день)';
                $e['type'] = 'limit';
                break;
            case 14:
                $e['message'] = 'Введите каптчу';
                $e['type'] = 'captcha';
                break;
            case 15:

                if ($e['error_msg'] == "Access denied: edit time expired") {
                    $e['message'] = 'Вышло время редактирования';
                    $e['type'] = 'timeExpired';
                } else {
                    $e['message'] = 'Нет доступа к методу, переавторизуйтесь';
                    $e['type'] = 'access';
                }
                $e['skip'] = 1;
                break;
            case 5:
                $e['message'] = 'Ошибка авторизации, переавторизуйтесь в настройках соцсетей пожалуйста';
                break;
            default:
                $e['message'] = $e['error_msg'];
                $e['type'] = 'undifined';
                break;
        }
        if($onlyMessage){
            return $e['message'];
        }
        return $e;
    }

    function setToken($token) {
        $this->access_token = $token;
    }

    function updatePostWall($uid, $wall_id, $post, $captcha = null) {
        if ($post['type'] == 'group') {
            $uid = "-" . $uid;
        }
        $attachments = array();

        if ($post['images']) {
            $images = $this->returnImgFromAttach($post['images']);
            $attachments = array_merge($attachments, $images);
        }

        if ($post['files']) {
            $files = $this->returnImgFromAttach($post['files']);
            $attachments = array_merge($attachments, $files);
        }

        if ($post['videos']) {
            $attachments = array_merge($attachments, $this->returnImgFromAttach($post['videos']));
        }

        $param = array(
            'message' => $post['message'],
            'owner_id' => $uid,
            'post_id' => $wall_id,
                //'signed'=>1
        );

        if ($captcha) {
            $param['captcha_sid'] = $captcha['captcha_sid'];
            $param['captcha_key'] = $captcha['captcha_key'];
        }

        if ($attachments) {
            $param['attachments'] = implode(',', $attachments);
        }
        $re = $this->callMethod("wall.edit", $param);

        /* if($re['error']['error_code']==14){
          $re = array(
          'error'=>array(
          'type'=>'captcha',
          'captcha_sid'=>$re['error']['captcha_sid'],
          'captcha_img'=>$re['error']['captcha_img']
          )
          );
          } */
        if ($re['error']) {
            $re['error'] = $this->returnError($re['error']);
        }

        return $re;
    }

    function getUploadPhoto($album=0,$group=0) {
        $param = array(
            'album_id' => ($album) ? $album : $this->album,
            'group_id' => $group
        );
        $re = $this->callMethod("photos.getUploadServer", $param);
        
        return $re['response'];
    }

    function getUploadVideo($param) {
        $param['album_id'] = $this->albumVideo;
        $re = $this->callMethod("video.save", $param);
        //dlog("video.getUploadServer",array($param,$re));
        pr('re',$re);
        return $re['response'];
    }

    function getUploadFile() {
        $re = $this->callMethod('docs.getUploadServer', array());
        return $re['response'];
    }

    public function allAlbums($id)
    {
        return  $this->callMethod("video.getAlbums", array(
            'owner_id' => $id
        ));
    }
    
    function getAlbumVideo() {
        if ($this->albumVideo) {
            return $this->albumVideo;
        }
        $re = $this->callMethod("video.getAlbums", array());
        $album_id = 0;

        if ($re['response']) {
            foreach ($re['response'] as $album) {
                if ($album['size'] < 420) {
                    $album_id = $album['aid'];
                    break;
                }
            }
        }
        if ($album_id) {
            $this->albumVideo = $album_id;
            return $album_id;
        }
    }

    function getAlbum() {
        if ($this->album) {
            return $this->album;
        }
        $re = $this->callMethod("photos.getAlbums", array());
        $album_id = 0;

        if ($re['response']) {
            foreach ($re['response'] as $album) {
                if ($album['size'] < 420) {
                    $album_id = $album['aid'];
                    break;
                }
            }
        }
        if ($album_id) {
            $this->album = $album_id;
            return $album_id;
        }
    }

    function createAlbum() {
        $re = $this->callMethod("photos.createAlbum", array(
            'title' => 'Альбом для фотографий CRM Intrum'
        ));
        if ($re['response']['aid']) {
            $this->album = $re['response']['aid'];
        }
    }

    function createAlbumVideo() {
        $re = $this->callMethod("video.createAlbum", array(
            'title' => 'Альбом для Видео CRM Intrum'
        ));
        if ($re['response']['aid']) {
            $this->albumVideo = $re['response']['aid'];
        }
    }

    public function _postFile($url, $file, $title) 
    {
        //print_r(func_get_args());
        
        //$file = preg_replace("#([A-Z]*:)#", "", $file);
        //$file = "@" . $file;
        
        $fileContents = array(
            'name' => $title,
            'file' => curl_file_create($file)
        );
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
        
        $ret = curl_exec($ch);
        
        curl_close($ch);
        $res = json_decode($ret, 1);

        return $res;
    }

    public function _postVideo($url,$file){
        $ch = curl_init();
        
        $file = preg_replace("#([A-Z]*:)#", "", $file);
        $post = array(
            'video_file' =>  curl_file_create($file)
                //"@" . realpath($file) . "" //;type=video/mp4
        );
        
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $ret = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($ret, 1);
        return $res;
    }
    
    function _postFiles($url, $files) {
        $fileContents = array();
        $it = 0;
        foreach ($files as $file) {
            $it++;
            $file = preg_replace("#([A-Z]*:)#", "", $file);
            $fileContents['file' . $it] = "@" . $file;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
        $ret = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($ret, 1);

        return $res;
    }

    function saveVideo($url) {
        $res = $this->callMethod($url, $param);
        return $res['response'];
    }

    public function savePhoto($param) 
    {
        $param['album_id'] = $this->album;
        $res = $this->callMethod('photos.save', $param);
        return $res['response'];
    }

    function saveFile($param) {
        $res = $this->callMethod('docs.save', $param);
        return $res['response'][0];
    }

    function uploadFiles($list) {
        $re = array();
        $set = $this->getUploadFile();
        $files = array();
        foreach ($list as $item) {
            $exts = explode(".", $item['title']);
            $location = ceil($item['id'] / 500);
            $files[$item['id']] = $_SERVER['DOCUMENT_ROOT'] . "/files/" . $location . "/" . $item['id'] . "_0." . $exts[count($exts) - 1];
        }
        $return = array();
        foreach ($files as $id => $file) {
            $server = $this->_postFile($set['upload_url'], $file, $list[$id]['title']);
            if(!$server || $server['error']){
                dlog('vk error upload server',$server);
            }
            $res = $this->saveFile($server);
            $return[] = array(
                'pid' => $res['did'],
                'src' => 'doc' . $res['owner_id'] . "_" . $res['did'],
                'soc' => 'vk'
            );
        }
        return $return;
    }

    public function uploadVideo($it,$file)
    {
        $album = $this->getAlbumVideo();
        $set = $this->getUploadVideo(array(
            'group_id' => $this->group,
            'wallpost' => 0,
            'name' => $it['name'],
            'description' => $it['description']
        ));
        
        if(!$set){
            return false;
        }
        
        $res = $this->_postVideo($set['upload_url'],$file);
        
        return $res;
    }
    
    function uploadVideos($list,$album) {
        $re = array();
        if(!$album){
            $album = $this->getAlbumVideo();
            if (!$album) {
                $this->createAlbumVideo();
            }
        }
        foreach ($list as $num => $it) {
            $set = $this->getUploadVideo(array(
                'group_id'    => $this->group,
                'album_id'    => $album,
                'link'        => $it['link'],
                'wallpost'    => 0,
                'name'        => $it['title'],
                'description' => $it['description']
            ));
            
            //dlog('setUploadVideo',$set);
            //$res = file_get_contents($set['upload_url']);
            //$res = json_decode($res,1);
            
            //$this->_postVideo();
            //pr($set);
            //$this->saveVideo($set['upload_url']);
            sleep(5);
            
            $stat = file_get_contents($set['upload_url']);
            
            if( !json_decode($stat,1)['response'] ){
                sleep(60);
                $stat = file_get_contents($set['upload_url']);
                //pr($stat,$set['upload_url']);
                dlog('stat save',[$stat,$set['upload_url']]);
            }
            
            $re[$num] = array(
                'pid' => $set['video_id'],
                'src' => 'video' . $set['owner_id'] . "_" . $set['video_id'],
            );
        }
        return $re;
    }

    function uploadPhotos($list) {
        $re = array();
        $album = $this->getAlbum();
        if (!$album) {
            $this->createAlbum();
        }
        $set = $this->getUploadPhoto();
        $files = array();
        foreach ($list as $item) {
            if ($item['path']) {
                $files[] = $item['path'];
            } else {
                $files[] = $_SERVER['DOCUMENT_ROOT'] . "/images/" . $item['location'] . "/" . $item['id'] . "." . $item['extension'];
            }
        }

        $server = $this->_postFiles($set['upload_url'], $files);
        $res = $this->savePhoto($server);

        $this->res = $res;

        foreach ($res as $it) {
            $re[] = array(
                'pid' => $it['pid'],
                'src' => $it['id'],
                'soc' => 'vk'
            );
        }

        return $re;
    }

    public function uploadPhoto($param,$album,$group=null)
    {
        $this->album = $album;
        
        $set = $this->getUploadPhoto($album,$group);
        
        $server = $this->_postFile($set['upload_url'], $param['path'],$param['title']);
        
        $server['caption'] = $param['caption'];
        unset($server['gid']);
        unset($server['aid']);
        $server['group_id'] = $group;
        $server['album_id'] = $album;
        
        $res = $this->savePhoto($server);
        
        return $res[0];
    }
    
    function returnImgFromAttach($list) {
        $mas = array();
        foreach ($list as $it) {
            $mas[] = $it['src'];
        }
        return $mas;
    }
    
    function getInfoForUrl($url, $type){
        $mas = explode("/", $url);
        $name = $mas[count($mas) - 1];
        if ($type == 'group') {
            $info = $this->getGroup($name);
            if ($info['error']) {
                $er = $this->returnError($info['error']);
                $this->error = $er['message'];
                return null;
            } else {
                return $info;
            }
        } else {
            $info = $this->getUser($name);
            return $info;
        }
    }
    
    function getUserForUrl($url, $type) {
        $mas = explode("/", $url);
        $name = $mas[count($mas) - 1];
        if ($type == 'group') {
            $info = $this->getGroup($name);
            if ($info['error']) {
                $er = $this->returnError($info['error']);
                $this->error = $er['message'];
                return null;
            } else {
                return $info['gid'];
            }
        } else {
            $info = $this->getUser($name);
            return $info['uid'];
        }
    }

    function getAvatarInfo($url) {
        $url = explode("?", $url);
        $url = $url[0];
        $name = explode("/", $url);
        $info = $this->getUser($name[count($name) - 1]);
        if(!$info){
            return null;
        }
        if($info['photo_50']){
             return array('uid' => $info['uid'], 'avatar' => $info['photo_50']);
        }else{
            $this->log = $info;
            return null;
        }
    }

    function searchUserAvatar($phone) {
        $res = $this->callMethod('account.lookupContacts', array(
            'service' => 'phone',
            'contacts' => $phone,
            'return_all' => 0,
            'fields' => 'photo_50',
            'mycontact' => "118151775"//$this->access_token
        ));
    }

    function checkAvatar($uid) {
        $info = $this->getUser($uid);
        return $info['photo_50'];
    }

    function getLinkProfile($uid) {
        $info = $this->getUser($uid);
        return "http://vk.com/" . $info['screen_name'];
    }

    function deletePost($type, $group_id, $wall_id) {

        if ($type == 'group') {
            $group_id = "-" . $group_id;
        }

        return $res = $this->callMethod('wall.delete', array(
            'owner_id' => $group_id,
            'post_id' => $wall_id
        ));
    }
    
    /* 
     * ADS - Маркетинг 
     */
    
    function getAdsAccounts(){
        $res = $this->callMethod('ads.getAccounts');
        
        if($res['error']){
            return array(
                'stat'=>0,
                'error'=>$this->returnError($res['error'],1)
            );
        }else{
            return array(
                'stat'=>1,
                'list'=>$res['response']
            );
        }
    }
    
    function getSegments($adsAccaunt = 0){
        if(!$adsAccaunt){
            $accaunts = $this->getAdsAccounts();
            if($accaunts['error'] || !$accaunts['list']){
                if(!$accaunts['list']){
                    return array('error'=>'У вас нет ни одного рекламного кабинета, <a href="https://vk.com/ads?act=office" target="_blank">создайте его</a>.');
                }
                return $accaunts;
            }else{
                $adsAccaunt = $accaunts['list'][0]['account_id'];
            }
        }
        
        $res = $this->callMethod('ads.getTargetGroups', array('account_id'=>$adsAccaunt));
        if($res['response']){
            return array(
                'stat'=>1,
                'list'=>$res['response'],
                'accountId'=>$adsAccaunt
            );
        }else{
            return array('stat'=>0,'error'=>$this->returnError($res['error'],1));
        }
    }
    /* segment = array( id,account ) */
    /* $contacts = array( emails=array,phones=array ); */
    function retargetImportContact($segmentId,$contacts,$accountId){
        
        //$segmentId = $segment['id'];
        //$accountId = $segment['account'];
        
        if(!$accountId){
            $accaunts = $this->getAdsAccounts();
            if($accaunts['error']){
                return $accaunts;
            }else{
                $accountId = $accaunts['list'][0]['account_id'];
            }
        }


        $res = $this->callMethod('ads.importTargetContacts', array(
            'target_group_id'=>$segmentId,
            'account_id'=>$accountId,
            'contacts'=>implode(",",$contacts)
        ));
        if($res['response']){
            return array(
                'stat'=>1,
                'count'=>$res['response']
            );
        }else{
            
            return array(
                'stat'=>0,
                'error'=>$this->returnError($res['error'],1)
            );
        }
    }
    
    function addSegment($name,$accountId=0){
        if(!$accountId){
            $accaunts = $this->getAdsAccounts();
            if($accaunts['error']){
                return $accaunts;
            }else{
                $accountId = $accaunts['list'][0]['account_id'];
            }
        }
        $res = $this->callMethod('ads.createTargetGroup', array(
            'account_id'=>$accountId,
            'name'=>$name
        ));
        if($res['response']){
            return array(
                'stat'=>1,
                'id'=>$res['response']['id']
            );
        }else{
            return array(
                'stat'=>0,
                'error'=>$this->returnError($res['error'],1)
            );
        }
    }
    
    public function getAttachMessage($object)
    {
        $data = array();
        foreach($object['attachments'] as $item){
            if($item['type'] == 'photo'){
                $data['photo'][] = $item['photo']['photo_604'];
            }
        }
        return $data;
    }
    
    public function getPostsGroup($id)
    {
        $res = $this->callMethod('wall.get', array(
            'owner_id' => "-".$id,
            'count'    => 100
        ));
        
        $data = $res['response']['items'];
        $count = $res['response']['count'];
        
        $n = round(($count) / 100);
        if($n>100){
            $n = 100;
        }
        for($i=1;$i<$n;$i++){
            $res = $this->callMethod('wall.get', array(
                'owner_id' => "-" . $id,
                'count' => 100,
                'offset' => $i*100
            ));
            $data = array_merge($data,$res['response']['items']);
        }
        
        return $data;
    }
    
    public function getPostsGroupForLink($link)
    {
        $group = $this->getGroup($link);
        //pr('group',$group);
        //$group['id'] = '146483010';
        $posts = $this->getPostsGroup($group['id']);
        //pr($posts);
        return $posts;
    }
    
    public function getVideo($ownerId,$ids)
    {
        $res = $this->callMethod('video.get', array(
            'owner_id' => $ownerId,
            'videos'   => implode(",",$ids)
        ));
        return $res['response'];
    }
    
    public function findGroups($name)
    {
        $res = $this->callMethod('groups.search', array(
            'q'     => $name,
            'type'  => 'group',
            'count' => 20
        ));
        return $res;
    }
}
