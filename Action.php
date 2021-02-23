<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

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
                $path = $path . '/' . $fileName;

                if (isset($file['tmp_name'])) {
                    //移动上传文件
                    if (!@move_uploaded_file($file['tmp_name'], $path)) {
                        $this->msg(['code' => 1, 'msg' => '移动上传文件失败']);
                    }
                } else if (isset($file['bytes'])) {
                    //直接写入文件
                    if (!file_put_contents($path, $file['bytes'])) {
                        $this->msg(['code' => 1, 'msg' => '直接写入文件失败']);
                    }
                } else {
                    $this->msg(['code' => 1, 'msg' => '上传失败']);
                    return false;
                }

                // 开始上传
                $data = $this->uploadAlibaba($path);

                // 删除文件
                @unlink($path);

                $data = json_decode($data);
                if (isset($data->url)) {
                    $this->msg(['code' => 0, 'msg' => $data->url]);
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
    public function uploadAlibaba($file)
    {
        $post = [
            'scene' => 'aeMessageCenterImageRule',
            'name' => 'player.jpg'
        ];
        if (class_exists('CURLFile')) {
            $post['file'] = new \CURLFile(realpath($file));
        } else {
            $post['file'] = '@' . realpath($file);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, 'https://kfupload.alibaba.com/mupload');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'iAliexpress/6.22.1 (iPhone; iOS 12.1.2; Scale/2.00)');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        if (curl_exec($ch) === false) {
            echo 'Curl error: ' . curl_error($ch);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function msg($data)
    {
        exit(json_encode($data));
    }
}
