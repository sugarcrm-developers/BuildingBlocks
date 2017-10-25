({
    /**
     * Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license. 
     */
    plugins: ['Dashlet'],

    /**
     * Record ID that is in context
     */
    record: undefined,

    /**
     * Module used 
     */
    moduleName: undefined,

    /**
     * Base URL for iFrame (retrieved via config)
     */
    url: undefined,

    /**
     * Height for iFrame element
     */
    frameHeight: undefined,

    /**
     * Overriding initDashlet to setup values needed to render our contextual dashlet
     */
    initDashlet: function(view) {
        var ctx = this.context;
        var model = ctx.get("model");
        if (!_.isEmpty(model)) {
            this.record = model.get("id");
        }
        this.moduleName = ctx.get("module");
        this.url = this.settings.get("url");
        this.frameHeight = this.settings.get("frameHeight");
    }

});
