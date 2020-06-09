/*
 * $Id: datasource_getcourse.js 1050 2013-02-25 16:48:03Z d.lallo $
 */
YUI({
	//Load from Web:
	//gallery: 'gallery-2012.08.15-20-00'
	//Load locally:
	groups:       {
            // set up for locally served gallery
            'gallery': {
                //combine:   true,
                base:      M.cfg.wwwroot+"/lib/yui/gallery/build/",
                //root:      "lib/yui/gallery/build/",
                //comboBase: "/combo?",
                patterns: {
                    "gallery-":    {},
                    //"gallerycss-": { type: "css" }
                }   
            }
	}
}).use(
	'datatable', 'datasource-arrayschema',
	'gallery-treeble', 'gallery-paginator',
function(Y)
{
	var ctype = (Y.one('#coursetype').get('text') == '1') ? 'C_OBB' : 'C_PROG';
	var isDir = Y.one('#usertype').get('text');
	
	function sendRequest()
	{
		table.datasource.load(
		{
			request:
			{
				startIndex:  pg.getStartIndex(),
				resultCount: pg.getRowsPerPage()
			}
		});
	}
	
	// column configuration
	var cols = [
				{
					key: 'treeblenub',
					label: '&nbsp;',
					nodeFormatter: Y.Treeble.buildTwistdownFormatter(sendRequest)
				},
				{
					key: 'course_code',
					label: 'Codice corso',
					formatter: Y.Treeble.treeValueFormatter,
					allowHTML: true
				}];
	
	if (ctype == 'C_PROG') {
		if (isDir == true) {
			var cols1 = [
					{ key: 'course_title', label: 'Titolo corso'},
					{ key: 'course_year', label: 'Anno'},
					{ key: 'session_name', label: 'Sessione'},
					{ key: 'editionum', label: 'Edizione', allowHTML: true},
					{ key: 'sede', label: 'Sede'},
					{ key: 'indirizzo', label: 'Indirizzo'},
					{ key: 'edition_timestart', label: 'Data inizio'},
					{ key: 'edition_timefinish', label: 'Data fine'},
					{ key: 'edition_seats_reserved', label: 'Posti riservati'},
					{ key: 'edition_seats_booked', label: 'Posti consumati'},
					{ key: 'azione', label: 'Azione', allowHTML: true}
				];
			cols = cols.concat(cols1);
		} else {
			var cols1 = [
					{ key: 'course_title', label: 'Titolo corso'},
					{ key: 'course_year', label: 'Anno'},
					{ key: 'session_name', label: 'Sessione'},
					{ key: 'editionum', label: 'Edizione', allowHTML: true},
					{ key: 'sede', label: 'Sede'},
					{ key: 'indirizzo', label: 'Indirizzo'},
					{ key: 'edition_timestart', label: 'Data inizio'},
					{ key: 'edition_timefinish', label: 'Data fine'},
					{ key: 'azione', label: 'Azione', allowHTML: true}
				];
			cols = cols.concat(cols1);
		}
	} else {
		var cols1 = [
				{ key: 'course_title', label: 'Titolo corso'},
				{ key: 'course_year', label: 'Anno' },
				{ key: 'editionum', label: 'Edizione', allowHTML: true},
                                { key: 'sede', label: 'Sede'},
				{ key: 'indirizzo', label: 'Indirizzo'},
				{ key: 'edition_timestart', label: 'Data inizio'},
				{ key: 'edition_timefinish', label: 'Data fine'},
				{ key: 'azione', label: 'Azione', allowHTML: true}
			];
			cols = cols.concat(cols1);
	}
	
	// treeble config to be set on root datasource

	if (ctype == 'C_PROG') {
		if (isDir == true) {
			var schema =
			{
				resultFields:
				[
					'course_code','course_title','course_year','session_name','editionum','sede',
					'indirizzo','edition_timestart','edition_timefinish','edition_seats_reserved','edition_seats_booked','azione','_open',
					{key: 'kiddies', parser: 'treebledatasource'}
				]
			};
		} else {
			var schema =
			{
				resultFields:
				[
					'course_code','course_title','course_year','session_name','editionum','sede',
					'indirizzo','edition_timestart','edition_timefinish','azione','_open',
					{key: 'kiddies', parser: 'treebledatasource'}
				]
			};
		}
	} else {
		var schema =
		{
			resultFields:
			[
				'course_code','course_title','editionum','sede','indirizzo','edition_timestart','edition_timefinish','azione','_open',
				{key: 'kiddies', parser: 'treebledatasource'}
			]
		};
	}
	

	var schema_plugin_config =
	{
		fn:  Y.Plugin.DataSourceArraySchema,
		cfg: {schema:schema}
	};

	var treeble_config =
	{
		generateRequest:        function() { },
		schemaPluginConfig:     schema_plugin_config,
		childNodesKey:          'kiddies',
		nodeOpenKey:            '_open',
		totalRecordsReturnExpr: '.meta.totalRecords'
	};

	// root data source

	var root            = new Y.DataSource.Local({source: data});
	root.treeble_config = Y.clone(treeble_config, true);
	root.plug(schema_plugin_config);

	// TreebleDataSource

	var ds = new Y.DataSource.Treeble(
	{
		root:             root,
		paginateChildren: false,
		uniqueIdKey:      'course_code'	// normally, it would a database row id, but title happens to be unique in this example
	});

	// Paginator

	var pg = new Y.Paginator(
	{
		totalRecords: 1,
		rowsPerPage: 10,
		rowsPerPageOptions: [1,2,5,10,25,50],
		template: '{FirstPageLink} {PreviousPageLink} {PageLinks} {NextPageLink} {LastPageLink} <span class="pg-rpp-label">Rows per page:</span> {RowsPerPageDropdown}'
	});
	pg.render('#pg');

	pg.on('changeRequest', function(state)
	{
		this.setPage(state.page, true);
		this.setRowsPerPage(state.rowsPerPage, true);
		this.setTotalRecords(state.totalRecords, true);
		sendRequest();
	});

	ds.on('response', function(e)
	{
		pg.setTotalRecords(e.response.meta.totalRecords, true);
		pg.render();
	});

	// DataTable

	var table = new Y.Treeble({columns: cols});
	table.plug(Y.Plugin.DataTableDataSource, {datasource: ds});

	table.render("#treeble");

	sendRequest();
});