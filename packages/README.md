# Package Listing
Each of these packages are ready to be zipped, uploaded and installed using [Module Loader](https://support.sugarcrm.com/Documentation/Sugar_Versions/7.6/Ent/Administration_Guide/Developer_Tools/Module_Loader/) into any existing Sugar 7 On-Demand or On-Site instance.

The `buildPackages.sh` script can be used to zip these packages up easily on Unix based environments.

## [Contextual iFrame Dashlet](ContextualIFrameDashlet/)

iFrame dashlet that additionally will pass URL parameters with context information (record id and module name) that can be used to drive a lightweight UI integration with an external application.  Can be used as-is for a Proof of Concept or Demos and can be easily customized for additional capability.

## [Hello World Dashlet](HelloWorldDashlet/)

Good starting point for building any [Sugar Dashlet](http://support.sugarcrm.com/Documentation/Sugar_Developer/Sugar_Developer_Guide_7.6/UI_Model/Dashlets/Introduction/) based integration from scratch.  Easily customizable for additional capability.

## [Custom Record View Button](CustomRecordViewButton/)

Shows how [Extensions Framework](http://support.sugarcrm.com/Documentation/Sugar_Developer/Sugar_Developer_Guide_7.6/Extension_Framework/) can be used to implement a custom button on a Record View without overriding any core files such as Sidecar View controllers, etc.

**Install Note:**  You may need to clear your cache after install of this package to ensure that old JavaScript is cleared out of the page.
