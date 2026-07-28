define(['core/log'], function(log) {
    'use strict';

    return {
        init: function(treeId, expandAll) {
            var container = document.getElementById(treeId);
            if (!container) {
                return;
            }

            var detailsElements = container.querySelectorAll('details.ompdf-folder-details');
            detailsElements.forEach(function(details) {
                if (expandAll) {
                    details.setAttribute('open', 'open');
                }
            });
        }
    };
});
