'use strict';

var components = {},
	pendingParent = {}
;

/*

if parentId is not null, add to a pending list. Remove that entry in the pending list when the parent component is instantiated

*/

// Define Component constructor
function Component(id, parentId, actionURLs) {
	var _this = this, children, key, child;
	
	this.id = id;
	this.rootEl = document.getElementById(id);
	this.parent = components[parentId] || null; // gets set in addComponent() method
	this.components = {};
	this.actionURLs = actionURLs;
	
	// Try to get children from the pendingParent associative array
	children = pendingParent[id] || {};
	for (key in children) {
		if (!children.hasOwnProperty(key))
			continue;
		child = children[key];
		_this.addComponent(child);
	}
	this.components = children;
	
	if(parentId) {
		children = pendingParent[parentId] = pendingParent[parentId] || {};
		children[this.id] = this;
	}
	
	components[id] = this;
}

// Set prototype object on the Component constructor
Component.prototype = {
	getLocalId: function() {
		return this.id;
	},
	getParent: function() {
		return this.parent;
	},
	broadcastMessage: function(message, data) {
		var key, root;
		
		for (key in components) {
			
			// Determine if it is a root
			if (!components.hasOwnProperty(key) || components[key].parent)
				continue;
			root = components[key];
			root.broadcastMessageToTree(message, data, this);
		}
	},
	broadcastMessageToTree: function(message, data, sender) {
		var key, obj, component;
		
		this.receiveMessage(message, data, sender);
		
		obj = this.components;
		for (key in obj) {
			if (!obj.hasOwnProperty(key))
				continue;
			component = obj[key];
			component.broadcastMessageToTree(message, data, sender);
		}
	},
	receiveMessage: function(message, data, sender) {
		// override if you need to receive a message
	},
	addComponent: function(component) {
		if (component instanceof Component) {
			this.components[component.id] = component;
			component.parent = this;
		}
	},
	switchToMode: function(mode) {
		if(this.mode === mode) return;
		
		this.rootEl.classList.remove('mode-' + this.mode);
		this.rootEl.classList.add('mode-' + mode);
		
		this.mode = mode;
	},
	getSelector: function(hook) {
		return '*[data-js-hook="' + hook + '"]';
	},
	mapEventsWithin: function(mappings, parentHook) {
		let parentEls = this.rootEl.querySelectorAll(this.getSelector(parentHook));
		if(!parentEls) parentEls = [this.rootEl];

		for (let i = parentEls.length - 1; i >= 0; i--) {
			let parentEl = parentEls[i];

			for (let j = mappings.length - 1; j >= 0; j--) {
				this.mapEvent(mappings[j], parentEl);
			}
		}
	},
	mapEvent: function(mapping, parentEl) {
		parentEl = parentEl || this.rootEl;
		mapping.parentEl = parentEl;
		let el = parentEl.querySelector(this.getSelector(mapping.elHook));
		el && el.addEventListener(mapping.event, mapping.handler);
	}
};
Component.prototype.constructor = Component; // make instanceof work for "subclasses" (which only use prototype, not constructor)

// Export the Component constructor
module.exports = Component;