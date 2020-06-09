/**
 * $Id: module.js 1028 2013-02-11 16:48:20Z d.lallo $
 * The f2_course namespace: Contains all things Forma 2 course customization related
 * @namespace
 */
M.f2_course = {
	Y: null,               // The YUI instance to use with dock related code
	ds: null,              // Ajax data source
	initialized: false     // True once the form has been initialized
};
M.f2_course.nodes = {
	body: null
};
M.f2_course.init = function(Y, sesskey) {
	if (this.initialized) {
		return true;
	}
	this.initialized = true;
	this.Y = Y;
	this.nodes.body = Y.one(document.body);
	this.ds = new Y.DataSource.IO({source:M.cfg.wwwroot+"/local/f2_support/get_support.php"});
	
	Y.on('change', this.switchFlag, '#id_flag_dir_scuola');
	Y.on('change', this.loadOptions,'#id_sf');
	Y.on('change', this.loadOptions,'#id_af');
	Y.on('keyup',  this.writeCF,    '#id_durata');
	Y.on('keyup',  this.writeIdNumberCourse,    '#id_shortname');
	Y.on('click',  this.setDisplay, '#id_button_scuola');
	
	Y.one('#id_flag_dir_scuola').simulate('change');
};
M.f2_course.loadOptions = function (evt) {
	var Y = M.f2_course.Y,
	frm = this.get("form"),
	src = this.get("id"),
	course = frm.one('input[name="courseid"]').get("value"),
	type = null,
	target = null,
	bFireChange = false,
	param = null;
	switch(src) {
		case "id_sf":
			target = Y.one("#id_af").getDOMNode(), type = "AF", param = "sf", bFireChange = true;
			break;
		case "id_af":
			target = Y.one("#id_subaf").getDOMNode(), type = "SUBAF", param = "af";
			break;
		default:
	}
	req = {
		request: "?course="+course+"&type="+type+"&"+param+"="+this.get("value"),
		on: {
			success: function(e) {
				var i = 0;
				target.options.length = 0;
				target.options[i] = new Option("--", "");
				Y.Array.each(e.response.results, 
					function(item) {
						target.options[++i] = new Option(item.descrizione, item.id);
					});
				if (bFireChange && target.options.length > 1) Y.one(target).simulate('change');
			},
			failure: function(e) {
				alert("Could not retrieve data: " + e.error.message);
			}
		}/*,
		cfg: {
			method: 'POST',
			form: {
				course: this.get("form").courseid,
				type:   "AF",
				sf:     this.get("value")
			}
		}*/
	};
	M.f2_course.ds.plug({
		fn: Y.Plugin.DataSourceJSONSchema, 
		cfg: {
			schema: {
        resultListLocator: "data",
        resultFields: ["id","descrizione"]
			}
		}
	});
	M.f2_course.ds.sendRequest(req);
};
M.f2_course.switchFlag = function (evt) {
	var Y = M.f2_course.Y;
	switch (this.get("value")) {
		case "D":
			Y.one("#id_button_scuola").setStyle("display", "none");
			Y.one("#div_tab_autosearch").setStyle("display", "none");
			Y.one("#id_button_orgs1").setStyle("display", "");
			break;
		case "S":
			Y.one("#id_button_scuola").setStyle("display", "");
			Y.one("#id_button_orgs1").setStyle("display", "none");
			break;
		default:
			Y.one("#id_button_scuola").setStyle("display", "none");
			Y.one("#div_tab_autosearch").setStyle("display", "none");
			Y.one("#id_button_orgs1").setStyle("display", "none");
	}
};
M.f2_course.writeCF = function (evt) {
	var Y = M.f2_course.Y;
	Y.one("#id_cf").set('value',this.get("value"));
};
M.f2_course.writeIdNumberCourse = function (evt) {
	var Y = M.f2_course.Y;

	if(document.getElementById('type_course_pro')){
		Y.one("#id_idnumber").set('value',this.get("value"));
	}
};
M.f2_course.setDisplay = function (evt) {
	var Y = M.f2_course.Y, node = Y.one("#div_tab_autosearch");
	switch(node.getStyle("display")) {
		case "none":
			node.setStyle("display", "");
			break;
		default:
			node.setStyle("display", "none");
	}
};

M.f2_course.changeValueButton = function (obj, chiudi, scuole) {

if (obj.value == chiudi)
	obj.value = scuole ;
else
	obj.value = chiudi ;
}