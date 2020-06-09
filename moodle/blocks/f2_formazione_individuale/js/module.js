/**
 * $Id: module.js 1150 2013-05-28 15:27:10Z d.lallo $
 */
M.f2_formazione_individuale = {
	Y: null,               // The YUI instance to use with dock related code
	ds: null,              // Ajax data source
	initialized: false     // True once the form has been initialized
};
M.f2_formazione_individuale.nodes = {
	body: null
};
M.f2_formazione_individuale.init = function(Y, sesskey) {
	if (this.initialized) {
		return true;
	}
	this.initialized = true;
	this.Y = Y;
	this.nodes.body = Y.one(document.body);
	this.ds = new Y.DataSource.IO({source:M.cfg.wwwroot+"/blocks/f2_formazione_individuale/get_support.php"});
	
	Y.on('change', this.loadOptions,'#id_sf');
	Y.on('change', this.loadOptions,'#id_af');
        Y.on('keyup',  this.writeCF,    '#id_durata');
        Y.on('keyup',  this.writeBP,    '#id_ente'); // 2018 04 20
	
};
M.f2_formazione_individuale.writeCF = function (evt) {
	var Y = M.f2_formazione_individuale.Y;
	Y.one("#id_credito_formativo").set('value',this.get("value"));
};
// 2018 04 20
M.f2_formazione_individuale.writeBP = function (evt) {
        var Y = M.f2_formazione_individuale.Y;
        Y.one("#id_beneficiario_pagamento").set('value',this.get("value"));
};
// 2018 04 20
M.f2_formazione_individuale.loadOptions = function (evt) {
	var Y = M.f2_formazione_individuale.Y,
	frm = this.get("form"),
	src = this.get("id"),
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
		request: "?type="+type+"&"+param+"="+this.get("value"),
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
		}
	};
	M.f2_formazione_individuale.ds.plug({
		fn: Y.Plugin.DataSourceJSONSchema, 
		cfg: {
			schema: {
        resultListLocator: "data",
        resultFields: ["id","descrizione"]
			}
		}
	});
	M.f2_formazione_individuale.ds.sendRequest(req);
};

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
		Y.on('keyup',  this.writeNameForn,    '#id_shortname');
		Y.on('click',  this.setDisplay, '#id_button_scuola');

	};
	M.f2_course.loadOptions = function (evt) {
		var Y = M.f2_course.Y,
		frm = this.get("form"),
		course = frm.one('input[name="courseid"]').get("value"),
		type = null,
		target = null,
		bFireChange = false,
		param = null;
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
	M.f2_course.writeNameForn = function (evt) {
		var Y = M.f2_course.Y;
		if(document.getElementById('type_course_pro')){
			Y.one("#id_beneficiario_pagamento").set('value',this.get("value"));
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
	
	function disableEnterKey(e)
	{
	     var key;      
	     if(window.event)
	          key = window.event.keyCode; //IE
	     else
	          key = e.which; //firefox      

	     return (key != 13);
	}
