define([], function () {
    require.config({
    paths: {
        'async': '../addons/example/js/async',
        'BMap': ['//api.map.baidu.com/api?v=2.0&ak=mXijumfojHnAaN2VxpBGoqHM'],
    },
    shim: {
        'BMap': {
            deps: ['jquery'],
            exports: 'BMap'
        }
    }
});

require.config({
    paths: {
        'bootstrap-markdown': '../addons/markdown/js/bootstrap-markdown.min',
        'hyperdown': '../addons/markdown/js/hyperdown.min',
        'pasteupload': '../addons/markdown/js/jquery.pasteupload'
    },
    shim: {
        'bootstrap-markdown': {
            deps: [
                'jquery',
                'css!../addons/markdown/css/bootstrap-markdown.css'
            ],
            exports: '$.fn.markdown'
        },
        'pasteupload': {
            deps: [
                'jquery',
            ],
            exports: '$.fn.pasteUploadImage'
        }
    }
});
require(['form', 'upload'], function (Form, Upload) {
    var _bindevent = Form.events.bindevent;
    Form.events.bindevent = function (form) {
        _bindevent.apply(this, [form]);
        try {
            if ($(".editor", form).size() > 0) {
                require(['bootstrap-markdown', 'hyperdown', 'pasteupload'], function (undefined, undefined, undefined) {
                    $.fn.markdown.messages.zh={Bold:"粗体",Italic:"斜体",Heading:"标题","URL/Link":"链接",Image:"图片",List:"列表","Unordered List":"无序列表","Ordered List":"有序列表",Code:"代码",Quote:"引用",Preview:"预览","strong text":"粗体","emphasized text":"强调","heading text":"标题","enter link description here":"输入链接说明","Insert Hyperlink":"URL地址","enter image description here":"输入图片说明","Insert Image Hyperlink":"图片URL地址","enter image title here":"在这里输入图片标题","list text here":"这里是列表文本","code text here":"这里输入代码","quote here":"这里输入引用文本"};
                    var parser = new HyperDown();
                    window.marked = function (text) {
                        return parser.makeHtml(text);
                    };
                    //粘贴上传图片
                    $.fn.pasteUploadImage.defaults = $.extend(true, $.fn.pasteUploadImage.defaults, {
                        fileName: "file",
                        appendMimetype: false,
                        ajaxOptions: {
                            url: Fast.api.fixurl(Config.upload.uploadurl),
                            beforeSend: function (jqXHR, settings) {
                                $.each(Config.upload.multipart, function(i,j ){
                                    settings.data.append(i, j);
                                });
                                return true;
                            }
                        },
                        success: function (data, filename, file) {
                            var ret = Upload.events.onUploadResponse(data);
                            $(this).insertToTextArea(filename, Config.upload.cdnurl + data.data.url);
                            return false;
                        },
                        error: function (data, filename, file) {
                            console.log(data, filename, file);
                        }
                    });
                    //手动选择上传图片
                    $(document).on("change", "#selectimage", function () {
                        $.each($(this)[0].files, function (i, file) {
                            $("").uploadFile(file, file.name);
                        });
                        //$("#message").pasteUploadImage();
                    });
                    $(".editor", form).each(function () {
                        $(this).markdown({
                            resize: 'vertical',
                            language: 'zh',
                            iconlibrary: 'fa',
                            autofocus: false,
                            savable: false
                        });
                        $(this).pasteUploadImage();
                    });
                });
            }
        } catch (e) {

        }

    };
});

require.config({
    paths: {
        'simditor': '../addons/simditor/js/simditor.min',
    },
    shim: {
        'simditor': [
            'css!../addons/simditor/css/simditor.min.css'
        ]
    }
});

if ($(".editor").size() > 0) {
    //修改上传的接口调用
    require(['upload', 'simditor'], function (Upload, Simditor) {
        var editor, mobileToolbar, toolbar;
        Simditor.locale = 'zh-CN';
        Simditor.list = {};
        toolbar = ['title', 'bold', 'italic', 'underline', 'strikethrough', 'fontScale', 'color', '|', 'ol', 'ul', 'blockquote', 'code', 'table', '|', 'link', 'image', 'hr', '|', 'indent', 'outdent', 'alignment'];
        mobileToolbar = ["bold", "underline", "strikethrough", "color", "ul", "ol"];
        $(".editor").each(function () {
            var id = $(this).attr("id");
            editor = new Simditor({
                textarea: this,
                toolbarFloat: false,
                toolbar: toolbar,
                pasteImage: true,
                defaultImage: Fast.api.cdnurl('/assets/addons/simditor/images/image.png'),
                upload: {url: '/'}
            });
            editor.uploader.on('beforeupload', function (e, file) {
                Upload.api.send(file.obj, function (data) {
                    var url = Fast.api.cdnurl(data.url);
                    editor.uploader.trigger("uploadsuccess", [file, {success: true, file_path: url}]);
                });
                return false;
            });
            editor.on("blur", function () {
                this.textarea.trigger("blur");
            });
            Simditor.list[id] = editor;
        });
    });
}
require.config({
    paths: {
        'summernote': '../addons/summernote/lang/summernote-zh-CN.min'
    },
    shim:{
        'summernote': ['../addons/summernote/js/summernote.min', 'css!../addons/summernote/css/summernote.css'],
    }
});
require(['form', 'upload'], function (Form, Upload) {
    var _bindevent = Form.events.bindevent;
    Form.events.bindevent = function (form) {
        _bindevent.apply(this, [form]);
        try {
            //绑定summernote事件
            if ($(".summernote,.editor", form).size() > 0) {
                require(['summernote'], function () {
                    $(".summernote,.editor", form).summernote({
                        height: 250,
                        lang: 'zh-CN',
                        fontNames: [
                            'Arial', 'Arial Black', 'Serif', 'Sans', 'Courier',
                            'Courier New', 'Comic Sans MS', 'Helvetica', 'Impact', 'Lucida Grande',
                            "Open Sans", "Hiragino Sans GB", "Microsoft YaHei",
                            '微软雅黑', '宋体', '黑体', '仿宋', '楷体', '幼圆',
                        ],
                        fontNamesIgnoreCheck: [
                            "Open Sans", "Microsoft YaHei",
                            '微软雅黑', '宋体', '黑体', '仿宋', '楷体', '幼圆'
                        ],
                        toolbar: [
                            ['style', ['style', 'undo', 'redo']],
                            ['font', ['bold', 'underline', 'strikethrough', 'clear']],
                            ['fontname', ['color', 'fontname', 'fontsize']],
                            ['para', ['ul', 'ol', 'paragraph', 'height']],
                            ['table', ['table', 'hr']],
                            ['insert', ['link', 'picture', 'video']],
                            ['view', ['fullscreen', 'codeview', 'help']]
                        ],
                        dialogsInBody: true,
                        callbacks: {
                            onChange: function (contents) {
                                $(this).val(contents);
                                $(this).trigger('change');
                            },
                            onInit: function () {
                            },
                            onImageUpload: function (files) {
                                var that = this;
                                //依次上传图片
                                for (var i = 0; i < files.length; i++) {
                                    Upload.api.send(files[i], function (data) {
                                        var url = Fast.api.cdnurl(data.url);
                                        $(that).summernote("insertImage", url, 'filename');
                                    });
                                }
                            }
                        }
                    });
                });
            }
        } catch (e) {

        }

    };
});

});