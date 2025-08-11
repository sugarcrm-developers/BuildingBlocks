/**
 * @class View.Views.Base.ProcessManagementDashletView
 * @alias SUGAR.App.view.views.BaseProcessManagementDashletView
 * @extends View.Views.Base.ListView
 */
({
    extendsFrom: 'ListView',
    plugins:[
        'Dashlet',
        'ResizableColumns',
        'Pagination',
        'ProcessActions'
    ],

    _defaultSettings: {
        label: 'LBL_PROCESS_MANAGEMENT',
        limit: 5,
        module: 'pmse_Inbox'
    },

    filterFields: [],
    filterFieldsModel: new Backbone.Model({
        status_filter: [],
        query_filter: ""
    }),
    isFocusDrawer: false, 
    
    /**
     * @inheritdoc
     */
    initialize(options) {
        this.logEnabled = app.user.lastState.get('pmse_Inbox:process-management-dashlet:logEnabled') === true;
        this.cacheKiller = (new Date()).getTime();
        this.log({"this.isFocusDrawer": this.isFocusDrawer});
        this.log({"~this": this});
        this.isFocusDrawer = options.context.parent.get('layout') == 'focus';
        let lastStateId = options.type + (this.isFocusDrawer ? ":focus" : "");
        this.log({"lastStateId": lastStateId});
        // Append Last State for bunch of feature
        options.meta['last_state'] = {'id': lastStateId };
        this._super('initialize', [options]);
        
        console.log("app.user.lastState.key('debug', this)", app.user.lastState.key('debug', this));
        console.log("app.user.lastState.get(app.user.lastState.key('debug', this))", app.user.lastState.get(app.user.lastState.key('debug', this)));
        console.log("this.debugMode", this.debugMode);
        this.orderByLastStateKey = app.user.lastState.key('order-by', this);
        this.log({"this.orderByLastStateKey": this.orderByLastStateKey});
        
        this.on('list:column:resize:save', (columns) => {
            app.user.lastState.set(app.user.lastState.key('width-fields', this), columns);
        });
        
        this.process_module = this.context.parent.get("module");
        this.process_record_id = this.context.parent.get("model").get('id');
        this.log({"~this.process_module": this.process_module, "~this.process_record_id": this.process_record_id});
        
        this.context.on('case:history', (model) => this.getHistory(model.get('cas_id')));
        this.context.on('case:notes', (model) => this.showNotes(model.get('cas_id'), 1));
        this.context.on('list:cancelCase:fire', (model) => this.cancelCases(model));
        this.context.on("list:preview:fire", (model) => app.events.trigger("preview:render", model, this.collection, true));
        this.context.on("case:preview:chart", (model) => window.open(
            app.api.buildFileURL({
                module: 'pmse_Inbox',
                id: model.get('cas_id'),
                field: 'id'
            }, {cleanCache: true}))
        );    
        this.events = _.extend(this.events, {
            'click [data-widths=reset]': 'resetColumnWidths',
            'click [track="click:rowactions"]': 'removeStyles'
        });

        this.rowActions = [];
        this.addStatusFiltersField();
        this.addQueryFiltersField();
    },

    removeStyles(e){
        this.$(e.currentTarget).next(".dropdown-menu").removeAttr('style');
    },

    /**
     * Adds status filter and necessary actions
     */
    addStatusFiltersField(){
        const field = {
            'type':'enum',
            'name':'status_filter',
            'options':{
                'COMPLETED': 'COMPLETED',
                'TERMINATED': 'TERMINATED',
                'IN PROGRESS': 'IN PROGRESS',
                'CANCELLED': 'CANCELLED',
                'ERROR': 'ERROR',
            },
            'label':'Status Filter: ',
            'placeholder': 'Status Filter',
            'tooltip':'Filters Processes by their statuses.',
            'isMultiSelect':true,
            'enabled':true,
            'tplName':'edit',
            'action':'edit',
            'view':'edit'
        };
        if(this.filterFields.filter(f => f.name == "status_filter").length == 0){
            this.filterFields.push(field);
        }
        const statusFilterKey = app.user.lastState.key('status_filter', this);
        const lastState = app.user.lastState.get(statusFilterKey);
        if(lastState){
            this.filterFieldsModel.set("status_filter", lastState, {silent: true});
        }
        this.filterFieldsModel.off("change:status_filter").on("change:status_filter", _.debounce(() => {
            const statusFilter = this.filterFieldsModel.get("status_filter");
            app.user.lastState.set(statusFilterKey, statusFilter);
            this.collection.fetch();
        }, 1000));
    },

    /**
     * Adds query filter and necessary actions
     */
    addQueryFiltersField(){
        const field = {
            'type':'text',
            'name':'query_filter',
            'label':'Query Filter',
            'placeholder': 'Filter in Process Num(pnum:1), ID(pid:<guid>) and Title(ptitle:"text")',
            'tooltip':'Usage Num(pnum:1), ID(pid:<guid>), Title(ptitle:"search key") or any text',
            'enabled':true,
            'tplName':'edit',
            'action':'edit',
            'view':'edit',
            'ignoreLabel': true
        };
        if(this.filterFields.filter(f => f.name == "query_filter").length == 0){
            this.filterFields.push(field);
        }
        const queryFilterKey = app.user.lastState.key('query_filter', this);
        const lastState = app.user.lastState.get(queryFilterKey);
        if(lastState){
            this.filterFieldsModel.set("query_filter", lastState, {silent: true});
        }
        this.filterFieldsModel.off("change:query_filter").on("change:query_filter", _.debounce(() => {
            const queryFilter = this.filterFieldsModel.get("query_filter");
            app.user.lastState.set(queryFilterKey, queryFilter);
            this.collection.fetch();
        }, 750));
    },

    /**
     * @inheritdoc
     */
    _initOrderBy() {
        var lastStateOrderBy = app.user.lastState.get(this.orderByLastStateKey) || {},
        lastOrderedFieldMeta = this.getFieldMeta(lastStateOrderBy.field);
        this.log({"~": "_initOrderBy", "~this.orderByLastStateKey": this.orderByLastStateKey, lastStateOrderBy,
        lastOrderedFieldMeta});

        if (_.isEmpty(lastOrderedFieldMeta) || !app.utils.isSortable(this.module, lastOrderedFieldMeta)) {
            lastStateOrderBy = {};
        }

        // if no access to the field, don't use it
        if (!_.isEmpty(lastStateOrderBy.field) && !app.acl.hasAccess('read', this.module, app.user.get('id'), lastStateOrderBy.field)) {
            lastStateOrderBy = {};
        }
        const dashletConfigOrderBy = (this.dashletConfig && 'orderBy' in this.dashletConfig) ? this.dashletConfig.orderBy : {};

        return _.extend({
                field : '',
                direction : 'desc'
            },
            this.meta.orderBy,
            dashletConfigOrderBy,
            lastStateOrderBy
        );
    },

    /**
     * Init dashlet settings
     */
    initDashlet() {
        this.log({"~": "initDashlet", "this": this});
        this.settings.on('change:module', () => {
            this._updateDisplayColumns();
            this._hideUnselectedColumns();
        });
        this._initializeSettings();
        this.addRowActions();
        
        this.log({"~this.meta.config": this.meta.config});
        if( !this.settings.get('display_columns') || this.settings.get('display_columns').length == 0 ){
            this._updateDisplayColumns();
        }
        if (this.meta.config) {
            this.log({"~": "Configuring Dashlet..", "this.settings.get('display_columns')": this.settings.get('display_columns')});
            this._configureDashlet();
            this.log({"~": "Configuring Dashlet.."});
        }
        else {
            this.log({"~": "Displaying Dashlet.."});
            this._displayDashlet();
        }
    },
    
    /**
     * @inheritdoc
     */
    getCacheWidths(){
        return app.user.lastState.get(app.user.lastState.key('width-fields', this));
    },
    
    /**
     * Certain dashlet settings can be defaulted.
     *
     * Builds the available module cache by way of the
     * {@link BaseDashablelistView#_setDefaultModule} call. The module is set
     * after "filter_id" because the value of "filter_id" could impact the value
     * of "label" when the label is set in response to the module change while
     * in configuration mode (see the "module:change" listener in
     * {@link BaseDashablelistView#initDashlet}).
     *
     * @private
     */
    _initializeSettings() {
        for (const key in this._defaultSettings) {
            if (!this.settings.get(key)) {
                this.settings.set(key, this._defaultSettings[key]);
            }
        }
    },

    /**
     * Perform any necessary setup before the user can configure the dashlet.
     *
     * Modifies the dashlet configuration panel metadata to allow it to be
     * dynamically primed prior to rendering.
     *
     * @private
     */
    _configureDashlet() {
        var availableColumns = this._getAvailableColumns();
        this.log({"~_configureDashlet": availableColumns});
        _.each(this.getFieldMetaForView(this.meta), function(field) {
            switch(field.name) {
                case 'display_columns':
                    // load the list of available columns into the metadata
                    field.options = availableColumns;
                    break;
            }
        });
    },
    
    /**
     * Gets all of the fields from the list view metadata for the currently
     * chosen module.
     *
     * This is used for the populating the list view columns field and
     * displaying the list.
     *
     * @return {Object} {@link BaseDashablelistView#_availableColumns}
     * @private
     */
    _getAvailableColumns() {
        var columns = {},
            module = this.settings.get('module');
        if (!module) {
            return columns;
        }
        _.each(this.getDashletFields(), function(field) {
            columns[field.name] = app.lang.get(field.label || field.name, module);
        });
        return columns;
    },

    /**
     * Gets the fields metadata from a particular view's metadata.
     *
     * @param {Object} meta The view's metadata.
     * @return {Object[]} The fields metadata or an empty array.
     */
    getFieldMetaForView(meta) {
        meta = _.isObject(meta) ? meta : {};
        var fields = !_.isUndefined(meta.panels) ? _.flatten(_.pluck(meta.panels, 'fields')) : [];
        return fields;
    },

    /**
     * Returns the dashlet fields
     * 
     * @returns {array}
     */
    getDashletFields() {
        let fields = [];
        if('fields' in this.dashletConfig) {
            fields = this.dashletConfig.fields;
        }
        else{
            fields = this.getFieldMetaForView(this._getListMeta(this.settings.get('module')))
        }
        return fields;
    },

    /**
     * Gets the correct list(casesList-list) view metadata.
     *
     * Returns the correct module list metadata
     *
     * @param  {String} module
     * @return {Object}
     */
    _getListMeta(module) {
        return app.metadata.getView(module, 'casesList-list');
    },

    /**
     * Update the display_columns attribute based on the current module defined
     * in settings.
     *
     * This will mark, as selected, all fields in the module's list view
     * definition. Any existing options will be replaced with the new options
     * if the "display_columns" DOM field ({@link EnumField}) exists.
     *
     * @private
     */
    _updateDisplayColumns() {
        var availableColumns = this._getAvailableColumns(),
            columnsFieldName = 'display_columns',
            columnsField = this.getField(columnsFieldName);
        if (columnsField) {
            columnsField.items = availableColumns;
        }
        this.settings.set(columnsFieldName, _.keys(availableColumns));
    },


    /**
     * Perform any necessary setup before displaying the dashlet.
     * @private
     */
    _displayDashlet() {
        // Get the columns that are to be displayed and update the panel metadata.
        const fields = this._getColumnsForDisplay();
        this.meta.panels = [{fields}];
        this.log({"~":"_displayDashlet", fields, "~this.meta.panels": this.meta.panels});
        this.context.set('skipFetch', false);
        this.limit = this.settings.get('limit');
        this.context.set('limit', this.limit);
        const contextFields  = fields.reduce((p, c) => (p.push(c.name), p), []);
        this.context.set('fields', contextFields);
        
        this.collection.setOption('endpoint', (method, model, options, callbacks) => {
            this.log({method, model, options});
            const params = options.params || {};
            const status = this.filterFieldsModel.get("status_filter").join(",");
            if(status !== ""){
                params['status'] = status;
            }
            const query = this.filterFieldsModel.get("query_filter");
            if(query !== ""){
                params['q'] = query;
            }
            const url = ['pmse_Inbox', this.process_module, this.process_record_id].join("/");
            this.log({url, params});
            return app.api.call('read',app.api.buildURL(url, 'read', '', params), null, callbacks);
        });
        this.orderBy = this._initOrderBy();
        this.collection.orderBy = this.orderBy;
        this.context.set('collection', this.collection);
       
    },

    /**
     * Gets the columns chosen for display for this dashlet list.
     *
     * The display_columns setting might not have been defined when the dashlet
     * is being displayed from a metadata definition, like is the case for
     * preview and the default dashablelist's that are defined. All columns for
     * the selected module are shown in these cases.
     *
     * @return {Object[]} Array of objects defining the field metadata for
     *   each column.
     * @private
     */
     _getColumnsForDisplay() {
        var columns = {};
        var fields = this.getDashletFields();
        this.log({"~":"_getColumnsForDisplay", fields, "this.settings.get('display_columns')": this.settings.get('display_columns')});
        if (!this.settings.get('display_columns')) {
            this._updateDisplayColumns();
            this._hideUnselectedColumns();
        }
        for (const field of fields) {
            const displayColumns = this.settings.get('display_columns');
            const index = displayColumns.indexOf(field.name);
            if(index !== -1){
                columns[index] = field;
            }
        }
        columns = Object.values(columns);
        this.log({columns});

        return columns;
    },
    
    /**
     * When creating a dashlet by default all columns available will be shown.
     * By a flag set in metadata (selected) some column can be rendered hidden
     * and optionally selectable. Display only columns that are not excluded from
     * the initial list of columns. Changes made by the users should not be overwritten.
     */
     _hideUnselectedColumns() {
        var columns = this.settings.get('display_columns');
        _.each(this.getDashletFields(), function(fieldDef) {
            if (_.contains(columns, fieldDef.name) && fieldDef.selected === false) {
                columns = _.without(columns, fieldDef.name);
            }
        });
        this.settings.set('display_columns', columns);
    },

    /**
     * Adds row actions as a meta to be used
     */
    addRowActions() {
        var _generateMeta = function(label, css_class, buttons) {
            return {
                'type': 'rowactions',
                'label': label || '',
                'css_class': css_class,
                'buttons': buttons || [],
                'name': 'process-manager-dashlet-actions',
                'no_default_action': true,
                'value': false,
                'sortable': false
            };
        };
        var def = this.dashletConfig.rowactions;
        if(this.isFocusDrawer){
            def.actions = def.actions.filter(button => !button.hideOnFocusDrawer);
        }
        this.rowActions = _generateMeta(def.label, def.css_class, def.actions);
    },

    /**
     * Resets the column widths to the default settings.
     *
     * If the stickiness is enabled, it also removes the entry from the cache.
     */
    resetColumnWidths: function() {
        const widthFieldsKey = app.user.lastState.key('width-fields', this);
        if (widthFieldsKey) {
            app.user.lastState.remove(widthFieldsKey);
        }
        if (!this.disposed) {
            this.render();
        }
    },

    /**
     * Cancel Project event handler
     * 
     * @param {object} model 
     */
    cancelCases(model){
        let messages = app.lang.get('LBL_PMSE_CANCEL_MESSAGE', this.module)
            .replace('[]', model.get('cas_title'))
            .replace('{}', model.get('cas_id'));

        const Alert = app.alert;
        Alert.show('cancelCase-id', {
            level: 'confirmation',
            messages,
            autoClose: false,
            onConfirm: () => {
                Alert.show('cancelCase-in-progress', {level: 'process', title: 'LBL_LOADING', autoclose: false});
                const payload = model.toJSON();
                payload.cas_id = [model.get('cas_id')];
                app.api.call('update', app.api.buildURL(this.module + '/cancelCases'), payload, {
                    success: () => {
                        Alert.dismiss('cancelCase-in-progress');
                        this.context.reloadData({
                            recursive:false,
                        });
                    }
                });
            },
            onCancel: () => Alert.dismiss('cancelCase-id')
        });
    },

    // Utils
    logEnabled: false,
    log(argsObject, level){
        if( this.logEnabled !== true) return;

        let colors = ['sienna', 'darkcyan',  'cornflowerblue', 'chocolate', 'palevioletred'];
        // let colors = [ 'darkkhaki', 'chocolate',  'sienna', 'saddlebrown', 'tomato', 'slategray', 'cadetblue' ];
        let niceColors = [ 'steelblue' ];
        let defaultValueColor = 'dimgray';
        let valueColor = defaultValueColor;
        if(level == "success"){
            niceColors = ['seagreen']
        }
        else if ( level == "warning" ){
            niceColors = ['peru']
        }
        else if ( level == "fail" || level == "error" || level == "danger" ){
            niceColors = ['indianred']
        }
        niceColors = niceColors.concat(colors);

        let styles = [],
            log = "",
            misc = [],
            count = 0,
            size = 0;

        let printable = {};
        for (const variableName in argsObject) {
            if(!["object", "function"].includes(typeof argsObject[variableName])) {
                size++;
                printable[variableName] = argsObject[variableName];
            }
            else{
                misc.push(variableName+":");
                misc.push(argsObject[variableName]);
            }
        }
        for (const variableName in printable) {
            const value = printable[variableName];
            let isIgnore = /\~.*/.test(variableName);
            let currentLog = (isIgnore ? `` : `%c ${variableName} `) + `%c ${value} `;
            let firstRadius = "";
            let secondRadius = "";
            let color = niceColors[count%niceColors.length];
            
            if (size == 1 && isIgnore) {
                secondRadius = "border-radius:3px;";
                valueColor = color;
            }
            else if (count == 0) {
                if(!isIgnore){
                    firstRadius = "border-radius:3px 0 0 3px;";
                }
                else{
                    secondRadius = "border-radius:3px 0 0 3px;";
                    valueColor = color;
                }
            }
            else if (count == size-1){
                secondRadius = "border-radius:0 3px 3px 0;";
            }
            
            if(!isIgnore)
                styles.push(`background:${color}; padding: 2px 1px; color: #fff; font-weight:bold; font-style:italic; ${firstRadius}`);
            styles.push(`background:${valueColor}; padding: 2px 1px; color: #fff; ${secondRadius}`);
            log += currentLog;
            count++;
            valueColor = defaultValueColor;
        }
        console.log(log, ...styles, ...misc);
    }
})