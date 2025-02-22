"use strict";

function new_crm_api() {
    appValidateForm($('form'), {
        user: 'required',
        name: 'required',
        expiration_date: 'required'
    });
    $('.add_api_form').modal('show');
    $('.edit-title').addClass('hide');
    $('.add_api_form input[name="user"]').val('');
    $('.add_api_form input[name="name"]').val('');
    $('.add_api_form input[name="expiration_date"]').val('');
}

function edit_crm_api(invoker, id) {
    appValidateForm($('form'), {
        user: 'required',
        name: 'required',
        expiration_date: 'required'
    });
    var user = $(invoker).data('user');
    var name = $(invoker).data('name');
    var expiration_date = $(invoker).data('expiration_date');
    $('#additional').append(hidden_input('id', id));
    $('.add_api_form input[name="user"]').val(user);
    $('.add_api_form input[name="name"]').val(name);
    $('.add_api_form input[name="expiration_date"]').val(expiration_date);
    $('.add_api_form').modal('show');
    $('.add-title').addClass('hide');
}