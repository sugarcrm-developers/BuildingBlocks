# Script Loader

Install this package using Module Loader in order to add the ScriptLoader plug-in for your Sugar instance.

Includes [require.js](http://requirejs.org/) which is BSD / MIT licensed.

## Usage
```javascript
    ({
        plugins: ['ScriptLoader'],

        scripts: [
            'include/javascript/3rdpartylib',
            'http://example.com/external.js'
        ],

        onLoadScript: function(3rdpartylib, external) {
            //Scripts loaded!
        }

        /**
         * Manually load script after dependency is ready
         * @param options
         */
        initialize: function(options){
            this._super('initialize', [options]);

            this.loadScript(["http://example.com/dependency.js"], function(){
                this.loadScript(["http://example.com/script.js"]);
            });

        },
    })
````
## Example

For an example of how to use the Script Loader, see [Using Script and CSS loading plug-ins for easy Sugar integrations](https://community.sugarcrm.com/community/developer/blog/2016/02/01/using-script-and-css-loading-plug-ins-for-easy-sugar-integrations).
