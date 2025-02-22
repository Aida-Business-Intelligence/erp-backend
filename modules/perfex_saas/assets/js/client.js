"use strict";

/**
 * Handles the company deployment response.
 * @param {Object} data - The response data.
 * @todo Add a pooling handling to update the user with current stage of deployment.
 */
function handleCompanyDeployment(data) {
	if (data?.total_success > 0) {
		setTimeout(function () {
			window.location.reload();
		}, 1000);
	}
	if (data.errors?.length) {
		data.errors.forEach(function (error) {
			alert_float("danger", error, 10000);
		});

		$(".company-status .fa-spin").removeClass("fa-spin");

		setTimeout(function () {
			window.location.reload();
		}, 8000);
	}
}

/**
 * Removes submenu items from the DOM.
 * It removes some menu/nav from the client side.
 */
function removeSubmenuItems() {
	let selectors =
		".section-client-dashboard>dl:first-of-type, .projects-summary-heading,.submenu.customer-top-submenu";
	document.querySelectorAll(selectors).forEach(function ($element) {
		$element.remove();
	});
	$(selectors)?.remove();
}

/**
 * Handles the file change event.
 * @param {Event} e - The file change event.
 */
function handleFileChange(e) {
	$("#selected_file_name").text(e.target.files[0].name);
}

/**
 * Handles the company modal view.
 */
function handleCompanyModalView() {
	let slug = $(this).data("slug");
	$("#view-company-modal").modal("show");
	$('select[name="view-company"]')
		.selectpicker("val", slug)
		.trigger("change");
}

/**
 * Handles the modal company change event.
 */
function handleModalCompanyChange() {
	let slug = $(this).val();
	if (!slug.length) $("#view-company-modal").modal("hide");
	magicAuth(slug);
}

/**
 * Loads a company into the modal viewer.
 * @param {string} slug - The company slug.
 */
function magicAuth(slug) {
	let iframe = document.querySelector("#company-viewer");
	iframe.src = PERFEX_SAAS_MAGIC_AUTH_BASE_URL + slug;
	iframe.onload = function () {
		$(".first-loader").hide();
	};
	iframe.contentWindow?.NProgress?.start() || $(".first-loader").show();
}

/*
 * Debounce function to limit the frequency of function execution
 * @param {Function} func - The function to be debounced
 * @param {number} wait - The debounce wait time in milliseconds
 * @param {boolean} immediate - Whether to execute the function immediately
 * @returns {Function} - The debounced function
 */
function debounce(func, wait, immediate) {
	var timeout;
	return function () {
		var context = this,
			args = arguments;
		var later = function () {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
}

/*
 * Function to generate slug and check its availability
 */
function generateSlugAndCheckAvailability() {
	// Generate the slug from the input value
	let slug = $("input[name=slug]")
		.val()
		.trim()
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, "-");
	let $statusLabel = $("#slug-check-label");

	if (!slug.length) {
		$statusLabel.html("");
		return;
	}

	let domain = slug + "." + PERFEX_SAAS_DEFAULT_HOST;

	// Set the generated slug as the input value
	$("input[name=slug]").val(slug);

	// Display a message indicating that availability is being checked
	$statusLabel.html("<i class='fa fa-spinner fa-spin tw-mr-1'></i>" + domain);

	// Send an AJAX request to check the slug availability on the server
	$.getJSON(site_url + "/clients/companies/check_slug/" + slug, (data) => {
		let isAvailable = data.exist;

		// Update the label with the slug availability status
		$statusLabel.html(
			`<span class='text-${
				isAvailable ? "success" : "danger"
			}'>${domain}</span>`
		);
	});
}

/*
 * Function to bind and listen to the slug input field
 */
function bindAndListenToSlugInput() {
	// Inject the result placeholder HTML
	$(
		'<small id="slug-check-label" class="text-right tw-w-full tw-block tw-text-xs"></small>'
	).insertAfter("input[name=slug]");

	// Debounced event handler for company name input changes
	let debouncedGenerateSlugAndCheckAvailability = debounce(
		generateSlugAndCheckAvailability,
		500
	);

	// Generate slug from company name input
	$("#add-company-form input[name='name']").on("input", function () {
		var companyName = $("#add-company-form input[name='name']").val();
		var slug = companyName
			.trim()
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, "-");
		$("input[name=slug]").val(slug).trigger("input");
	});

	// Check for availability of the slug
	$("#add-company-form input[name='slug']").on(
		"input",
		debouncedGenerateSlugAndCheckAvailability
	);
}

$(document).ready(function () {
	$(".ps-container").insertAfter("#greeting");

	// Remove submenu (e.g., calendar and files)
	if (PERFEX_SAAS_CONTROL_CLIENT_MENU) removeSubmenuItems();

	// Hide the form initially
	$("#add-company-form").hide();

	// Show the form when the add button is clicked
	$(".add-company-btn").click(function () {
		$("#add-company-trigger").slideUp();
		$("#add-company-form").slideDown();
	});

	// Cancel button closes the form and shows the early UI
	$("#cancel-add-company").click(function () {
		$("#add-company-form").slideUp();
		$("#add-company-trigger").slideDown();
	});

	// Show the edit form
	$(".company .dropdown-menu .edit-company-nav").click(function () {
		let $company = $(this).parents(".company");
		$company.find(".panel_footer, .info, .dropdown").slideUp();
		$company.find(".edit-form").slideDown();
	});

	// Cancel button closes the edit form and shows the early UI
	$(".company .edit-form .btn[type='button']").click(function () {
		let $company = $(this).parents(".company");
		$company.find(".edit-form").slideUp();
		$company.find(".info, .panel_footer, .dropdown").slideDown();
	});

	// Render Saas view
	let view = PERFEX_SAAS_ACTIVE_SEGMENT;
	if (view) {
		$(".ps-view").hide();
		showSaasView(view);
	}

	// Function to show the specified Saas view
	function showSaasView(view) {
		$(view.replace("?", "#")).show();
	}

	// Worker helper for instant deployment of a company
	$.getJSON(site_url + "/clients/companies/deploy", handleCompanyDeployment);

	// File change event
	$(document).on("change", "#sql_file", handleFileChange);

	// Company modal view
	$(".view-company").click(handleCompanyModalView);

	// Detect change in modal company list selector and react
	$(document).on("change", '[name="view-company"]', handleModalCompanyChange);

	// Click the first company by default if client is having only one.
	setTimeout(() => {
		//let companyList = $("#companies:visible .company.autolaunch");
		let companyList = $("#companies:visible .company");
		if (
			companyList.length === 1 &&
			sessionStorage.getItem("autolaunched") !== "1"
		) {
			sessionStorage.setItem("autolaunched", "1");
			$(companyList[0]).find(".view-company").click();
		}
	}, 500);

	/** Subdomain checking for improved UX */
	bindAndListenToSlugInput();
});
