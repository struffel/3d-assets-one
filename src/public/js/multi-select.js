// Make select elements toggleable.
document.querySelectorAll('select option').forEach(function (element) {
	element.addEventListener("mousedown", 
		function (e) {
			e.preventDefault();
			element.parentElement.focus();
			this.selected = !this.selected;
			document.getElementById('asset-filters-form').dispatchEvent(new Event('change'));
			return false;
		}, false );
});
