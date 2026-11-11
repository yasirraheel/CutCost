{{-- Rich text editor for HTML fields in the admin. CKEditor 5 Classic, version 44.0.0, vendored locally under public/vendor/ckeditor5 because the CDN does not load in the browser in this environment (the shell can reach it, the headless browser cannot), so the editor must work with no external request. Version 44 ships as an ES module, so it is loaded with type="module" and the editor class plus its plugins are imported from the vendored file. The vendored file is the npm self-hosted build, so the GPL license key is valid for it (the CDN build is marked as the Cloud channel and rejects the GPL key). The editor is composed from the bundle's plugins because the npm build's ClassicEditor ships with no plugins built in. Admin pages only, the public site never loads this. The edited HTML stays visible as rendered content while typing, and the editor writes its data back into the original textarea on submit, so saving works exactly as before. If the editor fails to initialise, the plain textarea stays usable and the form still submits normally. Timing matters: the captured page mounts Vue on #app and Vue re-renders the in-DOM template, replacing every node inside #app with a fresh clone. An editor created before that mount binds to nodes Vue then detaches, so typing never reaches the editor model and saving posts the original value. The editors are therefore created only once the DOM has been still for a short while (a MutationObserver watches for node additions and removals and the editors are created after 400 ms with no change), which holds no matter when Vue mounts, with a 5 second fallback so they always appear. --}}
@once
    <link rel="stylesheet" href="{{ asset('vendor/ckeditor5/ckeditor5.css') }}">
    <script type="module">
        var ckeditorModule = null;
        var domQuiet = false;
        var editorsStarted = false;
        function initRichTextEditors() {
            if (editorsStarted || !ckeditorModule || !domQuiet) {
                return;
            }
            editorsStarted = true;
            var m = ckeditorModule;
            var textareas = document.querySelectorAll('textarea.rich-text-editor');
            if (!textareas.length) {
                return;
            }
            textareas.forEach(function (textarea) {
                if (textarea.dataset.richTextDone) {
                    return;
                }
                textarea.dataset.richTextDone = '1';
                m.ClassicEditor.create(textarea, {
                    licenseKey: 'GPL',
                    plugins: [
                        m.Essentials, m.Paragraph, m.Bold, m.Italic, m.Underline, m.Strikethrough,
                        m.Link, m.AutoLink, m.DocumentList, m.BlockQuote, m.Heading, m.Alignment,
                        m.SourceEditing, m.GeneralHtmlSupport, m.RemoveFormat, m.FontSize,
                        m.FontColor, m.FontBackgroundColor, m.Highlight
                    ],
                    toolbar: {
                        items: [
                            'undo', 'redo', '|', 'heading', '|',
                            'bold', 'italic', 'underline', 'strikethrough', '|',
                            'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
                            'fontSize', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                            'alignment', '|', 'removeFormat', '|', 'sourceEditing'
                        ]
                    },
                    htmlSupport: {
                        allow: [{ name: /.*/, attributes: true, classes: true, styles: true }]
                    }
                }).then(function (editor) {
                    var form = textarea.closest('form');
                    if (form) {
                        form.addEventListener('submit', function () {
                            // Write the editor data straight into this field so
                            // the save posts the edited HTML, not the original.
                            textarea.value = editor.getData();
                        });
                    }
                }).catch(function (error) {
                    if (window.console && window.console.error) {
                        window.console.error(error);
                    }
                });
            });
        }
        // The observer is attached before the editor bundle is loaded, so
        // it sees Vue's mount even if the bundle is slow to arrive. Node
        // additions and removals reset the quiet timer; class and style
        // changes do not, since they cannot detach the textareas.
        var quietTimer = null;
        var observer = null;
        function markDomQuiet() {
            domQuiet = true;
            if (observer) {
                observer.disconnect();
            }
            initRichTextEditors();
        }
        function armQuietTimer() {
            if (quietTimer) {
                clearTimeout(quietTimer);
            }
            quietTimer = setTimeout(markDomQuiet, 400);
        }
        if (typeof MutationObserver !== 'undefined' && document.documentElement) {
            observer = new MutationObserver(function () {
                armQuietTimer();
            });
            observer.observe(document.documentElement, { childList: true, subtree: true });
        }
        armQuietTimer();
        setTimeout(markDomQuiet, 5000);
        import('{{ asset('vendor/ckeditor5/ckeditor5.js') }}').then(function (m) {
            ckeditorModule = m;
            initRichTextEditors();
        }).catch(function (error) {
            if (window.console && window.console.error) {
                window.console.error(error);
            }
        });
    </script>
@endonce
