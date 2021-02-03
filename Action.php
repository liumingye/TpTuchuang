<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

?>

<?php

include_once 'Option.php';

class TpTuchuang_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{

    public function action()
    {
        switch ($_GET['action']) {
            case 'upload':
                $option = new Option;
                if (isset($_GET['key']) && $_GET['key'] == md5($option->key)) {
                    $this->upload($_FILES['file']);
                }
                break;

        }
    }

    public function upload($file)
    {
        if (is_uploaded_file($file['tmp_name'])) {
            $arr = pathinfo($file['name']);
            $ext_suffix = $arr['extension'];
            $allow_suffix = array('jpg', 'gif', 'jpeg', 'png');
            if (!in_array($ext_suffix, $allow_suffix)) {
                $this->msg(['code' => 1, 'msg' => '上传格式不支持']);
            }
            $new_filename = time() . rand(100, 1000) . '.' . $ext_suffix;
            if (move_uploaded_file($file['tmp_name'], $new_filename)) {
                $data = $this->upload2('https://kfupload.alibaba.com/mupload', $new_filename);
                $pattern = '/"url":"(.*?)"/';
                preg_match($pattern, $data, $match);
                @unlink($new_filename);
                if ($match && $match[1] != '') {
                    $this->msg(['code' => 0, 'msg' => $match[1]]);
                } else {
                    $this->msg(['code' => 1, 'msg' => '上传失败']);
                }
            } else {
                $this->msg(['code' => 1, 'msg' => '上传数据有误']);
            }
        } else {
            $this->msg(['code' => 1, 'msg' => '上传数据有误']);
        }
    }

    public function upload2($url, $file)
    {
        return $this->get_url($url, [
            'scene' => 'aeMessageCenterImageRule',
            'name' => $file,
            'file' => new \CURLFile(realpath($file)),
        ]);
    }

    public function get_url($url, $post)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'iAliexpress/6.22.1 (iPhone; iOS 12.1.2; Scale/2.00)');
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
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
