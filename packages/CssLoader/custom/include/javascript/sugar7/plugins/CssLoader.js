/**
 * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */
(function (app) {
    app.events.on('app:init', function () {
        /**
         * This plugin allows components to dynamically load CSS files.
         *
         * <pre><code>
         * // Sample component:
         * {
         *      plugins: ['CssLoader'],
         *
         *      css: [
         *          'include/javascript/foo/foo',
         *          'clients/base/view/bar/bar'
         *      ]
         * }
         * </code></pre>
         */
        app.plugins.register('CssLoader', ['layout', 'view', 'field'], {
            /**
             * Load CSS files specified in component.css array.
             */
            onAttach: function() {
                this.loadCss();
            },

            /**
             * Load given CSS file paths.
             * @param {array} [cssFiles] - paths to css files
             */
            loadCss: function(cssFiles) {
                var $previouslyAdded;
                _.each(cssFiles || this.css, function(file) {
                    var $link;
                    if (!this.isCssLoaded(file)) {
                        if(file.indexOf('.css') === -1){
                            file = file + '.css';
                        }
                        $link = $('<link>', {
                            href: file,
                            type: 'text/css',
                            rel: 'stylesheet'
                        });

                        if ($previouslyAdded) {
                            $previouslyAdded.after($link);
                        } else {
                            // We prepend instead of append so that styles in Styleguide is preferred over
                            // dynamically loaded CSS styles when they have equal specificity order.
                            $link.prependTo(document.head);
                        }

                        $previouslyAdded = $link;
                    }
                }, this);
            },

            /**
             * Is the given CSS file already loaded in the browser?
             * @param {string} href
             * @returns {boolean}
             */
            isCssLoaded: function(href) {
                return !!_.find(document.styleSheets, function(style) {
                    return style.href && (style.href.indexOf(href) !== -1);
                });
            }
        });
    });
})(SUGAR.App);
