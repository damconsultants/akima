var config = {
    paths: {
        'bynderjs': 'DamConsultants_Akima/js/bynder',
        'select2': 'DamConsultants_Akima/js/select2'
    },
    shim: {
        'bynderjs': {
            deps: ['jquery']
        },
        'select2': {
            deps: ['jquery']
        },
    },
    map: {
        '*': {
            'Magento_PageBuilder/template/form/element/html-code.html': 'DamConsultants_Akima/template/form/element/html-code.html',
            'Magento_PageBuilder/js/form/element/html-code': 'DamConsultants_Akima/js/form/element/html-code',
            'Magento_PageBuilder/template/content-type/video/default/master.html': 'DamConsultants_Akima/template/content-type/video/default/master.html',
            'Magento_PageBuilder/template/content-type/video/default/preview.html': 'DamConsultants_Akima/template/content-type/video/default/preview.html',
        },
    }
};