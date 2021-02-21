<?php

/**
 * 图床
 *
 * @package TpTuchuang
 * @author 刘明野
 * @version 1.0.0
 * @link https://www.liumingye.cn
 */

class TpTuchuang_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // hook
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('TpTuchuang_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('TpTuchuang_Plugin', 'render');
        // 添加路由
        Helper::addRoute("route_TpTuchuang", "/TpTuchuang", "TpTuchuang_Action", 'action');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute('route_TpTuchuang');
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        $options = Helper::options();
        $path = Typecho_Common::url('TpTuchuang', $options->pluginUrl);
        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.7.1/dist/dropzone.min.css">' .
            '<script src="https://cdn.jsdelivr.net/npm/dropzone@5.7.1/dist/dropzone.min.js"></script>' .
            '<script>var TpTuchuang = ';
        $options->index('/TpTuchuang?action=upload');
        echo ';</script>' .
            '<script src="' . $path . '/assets/main.js?v=1.2"></script>';
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
}
