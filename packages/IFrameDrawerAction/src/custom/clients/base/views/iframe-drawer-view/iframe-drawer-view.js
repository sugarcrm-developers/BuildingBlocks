({
    /**
     * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
     */

    // Display iframe inline
    // tagName: 'span',

    initialize: function(view) {
        this._super('initialize', arguments);
        var ctx = this.context;
        this.url = ctx.get('url');
    }

});
