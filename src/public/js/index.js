function toggleOption(element) {
	element.parentElement.focus();
	element.selected = !element.selected;
	document.getElementById('asset-filters-form').dispatchEvent(new Event('change'));
	return false;
}