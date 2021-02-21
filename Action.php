<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class TpTuchuang_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
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
                $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $fileName)) {
                    $data = $this->uploadAlibaba($fileName);
                    @unlink($fileName);
                    $data = json_decode($data);
                    if (isset($data->url)) {
                        $this->msg(['code' => 0, 'msg' => $data->url]);
                    } else {
                        $this->msg(['code' => 1, 'msg' => '上传失败']);
                    }
                } else {
                    $this->msg(['code' => 1, 'msg' => '移动文件失败']);
                }
            } else {
                $this->msg(['code' => 1, 'msg' => '上传数据有误']);
            }
        } catch (\Throwable $th) {
            $this->msg(['code' => 1, 'msg' =>  $th->getMessage()]);
        }
    }

    public function uploadAlibaba($file)
    {
        $post = [
            'scene' => 'aeMessageCenterImageRule',
            'name' => $file,
            'file' => new \CURLFile(realpath($file)),
        ];
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
