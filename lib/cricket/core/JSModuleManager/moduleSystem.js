/*

This JS module "loader" code is based* on AMD and the Node.js module approaches.
It is designed to allow modular coding in the front end without concern for the back end.
(See: http://wiki.commonjs.org/wiki/Modules/AsynchronousDefinition)
(See: https://nodejs.org/dist/latest-v5.x/docs/api/modules.html)

*In no way do I imply compliance with any API or standard. This is a very proprietary solution.


One of the main differences here is that I force require() calls to pass document where it is used.
This pattern should allow us to reuse code on a Node.js back end if we ever need to.
(document objects can be passed around)

Even if we never do that, it still helps prevent the DOM from being used where it shouldn't,
in theory, by making the coder aware of which modules use it


Author: Brydon DeWitt

*/

// Add define & require functionality at the global scope (if it doesn't already exist)
if (!(define && require)) {
	var define, require;
	
	(function() {
		
		// Private
		var modules = {};
		
		function Module(nameStr, closure, domAccess) {
			this.name      = nameStr;
			this.run       = closure;
			this.domAccess = domAccess || false;
		}
		
		// Public
		define = function(nameStr, closure, domAccess) {
			modules[nameStr] || (modules[nameStr] = new Module(nameStr, closure, domAccess));
		};
		
		require = function(requireStr, optionalBool) {

            var moduleName, module, documentObj, windowObj;

			moduleName = requireStr;
			module     = modules[moduleName];
			
			if (!module) {

                if(!optionalBool)
				    throw ReferenceError('Module "' + moduleName + '" not found');

				return undefined;
			}

            documentObj  = (module.domAccess ? document : undefined);
            windowObj    = (documentObj ? window : undefined); // pass in window with document

			if (!module.exports) {

				// When a module is run, we feed it three environment "parameters" - module, window, document
				// The latter two are undefined unless document is being passed in - Ex. require(requireStr, document)  
				module.run(module, windowObj, documentObj);
				
				delete module.run;
			}
			return module.exports;
		};
		
	})();
}