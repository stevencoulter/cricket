
/*
 * (C) Copyright 2014 Bill Hubauer <bill@hubauer.com>
 *
 * This file is part of Cricket  https://github.com/bhubauer/cricket
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of the
 * GNU Lesser General Public License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this library;
 * if not, visit http://www.gnu.org/licenses/lgpl-2.1.html or write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */


var _CRICKET_PAGE_INSTANCE_ = null;

var CRICKET_BUSY_INDICATOR = 'cricket_busy';
var CRICKET_AUTO_BUSY = false;
var CRICKET_ACTIVE_CHANNELS = {};

/**
 * Object Extending Functionality
 */
var extend = function() {
    var extended = {};

    for(key in arguments) {
        var argument = arguments[key];
        for (prop in argument) {
            if (Object.prototype.hasOwnProperty.call(argument, prop)) {
                extended[prop] = argument[prop];
            }
        }
    }

    return extended;
};

function cricket_abort_ajax(inRequest) {
    if(inRequest) {
        inRequest.abort();
    }
}

// if inRequestChannel, the if there is any active ajax for this channel, it is aborted prior to issuing the new one

function cricket_ajax(inURL, inData, inIndicatorID, inConfirmation, inRequestChannel, inFailSilent) {

    if(inFailSilent == null) {
        inFailSilent = false;
    }

    if(inRequestChannel) {
        if(inRequestChannel in CRICKET_ACTIVE_CHANNELS) {
            cricket_abort_ajax(CRICKET_ACTIVE_CHANNELS[inRequestChannel]);
            delete CRICKET_ACTIVE_CHANNELS[inRequestChannel];
        }
    }


    var result = null;

    if(!inIndicatorID) {
        if(CRICKET_AUTO_BUSY) {
            inIndicatorID = CRICKET_BUSY_INDICATOR;
        }
    }


    if(!_CRICKET_PAGE_INSTANCE_){
        alert("Configuration error. _CRICKET_PAGE_INSTANCE_ not set");
        return;
    }

    // add page instance
    inData = extend({'_CRICKET_PAGE_INSTANCE_':_CRICKET_PAGE_INSTANCE_}, inData);

    if(!inConfirmation || confirm(inConfirmation)) {
        // TODO: Activate this
        if(inIndicatorID) {
            if(cricket_indicator_active(inIndicatorID)) {
                return;
            }
            cricket_indicator_start(inIndicatorID);
        }

        var r = new XMLHttpRequest();
        r.open("POST", inURL, true);
        r.setRequestHeader("x-cricket-ajax", "true");
        r.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        r.onreadystatechange = function () {

            if(r.readyState !== 4) return;

            // On Error
            if (r.status > 400) {
                if (!inFailSilent) {
                    document.getElementsByTagName("html").innerHTML = r.responseText;
                }
            }

            if (r.status !== 0) {
                var data = JSON.parse(r.responseText);

                if (data.redirect) {
                    document.location.href = data.redirect;
                    return;
                }

                if (data.message) {
                    alert(data.message);
                }

                for (var z = 0; z < data.scripts_pre.length; z++) {
                    eval(data.scripts_pre[z]);
                }

                for (var key in data.updates) {
                    if (data.replacements[key]) {
                        var el = document.createElement('div');
                        el.innerHTML = data.updates[key];
                        var targetEl = document.getElementById(key);
                        targetEl.parentNode.replaceChild(el.children[0], targetEl);
                    } else {
                        document.getElementById(key).innerHTML = data.updates[key];
                    }
                }

                for (var key in data.append) {
                    document.getElementById(key).append(data.append[key]);
                }

                for (var z = 0; z < data.scripts_post.length; z++) {
                    eval(data.scripts_post[z]);
                }

                if (data.sounds.length) {
                    if (window.HTMLAudioElement) {
                        for (var key in data.sounds) {
                            var audio = new Audio(data.sounds[key]);
                            audio.play();
                        }
                    }
                }

                // Final Step
                if(inRequestChannel) {
                    delete CRICKET_ACTIVE_CHANNELS[inRequestChannel];
                }

                if(inIndicatorID) cricket_indicator_stop(inIndicatorID);
            }
        };
        var post = inData;

        if (inData instanceof Object)
            post = Object.keys(inData).map(function(k) { return k + '=' + encodeURIComponent(inData[k]) }).join('&');


        r.send(post);
        //r.send(JSON.stringify(inData));

        if(inRequestChannel) {
            CRICKET_ACTIVE_CHANNELS[inRequestChannel] = result;
        }
    }
}

var CRICKET_SELECTION = {};
function cricket_click_row(inTableID, inSelectURL, inRowID, inItemID, inUseID) {
    if(CRICKET_SELECTION[inTableID]) {
        document.getElementById(CRICKET_SELECTION[inTableID]).classList.remove("selected");
    }

    document.getElementById(inRowID).classList.add("selected");
    CRICKET_SELECTION[inTableID] = inRowID;

    var indicator = "ind_select_row";
    if (inUseID) {
        indicator += "_" + inItemID;
    }

    cricket_ajax(inSelectURL, {id: inItemID, row_id: inRowID}, indicator);
}

function cricket_clear_selection(inTableID) {
    if(CRICKET_SELECTION[inTableID]) {
        document.getElementById(CRICKET_SELECTION[inTableID]).classList.remove("selected");
        delete CRICKET_SELECTION[inTableID];
    }
}

function cricket_isArray(obj) {
    if (typeof arguments[0] == 'object') {
        var criterion = arguments[0].constructor.toString().match(/array/i);
        return (criterion != null);
    }
    return false;
}

function cricket_ajax_form(inFormOrChild, inSubmitURL, inInd, inConfirmation, inRequestChannel, inFailSilent) {
    var values = cricket_serialize_form(inFormOrChild);
    cricket_ajax(inSubmitURL, values, inInd, inConfirmation, inRequestChannel, inFailSilent);
}

function cricket_serialize_form(inFormOrChild) {
    var
        form = inFormOrChild,
        formControls,
        formData = {}

        // These regex patterns were copied from jQuery v 2.1.1 (as were the techniques below)
        rsubmitterTypes = /^(?:submit|button|image|reset|file)$/i,
        rsubmittable = /^(?:input|select|textarea|keygen)/i,
        rcheckableType = (/^(?:checkbox|radio)$/i)
    ;

    // TODO: Fix this
    if(form.nodeName.toLowerCase() != 'form') {
        form = jQuery(form).parents('form').get();
    }

    // Get all elements with a name attribute (as an array)
    formControls = [].map.call(form.querySelectorAll('*[name]'), function(el) {
        return el;
    });

    // Filter out any controls that we don't want to get the value from
    formControls = formControls.filter(function(el) {
        return rsubmittable.test(el.nodeName) && !rsubmitterTypes.test(el.type) &&
            ( this.checked || !rcheckableType.test( el.type ) );
    });

    // Add data to the formData object
    formControls.forEach(function(control) {
        formData[control.name] = control.value;
    });

    return formData;
}

var cricket_indicators = {};

var cricket_indicator_timeout = 250;

function cricket_indicator_start(inID, inRestart, inTimeout) {
    if(inRestart == null) {
        inRestart = false;
    }

    if(inTimeout == null) {
        inTimeout = cricket_indicator_timeout;
    }

    var rec = cricket_indicators[inID];
    if(!rec) {
        rec = {
            level: 0,
            timer: 0
        };

        cricket_indicators[inID] = rec;
    }else{
        rec.level = 1;
        cricket_indicator_stop(inID);
    }

    rec.level++;
    if(rec.level == 1) {
        rec.timer = setTimeout(function(){
            rec.timer = 0;

            if(inID == CRICKET_BUSY_INDICATOR) {
                document.getElementsByTagName('body')[0].classList.add('cricket_busy');
            }else{
                var i = document.getElementById(inID);
                if(i) {
                    i.style.visibility = "visible";
                }
            }
        },inTimeout);
    }
}

function cricket_indicator_active(inID) {
    var rec = cricket_indicators[inID];
    if(!rec) {
        return false;
    }

    return rec.level > 0;
}

function cricket_indicator_stop(inID) {
    var rec = cricket_indicators[inID];
    if(!rec) {
        return false;
    }
    rec.level--;
    if(rec.level == 0) {
        if(rec.timer != 0) {
            clearTimeout(rec.timer);
        }
        if(inID == CRICKET_BUSY_INDICATOR) {
            document.getElementsByTagName('body').classList.remove('cricket_busy');
        }else{
            var i = document.getElementById(inID);
            if(i) {
                i.style.visibility = "hidden";
            }
        }
    }
}

// ************************************************ Cricket Revealing Module
class Cricket {
    constructor(inPageInstance) {
        this.pageInstance = inPageInstance;
        this.indicator_timeout = cricket_indicator_timeout;
        this.indicators = cricket_indicators;
    }

    get pageInstance() {
        return _CRICKET_PAGE_INSTANCE_;
    }
    set pageInstance(value) {
        _CRICKET_PAGE_INSTANCE_ = value;
    }

    abort_ajax(inRequest) {
        cricket_abort_ajax(inRequest);
    }
    ajax(inURL, inData, inIndicatorID, inConfirmation, inRequestChannel, inFailSilent) {
        cricket_ajax(inURL, inData, inIndicatorID, inConfirmation, inRequestChannel, inFailSilent);
    }
    ajax_form(inFormOrChild, inSubmitURL, inInd, inConfirmation, inRequestChannel, inFailSilent) {
        cricket_ajax_form(inFormOrChild, inSubmitURL, inInd, inConfirmation, inRequestChannel, inFailSilent)
    }
    clear_selection(inTableID) {
        cricket_clear_selection(inTableID);
    }
    click_row(inTableID, inSelectURL, inRowID, inItemID, inUseID) {
        cricket_click_row(inTableID, inSelectURL, inRowID, inItemID, inUseID);
    }
    indicator_active(inID) {
        cricket_indicator_active(inID);
    }
    indicator_start(inID, inRestart, inTimeout) {
        cricket_indicator_start(inID, inRestart, inTimeout);
    }
    indicator_stop(inID) {
        cricket_indicator_stop(inID);
    }
    isArray(obj) {
        cricket_isArray(obj);
    }
    serialize_form(inFormOrChild) {
        cricket_serialize_form(inFormOrChild);
    }
}

module.exports = new Cricket();