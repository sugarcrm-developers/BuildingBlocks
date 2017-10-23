/**
 * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */
(function (app) {
    /**
     * Allow jquery and moment.js to be used in requirejs modules.
     */
    var bootstrapScriptLoader = function() {
        define('jquery', [], function() {
            return jQuery;
        });
        define('moment', [], function() {
            return moment;
        });
    };

    app.events.on("app:init", function () {
        bootstrapScriptLoader();

        /**
         * This plugin allows components to dynamically load JavaScript files.
         *
         * <pre><code>
         * // Sample component:
         * {
         *      plugins: ['ScriptLoader'],
         *
         *      scripts: [
         *          'include/javascript/foo/file1',
         *          'clients/base/view/bar/file2'
         *      ],
         *
         *      onLoadScript: function(file1, file2) {
         *          //code here
         *      }
         * }
         * </code></pre>
         */
        app.plugins.register('ScriptLoader', ['layout', 'view', 'field'], {
            /**
             * Load scripts specified in component.scripts array.
             */
            onAttach: function() {
                this.loadScript();
            },

            /**
             * Load given JavaScript files. Once loaded, it calls onLoadScript().
             * By default, it loads the script from component.scripts array.
             * @param {Array} [scripts] - List of paths to scripts to load.
             */
            loadScript: function(scripts, callback) {
                var scriptsToLoad;
                callback = callback || this.onLoadScript || $.noop;

                if (scripts) {
                    scriptsToLoad = scripts;
                } else if (_.isArray(this.scripts)) {
                    scriptsToLoad = this.scripts;
                }

                require(scriptsToLoad, _.bind(callback, this));
            }
        });
    });
})(SUGAR.App);
