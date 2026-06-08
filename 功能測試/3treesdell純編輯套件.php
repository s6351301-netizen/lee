<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jodit 富文本編輯器獨立範例</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.5/build/jodit.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/jodit@3.24.5/build/jodit.min.js"></script>
    
    <style>
        /* 基礎編輯器外觀與對話視窗的自訂樣式 */
        .jodit-container { background: #f0fdf4 !important; color: #000000 !important; border: 1px solid #cbd5e1 !important; }
        .jodit-toolbar__box { background: #e0f2fe !important; border-bottom: 1px solid #bae6fd !important; }
        .jodit-toolbar-button__icon { fill: #1e293b !important; }
        .jodit-status-bar { background: #e0f2fe !important; color: #334155 !important; border-top: 1px solid #bae6fd !important; }
        .jodit-wysiwyg { color: #000000; }
        .jodit-wysiwyg a { text-decoration: underline !important; }
        
        /* 原始碼模式樣式修正 */
        .jodit-source__textarea, .jodit-src, .jodit-source, .jodit-source__textarea textarea { color: #000000 !important; background-color: #ffffff !important; background: #ffffff !important; text-shadow: none !important; }
        .jodit-container .ace_editor { background-color: #ffffff !important; }
        .jodit-container .ace_editor .ace_scroller { background-color: #ffffff !important; }
        .jodit-container .ace_editor * { text-shadow: none !important; background-color: transparent !important; }
        .jodit-container .ace_editor .ace_text-layer { color: #000000 !important; }

        /* 彈出視窗與下拉選單樣式修正 */
        .jodit-popup, .jodit-popup__content, .jodit-popup__container, .jodit-dialog, .jodit-dialog__box, .jodit-dialog__content, .jodit-dialog__header, .jodit-dialog__footer, .jodit-toolbar-list, .jodit-properties, .jodit-ui-form { background-color: #e0f2fe !important; color: #0f172a !important; border-color: #7dd3fc !important; }
        .jodit-popup__content *, .jodit-toolbar-list *, .jodit-toolbar-button, .jodit-toolbar-list .jodit-toolbar-button__text { color: #000000 !important; }
        .jodit-popup__content .jodit-colorpicker * { color: inherit !important; }
        .jodit-nav-button:hover, .jodit-toolbar-button:hover, .jodit-popup__content .jodit-toolbar-button:hover { background-color: #bae6fd !important; }
        .jodit-dialog input, .jodit-dialog select, .jodit-dialog textarea, .jodit-popup input, .jodit-popup select, .jodit-popup textarea, .jodit-ui-form input, .jodit-ui-form select, .jodit-ui-form textarea, .jproperties input, .jodit-properties select, .jodit-properties textarea { color: #000000 !important; background-color: #f0f9ff !important; border: 1px solid #7dd3fc !important; }
        .jodit-ui-form label, .jodit-ui-label, .jodit-dialog__content label, .jodit-dialog__content .jodit-ui-label { color: #1e3a8a !important; font-weight: bold !important; }
        
        /* 容器基本間距 */
        .editor-wrapper { max-width: 1000px; margin: 30px auto; padding: 0 15px; }
    </style>
</head>
<body>

    <div class="editor-wrapper">
        <textarea id="joditEditorTarget"></textarea>
    </div>

    <script>
        // 4. 定義工具列按鈕配置
        const fullFreeButtons = [
            'source', '|', 'bold', 'strikethrough', 'underline', 'italic', '|',
            'superscript', 'subscript', '|', 'ul', 'ol', '|',
            'outdent', 'indent', '|', 'font', 'fontsize', 'brush', 'paragraph', '|',
            'image', 'file', 'video', 'table', 'link', '|', 'align', 'undo', 'redo', '|',
            'hr', 'eraser', 'copyformat', '|', 'symbol', 'print', 'about'
        ];

        // 5. 初始化 Jodit 編輯器
        const joditEditor = new Jodit('#joditEditorTarget', {
            buttons: fullFreeButtons, 
            buttonsMD: fullFreeButtons, 
            buttonsSM: fullFreeButtons, 
            buttonsXS: fullFreeButtons, 
            disablePlugins: [], 
            height: 450, 
            language: 'zh_tw', 
            style: { color: '#000000' },
            controls: {
                font: {
                    list: {
                        'Microsoft JhengHei, sans-serif': '微軟正黑體',
                        'PMingLiU, serif': '新細明體',
                        'DFKai-SB, serif': '標楷體',
                        'PingFang TC, sans-serif': '蘋方體 (Mac)',
                        'Arial, Helvetica, sans-serif': 'Arial (無襯線體)',
                        'Times New Roman, Times, serif': 'Times New Roman (襯線體)'
                    }
                },
                file: {
                    text: '文件上傳 (檔案請保持在 500M 以內)',
                    tooltip: '上傳任意格式文件'
                }
            },
            // 6. 檔案與圖片上傳設定
            uploader: {
                url: '?action=upload_icon', // 請根據實際接收上傳的 PHP 路由調整此 URL
                format: 'json',
                path: 'files',
                multiple: true,
                isSuccess: function (resp) { return resp.success === true; },
                getMessage: function (resp) { return resp.error; },
                process: function (resp) {
                    return { files: resp.files || [], error: resp.error, msg: resp.msg };
                },
                defaultHandlerSuccess: function (data, resp) {
                    if (data.files && data.files.length) {
                        data.files.forEach(fileUrl => {
                            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(fileUrl);
                            
                            let displayFileName = fileUrl.substring(fileUrl.lastIndexOf('/') + 1);
                            try { displayFileName = decodeURIComponent(displayFileName); } catch(e) {}
                            
                            // 格式化檔名（去除時間戳記等前綴）
                            if (displayFileName.includes('_')) {
                                const parts = displayFileName.split('_');
                                if (parts.length >= 4 && /^\d{8}$/.test(parts[0])) {
                                    displayFileName = parts.slice(3).join('_');
                                }
                            }

                            if (isImage) {
                                this.s.insertImage(fileUrl, null, 200); 
                            } else {
                                this.s.insertHTML(`<a href="${fileUrl}" target="_blank" style="color: #0284c7; text-decoration: underline;">📎 下載附件: ${displayFileName}</a>&nbsp;`);
                            }
                        });
                    }
                },
                defaultHandlerError: function (err) { this.alerts.error(err.getMessage()); }
            }
        });
    </script>
</body>
</html>