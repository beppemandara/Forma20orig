/* 
 * $Id$
 */
M.f2_domains = {
	Y: null,               // The YUI instance to use with dock related code
	dTbl: null,            // JQuery DataTable object
	dTblSedi: null,
    dTblAgenzie: null,
	start_dates: [],
	end_dates: [],
	initialized: false     // True once the form has been initialized
};
M.f2_domains.init = function(Y, sesskey) {
	if (this.initialized) {
		return true;
	}
	this.initialized = true;
	this.Y = Y;

	//Event handlers
	Y.on('click', this.btnCleanupVisibilityDomClkHdr, '#id_cleanupvisibilitydom');
};
M.f2_domains.btnCleanupVisibilityDomClkHdr = function (evt) {
	var Y = M.f2_domains.Y, node = Y.one('input[type="hidden"][name=viewableorganisationid]');
    if(node.get("value") > 0) {
        node.set("value","");
//console.log("nodevalue="+node.get("value"));
        node.get("form").submit();
    }
};
