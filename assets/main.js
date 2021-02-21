$(document).ready(function () {
    $('body').append('<style>.dropzone{display:flex;flex-wrap:wrap;justify-content:center;min-height:0;border:none;padding:0}.dz-details{display:none}.dropzone .dz-default{display:none}#tab-tuchuang{margin:1em 0;border:1px dashed #d9d9d6}.dropzone .dz-preview{padding:0 8px;margin:0;min-height:0}.tc-upload-btn{padding:15px;background-color:#fff;color:#467b96;font-size:.92857em;text-align:center;cursor:pointer}.tc-list{width:100%;list-style:none;margin:0 10px;padding:0;max-height:450px;overflow:auto;word-break:break-all}.tc-list li{display:flex;padding:8px 0;border-top:1px dashed #d9d9d6;align-items:center;position:relative}.tc-list .dz-image{display:block;max-width:100%;width:100%!important;padding:0 4px;height:auto!important}.tc-list li img{width:32px;height:32px;min-width:32px;min-height:32px}.tc-list .dz-image,.tc-list li{overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.tc-list a,.tc-list a i{cursor:pointer!important}</style>');

    $('.typecho-option-tabs').append('<li class="w-50 tc-tab-btn"><a href="#tab-tuchuang">图床</a></li>');

    $('#edit-secondary').append('<div id="tab-tuchuang" class="tab-content hidden"><div class="tc-upload-btn">选择图片上传</div><div class="img-dropzone dropzone"></div></div>');

    $('#edit-secondary .typecho-option-tabs .tc-tab-btn').click(function () {
        $('#edit-secondary .typecho-option-tabs li').removeClass('active');
        $(this).addClass('active');
        $(this).parents('#edit-secondary').find('.tab-content').addClass('hidden');

        var selected_tab = $(this).find('a').attr('href'),
            selected_el = $(selected_tab).removeClass('hidden');

        return false;
    });

    Dropzone.autoDiscover = false;
    var dropzone = new Dropzone('.img-dropzone', {
        url: window.TpTuchuang,
        type: 'POST',
        acceptedFiles: "image/jpg,image/jpeg,image/png,image/gif",
        addRemoveLinks: false,
        dictCancelUpload: '取消',
        dictRemoveFile: '删除',
        previewTemplate: '<div class="tc-list dz-preview"><li><div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div><img data-dz-thumbnail /><a class="dz-image" href="#" title="点击插入图片" data-dz-name></a><div class="info"><a class="delete" href="#" title="删除" data-dz-remove><i class="i-delete"></i></a></div></li></div>',
        success: function (file, response) {
            response = JSON.parse(response);
            if (response.code == 1) {
                alert(response.msg);
                this.removeFile(file);
            } else {
                $('.tc-list').unbind('click').on('click', '.dz-image', function (e) {
                    var i = $('.tc-list .dz-image').index($(this));
                    try {
                        var json = JSON.parse(dropzone.files[i].xhr.response);
                        var text = "![](" + json.msg + ")";
                        if (typeof postEditormd !== "undefined") {
                            postEditormd.insertValue(text);
                            postEditormd.focus();
                        } else {
                            $('textarea[name="text"]').focus().insert({ "text": text });
                        }
                    } catch (e) { }
                    return false;
                });
            }
        }
    });

    $('.tc-upload-btn').click(function () {
        dropzone.clickableElements[0].click()
    });

    (function ($) {
        $.fn.extend({
            "insert": function (value) {
                //默认参数
                value = $.extend({
                    "text": ""
                }, value);
                var dthis = $(this)[0]; //将jQuery对象转换为DOM元素
                //IE下
                if (document.selection) {
                    $(dthis).focus(); //输入元素textara获取焦点
                    var fus = document.selection.createRange();//获取光标位置
                    fus.text = value.text; //在光标位置插入值
                    $(dthis).focus(); ///输入元素textara获取焦点
                }
                //火狐下标准
                else if (dthis.selectionStart || dthis.selectionStart == '0') {
                    var start = dthis.selectionStart; 　　 //获取焦点前坐标
                    var end = dthis.selectionEnd; 　　//获取焦点后坐标
                    //以下这句，应该是在焦点之前，和焦点之后的位置，中间插入我们传入的值 .然后把这个得到的新值，赋给文本框
                    dthis.value = dthis.value.substring(0, start) + value.text + dthis.value.substring(end, dthis.value.length);
                }
                //在输入元素textara没有定位光标的情况
                else {
                    this.value += value.text; this.focus();
                };
                return $(this);
            }
        })
    })(jQuery)

});
