import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom/model';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/code';
import 'tinymce/plugins/table';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/quickbars';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/autoresize';

document.addEventListener('DOMContentLoaded', () => {
    if (! document.querySelector('.js-richtext')) {
        return;
    }

    tinymce.remove('.js-richtext');

    tinymce.init({
        selector: '.js-richtext',
        base_url: '/tinymce',
        suffix: '.min',
        plugins: 'link lists code table fullscreen advlist quickbars wordcount autoresize',
        toolbar: [
            'undo redo | removeformat | styles | fontselect fontsizeselect',
            'bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify',
            'bullist numlist outdent indent | link table | fullscreen code',
        ].join(' | '),
        menubar: 'file edit view format table tools',
        branding: false,
        promotion: false,
        height: 420,
        content_style: 'body { font-family: inherit; font-size: 14px; }',
        toolbar_mode: 'sliding',
    });
});
