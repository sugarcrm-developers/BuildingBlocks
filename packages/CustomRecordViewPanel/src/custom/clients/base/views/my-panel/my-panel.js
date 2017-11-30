// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
({
    events: {
        'click button': 'clickedButton'
    },
    clickedButton: function(){
        app.alert.show('my-panel-clicked-button', {
            level: 'success',
            messages: 'Click!',
            autoClose: true
        });
    }
})
