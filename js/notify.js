(function(){
	if (!window.Swal) { return; }
	var root = document.documentElement;
	function cssVar(name, fallback){
		try {
			var v = getComputedStyle(root).getPropertyValue(name).trim();
			return v || fallback;
		} catch(e){ return fallback; }
	}
	var COLOR = {
		aqua: cssVar('--color-aqua', '#7ab4a1'),
		orange: cssVar('--color-orange', '#e08f5f'),
		pink: cssVar('--color-pink', '#e19985'),
		gray: cssVar('--sbh-medium-gray', '#6c757d')
	};

	// Global default wrapper for Swal.fire to inject theme defaults if not provided
	var __origFire = window.Swal.fire.bind(window.Swal);
	window.Swal.fire = function(opts){
		opts = opts || {};
		var isToast = !!opts.toast;
		var themed = Object.assign({
			confirmButtonColor: COLOR.aqua,
			cancelButtonColor: COLOR.gray,
			buttonsStyling: true,
			showClass: { popup: 'swal2-show' },
			hideClass: { popup: 'swal2-hide' }
		}, opts);
		if (isToast) {
			if (typeof themed.position === 'undefined') themed.position = 'top-end';
			if (typeof themed.timer === 'undefined') themed.timer = 2500;
			if (typeof themed.showConfirmButton === 'undefined') themed.showConfirmButton = false;
			if (typeof themed.timerProgressBar === 'undefined') themed.timerProgressBar = true;
		}
		return __origFire(themed);
	};

	// Lightweight helpers
	window.SBHNotify = {
		success: function(title, opts){ return Swal.fire(Object.assign({ icon:'success', title: title||'Success', toast:true }, opts)); },
		error: function(title, opts){ return Swal.fire(Object.assign({ icon:'error', title: title||'Error', toast:true }, opts)); },
		info: function(title, opts){ return Swal.fire(Object.assign({ icon:'info', title: title||'Info', toast:true }, opts)); },
		warning: function(title, opts){ return Swal.fire(Object.assign({ icon:'warning', title: title||'Warning', toast:true }, opts)); },
		confirm: function(title, text, opts){
			return Swal.fire(Object.assign({
				title: title||'Are you sure?',
				text: text||'',
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: 'Confirm',
				cancelButtonText: 'Cancel'
			}, opts));
		}
	};
})();


