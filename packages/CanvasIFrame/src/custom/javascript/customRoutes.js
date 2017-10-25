(function(app){app.events.on("router:init", function(){
    var routes = [
        {
            route: 'canvas_iframe',
            name: 'Canvas IFrame',
            callback: function(){
                app.controller.loadView({
                    layout: "canvas-iframe",
                    create: true
                });
            }
        }
    ];
    app.router.addRoutes(routes);
})})(SUGAR.App);