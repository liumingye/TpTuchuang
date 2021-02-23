<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

error_reporting(E_ALL);

class TpTuchuang_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
    //上传文件目录
    const UPLOAD_DIR = '/usr/uploads';

    /**
     * 判断用户是否登录
     * @return bool
     * @throws Typecho_Widget_Exception
     */
    public static function check_login()
    {
        //http与https相互独立
        return (Typecho_Widget::widget('Widget_User')->hasLogin());
    }

    /**
     * 创建上传路径
     *
     * @access private
     * @param string $path 路径
     * @return boolean
     */
    private static function makeUploadDir($path)
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last)) {
            return false;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::makeUploadDir($path);
    }

    /**
     * 获取安全的文件名 
     * 
     * @param string $name 
     * @static
     * @access private
     * @return string
     */
    private static function getSafeName(&$name)
    {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);

        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    public function action()
    {
        if (!$this->check_login()) return false;
        switch ($_GET['action']) {
            case 'upload':
                $this->upload($_FILES['file']);
                break;
        }
    }
    /**
     * 检查文件名
     *
     * @access private
     * @param string $ext 扩展名
     * @return boolean
     */
    public function checkFileType($ext)
    {
        return in_array($ext, ['jpg', 'gif', 'jpeg', 'png']);
    }

    public function upload($file)
    {
        try {
            if (is_uploaded_file($file['tmp_name'])) {
                $ext = $this->getSafeName($file['name']);
                if (!$this->checkFileType($ext)) {
                    $this->msg(['code' => 1, 'msg' => '上传格式不支持']);
                }

                $path = Typecho_Common::url(
                    defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                    defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
                );

                //创建上传目录
                if (!is_dir($path)) {
                    if (!self::makeUploadDir($path)) {
                        $this->msg(['code' => 1, 'msg' => '创建上传目录失败']);
                    }
                }

                //获取文件名
                $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
                $fullpath = $path . '/' . $fileName;

                if (isset($file['tmp_name'])) {
                    //移动上传文件
                    if (!@move_uploaded_file($file['tmp_name'], $fullpath)) {
                        $this->msg(['code' => 1, 'msg' => '移动上传文件失败']);
                    }
                } else if (isset($file['bytes'])) {
                    //直接写入文件
                    if (!file_put_contents($fullpath, $file['bytes'])) {
                        $this->msg(['code' => 1, 'msg' => '直接写入文件失败']);
                    }
                } else {
                    $this->msg(['code' => 1, 'msg' => '上传失败']);
                    return false;
                }

                // 开始上传
                $data = $this->uploadAlibaba($fullpath);
                // $data = $this->uploadSina($fullpath);

                // 删除文件
                @unlink($fullpath);

                if (isset($data['url'])) {
                    $this->msg(['code' => 0, 'msg' => $data['url']]);
                } else {
                    $this->msg(['code' => 1, 'msg' => '上传失败']);
                }
            } else {
                $this->msg(['code' => 1, 'msg' => '上传数据有误']);
            }
        } catch (\Throwable $th) {
            $this->msg(['code' => 1, 'msg' =>  $th->getMessage()]);
        }
    }

    /**
     * 上传阿里巴巴
     *
     * @param array $file 上传的文件
     * @return mixed
     */
    public function uploadAlibaba($path)
    {
        $post = [
            'scene' => 'aeMessageCenterImageRule',
            'name' => 'player.jpg',
            'file' =>  '@' . realpath($path) // 兼容php5.4
        ];
        if (class_exists('CURLFile')) {
            $post['file'] = new \CURLFile(realpath($path));
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://kfupload.alibaba.com/mupload');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $ip = $this->GetRandIp();
        $httpheader[] = 'X-Real-IP:' . $ip;
        $httpheader[] = 'X-Forwarded-For:' . $ip;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_USERAGENT, 'iAliexpress/6.22.1 (iPhone; iOS 12.1.2; Scale/2.00)');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        if (curl_exec($ch) === false) {
            echo 'Curl error: ' . curl_error($ch);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }

    // 新浪图床
    public function uploadSina($file)
    {
        $url = 'https://iask.sina.com.cn/question/ajax/fileupload';
        $data = ['wenwoImage' => new \CURLFile($file)];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $ip = $this->GetRandIp();
        $httpheader[] = 'CLIENT-IP:' . $ip;
        $httpheader[] = 'X-FORWARDED-FOR:' . $ip;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_REFERER, "https://iask.sina.com.cn/ask.html?q=");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($html, true);
        if (isset($json['id'])) {
            return ['url' => "https://pic.iask.cn/fimg/{$json['id']}.jpg"];
        }
        return false;
    }

    public function GetRandIp()
    {
        $ip_long = array(
            array('607649792', '608174079'), // 36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), // 61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), // 106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), // 121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), // 123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), // 139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), // 171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), // 182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), // 210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), // 222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        return long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    }

    public function msg($data)
    {
        exit(json_encode($data));
    }
}
