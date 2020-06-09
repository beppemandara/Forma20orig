/*
		      Calendar

		  Author: Pasquale Di Stasio
		  E-Mail: info@pasqualedistasio.com
		Web-Site: www.pasqualedistasio.com

		Released: 2010-05-02
		 Version: 2.2
*/


Calendar = {
	startDay: 1, // 0 => Sunday, 1 => Monday
	days: new Array("Domenica", "Lunedì","Martedì","Mercoledì","Giovedì","Venerdì", "Sabato"),
	months: new Array("Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"),
	path: 'calendar/',

	sDay: null,
	sMonth: null,
	sYear: null,
	cMonth: null,
	cyear: null,
	tYear: null,
	wTime: false,
	sHour: null,
	sMinute: null,
	sSecond: null,
	format: null,
	input: null,
	container: null,
	main: null,
	vDate: null,
	vTime: null,
	vDateImg: null,
	ym: null,
	labelYear: null,
	labelHours: null,
	labelMinutes: null,
	labelSeconds: null,
	timer: null,
	isOver: false,
	excluded: [],
	IE: (window.attachEvent && !window.opera),
	Opera: window.opera,
	WebKit: (navigator.userAgent.indexOf('AppleWebKit/') > -1),
	Gecko: (navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') == -1),

	createElement: function(tag, attr, css, txt) {
		var obj = document.createElement(tag);
		if (attr != null && attr != undefined && attr != '') {
			for (var i in attr) {
				obj[i] = attr[i];
			}
		}
		if (css != null && css != undefined && css != '') {
			for (var i in css) {
				obj.style[(i == 'float' || i == 'cssFloat' || i == 'styleFloat') ? ((obj.style.styleFloat == undefined) ? 'cssFloat' : 'styleFloat') : i] = css[i];
			}
		}
		if (txt != null && txt != undefined && txt != '') {
			obj.innerHTML = txt;
		}
		obj.observe = function(eventName, handler) {
			Calendar.addEvent(this, eventName, handler);
		}
		obj.setStyle = function(css) {
			for (var i in css) {
				this.style[(i == 'float' || i == 'cssFloat' || i == 'styleFloat') ? ((this.style.styleFloat == undefined) ? 'cssFloat' : 'styleFloat') : i] = css[i];
			}
		}
		obj.setOpacity = function(value) {
			if (Calendar.IE) {
				var filter = this.style.filter;
				var noAlpha = filter.replace(/alpha\([^\)]*\)/gi,'');
				if (value == 1 || value == '') {
					this.style.filter = noAlpha ? noAlpha : '';
				} else {
					this.style.filter = noAlpha + 'alpha(opacity=' + (value * 100) + ')';
				}
			} else {
				this.style.opacity = (value == 1 || value === '') ? '' : (value < 0) ? 0 : value;
			}
		}
		obj.visible = function() {
			return (this.style.display == 'none') ? false : true;
		}
		obj.show = function() {
			this.style.display = '';
		}
		obj.hide = function() {
			this.style.display = 'none';
		}
		return obj;
	},

	addEvent: function(obj, eventName, handler, addEvent) {
		if (obj.attachEvent) {
			obj['e' + eventName + handler] = handler;
			obj[eventName + handler] = function(){obj['e' + eventName + handler]( window.event );}
			obj.attachEvent('on' + eventName, obj[eventName + handler]);
		} else {
			obj.addEventListener(eventName, handler, false);
		}
	},

	removeEvent: function(obj, eventName, handler) {
		if (obj.detachEvent) {
			obj.detachEvent('on' + eventName, obj[eventName + handler]);
			obj[eventName + handler] = null;
		} else {
			obj.removeEventListener(eventName, handler, false);
		}
	},

	targetElement: function(e) {
		var targ;
		if (e.target) {
			targ = e.target;
		} else if (e.srcElement) {
			targ = e.srcElement;
		}
		if (targ.nodeType == 3) targ = targ.parentNode;
		return targ;
	},

	create: function() {
		
		// Calendar.path = '/moodle_csi/f2_lib/calendar/';
		// alert(Calendar.path);
		// alert(document.location.pathname);
		var pathn = document.location.pathname.substring(1);
//		alert(M.cfg.wwwroot);
		var moodle = pathn.substring(0,pathn.indexOf('/'));
//		Calendar.path = '/' + moodle+ '/f2_lib/calendar/';
		Calendar.path = M.cfg.wwwroot + '/f2_lib/calendar/';
//		 alert(Calendar.path);
		var fStyle = document.createElement("link")
		fStyle.setAttribute("rel", "stylesheet")
		fStyle.setAttribute("type", "text/css")
		fStyle.setAttribute("href", Calendar.path + 'style.css')
		document.getElementsByTagName("head")[0].appendChild(fStyle);

		Calendar.container = Calendar.createElement('div', {className: 'calendar'}, {position: 'absolute', zIndex: 9999, display: 'none'});
		Calendar.container.observe('mousedown', function(){Calendar.isOver = true});
		Calendar.container.observe('mouseup', function(){Calendar.isOver = false});
		Calendar.container.observe('mouseout', function(){Calendar.isOver = false});

		var header = Calendar.createElement('div', {className: 'header'});
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/prev.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.decMonth(); Calendar.timer = Calendar.periodicalFunciton(Calendar.decMonth, 0.2)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1);if (Calendar.timer != null) Calendar.timer.test()}.__C(e));
		header.appendChild(e);

		var e = Calendar.createElement('img', {src: Calendar.path + 'images/next.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.incMonth(); Calendar.timer = Calendar.periodicalFunciton(Calendar.incMonth, 0.2)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		header.appendChild(e);

		Calendar.vDate = Calendar.createElement('div', {className: 'date'}, {cssFloat: 'left'});
		header.appendChild(Calendar.vDate);
		Calendar.vDate.observe('mouseover', function(e) {e.setOpacity(0.6);}.__C(Calendar.vDate));
		Calendar.vDate.observe('mouseout', function(e) {if (!Calendar.ym.visible()) e.setOpacity(1);}.__C(Calendar.vDate));
		Calendar.vDate.observe('click', Calendar.toggleYM);
		header.appendChild(Calendar.vDate);

		var e = Calendar.createElement('input', {type: 'image', src: Calendar.path + 'images/close.gif'}, {cssFloat: 'right', marginTop: '3px', marginRight: '2px'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mouseout', function(e) {e.setOpacity(1);}.__C(e));
		e.observe('click', Calendar.forceHide.__C(true));
		header.appendChild(e);

		header.appendChild(Calendar.createElement('br', {clear: 'all'}));
		Calendar.container.appendChild(header);
		Calendar.main = Calendar.createElement('div', {className: 'main'});
		Calendar.container.appendChild(Calendar.main);

		Calendar.vTime = Calendar.createElement('div', {className: 'time'});
		var tContainer = Calendar.createElement('div');
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/left.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.decHour(); Calendar.timer = Calendar.periodicalFunciton(Calendar.decHour, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		tContainer.appendChild(e);
		Calendar.labelHours = Calendar.createElement('div', null, {cssFloat: 'left'}, '&nbsp;');
		tContainer.appendChild(Calendar.labelHours);
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/right.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.incHour(); Calendar.timer = Calendar.periodicalFunciton(Calendar.incHour, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		tContainer.appendChild(e);
		tContainer.appendChild(Calendar.createElement('span', null, {cssFloat: 'left'}, '&nbsp;'));
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/left.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.decMinute(); Calendar.timer = Calendar.periodicalFunciton(Calendar.decMinute, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		tContainer.appendChild(e);
		Calendar.labelMinutes = Calendar.createElement('div', null, {cssFloat: 'left'}, '&nbsp;');
		tContainer.appendChild(Calendar.labelMinutes);
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/right.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.incMinute(); Calendar.timer = Calendar.periodicalFunciton(Calendar.incMinute, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		tContainer.appendChild(e);
		tContainer.appendChild(Calendar.createElement('span', null, {cssFloat: 'left'}, '&nbsp;'));
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/left.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.decSecond(); Calendar.timer = Calendar.periodicalFunciton(Calendar.decSecond, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		tContainer.appendChild(e);
		Calendar.labelSeconds = Calendar.createElement('div', null, {cssFloat: 'left'}, '&nbsp;');
		tContainer.appendChild(Calendar.labelSeconds);
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/right.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.incSecond(); Calendar.timer = Calendar.periodicalFunciton(Calendar.incSecond, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		tContainer.appendChild(e);
		tContainer.appendChild(Calendar.createElement('br', {clear: 'all'}));
		Calendar.vTime.appendChild(tContainer);
		Calendar.container.appendChild(Calendar.vTime);

		var today = new Date();
		w = today.getDay();
		d = today.getDate();
		m = today.getMonth();
		y = today.getFullYear();
		var footer = Calendar.createElement('div', {className: 'footer'});
		var e = Calendar.createElement('div');
		e.innerHTML = Calendar.days[w].toLowerCase() + ', ' + d + ' ' + Calendar.months[m].toLowerCase() + ' ' + y;
		e.observe('click', Calendar.selectDate.__C(y, m, d));
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mouseout', function(e) {e.setOpacity(1);}.__C(e));
		footer.appendChild(e);
		Calendar.container.appendChild(footer);

		Calendar.ym = Calendar.createElement('div', {className: 'inc_dec'}, {position: 'absolute', zIndex: 9993, display: 'none', left: '42px', top: '18px'});
		var tbl = Calendar.createElement('table');
		var tBody = Calendar.createElement('tbody');
		tbl.appendChild(tBody);
		var row = Calendar.createElement('tr');
		tBody.appendChild(row);
		var cell = Calendar.createElement('th');
		row.appendChild(cell);
		cell.colSpan = 3;

		var e = Calendar.createElement('img', {src: Calendar.path + 'images/prev.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.decYear(); Calendar.timer = Calendar.periodicalFunciton(Calendar.decYear, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		cell.appendChild(e);

		Calendar.labelYear = Calendar.createElement('div', {className: 'years'}, {cssFloat: 'left'});
		cell.appendChild(Calendar.labelYear);
		var e = Calendar.createElement('img', {src: Calendar.path + 'images/next.gif'}, {cssFloat: 'left', cursor: 'pointer'});
		e.observe('mouseover', function(e) {e.setOpacity(0.5);}.__C(e));
		e.observe('mousedown', function() {Calendar.incYear(); Calendar.timer = Calendar.periodicalFunciton(Calendar.incYear, 0.1)});
		e.observe('mouseup', function() {if (Calendar.timer != null) Calendar.timer.stop()});
		e.observe('mouseout', function(e) {e.setOpacity(1); if (Calendar.timer != null) Calendar.timer.stop()}.__C(e));
		cell.appendChild(e);

		for (i = 0; i < 12; i++) {
			if (i % 3 == 0) {
				row = Calendar.createElement('tr');
				tBody.appendChild(row);
			}
			var cell = Calendar.createElement('td');
			cell.observe('click', Calendar.selectYM.__C(i));
			cell.observe('mouseover', function(e){e.className = 'month_on'}.__C(cell));
			cell.observe('mouseout', function(e){e.className = e.classNameBK}.__C(cell));
			cell.innerHTML = Calendar.months[i].substr(0, 3);
			row.appendChild(cell);
		}
		Calendar.ym.appendChild(tbl);
		Calendar.container.appendChild(Calendar.ym);

		document.body.appendChild(Calendar.container);

		if (Calendar.startDay == 1) {
			var tmp = Calendar.days[0];
			Calendar.days.shift();
			Calendar.days.push(tmp);
		}

		Calendar.shortDays = [];
		for (var i = 0, n = Calendar.days.length; i < n; i++) {
			Calendar.shortDays[i] = Calendar.days[i].substr(0, 3);
		}
		Calendar.xDays = new Array("dom", "lun","mar","mer","gio","ven", "sab");
	},
	
	isDisabled: function(d, m, y, p) {
		if (Calendar.excluded.length > 0) {
			var tmp, tmpDate, dName;
			for (var i = 0, n = Calendar.excluded.length; i < n; i++) {
				tmp = Calendar.excluded[i].substr(0,1);
				if (tmp == '<') {
					tmp = Calendar.excluded[i].substr(1).split('-');
					tmpDate = new Date(tmp[0], tmp[1] - 1, tmp[2]);
					if ((new Date(y, m - 1, d)).getTime() < tmpDate.getTime()) {
						return true;
					}
				} else if (tmp == '>') {
					tmp = Calendar.excluded[i].substr(1).split('-');
					tmpDate = new Date(tmp[0], tmp[1] - 1, tmp[2]);
					if ((new Date(y, m - 1, d)).getTime() > tmpDate.getTime()) {
						return true;
					}
				} else {
					tmp = Calendar.excluded[i].split('-');
					if (tmp[0] == "" || tmp[0] == y) {
						if (tmp[1] == "" || tmp[1] == m) {
							if (isNaN(tmp[2])) {
								dName = tmp[2].toLowerCase();
								for (var ii = 0, nn = Calendar.xDays.length; ii < nn; ii++) {
									if (Calendar.xDays[ii] == dName) {
										break;
									}
								}
								if ((p + Calendar.startDay) % 7 == ii) {
									return true;
								}
							} else {
								if (tmp[2] == "" || tmp[2] == d) {
									return true;
								}
							}
						}
					}
				}
			}
		}
		return false;
	},

	constructCalendar: function() {
		var nDaysInMonth;
		if (Calendar.cMonth == 1) {
			var cDate = new Date(new Date(Calendar.cYear, Calendar.cMonth + 1, 1) - 86400000);
			nDaysInMonth = cDate.getDate();
		} else {
			var nDays = Array(31,0,31,30,31,30,31,31,30,31,30,31);
			nDaysInMonth = nDays[Calendar.cMonth];
		}
		var startDate = new Date(Calendar.cYear, Calendar.cMonth, 1);
		var dayPointer = startDate.getDay() - Calendar.startDay;
		if (dayPointer < 0) dayPointer = 6;

		var tbl = Calendar.createElement('table');
		var tBody = Calendar.createElement('tBody');
		tbl.appendChild(tBody);
		var row = Calendar.createElement('tr');
		tBody.appendChild(row);
		for (i = 0; i < 7; i++) {
			var cell = Calendar.createElement('th');
			cell.innerHTML = Calendar.shortDays[i];
			row.appendChild(cell);
		}
		var row = Calendar.createElement('tr');
		tBody.appendChild(row);
		for (i = 0; i < dayPointer; i++) {
			var cell = Calendar.createElement('td');
			cell.innerHTML = '&nbsp;';
			row.appendChild(cell);
		}
		for (var i = 1; i <= nDaysInMonth; i++) {
			var cell = Calendar.createElement('td');
			if (Calendar.isDisabled(i, Calendar.cMonth + 1, Calendar.cYear, dayPointer)) {
				cell.className = 'day_disabled';
			} else  {
				cell.observe('click', Calendar.selectDate.__C(Calendar.cYear, Calendar.cMonth, i));
				cell.observe('mouseover', function(e){e.className = 'day_on'}.__C(cell));
				if(Calendar.sDay == i && Calendar.sMonth == Calendar.cMonth && Calendar.sYear == Calendar.cYear) {
					cell.className = 'day_selected';
					cell.observe('mouseout', function(e){e.className = 'day_selected'}.__C(cell));
				} else {
					cell.observe('mouseout', function(e){e.className = ''}.__C(cell));
				}
			}
			//return;
			cell.innerHTML = i
			row.appendChild(cell);
			if ((++dayPointer + Calendar.startDay) % 7 == Calendar.startDay && i < nDaysInMonth) {
				row = Calendar.createElement('tr');
				tBody.appendChild(row);
			}
		}
		Calendar.main.innerHTML = '';
		Calendar.main.appendChild(tbl);
		Calendar.vDate.innerHTML = Calendar.months[Calendar.cMonth] + ' ' + Calendar.cYear;
		
		Calendar.updateMonths()
	},
	decHour: function() {
		var v = --Calendar.sHour;
		if (v < 0) {
			Calendar.sHour = v = 23;
		} else if (v > 23) {
			Calendar.sHour = v = 0;
		}
		Calendar.labelHours.innerHTML = Calendar.padZero(v);
	},
	decMinute: function() {
		var v = --Calendar.sMinute;
		if (v < 0) {
			Calendar.sMinute = v = 59;
		} else if (v > 59) {
			Calendar.sMinute = v = 0;
		}
		Calendar.labelMinutes.innerHTML = Calendar.padZero(v);
	},
	decMonth: function() {
		--Calendar.cMonth;
		if (Calendar.cMonth < 0) {
			Calendar.cMonth = 11;
			--Calendar.cYear;
		}
		if (Calendar.ym.visible()) Calendar.hideYM();
		Calendar.constructCalendar();
	},
	decSecond: function() {
		var v = --Calendar.sSecond;
		if (v < 0) {
			Calendar.sSecond = v = 59;
		} else if (v > 59) {
			Calendar.sSecond = v = 0;
		}
		Calendar.labelSeconds.innerHTML = Calendar.padZero(v);
	},
	decYear: function() {
		Calendar.labelYear.innerHTML = --Calendar.tYear;
		Calendar.updateMonths();
	},
	fire: function(element, eventName) {
		if (element == document && document.createEvent && !element.dispatchEvent) element = document.documentElement;
		var event;
		if (document.createEvent) {
			event = document.createEvent('HTMLEvents');
			event.initEvent(eventName, true, true);
			element.dispatchEvent(event);
		} else {
			event = document.createEventObject();
			element.fireEvent('on' + eventName, event);
		}
	},
	forceHide: function(wFocus) {
		Calendar.removeEvent(document, 'mousedown', Calendar.hide.__C());
		if (wFocus) {
			var tmp = Calendar.input.onfocus;
			Calendar.input.onfocus = null;
			Calendar.input.focus();
			setTimeout('Calendar.input.onfocus=' + tmp + ';Calendar.input=null;', 10);
		} else {
			Calendar.input = null;
		}
		Calendar.sDay = null;
		Calendar.sMonth = null;
		Calendar.sYear = null;
		Calendar.cMonth = null;
		Calendar.cyear = null;
		Calendar.tYear = null;
		Calendar.isOver = false;
		Calendar.hideYM();
		Calendar.container.hide();
	},
	getDaySuffix: function(d) {
		switch (d) {
			case 1:
				return 'st';
			case 2:
				return 'nd';
			case 3:
				return 'rd';
			case 21:
				return 'st';
			case 22:
				return 'nd';
			case 23:
				return 'rd';
			case 31:
				return 'st';
			default:
				return 'th';
		}
	},
	hide: function() {
		if (!Calendar.isOver && Calendar.container.visible()) Calendar.forceHide(false);
	},
	hideYM: function() {
		Calendar.ym.hide();
		Calendar.vDate.className = 'date';
		Calendar.vDate.setOpacity(1);
	},
	incHour: function() {
		var v = ++Calendar.sHour;
		if (v < 0) {
			Calendar.sHour = v = 23;
		} else if (v > 23) {
			Calendar.sHour = v = 0;
		}
		Calendar.labelHours.innerHTML = Calendar.padZero(v);
	},
	incMinute: function() {
		var v = ++Calendar.sMinute;
		if (v < 0) {
			Calendar.sMinute = v = 59;
		} else if (v > 59) {
			Calendar.sMinute = v = 0;
		}
		Calendar.labelMinutes.innerHTML = Calendar.padZero(v);
	},
	incMonth: function() {
		++Calendar.cMonth;
		if (Calendar.cMonth > 11) {
			Calendar.cMonth = 0;
			++Calendar.cYear;
		}
		if (Calendar.ym.visible()) Calendar.hideYM();
		Calendar.constructCalendar();
	},
	incSecond: function() {
		var v = ++Calendar.sSecond;
		if (v < 0) {
			Calendar.sSecond = v = 59;
		} else if (v > 59) {
			Calendar.sSecond = v = 0;
		}
		Calendar.labelSeconds.innerHTML = Calendar.padZero(v);
	},
	incYear: function() {
		Calendar.labelYear.innerHTML = ++Calendar.tYear;
		Calendar.updateMonths();
	},
	isNum: function(num) {
		return !isNaN(parseInt(num));
	},
	padZero: function padZero(num) {
		return (num < 10) ? '0' + num : num;
	},
	periodicalFunciton: function(fnc, frequency) {
		var t = setInterval(fnc, frequency * 1000);
		return {
			timerID: t,
			stop: function() {
				if (!this.timerID) return;
				clearInterval(this.timerID);
				Calendar.timer = null;
			}
		};
	},
	positionedOffset: function(element) {
		var valueT = 0;
		var valueL = 0;
		do {
			valueT += element.offsetTop || 0;
			valueL += element.offsetLeft || 0;
			element = element.offsetParent;
		} while (element);
		return {left: valueL, top: valueT};
	},

	selectDate: function(y, m, d) {
		var tmp = Calendar.format;
		tmp = tmp.replace('%d', Calendar.padZero(d));
		tmp = tmp.replace('%e', d);
		tmp = tmp.replace('%b', Calendar.months[m].substr(0,3));
		tmp = tmp.replace('%M', Calendar.months[m]);
		tmp = tmp.replace('%m', Calendar.padZero(parseInt(m) + 1));
		tmp = tmp.replace('%c', parseInt(m) + 1);
		tmp = tmp.replace('%Y', y);
		tmp = tmp.replace('%y', Calendar.padZero(y % 100));
		tmp = tmp.replace('%D', d + Calendar.getDaySuffix(d));
		if (Calendar.wTime) {
			tmp += ' ' + Calendar.padZero(Calendar.sHour) + ':' + Calendar.padZero(Calendar.sMinute) + ':' + Calendar.padZero(Calendar.sSecond);
		}
		var oldValue = Calendar.input.value;
		Calendar.input.value = tmp;
		if (tmp != oldValue) {
			Calendar.fire(Calendar.input, 'change');
		}
		Calendar.forceHide(true);
	},
	selectYM: function(m) {
		Calendar.cMonth = m;
		Calendar.cYear = Calendar.tYear;
		Calendar.constructCalendar();
		Calendar.hideYM();
	},
	show: function(input, format, wTime, excl, path) {
		if (Calendar.input !== null && (input !== Calendar.input || format !== Calendar.format || Boolean(wTime) !== Calendar.wTime)) {
			Calendar.forceHide(false);
		} else if (Calendar.container.visible()) {
			return false;
		}
		Calendar.excluded = excl || [];
		var today = new Date();
		Calendar.input = input;
		Calendar.format = format;
		Calendar.path = path;
		var cDate;
		if (wTime === true) {
			Calendar.wTime = true;
			Calendar.vTime.show();
			if (Calendar.input.value == '') {
				cDate = '';
				Calendar.sHour = Calendar.sMinute = Calendar.sSecond = '';
			} else {
				var tmp = Calendar.input.value.match(/ \d\d:\d\d:\d\d$/);
				cDate = Calendar.input.value.replace(tmp, '');
				var t = Calendar.input.value.split(' ');
				var t = t[t.length - 1].split(':');
				Calendar.sHour = (t[0] >= 0 && t[0] <= 24) ? parseInt(t[0]) : '';
				Calendar.sMinute = (t[1] >= 0 && t[1] <= 59) ? parseInt(t[1]) : '';
				Calendar.sSecond = (t[2] >= 0 && t[2] <= 59) ? parseInt(t[2]) : '';
			}
			if (!Calendar.isNum(Calendar.sHour) || !Calendar.isNum(Calendar.sMinute) || !Calendar.isNum(Calendar.sSecond)) {
				Calendar.sHour = today.getHours();
				Calendar.sMinute = today.getMinutes();
				Calendar.sSecond = today.getSeconds();
			}
		} else {
			Calendar.wTime = false;
			Calendar.vTime.hide();
			cDate = Calendar.input.value;
		}
		var y, m, d = '';
		var s = '/';
		var aFormat = Calendar.format.split(s);
		if (aFormat.length < 3) {
			s = ' ';
			aFormat = Calendar.format.split(s);
			if (aFormat.length < 3) {
				s = '.';
				aFormat = Calendar.format.split(s);
				if (aFormat.length < 3) {
					s = '-';
					aFormat = Calendar.format.split(s);
					if (aFormat.length < 3) {
						s = ',';
						aFormat = Calendar.format.split(s);
						if (aFormat.length < 3) {
							s = '';
						}
					}
				}
			}
		}
		if (cDate != '') {
			cDate = cDate.split(s);
			for (var i = 0; i < 3; i++){
				if (aFormat[i] == '%d' || aFormat[i] == '%D' || aFormat[i] == '%e') {
					d = parseInt(cDate[i], 10);
				} else if (aFormat[i] == '%m' || aFormat[i] == '%c') {
					m = parseInt(cDate[i], 10) - 1;
				} else if (aFormat[i] == '%M') {
					for (var j = 0; j < 12; j++) {
						if (cDate[i].toLowerCase() == Calendar.months[j].toLowerCase()) {
							m = j;
							break;
						}
					}
				} else if (aFormat[i] == '%b') {
					for (var j = 0; j < 12; j++) {
						var mShort = Calendar.months[j].toLowerCase().substr(0, 3);
						if (cData[i].toLowerCase() == mShort) {
							m = j;
							break;
						}
					}
				} else if (aFormat[i] == '%y') {
					y = 2000 + parseInt(cDate[i], 10);
				} else if (aFormat[i] == '%Y') {
					y = parseInt(cDate[i], 10);
				}
			}
		}
		if (!Calendar.isNum(d) || !Calendar.isNum(m) || !Calendar.isNum(y)) {
			Calendar.sDay = today.getDate();
			Calendar.sMonth = today.getMonth();
			Calendar.sYear = today.getFullYear();
		} else {
			Calendar.sDay = d;
			Calendar.sMonth = m;
			Calendar.sYear = y;
		}
		Calendar.cMonth = Calendar.sMonth;
		Calendar.cYear = Calendar.sYear;
		Calendar.tYear = Calendar.sYear;
		Calendar.constructCalendar();
		if (Calendar.wTime) {
			Calendar.labelHours.innerHTML = Calendar.padZero(Calendar.sHour);
			Calendar.labelMinutes.innerHTML = Calendar.padZero(Calendar.sMinute);
			Calendar.labelSeconds.innerHTML = Calendar.padZero(Calendar.sSecond);
		}
		var p = Calendar.positionedOffset(Calendar.input);
		Calendar.container.setStyle({left: p.left + 'px', top: (p.top + Calendar.input.offsetHeight + 1) + 'px'});
		Calendar.container.show();
		Calendar.addEvent(document, 'mousedown', Calendar.hide.__C());
	},
	toggleYM: function() {
		if (Calendar.ym.visible()) {
			Calendar.vDate.className = 'date';
			Calendar.ym.hide();
		} else {
			Calendar.vDate.className = 'date_up';
			Calendar.ym.show();
			Calendar.tYear = Calendar.cYear;
			Calendar.labelYear.innerHTML = Calendar.tYear;
		}
	},
	updateMonths: function() {
		var mCell = Calendar.ym.getElementsByTagName('table')[0].getElementsByTagName('td');
		for (var i = 0, n = mCell.length; i < n; i++) {
			if (i == Calendar.cMonth && Calendar.cYear == Calendar.tYear) {
				mCell[i].className = 'month_selected';
				mCell[i].classNameBK = 'month_selected';
			} else {
				mCell[i].className = '';
				mCell[i].classNameBK = '';
			}
		}
	
	}
}

__2A=function(v){if(!v)return [];var len=v.length||0,results=new Array(len);while(len--)results[len]=v[len];return results;}
Function.prototype.__C=function(){if(!arguments.length)return this;var __method=this,args=__2A(arguments);return function(){return __method.apply(this,args.concat(__2A(arguments)));}}

Calendar.addEvent(window, 'load', Calendar.create);

