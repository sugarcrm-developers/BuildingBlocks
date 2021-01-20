({
    // Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
    events: {
        //On click of our "button" element
        'click [data-action=open_phone]': 'togglePopup',
    },
    
    // tagName attribute is inherited from Backbone.js.
    // We set it to "span" instead of default "div" so that our "button" element is displayed inline.
    tagName: "span",
    // Used to keep track of Popup since it is not attached to this View's DOM
    $popup: undefined,
    /**
     * Toggle the display of the popup.  Called when the phone icon is pressed in the footer of the page.
     */
    togglePopup: function () {
        //Toggle active status on button in footer
        var $button = this.$('[data-action="open_phone"]');
        $button.toggleClass('active');
        //Create popup if necessary, otherwise just toggle the hidden class to hide/show.
        if (!this.$popup) {
            this._createPopup();
        } else {
            this.$popup.toggleClass('hidden');
        }
    },
    /**
     * Used to create Popup as needed. Avoid calling this directly, should only need to be called once.
     * @private
     */
    _createPopup: function () {
        var popupCss = app.template.get("click-to-call.popup-css");
        // We need to load some custom CSS, this is an easy way to do it without having to edit custom.less
        $('head').append(popupCss());
        var popup = app.template.get("click-to-call.popup")(this);
        // Add to main content pane of screen
        $('#sidecar').append(popup);
        this.$popup = $('#sidecar').find('div.cti-popup');
        // Hide pop up on click of X (close button)
        this.$popup.find('[data-action=close]').click(_.bind(this._closePopup, this));
        // Make pop up draggable using existing jQuery UI plug-in
        this.$popup.draggable();
    },
    /**
     * Called when close button is pressed on CTI popup.
     * @private
     */
    _closePopup: function () {
        this.$popup.addClass('hidden');
        var $button = this.$('[data-action="open_phone"]');
        $button.removeClass('active');
    },
    /**
     * Dispose of unattached popup when footer destroyed
     * @private
     */
    _dispose: function(){
        this._super('_dispose');
        this.$popup.remove();
        this.$popup = null;
    }

})

