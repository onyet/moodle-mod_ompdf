define(['core/ajax'], function(Ajax) {
    'use strict';

    return {
        call: function(args) {
            return Ajax.call([{
                methodname: 'mod_ompdf_execute_action',
                args: args
            }])[0];
        }
    };
});
