(function(app){
    /**
     * Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
     */
    //Run callback when Sidecar metadata is fully initialized
    app.events.on('app:sync:complete', function(){

        //When a record layout is loaded...
        app.router.on('route:record', function(module){
            //AND the module is Accounts...
            if(module === 'Accounts') {
                //AND the 'button:custom_button:click' event occurs on the current Context
                app.controller.context.on('button:custom_button:click', function(model){
                    console.log("Custom Button event triggered on: " + model.get("name"));
                    //Show an alert on screen
                    app.alert.show('custom-message-id', {
                        level: 'success',
                        messages: 'It worked!',
                        autoClose: true
                    });
                });
            }
        });

    });
})(SUGAR.App);
