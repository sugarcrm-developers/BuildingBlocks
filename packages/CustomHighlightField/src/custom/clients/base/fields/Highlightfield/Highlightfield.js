/**
 * @class View.Fields.Base.Highlightfield
 * @alias SUGAR.App.view.fields.Highlightfield
 * @extends View.Fields.Base.BaseField
 */
({
    extendsFrom: 'BaseField',

    /**
     * Called when initializing the field
     * @param options
     */
    initialize: function(options) {
        this._super('initialize', [options]);
    },

    /**
     * Called when rendering the field
     * @private
     */
    _render: function() {
        this._super('_render');
    },

    /**
     * Called when formatting the value for display
     * @param value
     */
    format: function(value) {
        return this._super('format', [value]);
    },

    /**
     * Called when unformatting the value for storage
     * @param value
     */
    unformat: function(value) {
        return this._super('unformat', [value]);
    }
})