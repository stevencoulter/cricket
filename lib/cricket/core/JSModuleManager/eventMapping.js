'use strict';

class EventMapping {
    constructor(elHook, event, component, handlerName) {
        this.parentEl = null;
        this.elHook = elHook;
        this.event = event;
        this.handler = (e) => {
            var dataset = {};
            if(this.parentEl) dataset = this.parentEl.dataset;
            return component[handlerName](e, dataset);
        };
    }
}

module.exports = EventMapping;