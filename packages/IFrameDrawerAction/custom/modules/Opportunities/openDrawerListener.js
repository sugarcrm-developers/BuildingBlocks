(function(app){
    /**
     * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
     */
    //Run callback when Sidecar metadata is fully initialized
    app.events.on('app:sync:complete', function(){

        var openDrawerCallback = function(model){
            // if no drawer currently open
            if(app.drawer.getActiveDrawerLayout() == app.controller.layout){
                app.drawer.open({
                    layout: 'iframe-drawer',
                    context: {
                        model: model,
                        url: "//httpbin.org/get?record="+model.get("id")+"&module=Opportunities",
                        title: "IFrame Drawer"
                    }
                }, function(){
                    //Re-attach listener on close of drawer
                    app.controller.context.once('button:open_drawer:click', openDrawerCallback);
                    // Reload Oppty info
                    app.controller.context.reloadData();
                });
            }
        };

        //When a record layout is loaded...
        app.router.on('route:record', function(module){
            //AND the module is Opportunities...
            if(module === 'Opportunities') {
                //AND the 'button:open_drawer:click' event occurs on the current Context
                app.controller.context.once('button:open_drawer:click', openDrawerCallback);
            }
        });

    });
})(SUGAR.App);
