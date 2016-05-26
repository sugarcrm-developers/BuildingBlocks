({
    /**
     * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
     */

    events: {
        'click [name=cancel_button]': 'closeDrawer'
    },

    initialize: function(view) {
        this._super('initialize', arguments);
        var ctx = this.context;
        this.title = ctx.get('title');
    },

    closeDrawer: function(){
        app.drawer.close();
    }


});
