<?php
/**
 * 图床
 *
 * @package TpTuchuang
 * @author 刘明野
 * @version 1.0.0
 * @link https://www.liumingye.cn
 */

include_once 'Option.php';

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
    {}

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
        $option = new Option;
        ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.7.1/dist/dropzone.min.css">
        <script src="https://cdn.jsdelivr.net/npm/dropzone@5.7.1/dist/dropzone.min.js"></script>
        <script>var TpTuchuang = '<?php $options->index('/TpTuchuang?action=upload&key=' . md5($option->key));?>'</script>
        <script src="<?php echo $path; ?>/assets/main.js"></script>
        <?php
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {}

    /**
     * 过滤more标记
     *
     * @access public
     * @return string
     */
    /*public static function filter_content($content, Widget_Archive $archive)
{
// 首页和分类、归档页
if ($archive->is('index') || $archive->is('archive')) {
if (strpos($content, '<div class="ke-more-excerpt"></div>')) {
// 如果含有摘要标签
$archive->text = str_replace('<div class="ke-more-excerpt"></div>', '<!--more-->', $content);
$plugin_options = Typecho_Widget::widget('Widget_Options')->plugin('KEditor');
return $archive->excerpt . "<p class=\"more\"><a href=\"{$archive->permalink}\" title=\"{$archive->title}\">{$plugin_options->moreTitle}</a></p>";
}
return $content;
}
return str_replace(array('<!--more-->', '<div class="ke-more-excerpt"></div>'), '', $content);
}*/

}
