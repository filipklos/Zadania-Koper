function setCookie(name, value, period = 30) {
	const d = new Date();
	d.setTime(d.getTime() + 86400000 * period);
	let expires = "expires=" + d.toUTCString();
	document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
	let l = name.length;
	let c = document.cookie;

	let start = c.indexOf(name);
	if (start < 0) return null;
	start += l + 1;
	let end = c.indexOf(';', start);

	if (end < 0) return c.substring(start);
	return c.substring(start, end);
}

function addEvent(query, eventName, event) {
	let elements = document.querySelectorAll(query);
	if (elements != null)
	elements.forEach(element => {
		element.addEventListener(eventName, event);
	});
}

function showHideElement(name) {
	addEvent('select[name="'+name+'"]', 'change', function() {
		let div = document.querySelector('.form-div#form-div-'+name);
		let inp = document.querySelector('.form-div#form-div-'+name+' > input');
		let span = document.querySelector('.form-div#form-div-'+name+' + span');

		if (this.selectedIndex == 1) {
			div.classList.remove('hide');
			if (span != null) span.classList.remove('hide');
			inp.setAttribute('type', 'text');
		} else {
			div.classList.add('hide');
			if (span != null) span.classList.add('hide');
			inp.setAttribute('type', 'hidden');
		}

		let fileDiv = document.querySelector('.form-file-div');
		if (name == 'group' && fileDiv != null && this.selectedIndex < 2) fileDiv.classList.add('hide');
	});
}

function checkAll(myCheckbox, checkboxes) {
	checkboxes.forEach((c) => c.checked = myCheckbox.checked);
}

function check(checkboxes, mainCheckboxQuery) {
	let checkCond = true;
	checkboxes.forEach(function(c) {
		if (!c.checked) checkCond = false;
	});
	document.querySelector(mainCheckboxQuery).checked = checkCond;
}

function autoGrow(element, reset = false, defaultHeight = 150, border = 2) {
	if (element.scrollHeight > defaultHeight - border * 2) {
		if (reset) element.style.height = '0';
		element.style.height = (element.scrollHeight + border * 2).toString() + 'px';
	}
}

window.onload = function() {
	setTimeout(function() {
		let a = document.querySelector('.alert.green:not(.stamp)');
		if (a != null)
		a.classList.add('area');
	}, 5000);

	addEvent('.filter', 'click', function() {
		document.querySelector('.area').classList.toggle('show-area');
		document.querySelector('.roll').classList.toggle('reverse');
		
		let roll = getCookie('roll');
		if (roll !== null) roll == "1" ? setCookie('roll', 0) : setCookie('roll', 1);
		else setCookie('roll', 0);
	});

	addEvent('input[type="radio"], input[type="file"]', 'change', function() { this.form.submit(); });

	elements = document.querySelectorAll('.task-title-header');
	if (elements != null) elements.forEach(element => {
		addEvent('.task-title-header .del-task', 'click', function() {
			let title = element.querySelector('h2').innerHTML;
			let id = element.querySelector('span.id').innerHTML;
			if (confirm('Czy na pewno chcesz usunąć zadanie "'+title+'"?')) location.href = 'del.php?id=' + id;
		});
	});

	addEvent('.form-file-div .button.tile', 'click', function() {
		let id = this.getAttribute('id');
		let name = document.querySelector('#fs'+id).innerHTML;
		let inp = document.querySelector('input[name="del"]');
		
		if (confirm('Czy na pewno chcesz usunąć plik "'+name+'"?')) {
			inp.setAttribute('value', id);
			document.querySelector('input[type="file"]').setAttribute('type', 'hidden');
			inp.form.submit();
		}
	});

	addEvent('.form-answers-div .button.tile', 'click', function() {
		let id = this.getAttribute('id');
		let inp = document.querySelector('input[name="del_ans"]');
		
		inp.setAttribute('value', id);
		inp.form.submit();
	});

	showHideElement('category');
	showHideElement('group');

	addEvent('select[name="group"]', 'change', function() {
		if (this.selectedIndex > 1) {
			document.querySelector('input[type="file"]').setAttribute('type', 'hidden');
			this.form.submit();
		}
	});

	addEvent('.add-button .button.longer', 'click', function() {
		this.classList.add('hide');
		document.querySelector('.form-div.add-task').classList.remove('hide');
	});

	addEvent('.form-action', 'click', function() {
		let form = document.querySelector('form#users_data');
		form.action = this.id;
		form.submit();
	});

	let checkboxes = document.querySelectorAll('input.option-select');
	addEvent('input#option-all', 'change', function() { checkAll(this, checkboxes); });
	addEvent('.option-select', 'change', function() { check(checkboxes, 'input#option-all'); });

	addEvent('textarea', 'input',     function() { autoGrow(this, true); });
	addEvent('textarea[readonly]', 'click', function() { document.querySelector('form.answer input[type="submit"]').click(); });

	addEvent('.send', 'click', function() {
		let input = document.querySelector('input[name="send"]');
		if (confirm('Czy chcesz oznaczyć to zadanie jako zrobione?')) input.setAttribute('checked', 'true');
		else input.removeAttribute('checked');
	});

	addEvent('.print', 'click', function() {
		let img = confirm('Czy chcesz załączyć obrazki?') ? '' : '&img=no';
		window.open('print.php?' + this.getAttribute('data-param') + img);
	});
};
