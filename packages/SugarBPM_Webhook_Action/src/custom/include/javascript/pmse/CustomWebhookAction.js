(function (app) {

    function WithConfig(Base) {
        return class extends Base {
            constructor(...args) {
                super(...args);
                this._configButton = null;
                this._variablesList = null;
                this._variables = null;
                this._control = null;
                this.currentField = null;
            }
            setVariables(variables) {
                this._variables = variables;
                return this;
            }
            _createConfigButton() {
                var button = this.createHTMLElement("a");
                button.href = "#";
                button.className = 'adam-itemupdater-cfg sicon sicon-settings';
                button.setAttribute('rel', 'tooltip');
                button.setAttribute('data-bs-placement', 'right');
                button.setAttribute('data-original-title', app.lang.get('LBL_SUGAR_FIELD_SELECTOR', 'pmse_Emails_Templates'));
                this._configButton = button;

                // attach event listener
                jQuery(this._configButton).on("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.openPanelOnItem();
                });
                return this._configButton;
            }
            _onValueGenerationHandler(module) {
                const that = this;
                return function () {
                    const [panel, , selected] = arguments;
                    const variable = "{::" + module + "::" + selected.value + "::}";
                    const control = that.controlObject;
                    const startPos = control.selectionStart;
                    const endPos = control.selectionEnd;
                    const textBefore = control.value.substring(0, startPos);
                    const textAfter = control.value.substring(endPos, control.value.length);
                    const newValue = textBefore + variable + textAfter;
                    that.setValue(newValue);
                    setTimeout(() => {
                        control.selectionStart = startPos + variable.length;
                        control.selectionEnd = startPos + variable.length;
                    }, 200);
                    panel.close();
                    control.focus();
                };
            }
            openPanelOnItem() {
                const that = this;
                if (!this._variablesList) {
                    this._variablesList = new FieldPanel({
                        className: "updateritem-panel",
                        appendTo: that,
                        items: [
                            {
                                type: "list",
                                bodyHeight: 100,
                                collapsed: false,
                                itemsContent: "{{text}}",
                                fieldToFilter: "type",
                                title: translate('LBL_PMSE_UPDATERFIELD_VARIABLES_LIST_TITLE').replace(/%MODULE%/g, app.lang.getModuleName(PROJECT_MODULE))
                            }
                        ],
                        onItemValueAction: that._onValueGenerationHandler(PROJECT_MODULE),
                        onOpen: function () {
                            jQuery(that.currentField.html).addClass("opened");
                        },
                        onClose: function () {
                            jQuery(that.currentField.html).removeClass("opened");
                        }
                    });
                }
                const targetPanel = this._variablesList;
                const list = this._variablesList.getItems()[0];
                //We check if the variables list has the same filter than the one we need right now,
                //if it do then we don't need to apply the data filtering for a new criteria
                if (list.getFilterMode() === 'inclusive') {
                    list.setFilterMode('exclusive')
                        .setDataItems(this._variables, "type", ["Checkbox", "DropDown"]);
                }
                this.currentField = this;
                const currentOwner = targetPanel.getOwner();
                if (currentOwner !== this.controlObject) {
                    targetPanel.close();
                    targetPanel.setOwner(this.controlObject);
                    targetPanel.open();
                } else {
                    if (targetPanel.isOpen()) {
                        targetPanel.close();
                    } else {
                        targetPanel.open();
                    }
                }
                return this;
            }
            createHTML() {
                this.html = Base.prototype.createHTML.call(this);
                this._createConfigButton();
                if (this._configButton) {
                    // append config button after
                    this.controlObject.after(this._configButton);
                }
                // return the containing html
                return this.html;
            }
        }
    }

    function WithKeyValueController(Base) {
        return class extends Base {
            constructor(...args) {
                const [options] = args;
                super(...args);
                this.mode = options.mode || 'list';
                this.allowPlain = !!options.allowPlain;
                // binding default validator
                this.setValidators([{
                    jtype: 'custom',
                    criteria: {
                        validationFunction: () => this.inputValidator(this.value),
                    },
                    errorMessage: translate('LBL_PMSE_FORM_ERROR_JSON_FORMAT')
                }]);
            }
            insertStyles() {
                const style = this.createHTMLElement("style");
                style.innerHTML = `
                    #inputContainerWrapper.sourceMode {
                        display: flex;
                    }
                    #inputContainerWrapper button.mode {
                        /* gray background */
                        background-color: #94a3b8;
                        border-radius: 5px;
                        margin-left: 5px;
                        margin-right: 10px;
                        color: #fff;
                        position: absolute;
                        right: 6px;
                        top: 6px;
                    }
                    #inputContainer {
                        padding: 0 25px;
                    }
                    #inputContainer > .row {
                        display: flex;
                        flex-direction: row;
                        position: relative;
                        align-items: center;
                        gap: 3px;
                    }
                    #inputContainer .row:not(:first-child){
                        margin-top: 5px;
                    }
                    #inputContainer > .row > input {
                        margin: 0px;
                        padding: 2px 3px;
                        font-size: 90%;
                        height: 14px;
                        width: auto;
                        flex-grow: 1;
                    }
                    #inputContainer > .row > button {
                        border-radius: 5px;
                        display: none;
                        width: 20px;
                        color: #fff;
                        margin-right: 5px;
                        margin-left: 5px;
                    }
                    #inputContainer .row button.addRow{
                        background-color: #007bff;
                    }
                    #inputContainer .row button.removeRow{
                        background-color: #dc3545;
                    }

                    #inputContainerWrapper.sourceMode > textarea,
                    #inputContainerWrapper.sourceMode > input {
                        flex-grow: 1;
                        display: inline-block;
                        margin-right: 45px;
                    }

                    #inputContainerWrapper > textarea,
                    #inputContainerWrapper > input,
                    #inputContainerWrapper.sourceMode #inputContainer,
                    #inputContainerWrapper:not(.sourceMode) .adam-itemupdater-cfg,
                    #inputContainerWrapper:not(.sourceMode) button.mode > span:not(.active),
                    #inputContainerWrapper.sourceMode button.mode > span.active {
                        display: none;
                    }

                    #inputContainer .row:first-child  button.addRow,
                    #inputContainer .row:not(:first-child)  button.removeRow {
                        display: block;
                    }
                `;
                this.html.id = "inputContainerWrapper";

                // append styles to begining of the this.html
                this.html.insertBefore(style, this.html.firstChild);
            }
            isSourceMode() {
                return this.html.classList.contains("sourceMode");
            }
            switchMode(mode) {
                if (mode === 'source') {
                    this.html?.classList?.add("sourceMode");
                } else {
                    this.html?.classList?.remove("sourceMode");
                }
            }
            getModeButton() {
                const modeButton = this.createHTMLElement("button");
                modeButton.className = "mode";
                modeButton.innerHTML = '<span class="active">{}</span><span>+/-</span>';
                modeButton.addEventListener("click", (e) => {
                    e.preventDefault(); e.stopPropagation();
                    // check if this.html have class sourceMode
                    const isCurrentModeSourceMode = this.isSourceMode();
                    const isValidKeyValueJSON = this.validateKeyValueJSON();
                    if (isCurrentModeSourceMode && isValidKeyValueJSON) {
                        this.switchMode('list');
                    } else {
                        this.switchMode('source');
                    }
                });
                return modeButton;
            }
            initiateKeyValueInputsAndButtons() {
                this.insertStyles();

                const inputContainer = this.createHTMLElement("div");
                inputContainer.id = "inputContainer";
                inputContainer.appendChild(this.generateRow());

                this.html.appendChild(inputContainer);
                this.html.appendChild(this.getModeButton());

                if (this.mode === 'source') {
                    this.html.classList.add("sourceMode");
                } else {
                    this.html.classList.remove("sourceMode");
                }
                return inputContainer;
            }
            generateRow(valueForKey, valueForValue) {
                const row = this.createHTMLElement("div");
                row.className = "row";

                const key = this.createHTMLElement("input");
                key.type = "text";
                key.className = "key";
                key.value = valueForKey || "";
                key.placeholder = "key";
                key.onkeyup = this.onListItemKeyUp.bind(this);

                // add semicolon in between key and value
                const semicolon = this.createHTMLElement("span");
                semicolon.innerHTML = ":";

                const value = this.createHTMLElement("input");
                value.type = "text";
                value.className = "value";
                value.value = valueForValue || "";
                value.placeholder = "value";
                value.onkeyup = this.onListItemKeyUp.bind(this);

                const addRow = this.createHTMLElement("button");
                addRow.className = "addRow";
                addRow.innerHTML = "+";
                addRow.addEventListener("click", (e) => {
                    e.preventDefault(); e.stopPropagation();
                    const newRow = this.generateRow()
                    row.parentElement.insertBefore(newRow, row.parentElement.firstChild);
                });

                const removeRow = this.createHTMLElement("button");
                removeRow.className = "removeRow";
                removeRow.innerHTML = "-";
                removeRow.addEventListener("click", (e) => {
                    e.preventDefault(); e.stopPropagation();
                    const target = e.target;
                    const row = target.parentElement;
                    const container = row.parentElement;
                    if (container.children.length > 1) {
                        row.remove();
                        this.onListItemKeyUp();
                    }
                });

                row.appendChild(key);
                row.appendChild(semicolon);
                row.appendChild(value);
                row.appendChild(addRow);
                row.appendChild(removeRow);
                return row;
            }
            isJsonString(value) {
                try {
                    JSON.parse(value);
                    return true;
                } catch (e) {
                    return false;
                }
            }
            setValue(value, skipPopulate = false) {
                const isValueJSON = this.isJsonString(value);
                if (isValueJSON) {
                    // parse the value to JSON
                    value = JSON.parse(value);
                    if (!skipPopulate) {
                        // on set value we should sync the list view
                        this.populateRowsFromJSON(value);
                    }
                    // reassigning value to stringified JSON
                    value = JSON.stringify(value, null, 2);
                } else {
                    this.switchMode('source'); // forcing to source mode if value is not JSON
                    // make sure the value is string
                    value = value?.toString() || "";
                }
                super.setValue(value);
            }
            inputValidator(value) {
                return this.allowPlain || this.validateKeyValueJSON(value);
            }
            // validate() {
            //     return this.inputValidator(this.value);
            // }
            // checks the value is json and only key value contains
            isKeyValueJSON(value) {
                if (!this.isJsonString(value)) {
                    return false;
                }
                const jsonData = JSON.parse(value);
                return Object.keys(jsonData).every(key => typeof jsonData[key] === "string");
            }
            getJsonFromInput() {
                const inputContainer = this.html.querySelector("#inputContainer");
                const rows = inputContainer.querySelectorAll(".row");
                const data = {};
                rows.forEach(row => {
                    const key = row.querySelector(".key").value;
                    const value = row.querySelector(".value").value;
                    if (key.trim() !== '') {
                        data[key] = value || "";
                    }
                });
                return data;
            }
            onListItemKeyUpTimer = null;
            onListItemKeyUp() {
                clearTimeout(this.onListItemKeyUpTimer);
                this.onListItemKeyUpTimer = setTimeout(() => {
                    const data = this.getJsonFromInput();
                    this.setValue(JSON.stringify(data), true);
                }, 100);
            }
            populateRowsFromJSON(jsonData) {
                const inputContainer = this.html.querySelector("#inputContainer");
                inputContainer.innerHTML = "";
                const keys = Object.keys(jsonData);
                inputContainer.appendChild(this.generateRow());
                if (keys.length > 0) {
                    keys.forEach((key, index) => {
                        const row = this.generateRow(key, jsonData[key]);
                        inputContainer.appendChild(row);
                    });
                }
            }
            validateKeyValueJSON() {
                let ret = !this.value || this.isKeyValueJSON(this.value);
                if (!ret) {
                    app.alert.show("invalid-json", {
                        level: "error",
                        messages: "The value must be a valid JSON object with only key value pairs",
                        autoClose: true
                    });
                }
                return ret;
            }
            createHTML() {
                this.html = Base.prototype.createHTML.call(this);
                this.initiateKeyValueInputsAndButtons();
                return this.html;
            }
        }
    }

    class TextFieldWithConfig extends WithConfig(TextField) { }
    class TextareaFieldWithConfig extends WithConfig(TextareaField) { }
    class TextareaFieldWithKeyValueControllerAndConfig extends WithKeyValueController(TextareaFieldWithConfig) { }

    const doMagic = (w) => {
        const html = w.html;
        const parent = html.parentElement;
        const style = document.createElement("style");
        style.innerHTML = `
            .customDynamicWindow {
                position: fixed;
                left: calc(50vw - 330px + 56px + 25px);
                top: 138px;
                width: auto;
                height: auto;
                display: block;
            }
            .customDynamicWindow .adam-panel-body {
                padding: 10px 10px 15px;
                max-height: calc(80vh - 138px);
                max-width: 80vw;
                display: flex;
                flex-direction: column;
            }
            .customDynamicWindow .adam-field {
                display: flex;
                flex-wrap: wrap;
                gap: 3px;
            }
            .customDynamicWindow .adam-form-label {
                flex: 1 1 100%;
                margin:0px;
                text-align: left;
            }
            .customDynamicWindow .adam-field > :not(.adam-form-label){ 
                display: flex;
                white-space: nowrap;
            }
            .customDynamicWindow .adam-field > select {
                margin: 0px;
            }
            .customDynamicWindow .adam-field > input,
            .customDynamicWindow .adam-field > select,
            .customDynamicWindow .adam-field > textarea{
                min-width: 370px;
                flex-grow: 1;
            },
            .customDynamicWindow .adam-field > #inputContainer > .row {
                flex-grow: 1;
            }
            .customDynamicWindow .adam-field > #inputContainer {
                flex: 1;
                flex-direction: column;
            }
            .customDynamicWindow #inputContainer > .row > button,
            .customDynamicWindow #inputContainerWrapper button.mode,
            .customDynamicWindow #inputContainerWrapper.sourceMode > textarea {
                margin-right: 0px !important;
            }
            .customDynamicWindow #inputContainerWrapper .adam-form-label{
                margin-bottom: 5px;
            }
            .customDynamicWindow #inputContainer {
                padding: 0;
            }
            .customDynamicWindow .adam-panel-footer {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                padding-top: 7px;
            }
            .customDynamicWindow .adam-panel-footer .adam-button {
                margin:0px;
            }
            .customDynamicWindow .pmse-form-error {
                flex-grow: 1;
                padding: 0 15px;
            }
            .customDynamicWindow .pmse-form-error .sicon {
                margin-right: 5px;
            }
            /* Hack for adding bigger click region wo/ changing the button size */
            .customDynamicWindow .adam-window-close {
                padding: 5px;
                margin: -5px;
            }
            .customDynamicWindow .adam-field > style {
                display: none !important;
            }
        `;
        //append beginnig of the window
        parent.insertBefore(style, html);
        html.classList.add("customDynamicWindow");
        html.removeAttribute("style");
        html.querySelectorAll(".adam-panel-body,.adam-window-body,.adam-form-label,.adam-field,.adam-panel-footer").forEach(e => e.removeAttribute('style'));
        html.querySelector('.adam-panel').style.overflow = 'visible';
    }
    app.events.on("app:sync:complete", function () {
        const ACTION_IDENDTIFIER = "CUSTOM_WEBHOOK";

        const customContextMenuActions = AdamActivity.prototype.customContextMenuActions;
        AdamActivity.prototype.customContextMenuActions = function () {
            var baseActions = customContextMenuActions?.apply(this) || [];
            const webhookActionDef = {
                text: translate('LBL_PA_FORM_ACTIVITY_CUSTOM_WEBHOOK'),
                cssStyle: 'adam-menu-script-assign_team',
                name: ACTION_IDENDTIFIER,
            };
            const webhookAction = {
                ...webhookActionDef,
                handler: this._getScriptTypeActionHandler(webhookActionDef.name)
            };
            baseActions.push(webhookAction);
            return baseActions;
        };

        const customGetAction = AdamActivity.prototype.customGetAction;
        AdamActivity.prototype.customGetAction = function (type, w) {
            const self = this;
            let action;
            if (type == ACTION_IDENDTIFIER) {
                const hidden_module = new HiddenField({
                    name: 'act_field_module',
                    initialValue: PROJECT_MODULE
                });

                const request_method = new ComboboxField({
                    jtype: 'combobox',
                    name: 'act_request_method',
                    label: translate('LBL_PMSE_FORM_LABEL_REQUEST_METHOD'),
                    options: [
                        { text: 'GET', value: 'GET' },
                        { text: 'POST', value: 'POST' },
                        { text: 'PUT', value: 'PUT' },
                        { text: 'DELETE', value: 'DELETE' },
                        { text: 'PATCH', value: 'PATCH' },
                        { text: 'HEAD', value: 'HEAD' },
                    ],
                    initialValue: 'GET',
                    readOnly: false,
                });

                const request_timeout = new TextFieldWithConfig({
                    jtype: 'text',
                    name: 'act_request_timeout',
                    label: translate('LBL_PMSE_FORM_LABEL_REQUEST_TIMEOUT'),
                    value: '30',
                    required: true,
                    validators: [{
                        jtype: 'custom',
                        criteria: {
                            validationFunction: () => {
                                return request_timeout.value.trim() !== ''
                                    && parseInt(request_timeout.value) == request_timeout.value;
                            },
                        },
                        errorMessage: translate('LBL_PMSE_FORM_ERROR_INVALID_TIMEOUT')
                    }],
                });

                const request_url = new TextFieldWithConfig({
                    jtype: 'required',
                    name: 'act_request_url',
                    label: translate('LBL_PA_FORM_LABEL_REQUEST_URL'),
                    required: true,
                    validators: [{
                        jtype: 'custom',
                        criteria: {
                            validationFunction: () => {
                                return request_url.value.trim() !== ''
                            },
                        },
                        errorMessage: translate('LBL_PMSE_FORM_ERROR_MISSING_REQ_URL')
                    }],
                    value: '',
                });
                const request_headers = new TextareaFieldWithKeyValueControllerAndConfig({
                    jtype: 'text',
                    name: 'act_request_headers',
                    label: translate('LBL_PA_FORM_LABEL_REQUEST_HEADERS'),
                    value: '',
                });
                const request_payload = new TextareaFieldWithKeyValueControllerAndConfig({
                    jtype: 'text',
                    name: 'act_request_payload',
                    label: translate('LBL_PA_FORM_LABEL_REQUEST_PAYLOAD'),
                    value: '',
                    allowPlain: true,
                    mode: 'source'
                });

                const proxy = new SugarProxy({
                    url: 'pmse_Project/ActivityDefinition/' + this.id,
                    uid: this.id,
                    callback: null
                });

                const items = [request_method, request_url, request_payload, request_headers, request_timeout, hidden_module];
                const labelWidth = '40%';
                const actionText = translate('LBL_PMSE_CONTEXT_MENU_SETTINGS');
                const actionCSS = 'adam-menu-icon-configure';
                const callback = {
                    'loaded': function (data) {
                        self.canvas.emptyCurrentSelection();
                        app.alert.dismiss("upload");
                        doMagic(w);
                        // w.html.style.display = "inline"; // shows the window 🤯

                        if (data.act_fields) {
                            let fieldDatas = {};
                            try {
                                fieldDatas = JSON.parse(data.act_fields);
                            } catch (e) { }
                            items.forEach(item => {
                                if (item.name in fieldDatas) {
                                    item.setValue(fieldDatas[item.name]);
                                }
                            });
                        }
                        project.addMetadata('projectModuleFields', {
                            dataURL: 'pmse_Project/CrmData/fields/' + PROJECT_MODULE,
                            dataRoot: 'result',
                            success: function (data) {
                                request_url.setVariables(data);
                                request_headers.setVariables(data);
                                request_payload.setVariables(data);
                                request_timeout.setVariables(data);
                            }
                        });
                        const dataCollector = () => {
                            const data = {};
                            items.forEach(item => (item.name.startsWith('act_request')) && (data[item.name] = item.value));
                            return data;
                        }
                        this.getData = function () {
                            const act_fields = JSON.stringify(dataCollector());
                            return { act_fields };
                        }
                    }
                };
                action = {
                    proxy,
                    items,
                    labelWidth,
                    actionText,
                    actionCSS,
                    callback
                };
            } else {
                action = customGetAction?.apply(this, [type, w]) || {};
            }
            return action;
        };


        const customGetWindowDef = AdamActivity.prototype.customGetWindowDef;
        AdamActivity.prototype.customGetWindowDef = function (type) {
            const wWidth = 550;
            const wHeight = 302;
            const wTitle = translate('LBL_PMSE_FORM_TITLE_WEBHOOK');
            const windowDef = { wWidth, wHeight, wTitle };
            return (type == ACTION_IDENDTIFIER) ? windowDef : (customGetWindowDef?.apply(this, [type]) || {});
        };
    });
})(SUGAR.App);