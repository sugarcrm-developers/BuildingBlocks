(function(app){
    /**
     * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
     */
    //Run callback when Sidecar metadata is fully initialized
    app.events.on('app:sync:complete', function(){

        //When a record layout is loaded...
        app.router.on('route:list', function(module){
            //AND the module is Opportunities...
            if(module === 'Opportunities') {
                //AND the 'button:create_quote:click' event occurs on the current Context
                app.controller.context.on('button:custom_button:click', function(){
                    //On click of custom button, go to iframe module route
                    app.router.navigate('test_Test', {trigger: true});
                }, this);
            }
        });

    });
})(SUGAR.App);
